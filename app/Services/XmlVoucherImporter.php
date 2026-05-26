<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ServiceRepository;
use PDO;
use SimpleXMLElement;

/**
 * Imports transfer-service vouchers from the legacy XML feed.
 *
 * One <Grouping> contains one or more <bookingItem>; the first item
 * carries the canonical service id from the feed (so we can reconcile
 * later) and the remaining items are stored as anonymous siblings.
 *
 * Duplicates are detected on the tuple
 * (serviceDate, serviceStartTime, NomeCliente, FlightNumber) — if the
 * tuple already exists, the booking is skipped.
 *
 * Phone numbers are extracted from the free-text <remarks> field with
 * two regex passes (Mobile: / Phone number:).
 */
final class XmlVoucherImporter
{
    public function __construct(
        private readonly PDO  $db,
        private readonly ?int $companyId = null,
    ) {
    }

    public static function default(): self
    {
        return new self(\App\Support\Database::connection(), \App\Support\Session::companyId());
    }

    /**
     * Parses the uploaded XML and inserts new services. Returns the
     * number of services actually inserted (duplicates do not count).
     */
    public function importFromString(string $xmlContent): int
    {
        $xml = @simplexml_load_string($xmlContent);
        if (!$xml || !isset($xml->Groupings->Grouping)) {
            return 0;
        }

        $duplicateCheck = $this->db->prepare('
            SELECT COUNT(*) FROM Services
            WHERE serviceDate = ? AND serviceStartTime = ?
              AND NomeCliente = ? AND FlightNumber = ?
        ');
        $insertWithId = $this->db->prepare('
            INSERT INTO Services (ID, serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY,
                                  serviceStartPoint, serviceTargetPoint, FlightNumber,
                                  NomeCliente, ClientNumber, serviceType, company_id)
            VALUES (:ID, :sd, :st, :pa, :pc, :bby, :sp, :tp, :fn, :nc, :cn, :stype, :cid)
        ');
        $insertWithoutId = $this->db->prepare('
            INSERT INTO Services (serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY,
                                  serviceStartPoint, serviceTargetPoint, FlightNumber,
                                  NomeCliente, ClientNumber, serviceType, company_id)
            VALUES (:sd, :st, :pa, :pc, :bby, :sp, :tp, :fn, :nc, :cn, :stype, :cid)
        ');

        $inserted = 0;
        foreach ($xml->Groupings->Grouping as $group) {
            $serviceDate = (string) $group->serviceDate;
            $serviceTime = (string) $group->serviceStartTime;
            $isShared    = stripos((string) $group->serviceUnitVehicleName, 'Shared') !== false;
            $type        = $isShared ? \App\Models\Service::TYPE_SHARED : \App\Models\Service::TYPE_PRIVATE;
            $groupId     = (int) $group->serviceId;

            foreach ($group->bookings->bookingItem as $index => $item) {
                $client = (string) $item->paxLeadName;
                $flight = $this->extractFlightNumber($item);

                $duplicateCheck->execute([$serviceDate, $serviceTime, $client, $flight]);
                if ((int) $duplicateCheck->fetchColumn() > 0) {
                    continue;
                }

                $pickup   = $this->resolvePickup($group, $item);
                $dropoff  = $this->resolveDropoff($group, $item);
                $remarks  = (string) ($item->remarks ?? '');
                $phone    = $this->extractPhone($remarks);

                $paxBby = (int) ($item->paxINF ?? 0);
                if ($paxBby === 0) {
                    $paxBby = $this->extractPaxBby($remarks);
                }

                $params = [
                    ':sd'    => $serviceDate,
                    ':st'    => $serviceTime,
                    ':pa'    => (int) $item->paxADT,
                    ':pc'    => (int) $item->paxCHD,
                    ':bby'   => $paxBby,
                    ':sp'    => $pickup,
                    ':tp'    => $dropoff,
                    ':fn'    => $flight,
                    ':nc'    => $client,
                    ':cn'    => $phone,
                    ':stype' => $type,
                    ':cid'   => $this->companyId,
                ];

                try {
                    if ($index === 0 && $groupId > 0) {
                        $params[':ID'] = $groupId;
                        $insertWithId->execute($params);
                    } else {
                        $insertWithoutId->execute($params);
                    }
                    $inserted++;
                } catch (\PDOException) {
                    unset($params[':ID']);
                    $insertWithoutId->execute($params);
                    $inserted++;
                }
            }
        }

        return $inserted;
    }

    private function extractFlightNumber(SimpleXMLElement $item): string
    {
        $candidate = null;
        if (isset($item->pickup->pickupPoint->flightNumber) && (string) $item->pickup->pickupPoint->flightNumber !== '') {
            $candidate = $item->pickup->pickupPoint;
        } elseif (isset($item->dropoff->pickupPoint->flightNumber) && (string) $item->dropoff->pickupPoint->flightNumber !== '') {
            $candidate = $item->dropoff->pickupPoint;
        }
        if ($candidate === null) {
            return 'N/A';
        }
        return (string) $candidate->flightCompanyCode . (string) $candidate->flightNumber;
    }

    private function resolvePickup(SimpleXMLElement $group, SimpleXMLElement $item): string
    {
        if (isset($item->pickup->accommodationtName) && (string) $item->pickup->accommodationtName !== '') {
            return (string) $item->pickup->accommodationtName;
        }
        return (string) $group->serviceStartPoint;
    }

    private function resolveDropoff(SimpleXMLElement $group, SimpleXMLElement $item): string
    {
        if (isset($item->dropoff->accommodationtName) && (string) $item->dropoff->accommodationtName !== '') {
            return (string) $item->dropoff->accommodationtName;
        }
        return (string) $group->serviceTargetPoint;
    }

    private function extractPhone(string $remarks): string
    {
        if (preg_match('/Mobile:\s*([^,]+)/i', $remarks, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/Phone number:\s*([^,]+)/i', $remarks, $m)) {
            return trim($m[1]);
        }
        return 'N/A';
    }

    /**
     * Fallback: extracts baby/infant count from the remarks free-text.
     * Used only when the structured <paxINF> element is absent or zero.
     */
    private function extractPaxBby(string $remarks): int
    {
        if (preg_match('/(\d+)\s*beb[eé]/ui', $remarks, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(\d+)\s*(?:infant|bab(?:y|ies))/ui', $remarks, $m)) {
            return (int) $m[1];
        }
        return 0;
    }
}
