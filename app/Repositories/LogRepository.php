<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

/**
 * Append-only audit log. Every state-changing controller writes here.
 */
final class LogRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function default(): self
    {
        return new self(Database::connection());
    }

    public function record(string $action): void
    {
        $this->db->prepare('INSERT INTO Logs (Action, date) VALUES (:a, NOW())')
            ->execute(['a' => $action]);
    }

    /**
     * @return array<array{logID:int,Action:string,date:string}>
     */
    public function recent(int $limit = 100): array
    {
        $limit = max(1, min(1000, $limit));
        $stmt = $this->db->query("SELECT * FROM Logs ORDER BY date DESC, logID DESC LIMIT {$limit}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lastBackupDate(): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT date FROM Logs WHERE Action = 'Database backup generated' ORDER BY date DESC LIMIT 1"
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string) $row['date'] : null;
    }

    /**
     * All logs mentioning a specific service ID, used for the trip report email.
     * @return array<array{Action:string,date:string}>
     */
    public function forRide(int $rideId): array
    {
        $stmt = $this->db->prepare(
            "SELECT Action, date FROM Logs WHERE Action LIKE :pat ORDER BY date ASC"
        );
        $stmt->execute(['pat' => "%ID #{$rideId}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clear(): void
    {
        $this->db->exec('DELETE FROM Logs');
        $this->record('Audit log cleared');
    }
}
