<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DriverConversation;
use App\Support\Database;
use App\Support\Session;
use PDO;

/** Topics ("Geral" + admin-labelled conversations) within a driver's chat. */
final class DriverConversationRepository
{
    public function __construct(
        private readonly PDO  $db,
        private readonly ?int $companyId = null,
    ) {
    }

    public static function default(): self
    {
        return new self(Database::connection(), Session::companyId());
    }

    public static function forDriverContext(): self
    {
        return new self(Database::connection(), null);
    }

    public function find(int $id): ?DriverConversation
    {
        $stmt = $this->db->prepare('SELECT * FROM DriverConversations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? DriverConversation::fromRow($row) : null;
    }

    /** True if the conversation's driver belongs to this repo's company scope. */
    public function inScope(int $conversationId): bool
    {
        if ($this->companyId === null) {
            return true;
        }
        $stmt = $this->db->prepare('
            SELECT 1 FROM DriverConversations dc
            JOIN Users u ON u.id = dc.driver_id
            LEFT JOIN UserCompanies uc ON uc.user_id = u.id AND uc.company_id = :cid2
            WHERE dc.id = :id AND (u.company_id = :cid OR uc.company_id = :cid3)
            LIMIT 1
        ');
        $stmt->execute(['id' => $conversationId, 'cid' => $this->companyId, 'cid2' => $this->companyId, 'cid3' => $this->companyId]);
        return (bool) $stmt->fetchColumn();
    }

    /** The driver's always-there default thread, created lazily on first use. */
    public function getOrCreateGeneral(int $driverId): DriverConversation
    {
        $stmt = $this->db->prepare('SELECT * FROM DriverConversations WHERE driver_id = :did AND is_general = 1 LIMIT 1');
        $stmt->execute(['did' => $driverId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return DriverConversation::fromRow($row);
        }

        $this->db->prepare('INSERT INTO DriverConversations (driver_id, is_general, status) VALUES (:did, 1, \'open\')')
            ->execute(['did' => $driverId]);
        return $this->find((int) $this->db->lastInsertId());
    }

    /**
     * Create a fresh, empty topic — admin only, per product decision.
     * A title given up front is date-prefixed immediately (same as close()/convert*());
     * an omitted title stays NULL and gets prefixed later, whenever it's finally set.
     */
    public function create(int $driverId, ?int $createdBy, ?string $title = null, ?int $linkedRideId = null): DriverConversation
    {
        $titleWithDate = $title !== null && trim($title) !== ''
            ? $this->withDatePrefix(date('Y-m-d H:i:s'), $title)
            : null;

        $this->db->prepare('
            INSERT INTO DriverConversations (driver_id, title, is_general, status, linked_ride_id, created_by)
            VALUES (:did, :title, 0, \'open\', :ride, :by)
        ')->execute(['did' => $driverId, 'title' => $titleWithDate, 'ride' => $linkedRideId, 'by' => $createdBy]);
        return $this->find((int) $this->db->lastInsertId());
    }

    /**
     * Close with a title. Soft close: this is a status label, not a hard gate —
     * sending into it again silently reopens it (see reopenIfClosed()).
     * The title is date-prefixed so a company-wide keyword search also works as
     * a rough timeline (client's explicit ask: "mais fácil pesquisar").
     */
    public function close(int $id, string $rawTitle): void
    {
        $convo = $this->find($id);
        if ($convo === null || $convo->isGeneral) {
            return; // Geral is never closed/titled.
        }
        $title = $this->withDatePrefix($convo->createdAt, $rawTitle);
        $this->db->prepare("UPDATE DriverConversations SET status = 'closed', title = :title WHERE id = :id")
            ->execute(['title' => $title, 'id' => $id]);
    }

    /** Called internally whenever a message lands in a closed (non-general) topic. */
    public function reopenIfClosed(int $id): void
    {
        $this->db->prepare("UPDATE DriverConversations SET status = 'open' WHERE id = :id AND is_general = 0 AND status = 'closed'")
            ->execute(['id' => $id]);
    }

    public function pin(int $id, bool $pinned): void
    {
        $this->db->prepare('UPDATE DriverConversations SET pinned_at = :val WHERE id = :id')
            ->execute(['val' => $pinned ? date('Y-m-d H:i:s') : null, 'id' => $id]);
    }

    /** Only ever called on a closed, non-general topic (enforced by the controller) — Geral and open topics can't be deleted. */
    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM DriverMessages WHERE conversation_id = :id')->execute(['id' => $id]);
        $this->db->prepare('DELETE FROM DriverConversations WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Every topic for a driver, Geral first, then pinned, then most recent activity.
     * @return array<DriverConversation>
     */
    public function listForDriver(int $driverId): array
    {
        $stmt = $this->db->prepare('
            SELECT dc.*,
                   COALESCE((SELECT MAX(timestamp) FROM DriverMessages WHERE conversation_id = dc.id), dc.created_at) AS last_activity
            FROM DriverConversations dc
            WHERE dc.driver_id = :did
            ORDER BY dc.is_general DESC, (dc.pinned_at IS NULL) ASC, last_activity DESC
        ');
        $stmt->execute(['did' => $driverId]);
        return array_map(DriverConversation::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Peel a message and everything after it (within the same conversation) off
     * into a brand-new topic. Everything before stays where it was.
     */
    public function convertFromMessage(int $sourceConversationId, int $fromMessageId, string $rawTitle, ?int $createdBy, ?int $linkedRideId = null): DriverConversation
    {
        $stmt = $this->db->prepare('SELECT driver_id FROM DriverConversations WHERE id = :id');
        $stmt->execute(['id' => $sourceConversationId]);
        $driverId = (int) $stmt->fetchColumn();

        $newConvo = $this->create($driverId, $createdBy, null, $linkedRideId);
        $title    = $this->withDatePrefix($newConvo->createdAt, $rawTitle);
        $this->db->prepare('UPDATE DriverConversations SET title = :title WHERE id = :id')
            ->execute(['title' => $title, 'id' => $newConvo->id]);

        $this->db->prepare('
            UPDATE DriverMessages SET conversation_id = :newId
            WHERE conversation_id = :oldId AND id >= :fromId
        ')->execute(['newId' => $newConvo->id, 'oldId' => $sourceConversationId, 'fromId' => $fromMessageId]);

        return $this->find($newConvo->id);
    }

    /** Move every message currently in $sourceConversationId into a new topic, emptying the source. */
    public function convertAll(int $sourceConversationId, string $rawTitle, ?int $createdBy, ?int $linkedRideId = null): DriverConversation
    {
        $stmt = $this->db->prepare('SELECT MIN(id) AS min_id FROM DriverMessages WHERE conversation_id = :id');
        $stmt->execute(['id' => $sourceConversationId]);
        $minId = (int) $stmt->fetchColumn();
        if ($minId === 0) {
            // Nothing to convert — still create an empty titled topic rather than no-op silently.
            $driverStmt = $this->db->prepare('SELECT driver_id FROM DriverConversations WHERE id = :id');
            $driverStmt->execute(['id' => $sourceConversationId]);
            $driverId = (int) $driverStmt->fetchColumn();
            $newConvo = $this->create($driverId, $createdBy, null, $linkedRideId);
            $title    = $this->withDatePrefix($newConvo->createdAt, $rawTitle);
            $this->db->prepare('UPDATE DriverConversations SET title = :title WHERE id = :id')
                ->execute(['title' => $title, 'id' => $newConvo->id]);
            return $this->find($newConvo->id);
        }
        return $this->convertFromMessage($sourceConversationId, $minId, $rawTitle, $createdBy, $linkedRideId);
    }

    /** dd/mm/yyyy prefix, matching this app's date convention elsewhere (e.g. rideNotifBody()). */
    private function withDatePrefix(string $createdAt, string $rawTitle): string
    {
        $rawTitle = trim($rawTitle) !== '' ? trim($rawTitle) : 'Sem título';
        $date     = \DateTime::createFromFormat('Y-m-d H:i:s', $createdAt) ?: new \DateTime();
        return $date->format('d/m/Y') . ' — ' . $rawTitle;
    }
}
