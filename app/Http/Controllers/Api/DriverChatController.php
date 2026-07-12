<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\DriverConversation;
use App\Models\DriverMessage;
use App\Repositories\DriverConversationRepository;
use App\Repositories\DriverMessageRepository;
use App\Services\FCMSender;
use App\Support\Session;

/** General admin <-> driver chat, organized into topics ("Geral" + admin-labelled conversations). */
final class DriverChatController extends BaseController
{
    private DriverMessageRepository      $messages;
    private DriverConversationRepository $conversations;
    private bool                         $isDriver;

    public function __construct()
    {
        $this->isDriver     = Session::role() === 2;
        $this->messages     = $this->isDriver ? DriverMessageRepository::forDriverContext()      : DriverMessageRepository::default();
        $this->conversations = $this->isDriver ? DriverConversationRepository::forDriverContext() : DriverConversationRepository::default();
    }

    /** GET /api/driver-chat.php?inbox=1 — admin only: list of drivers with preview + unread. */
    public function inbox(): never
    {
        $this->cors();
        $this->requireAdmin();

        $companyId = Session::companyId();
        $rows      = $companyId !== null ? $this->messages->inboxForAdmin($companyId) : [];

        $this->json(['success' => true, 'drivers' => array_map(
            static fn(array $r): array => [
                'driver_id'      => (int) $r['driver_id'],
                'driver_name'    => (string) $r['driver_name'],
                'last_message'   => $r['last_message'] ?? null,
                'last_timestamp' => $r['last_timestamp'] ?? null,
                'unread'         => (int) $r['unread'],
            ],
            $rows
        )]);
    }

    /** GET /api/driver-chat.php?count=1 — unread badge count (self for a driver, total for an admin). */
    public function count(): never
    {
        $this->cors();

        if ($this->isDriver) {
            $this->json(['success' => true, 'unread' => $this->messages->unreadCountForDriver((int) Session::userId())]);
        }

        $companyId = Session::companyId();
        $this->json(['success' => true, 'unread' => $companyId !== null ? $this->messages->unreadTotalForAdmin($companyId) : 0]);
    }

    /** GET /api/driver-chat.php?topics=1[&driver_id=X] — list of topics (Geral first) for a driver. */
    public function topics(): never
    {
        $this->cors();

        $driverId = $this->isDriver ? (int) Session::userId() : (int) ($_GET['driver_id'] ?? 0);
        if ($driverId === 0) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }
        if (!$this->isDriver && !$this->messages->driverInScope($driverId)) {
            $this->json(['success' => false, 'error' => 'Not your driver'], 403);
        }

        $this->conversations->getOrCreateGeneral($driverId); // guarantee it exists before listing
        $list = $this->conversations->listForDriver($driverId);

        $this->json(['success' => true, 'topics' => array_map($this->topicToArray(...), $list)]);
    }

    /** GET /api/driver-chat.php[?conversation_id=X][&driver_id=X] — fetch a thread, marks read.
     *  Drivers default to their own Geral; admins must pass driver_id (Geral) or conversation_id (a specific topic). */
    public function fetch(): never
    {
        $this->cors();

        $conversation = $this->resolveConversation((int) ($_GET['conversation_id'] ?? 0), (int) ($_GET['driver_id'] ?? 0));
        if ($conversation === null) {
            $this->json(['success' => false, 'error' => 'Not found'], 404);
        }

        $markAs = $this->isDriver ? DriverMessage::SENDER_DRIVER : DriverMessage::SENDER_ADMIN;
        $rows   = $this->messages->forConversation($conversation->id, $markAs);

        $this->json(['success' => true, 'topic' => $this->topicToArray($conversation), 'messages' => array_map(
            static fn(DriverMessage $m): array => [
                'id'              => $m->id,
                'sender'          => $m->senderType,
                'sender_name'     => $m->senderName,
                'message'         => $m->message,
                'timestamp'       => $m->timestamp,
                'reply_to_id'     => $m->replyToId,
                'attachment_path' => $m->attachmentPath,
            ],
            $rows
        )]);
    }

    /**
     * POST /api/driver-chat.php — WAF-shielded body:
     * { message?, conversation_id?, reply_to_id?, image_data? } (+ driver_id when sent by an admin, to default into Geral).
     * A message needs text and/or a photo — not necessarily both.
     */
    public function send(): never
    {
        $this->cors();
        $this->requirePostMethod();

        $json    = $this->shieldedBody();
        $message = trim((string) ($json['message'] ?? ''));
        if (mb_strlen($message) > 2000) {
            $message = mb_substr($message, 0, 2000);
        }

        $conversation = $this->resolveConversation((int) ($json['conversation_id'] ?? 0), (int) ($json['driver_id'] ?? 0));
        if ($conversation === null) {
            $this->json(['success' => false, 'error' => 'Not found'], 404);
        }

        $attachmentPath = $this->storeAttachmentIfAny($conversation->driverId, (string) ($json['image_data'] ?? ''));
        if ($message === '' && $attachmentPath === null) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }

        $replyToId = isset($json['reply_to_id']) && $json['reply_to_id'] !== '' ? (int) $json['reply_to_id'] : null;

        if ($conversation->isClosed()) {
            $this->conversations->reopenIfClosed($conversation->id);
            $this->messages->send(
                $conversation->driverId, $conversation->id, DriverMessage::SENDER_SYSTEM, null,
                t('chat.system_reopened')
            );
        }

        $senderType = $this->isDriver ? DriverMessage::SENDER_DRIVER : DriverMessage::SENDER_ADMIN;
        $saved      = $this->messages->send(
            $conversation->driverId, $conversation->id, $senderType, Session::userId(),
            $message, $replyToId, $attachmentPath
        );

        $this->notifyOtherParty($conversation->driverId, $message !== '' ? $message : '📷 Foto');

        $this->json(['success' => true, 'message' => [
            'id'              => $saved->id,
            'sender'          => $saved->senderType,
            'message'         => $saved->message,
            'timestamp'       => $saved->timestamp,
            'attachment_path' => $saved->attachmentPath,
        ]]);
    }

    /** GET /api/driver-chat.php?search=term[&driver_id=X] — keyword search over messages + topic titles. */
    public function search(): never
    {
        $this->cors();

        $term = trim((string) ($_GET['search'] ?? ''));
        if ($term === '' || mb_strlen($term) < 2) {
            $this->json(['success' => true, 'results' => []]);
        }

        if ($this->isDriver) {
            $rows = $this->messages->searchForDriver((int) Session::userId(), $term);
        } else {
            $companyId = Session::companyId();
            $driverId  = isset($_GET['driver_id']) && $_GET['driver_id'] !== '' ? (int) $_GET['driver_id'] : null;
            $rows      = $companyId !== null ? $this->messages->searchForAdmin($companyId, $term, $driverId) : [];
        }

        $this->json(['success' => true, 'results' => $rows]);
    }

    /** GET /api/driver-chat.php?recent_rides=1&driver_id=X — admin only: recent rides for the ride-link picker. */
    public function recentRides(): never
    {
        $this->cors();
        $this->requireAdmin();

        $driverId = (int) ($_GET['driver_id'] ?? 0);
        if ($driverId === 0 || !$this->messages->driverInScope($driverId)) {
            $this->json(['success' => false, 'error' => 'Not your driver'], 403);
        }

        $term = trim((string) ($_GET['term'] ?? ''));
        $this->json(['success' => true, 'rides' => \App\Repositories\ServiceRepository::default()->recentForDriver($driverId, $term !== '' ? $term : null)]);
    }

    /** POST /api/driver-chat.php?action=create_topic — admin only. Body: driver_id, title?, linked_ride_id? */
    public function createTopic(): never
    {
        $this->cors();
        $this->requirePostMethod();
        $this->requireAdmin();

        $json     = $this->shieldedBody();
        $driverId = (int) ($json['driver_id'] ?? 0);
        if ($driverId === 0 || !$this->messages->driverInScope($driverId)) {
            $this->json(['success' => false, 'error' => 'Not your driver'], 403);
        }

        $title        = trim((string) ($json['title'] ?? '')) ?: null;
        $linkedRideId = isset($json['linked_ride_id']) && $json['linked_ride_id'] !== '' ? (int) $json['linked_ride_id'] : null;
        $convo        = $this->conversations->create($driverId, Session::userId(), $title, $linkedRideId);

        $this->json(['success' => true, 'topic' => $this->topicToArray($convo)]);
    }

    /** POST /api/driver-chat.php?action=close_topic — admin only. Body: conversation_id, title. */
    public function closeTopic(): never
    {
        $this->cors();
        $this->requirePostMethod();
        $this->requireAdmin();

        $json           = $this->shieldedBody();
        $conversationId = (int) ($json['conversation_id'] ?? 0);
        $title          = trim((string) ($json['title'] ?? ''));

        if ($conversationId === 0 || $title === '' || !$this->conversations->inScope($conversationId)) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }

        $this->conversations->close($conversationId, $title);
        $convo = $this->conversations->find($conversationId);

        $adminName = Session::name() ?? 'Admin';
        $this->messages->send(
            $convo->driverId, $convo->id, DriverMessage::SENDER_SYSTEM, null,
            sprintf(t('chat.system_closed'), $adminName, $convo->title)
        );

        $this->json(['success' => true, 'topic' => $this->topicToArray($convo)]);
    }

    /** POST /api/driver-chat.php?action=convert_topic — admin only. Body: conversation_id, title, from_message_id? (omit to convert everything). */
    public function convertTopic(): never
    {
        $this->cors();
        $this->requirePostMethod();
        $this->requireAdmin();

        $json           = $this->shieldedBody();
        $conversationId = (int) ($json['conversation_id'] ?? 0);
        $title          = trim((string) ($json['title'] ?? ''));
        $fromMessageId  = isset($json['from_message_id']) && $json['from_message_id'] !== '' ? (int) $json['from_message_id'] : null;
        $linkedRideId   = isset($json['linked_ride_id']) && $json['linked_ride_id'] !== '' ? (int) $json['linked_ride_id'] : null;

        if ($conversationId === 0 || $title === '' || !$this->conversations->inScope($conversationId)) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }

        $newConvo = $fromMessageId !== null
            ? $this->conversations->convertFromMessage($conversationId, $fromMessageId, $title, Session::userId(), $linkedRideId)
            : $this->conversations->convertAll($conversationId, $title, Session::userId(), $linkedRideId);

        $this->json(['success' => true, 'topic' => $this->topicToArray($newConvo)]);
    }

    /** POST /api/driver-chat.php?action=pin_topic — admin only. Body: conversation_id, pinned (1/0). */
    public function pinTopic(): never
    {
        $this->cors();
        $this->requirePostMethod();
        $this->requireAdmin();

        $json           = $this->shieldedBody();
        $conversationId = (int) ($json['conversation_id'] ?? 0);
        $pinned         = (bool) ($json['pinned'] ?? false);

        if ($conversationId === 0 || !$this->conversations->inScope($conversationId)) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }

        $this->conversations->pin($conversationId, $pinned);
        $this->json(['success' => true, 'topic' => $this->topicToArray($this->conversations->find($conversationId))]);
    }

    /** POST /api/driver-chat.php?action=delete_topic — admin only. Body: conversation_id. Closed, non-general topics only. */
    public function deleteTopic(): never
    {
        $this->cors();
        $this->requirePostMethod();
        $this->requireAdmin();

        $json           = $this->shieldedBody();
        $conversationId = (int) ($json['conversation_id'] ?? 0);
        $convo          = $conversationId > 0 && $this->conversations->inScope($conversationId)
            ? $this->conversations->find($conversationId)
            : null;

        if ($convo === null || $convo->isGeneral || !$convo->isClosed()) {
            $this->json(['success' => false, 'error' => 'Only closed topics can be deleted'], 422);
        }

        $this->conversations->delete($conversationId);
        $this->json(['success' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolves which conversation a request means: an explicit conversation_id
     * (verified in-scope), or a driver_id defaulting to that driver's Geral
     * (self for a driver, the given id for an admin).
     */
    private function resolveConversation(int $conversationId, int $driverId): ?DriverConversation
    {
        if ($conversationId > 0) {
            if ($this->isDriver) {
                $convo = $this->conversations->find($conversationId);
                return ($convo !== null && $convo->driverId === Session::userId()) ? $convo : null;
            }
            return $this->conversations->inScope($conversationId) ? $this->conversations->find($conversationId) : null;
        }

        $targetDriverId = $this->isDriver ? (int) Session::userId() : $driverId;
        if ($targetDriverId === 0) {
            return null;
        }
        if (!$this->isDriver && !$this->messages->driverInScope($targetDriverId)) {
            return null;
        }
        return $this->conversations->getOrCreateGeneral($targetDriverId);
    }

    /** @return array{id:int, title:?string, is_general:bool, status:string, pinned:bool, linked_ride_id:?int, linked_ride_label:?string} */
    private function topicToArray(DriverConversation $c): array
    {
        return [
            'id'                => $c->id,
            'title'             => $c->title,
            'is_general'        => $c->isGeneral,
            'status'            => $c->status,
            'pinned'            => $c->isPinned(),
            'linked_ride_id'    => $c->linkedRideId,
            'linked_ride_label' => $c->linkedRideId !== null ? \App\Repositories\ServiceRepository::default()->labelFor($c->linkedRideId) : null,
        ];
    }

    /** Same decode/save pattern as NoShowsController/UploadController — data-URI base64, saved as-is. */
    private function storeAttachmentIfAny(int $driverId, string $imageData): ?string
    {
        if ($imageData === '') {
            return null;
        }
        if (!str_contains($imageData, ';base64,')) {
            error_log('Chat photo rejected: no ";base64," marker, length=' . strlen($imageData));
            return null;
        }
        $parts = explode(';base64,', $imageData, 2);
        $bytes = base64_decode($parts[1] ?? '', true);
        if ($bytes === false || $bytes === '') {
            error_log('Chat photo rejected: base64_decode failed (strict), payload length=' . strlen($parts[1] ?? ''));
            return null;
        }

        $appRoot   = dirname(__DIR__, 4);
        $uploadDir = $appRoot . '/public/uploads/chat/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'chat_' . $driverId . '_' . time() . '.jpg';
        if (file_put_contents($uploadDir . $fileName, $bytes) === false) {
            return null;
        }
        return 'uploads/chat/' . $fileName;
    }

    private function requireAdmin(): void
    {
        if (Session::role() !== 1) {
            $this->json(['success' => false, 'error' => 'Admins only'], 403);
        }
    }

    private function requirePostMethod(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Method not allowed'], 405);
        }
    }

    private function notifyOtherParty(int $driverId, string $message): void
    {
        $preview = mb_strlen($message) > 80 ? mb_substr($message, 0, 78) . '…' : $message;

        if ($this->isDriver) {
            // The driver's own session carries their own company — no extra lookup needed.
            $companyId = Session::companyId();
            if ($companyId !== null) {
                FCMSender::sendToAdmins(
                    $companyId,
                    '💬 ' . t('chat.push_title_from_driver'),
                    $preview,
                    ['type' => 'chat', 'driver_id' => (string) $driverId]
                );
            }
        } else {
            FCMSender::sendToUser(
                $driverId,
                '💬 ' . t('chat.push_title_from_admin'),
                $preview,
                ['type' => 'chat']
            );
        }
    }

    private function cors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Content-Type: application/json');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
