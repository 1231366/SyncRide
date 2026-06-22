<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use App\Support\Session;
use PDO;

/**
 * Auditoria e undo das importações (Excel/XML).
 *
 * Cada importação cria um registo em `ImportBatches`; os serviços inseridos
 * ficam ligados via `Services.import_batch_id`, o que permite desfazer um
 * lote inteiro sem afetar nada do resto.
 */
final class ImportBatchRepository
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

    public function create(string $filename, string $source, int $rowsTotal): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO ImportBatches (company_id, filename, source, rows_total, created_by)
            VALUES (:cid, :fn, :src, :total, :by)
        ');
        $stmt->execute([
            'cid'   => $this->companyId,
            'fn'    => $filename,
            'src'   => $source,
            'total' => $rowsTotal,
            'by'    => Session::userId(),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function finalize(int $batchId, int $inserted, int $skipped, int $failed): void
    {
        $this->db->prepare('
            UPDATE ImportBatches
            SET rows_inserted = :ins, rows_skipped = :skp, rows_failed = :fld
            WHERE id = :id
        ')->execute(['ins' => $inserted, 'skp' => $skipped, 'fld' => $failed, 'id' => $batchId]);
    }

    /** @return array<array<string,mixed>> lotes recentes desta empresa. */
    public function recent(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        if ($this->companyId !== null) {
            $stmt = $this->db->prepare(
                'SELECT * FROM ImportBatches WHERE company_id = :cid ORDER BY created_at DESC LIMIT ' . $limit
            );
            $stmt->execute(['cid' => $this->companyId]);
        } else {
            $stmt = $this->db->query('SELECT * FROM ImportBatches ORDER BY created_at DESC LIMIT ' . $limit);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $batchId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ImportBatches WHERE id = :id');
        $stmt->execute(['id' => $batchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Desfaz um lote: apaga os serviços inseridos por ele (e respetivas
     * atribuições), respeitando a empresa do utilizador. Devolve o nº apagado.
     */
    public function undo(int $batchId): int
    {
        $batch = $this->find($batchId);
        if ($batch === null) {
            return 0;
        }
        // Tenancy: super-admin (null) pode tudo; admin só o seu lote.
        if ($this->companyId !== null && (int) $batch['company_id'] !== $this->companyId) {
            return 0;
        }

        $this->db->beginTransaction();
        try {
            $ids = $this->db->prepare('SELECT ID FROM Services WHERE import_batch_id = :b');
            $ids->execute(['b' => $batchId]);
            $rideIds = $ids->fetchAll(PDO::FETCH_COLUMN);

            $count = 0;
            if ($rideIds !== []) {
                $ph = implode(',', array_fill(0, count($rideIds), '?'));
                $this->db->prepare("DELETE FROM Services_Rides WHERE RideID IN ({$ph})")->execute($rideIds);
                $del = $this->db->prepare("DELETE FROM Services WHERE ID IN ({$ph})");
                $del->execute($rideIds);
                $count = $del->rowCount();
            }
            $this->db->prepare('DELETE FROM ImportBatches WHERE id = :id')->execute(['id' => $batchId]);
            $this->db->commit();
            return $count;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
