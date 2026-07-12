<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Repositories\AiConversationRepository;
use App\Services\AiToolExecutor;
use App\Support\Session;

/**
 * SyncAI: a tool-calling assistant over the company's live data (rides,
 * no-shows, vouchers, driver stats, chat history, expenses), with a proper
 * multi-thread conversation history (like the sidebar in a chat app) instead
 * of a single stateless Q&A box.
 */
final class AiSyncController extends BaseController
{
    private const MAX_TOOL_ROUNDS = 5;

    private AiConversationRepository $conversations;

    public function __construct()
    {
        $this->conversations = AiConversationRepository::default();
    }

    /** GET /api/sync-ai-engine.php?conversations=1 */
    public function conversationsList(): never
    {
        $this->json(['success' => true, 'conversations' => $this->conversations->listForAdmin()]);
    }

    /** GET /api/sync-ai-engine.php?history=1&conversation_id=X */
    public function history(): never
    {
        $id = (int) ($_GET['conversation_id'] ?? 0);
        if ($id === 0 || !$this->conversations->ownedByCurrentAdmin($id)) {
            $this->json(['success' => false, 'error' => 'Not found'], 404);
        }
        $messages = array_values(array_filter(
            array_map([$this, 'toUiMessage'], $this->conversations->messagesFor($id)),
            static fn(?array $m): bool => $m !== null
        ));
        $this->json(['success' => true, 'messages' => $messages]);
    }

    /** POST /api/sync-ai-engine.php?action=create_conversation */
    public function createConversation(): never
    {
        $id = $this->conversations->create();
        $this->json(['success' => true, 'conversation_id' => $id]);
    }

    /** POST /api/sync-ai-engine.php?action=delete_conversation — body: conversation_id */
    public function deleteConversation(): never
    {
        $body = $this->shieldedBody();
        $id   = (int) ($body['conversation_id'] ?? 0);
        if ($id === 0 || !$this->conversations->ownedByCurrentAdmin($id)) {
            $this->json(['success' => false, 'error' => 'Not found'], 404);
        }
        $this->conversations->delete($id);
        $this->json(['success' => true]);
    }

    /** POST /api/sync-ai-engine.php — body: conversation_id, message */
    public function send(): never
    {
        $body          = $this->shieldedBody();
        $conversationId = (int) ($body['conversation_id'] ?? 0);
        $userMsg       = trim((string) ($body['message'] ?? ''));

        if ($userMsg === '') {
            $this->json(['success' => false, 'error' => 'Empty message'], 422);
        }
        if ($conversationId === 0) {
            $conversationId = $this->conversations->create();
        } elseif (!$this->conversations->ownedByCurrentAdmin($conversationId)) {
            $this->json(['success' => false, 'error' => 'Not found'], 404);
        }

        $this->conversations->appendMessage($conversationId, 'user', $userMsg);
        $this->conversations->setTitleIfMissing($conversationId, mb_strlen($userMsg) > 60 ? mb_substr($userMsg, 0, 58) . '…' : $userMsg);

        $adminName = (string) (Session::name() ?? 'Admin');
        $systemPrompt = "You are SyncAI, the operations assistant for {$adminName} at SyncRide, a fleet/transfer management platform. "
            . "Today is " . date('Y-m-d (l)') . ", current time {$this->currentTime()}. "
            . "You have tools to search rides, no-shows, vouchers, driver stats, chat history and expenses — use them whenever the "
            . "question needs real data instead of guessing. Never invent ride IDs, photos, or numbers — if a tool returns nothing, say so. "
            . "Be concise and precise. Address {$adminName} naturally, not stiffly formal.";

        $wireMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            array_map([$this, 'toWireMessage'], $this->conversations->messagesFor($conversationId))
        );

        $executor          = AiToolExecutor::default();
        $turnAttachments    = [];
        $finalContent       = null;

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $assistantMsg = $this->callGroq($wireMessages);

            if (empty($assistantMsg['tool_calls'])) {
                $finalContent = $assistantMsg['content'] ?? "{$adminName}, I couldn't generate a response just now.";
                break;
            }

            // Persist + replay the assistant's tool-call request itself.
            $this->conversations->appendMessage($conversationId, 'assistant', $assistantMsg['content'] ?? null, $assistantMsg['tool_calls']);
            $wireMessages[] = ['role' => 'assistant', 'content' => $assistantMsg['content'] ?? null, 'tool_calls' => $assistantMsg['tool_calls']];

            foreach ($assistantMsg['tool_calls'] as $call) {
                $toolName = (string) ($call['function']['name'] ?? '');
                $args     = json_decode((string) ($call['function']['arguments'] ?? '{}'), true) ?? [];
                $outcome  = $executor->execute($toolName, $args);
                $resultStr = json_encode($outcome['result'], JSON_UNESCAPED_UNICODE);

                if (!empty($outcome['attachments'])) {
                    $turnAttachments = array_merge($turnAttachments, $outcome['attachments']);
                }

                $this->conversations->appendMessage($conversationId, 'tool', $resultStr, null, $call['id'], $toolName);
                $wireMessages[] = ['role' => 'tool', 'tool_call_id' => $call['id'], 'name' => $toolName, 'content' => $resultStr];
            }
        }

        if ($finalContent === null) {
            $finalContent = "{$adminName}, that took too many steps to answer — try narrowing the question.";
        }

        // A model can legitimately call the same search twice in one turn (e.g. broad
        // then narrowed) — dedupe by a type-aware key (photos by URL, rides by id).
        // Also cap the total: a broad search (e.g. "no-shows for X") can match dozens
        // of rows, and dumping every photo/ride chip into one bubble is unusable.
        $seen = [];
        $deduped = [];
        foreach ($turnAttachments as $att) {
            $key = $att['type'] === 'ride' ? 'ride-' . $att['ride']['ID'] : ($att['url'] ?? json_encode($att));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[]  = $att;
            if (count($deduped) >= 6) {
                break;
            }
        }
        $turnAttachments = $deduped;

        $this->conversations->appendMessage($conversationId, 'assistant', $finalContent, null, null, null, $turnAttachments ?: null);

        $this->json([
            'success'         => true,
            'conversation_id' => $conversationId,
            'message'         => ['role' => 'assistant', 'content' => $finalContent, 'attachments' => $turnAttachments],
        ]);
    }

    private function currentTime(): string
    {
        return date('H:i');
    }

    /** @return array{content:?string, tool_calls:?array} */
    private function callGroq(array $wireMessages): array
    {
        $apiKey = (string) (getenv('GROQ_API_KEY') ?: '');
        $ch     = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POSTFIELDS     => json_encode([
                // 5x the daily/per-minute token budget of llama-3.3-70b-versatile on Groq's
                // free tier, comparable tool-calling quality — swapped after hitting the
                // 100K TPD cap during testing.
                'model'       => 'meta-llama/llama-4-scout-17b-16e-instruct',
                'messages'    => $wireMessages,
                'tools'       => AiToolExecutor::toolSchemas(),
                'tool_choice' => 'auto',
                'temperature' => 0.2,
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
        ]);
        $raw     = (string) curl_exec($ch);
        $err     = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            error_log('SyncAI Groq request failed: ' . $err);
            return ['content' => 'The AI service is temporarily unavailable.', 'tool_calls' => null];
        }

        $decoded = json_decode($raw, true) ?? [];
        $msg     = $decoded['choices'][0]['message'] ?? null;
        if ($msg === null) {
            error_log('SyncAI Groq unexpected response: ' . substr($raw, 0, 500));
            return ['content' => $this->friendlyGroqError($decoded), 'tool_calls' => null];
        }
        return ['content' => $msg['content'] ?? null, 'tool_calls' => $msg['tool_calls'] ?? null];
    }

    /** Surface the real cause instead of a generic "unavailable" — rate limits especially are a normal, temporary condition, not a break. */
    private function friendlyGroqError(array $decoded): string
    {
        $code = $decoded['error']['code'] ?? null;
        if ($code === 'rate_limit_exceeded') {
            if (preg_match('/try again in ([0-9.ms]+)/i', (string) ($decoded['error']['message'] ?? ''), $m)) {
                return "Diária de pedidos à IA esgotada por hoje — tenta novamente em " . rtrim($m[1], '.') . '.';
            }
            return 'Diária de pedidos à IA esgotada por hoje — tenta novamente mais tarde.';
        }
        if ($code === 'invalid_api_key') {
            return 'A chave da IA não é válida — verifica o GROQ_API_KEY no .env.';
        }
        return 'The AI service is temporarily unavailable.';
    }

    /** DB row -> Groq wire format for replay into the next API call. */
    private function toWireMessage(array $row): array
    {
        $msg = ['role' => $row['role'], 'content' => $row['content']];
        if ($row['role'] === 'assistant' && !empty($row['tool_calls'])) {
            $msg['tool_calls'] = $row['tool_calls'];
        }
        if ($row['role'] === 'tool') {
            $msg['tool_call_id'] = $row['tool_call_id'];
            $msg['name']         = $row['tool_name'];
        }
        return $msg;
    }

    /** DB row -> UI-facing message (skips internal tool-call plumbing, keeps only what's shown as a bubble). */
    private function toUiMessage(array $row): ?array
    {
        if ($row['role'] === 'tool' || ($row['role'] === 'assistant' && ($row['content'] ?? null) === null)) {
            return null; // internal tool-calling step, not a visible bubble
        }
        if ($row['role'] !== 'user' && $row['role'] !== 'assistant') {
            return null;
        }
        return [
            'id'          => (int) $row['id'],
            'role'        => $row['role'],
            'content'     => $row['content'],
            'attachments' => $row['attachments'] ?? [],
            'timestamp'   => $row['created_at'],
        ];
    }
}
