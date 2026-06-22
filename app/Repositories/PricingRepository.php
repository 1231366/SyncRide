<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use App\Support\Session;
use PDO;

/**
 * Acesso ao rate card (`PricingRates`) — substitui os Excel de preçário.
 *
 * Cada linha é um preço para uma combinação (cartão, resort, distributor,
 * veículo, escalão de pax). O distributor `NULL` funciona como wildcard
 * ("all others"). As consultas preferem a correspondência mais específica.
 */
final class PricingRepository
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

    /** Apaga todas as linhas de um cartão (para reimportar do zero). */
    public function clearCard(string $card): void
    {
        if ($this->companyId !== null) {
            $this->db->prepare('DELETE FROM PricingRates WHERE card = :c AND company_id = :cid')
                ->execute(['c' => $card, 'cid' => $this->companyId]);
        } else {
            $this->db->prepare('DELETE FROM PricingRates WHERE card = :c AND company_id IS NULL')
                ->execute(['c' => $card]);
        }
    }

    /** Insere uma linha de preço. `$rate` usa as chaves das colunas. */
    public function insert(array $rate): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO PricingRates
                (company_id, card, supplier, resort, distributor_code, vehicle_label, pax_tier, price, hotel_extra, valid_until)
            VALUES
                (:cid, :card, :supplier, :resort, :dist, :veh, :tier, :price, :hx, :valid)
        ');
        $stmt->execute([
            'cid'      => $this->companyId,
            'card'     => $rate['card'],
            'supplier' => $rate['supplier']         ?? null,
            'resort'   => $rate['resort']           ?? null,
            'dist'     => $rate['distributor_code'] ?? null,
            'veh'      => $rate['vehicle_label']     ?? null,
            'tier'     => $rate['pax_tier']          ?? null,
            'price'    => $rate['price']             ?? null,
            'hx'       => $rate['hotel_extra']       ?? null,
            'valid'    => $rate['valid_until']        ?? null,
        ]);
    }

    public function countByCard(string $card): int
    {
        $sql    = 'SELECT COUNT(*) FROM PricingRates WHERE card = :c';
        $params = ['c' => $card];
        if ($this->companyId !== null) {
            $sql .= ' AND (company_id = :cid OR company_id IS NULL)';
            $params['cid'] = $this->companyId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Procura o melhor preço para os critérios dados. Linhas com distributor
     * específico ganham ao wildcard (NULL); idem para o escalão de pax.
     * Devolve a linha (com `price` e `hotel_extra`) ou null.
     *
     * @param array<string,mixed> $criteria  card, resort, distributor_code, vehicle_label, pax_tier
     * @return array<string,mixed>|null
     */
    public function findRate(array $criteria): ?array
    {
        $where  = ['card = :card'];
        $params = ['card' => $criteria['card']];

        // Resort: igualdade (case-insensitive)
        if (!empty($criteria['resort'])) {
            $where[] = 'LOWER(resort) = LOWER(:resort)';
            $params['resort'] = $criteria['resort'];
        }
        if (!empty($criteria['vehicle_label'])) {
            $where[] = 'vehicle_label = :veh';
            $params['veh'] = $criteria['vehicle_label'];
        }
        // Distributor: se vier um código, aceita esse OU o wildcard (NULL),
        // preferindo o específico. Se NÃO vier, só casa o wildcard — nunca
        // empresta o preço de outro distributor.
        if (!empty($criteria['distributor_code'])) {
            $where[] = '(distributor_code = :dist OR distributor_code IS NULL)';
            $params['dist'] = $criteria['distributor_code'];
        } else {
            $where[] = 'distributor_code IS NULL';
        }
        // Pax tier: igual lógica — específico, ou só o sem-escalão.
        if (!empty($criteria['pax_tier'])) {
            $where[] = '(pax_tier = :tier OR pax_tier IS NULL)';
            $params['tier'] = $criteria['pax_tier'];
        } else {
            $where[] = 'pax_tier IS NULL';
        }
        // Empresa: a sua OU global (NULL)
        if ($this->companyId !== null) {
            $where[] = '(company_id = :cid OR company_id IS NULL)';
            $params['cid'] = $this->companyId;
        }

        $sql = 'SELECT * FROM PricingRates WHERE ' . implode(' AND ', $where) . '
                ORDER BY (distributor_code IS NOT NULL) DESC,
                         (pax_tier IS NOT NULL) DESC,
                         (company_id IS NOT NULL) DESC
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Todas as linhas de um cartão visíveis para esta empresa:
     * as da própria empresa + as globais (company_id IS NULL, geridas pelo super-admin).
     * Super-admin (companyId = null) vê tudo.
     *
     * @return array<array<string,mixed>>
     */
    public function listCard(string $card): array
    {
        $sql    = 'SELECT * FROM PricingRates WHERE card = :c';
        $params = ['c' => $card];
        if ($this->companyId !== null) {
            $sql .= ' AND (company_id = :cid OR company_id IS NULL)';
            $params['cid'] = $this->companyId;
        }
        $sql .= ' ORDER BY resort, distributor_code, vehicle_label, pax_tier';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza uma linha de tarifa — apenas se pertencer a esta empresa.
     * Super-admin (companyId = null) pode editar qualquer tarifa.
     */
    public function updateRate(int $id, array $data): void
    {
        $sql    = '
            UPDATE PricingRates SET
                card = :card, supplier = :supplier, resort = :resort,
                distributor_code = :dist, vehicle_label = :veh, pax_tier = :tier,
                price = :price, hotel_extra = :hx, valid_until = :valid
            WHERE id = :id';
        $params = [
            'id'       => $id,
            'card'     => $data['card'],
            'supplier' => $data['supplier']         ?? null,
            'resort'   => $data['resort']           ?? null,
            'dist'     => $data['distributor_code'] ?? null,
            'veh'      => $data['vehicle_label']    ?? null,
            'tier'     => $data['pax_tier']         ?? null,
            'price'    => $data['price']            ?? null,
            'hx'       => $data['hotel_extra']      ?? null,
            'valid'    => $data['valid_until']       ?? null,
        ];
        if ($this->companyId !== null) {
            $sql .= ' AND company_id = :cid';
            $params['cid'] = $this->companyId;
        }
        $this->db->prepare($sql)->execute($params);
    }

    /** Apaga uma linha de tarifa — apenas se pertencer a esta empresa. */
    public function deleteRate(int $id): void
    {
        $sql    = 'DELETE FROM PricingRates WHERE id = :id';
        $params = ['id' => $id];
        if ($this->companyId !== null) {
            $sql .= ' AND company_id = :cid';
            $params['cid'] = $this->companyId;
        }
        $this->db->prepare($sql)->execute($params);
    }
}
