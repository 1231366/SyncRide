<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use App\Support\Session;
use PDO;

/**
 * Append-only audit log. Every state-changing controller writes here.
 * Reads are scoped to the active company; super-admin sees everything.
 */
final class LogRepository
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

    public function record(string $action): void
    {
        $this->db->prepare('INSERT INTO Logs (Action, date, company_id) VALUES (:a, NOW(), :cid)')
            ->execute(['a' => $action, 'cid' => $this->companyId]);
    }

    /**
     * @return array<array{logID:int,Action:string,date:string}>
     */
    public function recent(int $limit = 100): array
    {
        $limit = max(1, min(1000, $limit));
        $sql   = 'SELECT * FROM Logs WHERE 1=1 ' . $this->companyClause('AND') . " ORDER BY date DESC, logID DESC LIMIT {$limit}";
        $stmt  = $this->db->prepare($sql);
        $stmt->execute($this->companyBindings());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lastBackupDate(): ?string
    {
        $sql  = "SELECT date FROM Logs WHERE Action = 'Database backup generated' " . $this->companyClause('AND') . ' ORDER BY date DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->companyBindings());
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string) $row['date'] : null;
    }

    /**
     * All logs mentioning a specific service ID — used for the trip report email.
     * Not company-scoped (ride IDs are globally unique).
     * @return array<array{Action:string,date:string}>
     */
    public function forRide(int $rideId): array
    {
        $stmt = $this->db->prepare(
            'SELECT Action, date FROM Logs WHERE Action LIKE :pat ORDER BY date ASC'
        );
        $stmt->execute(['pat' => "%ID #{$rideId}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clear(): void
    {
        $sql = 'DELETE FROM Logs WHERE 1=1 ' . $this->companyClause('AND');
        $this->db->prepare($sql)->execute($this->companyBindings());
        $this->record('Audit log cleared');
    }

    // ------------------------------------------------------------------ helpers

    private function companyClause(string $prefix = 'WHERE'): string
    {
        return $this->companyId !== null ? "{$prefix} company_id = :company_id" : '';
    }

    private function companyBindings(): array
    {
        return $this->companyId !== null ? ['company_id' => $this->companyId] : [];
    }
}
