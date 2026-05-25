<?php

declare(strict_types=1);

namespace App\Models;

final class ChatMessage
{
    public const SENDER_CLIENT = 'client';
    public const SENDER_DRIVER = 'driver';

    public function __construct(
        public readonly int $id,
        public readonly int $rideId,
        public readonly string $senderType,
        public readonly ?int $senderId,
        public readonly string $message,
        public readonly string $timestamp,
        public readonly bool $isReadByDriver,
        public readonly bool $isReadByClient,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id:             (int) $row['id'],
            rideId:         (int) $row['ride_id'],
            senderType:     (string) $row['sender_type'],
            senderId:       isset($row['sender_id']) ? (int) $row['sender_id'] : null,
            message:        (string) $row['message'],
            timestamp:      (string) $row['timestamp'],
            isReadByDriver: (bool) ($row['is_read_by_driver'] ?? false),
            isReadByClient: (bool) ($row['is_read_by_client'] ?? false),
        );
    }

    public function isFromClient(): bool { return $this->senderType === self::SENDER_CLIENT; }
}
