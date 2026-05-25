<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ChatMessage;
use App\Support\Database;
use PDO;

/** Driver ↔ client chat for the public tracking page. */
final class ChatMessageRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function default(): self
    {
        return new self(Database::connection());
    }

    /**
     * @return array<ChatMessage> messages for a ride, chronological. If
     * `$markReadFor` is provided ('driver' or 'client'), unread messages
     * the *other* side sent are flagged read as a side effect.
     */
    public function forRide(int $rideId, ?string $markReadFor = null): array
    {
        if ($markReadFor === ChatMessage::SENDER_DRIVER) {
            $this->db->prepare("UPDATE ChatMessages SET is_read_by_driver = 1
                                WHERE ride_id = :rid AND sender_type = 'client' AND is_read_by_driver = 0")
                ->execute(['rid' => $rideId]);
        } elseif ($markReadFor === ChatMessage::SENDER_CLIENT) {
            $this->db->prepare("UPDATE ChatMessages SET is_read_by_client = 1
                                WHERE ride_id = :rid AND sender_type = 'driver' AND is_read_by_client = 0")
                ->execute(['rid' => $rideId]);
        }

        $stmt = $this->db->prepare('SELECT * FROM ChatMessages WHERE ride_id = :rid ORDER BY timestamp');
        $stmt->execute(['rid' => $rideId]);
        return array_map(ChatMessage::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function send(int $rideId, string $senderType, ?int $senderId, string $message): ChatMessage
    {
        $stmt = $this->db->prepare('
            INSERT INTO ChatMessages (ride_id, sender_type, sender_id, message)
            VALUES (:rid, :type, :sender, :msg)
        ');
        $stmt->execute([
            'rid'    => $rideId,
            'type'   => $senderType,
            'sender' => $senderId,
            'msg'    => $message,
        ]);

        $id = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare('SELECT * FROM ChatMessages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return ChatMessage::fromRow($stmt->fetch(PDO::FETCH_ASSOC));
    }
}
