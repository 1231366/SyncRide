<?php

declare(strict_types=1);

namespace App\Models;

final class DriverMessage
{
    public const SENDER_ADMIN  = 'admin';
    public const SENDER_DRIVER = 'driver';
    public const SENDER_SYSTEM = 'system';

    public function __construct(
        public readonly int $id,
        public readonly int $driverId,
        public readonly int $conversationId,
        public readonly string $senderType,
        public readonly ?int $senderId,
        public readonly string $message,
        public readonly string $timestamp,
        public readonly bool $isReadByAdmin,
        public readonly bool $isReadByDriver,
        public readonly ?string $senderName = null,
        public readonly ?int $replyToId = null,
        public readonly ?string $attachmentPath = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id:             (int) $row['id'],
            driverId:       (int) $row['driver_id'],
            conversationId: (int) $row['conversation_id'],
            senderType:     (string) $row['sender_type'],
            senderId:       isset($row['sender_id']) ? (int) $row['sender_id'] : null,
            message:        (string) $row['message'],
            timestamp:      (string) $row['timestamp'],
            isReadByAdmin:  (bool) ($row['is_read_by_admin']  ?? false),
            isReadByDriver: (bool) ($row['is_read_by_driver'] ?? false),
            senderName:     $row['sender_name'] ?? null,
            replyToId:      isset($row['reply_to_id']) ? (int) $row['reply_to_id'] : null,
            attachmentPath: $row['attachment_path'] ?? null,
        );
    }

    public function isFromDriver(): bool { return $this->senderType === self::SENDER_DRIVER; }
}
