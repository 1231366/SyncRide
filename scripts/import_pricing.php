<?php
/**
 * SyncRide — Importa os preçários da PRtours para a tabela `PricingRates`.
 *
 * CLI:  php scripts/import_pricing.php ["TABELA PREÇÁRIO para SyncRide.xlsx"]
 *
 * Lê as 4 folhas de tarifas e (re)popula `PricingRates`. Idempotente: cada
 * cartão é limpo e reinserido. Por omissão grava como tarifas GLOBAIS
 * (company_id NULL) — aplicam-se a todas as empresas; o PricingEngine prefere
 * uma tarifa específica da empresa se existir.
 *
 * Layout das folhas (confirmado via XlsxReader):
 *   PREÇARIO_MTS            r3=cabeçalho; bloco Shared cols 1/2/3; bloco Private cols 5/6/7-10
 *   PREÇARIO_PRTours        r2=cabeçalho; cols 1 / 2-5
 *   PREÇARIO_MOTORISTAS PRtours    r2=cabeçalho; cols 1 / 2(base) / 3(hotel extra)
 *   PREÇARIO_MOTORISTAS Parceiros  r2=cabeçalho, r3=sub; cols 1 / 2 / 3 / 4-6(shared 2/3/4) / 7 / 8
 */
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Repositories\PricingRepository;
use App\Support\XlsxReader;

$cli = \PHP_SAPI === 'cli';
$nl  = $cli ? "\n" : "<br>\n";
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
}

$file = $argv[1] ?? (__DIR__ . '/../precario.xlsx');
if (!is_file($file)) {
    $err = "Ficheiro não encontrado: {$file}{$nl}";
    $cli ? fwrite(STDERR, $err) : print($err);
    exit(1);
}

$companyId = null;
foreach ($argv ?? [] as $arg) {
    if ($arg !== null && str_starts_with($arg, '--company=')) {
        $companyId = (int) substr($arg, 10) ?: null;
    }
}

$reader = XlsxReader::open($file);
$rates  = new PricingRepository(\App\Support\Database::connection(), $companyId);

/** Converte célula em montante; '-', vazio ou não-numérico → null. */
$money = static function (mixed $v): ?float {
    if ($v === null) {
        return null;
    }
    $v = trim((string) $v);
    if ($v === '' || $v === '-') {
        return null;
    }
    $v = str_replace([' ', '€', ','], ['', '', '.'], $v);
    return is_numeric($v) ? (float) $v : null;
};
/** Normaliza distributor: "all others" → null (wildcard). */
$dist = static function (mixed $v): ?string {
    $v = trim((string) ($v ?? ''));
    if ($v === '' || stripos($v, 'all others') !== false) {
        return null;
    }
    return $v;
};
$resort = static fn(mixed $v): ?string => ($s = trim((string) ($v ?? ''))) !== '' ? $s : null;

$count = [];
$add = static function (string $card, array $rate) use ($rates, &$count): void {
    $rate['card'] = $card;
    $rates->insert($rate);
    $count[$card] = ($count[$card] ?? 0) + 1;
};

// ── 1. PREÇARIO_MTS ─────────────────────────────────────────────────────────
$rates->clearCard('mts');
$rows = $reader->rows('PREÇARIO_MTS');
$VALID_MTS = '2026-12-31';
$mtsPrivateCols = [7 => 'Standard', 8 => 'Mini Van', 9 => 'Private Luxury Car', 10 => 'Private Luxury Minibus'];
foreach (array_slice($rows, 4, null, true) as $r) {
    // Bloco Shared (cols 1/2/3)
    if (($res = $resort($r[1] ?? null)) !== null && ($p = $money($r[3] ?? null)) !== null) {
        $add('mts', ['resort' => $res, 'distributor_code' => $dist($r[2] ?? null), 'vehicle_label' => 'Shared', 'price' => $p, 'valid_until' => $VALID_MTS]);
    }
    // Bloco Private (cols 5/6/7-10)
    if (($res2 = $resort($r[5] ?? null)) !== null) {
        foreach ($mtsPrivateCols as $col => $veh) {
            if (($p = $money($r[$col] ?? null)) !== null) {
                $add('mts', ['resort' => $res2, 'distributor_code' => $dist($r[6] ?? null), 'vehicle_label' => $veh, 'price' => $p, 'valid_until' => $VALID_MTS]);
            }
        }
    }
}

// ── 2. PREÇARIO_PRTours (venda a cliente final) ─────────────────────────────
$rates->clearCard('prtours_retail');
$rows = $reader->rows('PREÇARIO_PRTours');
$retailCols = [2 => 'Standard', 3 => 'Mini Van', 4 => 'Private Luxury Car', 5 => 'Private Luxury Minibus'];
foreach (array_slice($rows, 3, null, true) as $r) {
    if (($res = $resort($r[1] ?? null)) === null) {
        continue;
    }
    foreach ($retailCols as $col => $veh) {
        if (($p = $money($r[$col] ?? null)) !== null) {
            $add('prtours_retail', ['resort' => $res, 'vehicle_label' => $veh, 'price' => $p]);
        }
    }
}

// ── 3. PREÇARIO_MOTORISTAS PRtours (viatura empresa) ────────────────────────
$rates->clearCard('driver_company_vehicle');
$rows = $reader->rows('PREÇARIO_MOTORISTAS PRtours');
foreach (array_slice($rows, 3, null, true) as $r) {
    if (($res = $resort($r[1] ?? null)) === null) {
        continue;
    }
    if (($p = $money($r[2] ?? null)) !== null) {
        $add('driver_company_vehicle', ['resort' => $res, 'price' => $p, 'hotel_extra' => $money($r[3] ?? null)]);
    }
}

// ── 4. PREÇARIO_MOTORISTAS Parceiros (viatura própria) ──────────────────────
$rates->clearCard('driver_own_vehicle');
$rows = $reader->rows('PREÇARIO_MOTORISTAS Parceiros');
$ownSimpleCols  = [2 => 'Standard', 3 => 'Mini Van', 7 => 'Private Luxury Car', 8 => 'Private Luxury Minibus'];
$ownSharedTiers = [4 => 2, 5 => 3, 6 => 4]; // col → pax_tier
foreach (array_slice($rows, 4, null, true) as $r) {
    if (($res = $resort($r[1] ?? null)) === null) {
        continue;
    }
    foreach ($ownSimpleCols as $col => $veh) {
        if (($p = $money($r[$col] ?? null)) !== null) {
            $add('driver_own_vehicle', ['resort' => $res, 'vehicle_label' => $veh, 'price' => $p]);
        }
    }
    foreach ($ownSharedTiers as $col => $tier) {
        if (($p = $money($r[$col] ?? null)) !== null) {
            $add('driver_own_vehicle', ['resort' => $res, 'vehicle_label' => 'Shared', 'pax_tier' => $tier, 'price' => $p]);
        }
    }
}

echo "Preçários importados para PricingRates:{$nl}";
foreach (['mts', 'prtours_retail', 'driver_company_vehicle', 'driver_own_vehicle'] as $card) {
    echo sprintf("  %-24s %d linhas%s", $card, $count[$card] ?? 0, $nl);
}
echo "✅ Concluído.{$nl}";
