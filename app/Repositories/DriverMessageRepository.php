<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DriverMessage;
use App\Support\Database;
use App\Support\Session;
use PDO;

/** General admin <-> driver chat — one ongoing thread per driver, company-wide. */
final class DriverMessageRepository
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

    /** Driver-scoped: no company filter (the driver only ever reads their own thread). */
    public static function forDriverContext(): self
    {
        return new self(Database::connection(), null);
    }

    /** True if the driver belongs to this repo's company scope (shared drivers included). */
    public function driverInScope(int $driverId): bool
    {
        if ($this->companyId === null) {
            return true;
        }
        $stmt = $this->db->prepare('
            SELECT 1 FROM Users u
            LEFT JOIN UserCompanies uc ON uc.user_id = u.id AND uc.company_id = :cid2
            WHERE u.id = :uid AND u.role = 2 AND (u.company_id = :cid OR uc.company_id = :cid3)
            LIMIT 1
        ');
        $stmt->execute(['uid' => $driverId, 'cid' => $this->companyId, 'cid2' => $this->companyId, 'cid3' => $this->companyId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @return array<DriverMessage> messages for one topic/conversation, chronological.
     * If $markReadFor is given ('admin'|'driver'), unread messages from the
     * *other* side within this conversation are flagged read as a side effect.
     */
    public function forConversation(int $conversationId, ?string $markReadFor = null): array
    {
        if ($markReadFor === DriverMessage::SENDER_DRIVER) {
            $this->db->prepare("UPDATE DriverMessages SET is_read_by_driver = 1
                                WHERE conversation_id = :cid AND sender_type = 'admin' AND is_read_by_driver = 0")
                ->execute(['cid' => $conversationId]);
        } elseif ($markReadFor === DriverMessage::SENDER_ADMIN) {
            $this->db->prepare("UPDATE DriverMessages SET is_read_by_admin = 1
                                WHERE conversation_id = :cid AND sender_type = 'driver' AND is_read_by_admin = 0")
                ->execute(['cid' => $conversationId]);
        }

        // Sender's name matters here — a driver may be talking to more than one
        // admin over time and should see who actually wrote each message.
        $stmt = $this->db->prepare('
            SELECT dm.*, u.name AS sender_name
            FROM DriverMessages dm
            LEFT JOIN Users u ON u.id = dm.sender_id
            WHERE dm.conversation_id = :cid
            ORDER BY dm.timestamp
        ');
        $stmt->execute(['cid' => $conversationId]);
        return array_map(DriverMessage::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function send(
        int $driverId,
        int $conversationId,
        string $senderType,
        ?int $senderId,
        string $message,
        ?int $replyToId = null,
        ?string $attachmentPath = null,
    ): DriverMessage {
        $stmt = $this->db->prepare('
            INSERT INTO DriverMessages (driver_id, conversation_id, sender_type, sender_id, message, reply_to_id, attachment_path)
            VALUES (:did, :cid, :type, :sender, :msg, :reply, :attachment)
        ');
        $stmt->execute([
            'did'        => $driverId,
            'cid'        => $conversationId,
            'type'       => $senderType,
            'sender'     => $senderId,
            'msg'        => $message,
            'reply'      => $replyToId,
            'attachment' => $attachmentPath,
        ]);

        $id = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare('SELECT * FROM DriverMessages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return DriverMessage::fromRow($stmt->fetch(PDO::FETCH_ASSOC));
    }

    /** Unread-by-driver count for their own thread. */
    public function unreadCountForDriver(int $driverId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM DriverMessages
            WHERE driver_id = :did AND sender_type = 'admin' AND is_read_by_driver = 0");
        $stmt->execute(['did' => $driverId]);
        return (int) $stmt->fetchColumn();
    }

    /** Total unread (across every driver) for the admin-side header badge. */
    public function unreadTotalForAdmin(int $companyId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM DriverMessages dm
            INNER JOIN Users u ON u.id = dm.driver_id
            LEFT JOIN UserCompanies uc ON uc.user_id = u.id AND uc.company_id = :cid2
            WHERE dm.sender_type = 'driver' AND dm.is_read_by_admin = 0
              AND (u.company_id = :cid OR uc.company_id = :cid3)
        ");
        $stmt->execute(['cid' => $companyId, 'cid2' => $companyId, 'cid3' => $companyId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Drivers of this company with their last message preview and unread
     * count, most recent activity first — the admin's chat inbox list.
     *
     * @return array<array{driver_id:int, driver_name:string, last_message:?string, last_timestamp:?string, unread:int}>
     */
    public function inboxForAdmin(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id AS driver_id, u.name AS driver_name,
                   lm.message AS last_message, lm.timestamp AS last_timestamp,
                   COALESCE(uc_unread.unread, 0) AS unread
            FROM Users u
            LEFT JOIN UserCompanies uc ON uc.user_id = u.id AND uc.company_id = :cid2
            LEFT JOIN (
                SELECT dm1.driver_id, dm1.message, dm1.timestamp
                FROM DriverMessages dm1
                INNER JOIN (SELECT driver_id, MAX(id) AS max_id FROM DriverMessages GROUP BY driver_id) dm2
                    ON dm1.driver_id = dm2.driver_id AND dm1.id = dm2.max_id
            ) lm ON lm.driver_id = u.id
            LEFT JOIN (
                SELECT driver_id, COUNT(*) AS unread FROM DriverMessages
                WHERE sender_type = 'driver' AND is_read_by_admin = 0
                GROUP BY driver_id
            ) uc_unread ON uc_unread.driver_id = u.id
            WHERE u.role = 2 AND (u.company_id = :cid OR uc.company_id = :cid3)
            ORDER BY (lm.timestamp IS NULL) ASC, lm.timestamp DESC, u.name ASC
        ");
        $stmt->execute(['cid' => $companyId, 'cid2' => $companyId, 'cid3' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Keyword search across message text and topic titles, company-wide (or narrowed to one driver).
     * @return array<array<string,mixed>>
     */
    public function searchForAdmin(int $companyId, string $term, ?int $driverId = null): array
    {
        $like = '%' . $term . '%';
        $sql  = "
            SELECT dm.id, dm.conversation_id, dm.driver_id, dm.message, dm.timestamp,
                   u.name AS driver_name, dc.title AS topic_title, dc.is_general
            FROM DriverMessages dm
            JOIN DriverConversations dc ON dc.id = dm.conversation_id
            JOIN Users u ON u.id = dm.driver_id
            LEFT JOIN UserCompanies uc ON uc.user_id = u.id AND uc.company_id = :cid2
            WHERE (u.company_id = :cid OR uc.company_id = :cid3)
              AND (dm.message LIKE :term OR dc.title LIKE :term2)
        ";
        $params = ['cid' => $companyId, 'cid2' => $companyId, 'cid3' => $companyId, 'term' => $like, 'term2' => $like];
        if ($driverId !== null) {
            $sql .= ' AND dm.driver_id = :did';
            $params['did'] = $driverId;
        }
        $sql .= ' ORDER BY dm.timestamp DESC LIMIT 40';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Keyword search over a single driver's own messages + topic titles. */
    public function searchForDriver(int $driverId, string $term): array
    {
        $like = '%' . $term . '%';
        $stmt = $this->db->prepare('
            SELECT dm.id, dm.conversation_id, dm.message, dm.timestamp, dc.title AS topic_title, dc.is_general
            FROM DriverMessages dm
            JOIN DriverConversations dc ON dc.id = dm.conversation_id
            WHERE dm.driver_id = :did AND (dm.message LIKE :term OR dc.title LIKE :term2)
            ORDER BY dm.timestamp DESC LIMIT 40
        ');
        $stmt->execute(['did' => $driverId, 'term' => $like, 'term2' => $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
