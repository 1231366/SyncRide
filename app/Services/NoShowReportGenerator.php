<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Generates a no-show incident PDF report using FPDF.
 * Returns the relative DB path to the saved file.
 */
final class NoShowReportGenerator
{
    private const FPDF = __DIR__ . '/../../vendor/fpdf/fpdf.php';

    // Brand palette
    private const BG = [248, 250, 252];
    private const BLUE = [37, 99, 235];
    private const BLUE_LIGHT = [239, 246, 255];
    private const RED = [220, 38, 38];
    private const BORDER = [226, 232, 240];
    private const TEXT1 = [15, 23, 42];
    private const TEXT2 = [71, 85, 105];
    private const TEXT3 = [148, 163, 184];

    public function generate(
        int     $tripId,
        array   $tripData,
        string  $photoServerPath,
        ?string $lat,
        ?string $lng,
    ): string {
        require_once self::FPDF;

        date_default_timezone_set('Europe/Lisbon');
        $now         = date('d/m/Y H:i');
        $companyName = (string) ($tripData['company_name']        ?? 'SyncRide');
        $driverName  = (string) ($tripData['driver_name']         ?? 'N/A');
        $clientName  = strtoupper((string) ($tripData['NomeCliente']       ?? 'N/A'));
        $origin      = (string) ($tripData['serviceStartPoint']   ?? '');
        $destination = (string) ($tripData['serviceTargetPoint']  ?? '');
        $date        = !empty($tripData['serviceDate'])
            ? (new \DateTime($tripData['serviceDate']))->format('d/m/Y')
            : date('d/m/Y');
        $time        = !empty($tripData['serviceStartTime'])
            ? substr((string) $tripData['serviceStartTime'], 0, 5)
            : '';

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        // ── PAGE BACKGROUND ───────────────────────────────────────
        $pdf->SetFillColor(...self::BG);
        $pdf->Rect(0, 0, 210, 297, 'F');

        // ── HEADER BAR ────────────────────────────────────────────
        $pdf->SetFillColor(...self::BLUE);
        $pdf->Rect(0, 0, 210, 40, 'F');

        // Small decorative accent strip (darker blue)
        $pdf->SetFillColor(29, 78, 216);
        $pdf->Rect(0, 35, 210, 5, 'F');

        // Logo — "SyncRide OS"
        $pdf->SetXY(15, 10);
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 10, 'SyncRide', 0, 0, 'L');

        $pdf->SetXY(15, 22);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(147, 197, 253); // blue-300
        $pdf->Cell(0, 5, 'Fleet Management Platform', 0, 0, 'L');

        // Right — report label
        $pdf->SetXY(105, 9);
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(90, 8, 'No-Show Report', 0, 0, 'R');

        $pdf->SetXY(105, 18);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(147, 197, 253);
        $pdf->Cell(90, 6, 'Ride #' . $tripId, 0, 0, 'R');

        $pdf->SetXY(105, 26);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(186, 230, 253); // blue-200
        $pdf->Cell(90, 5, 'Generated: ' . $now, 0, 0, 'R');

        // ── COMPANY BAND ──────────────────────────────────────────
        $pdf->SetFillColor(...self::BLUE_LIGHT);
        $pdf->Rect(0, 40, 210, 16, 'F');

        $pdf->SetXY(15, 44);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(...self::BLUE);
        $pdf->Cell(130, 7, $companyName, 0, 0, 'L');

        // NO-SHOW pill
        $pdf->SetFillColor(...self::RED);
        $pdf->Rect(158, 44, 38, 8, 'F');
        $pdf->SetXY(158, 44);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(38, 8, ' NO-SHOW ', 0, 0, 'C');

        // ── SECTION: SERVICE DETAILS ──────────────────────────────
        $y = 62;

        $pdf->SetDrawColor(...self::BORDER);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(15, $y, 180, 52, 'FD');

        // Section title
        $pdf->SetXY(15, $y + 4);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(...self::TEXT3);
        $pdf->Cell(180, 4, '  SERVICE DETAILS', 0, 1, 'L');

        $pdf->SetDrawColor(...self::BORDER);
        $pdf->Line(15, $y + 10, 195, $y + 10);

        $y += 13;

        // Two-column details
        $lx = 20; $rx = 115; $colW = 85;
        foreach ([
            [['Driver', $driverName],        ['Date / Time', $date . '  ' . $time]],
            [['Client', $clientName],         ['Service ID',  '#' . $tripId]],
            [['Origin', $origin],             null],
            [['Destination', $destination],   null],
        ] as $row) {
            [$label, $value] = $row[0];
            $this->detailCell($pdf, $lx, $y, $label, $value, $colW);
            if ($row[1] !== null) {
                [$rlabel, $rvalue] = $row[1];
                $this->detailCell($pdf, $rx, $y, $rlabel, $rvalue, $colW);
            }
            $y += 8;
        }

        if ($lat && $lng) {
            $this->detailCell($pdf, $lx, $y, 'GPS', $lat . ', ' . $lng, 160);
            $y += 8;
        }

        // ── SECTION: PHOTO EVIDENCE ───────────────────────────────
        $y = 120;

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(...self::BORDER);

        // Footer is at y=262; reserve 30mm for footer + gap
        $maxPhotoH  = 262 - $y - 22 - 10; // available height for the image itself
        $imgExists  = file_exists($photoServerPath);
        $rawH       = $imgExists ? $this->computePhotoH($photoServerPath, 160) : 40;
        $photoH     = min($rawH, (float) $maxPhotoH);
        $sectionH   = $photoH + 22;

        $pdf->Rect(15, $y, 180, $sectionH, 'FD');

        $pdf->SetXY(15, $y + 4);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(...self::TEXT3);
        $pdf->Cell(180, 4, '  PHOTO EVIDENCE', 0, 1, 'L');

        $pdf->Line(15, $y + 10, 195, $y + 10);

        if ($imgExists) {
            $photoW = 160;
            $imgX   = (210 - $photoW) / 2;
            $imgY   = $y + 13;

            // Shadow/border behind photo
            $pdf->SetFillColor(...self::BORDER);
            $pdf->Rect($imgX - 1, $imgY - 1, $photoW + 2, $photoH + 2, 'F');
            $pdf->Image($photoServerPath, $imgX, $imgY, $photoW, $photoH);
        } else {
            $pdf->SetXY(15, $y + 20);
            $pdf->SetFont('Helvetica', 'I', 9);
            $pdf->SetTextColor(...self::TEXT3);
            $pdf->Cell(180, 8, 'Photo not available.', 0, 0, 'C');
        }

        $y += $sectionH + 10;

        // ── FOOTER (fixed at bottom of page 1) ────────────────────
        $fy = 262;
        $pdf->SetDrawColor(...self::BORDER);
        $pdf->Line(15, $fy, 195, $fy);

        $pdf->SetXY(15, $fy + 4);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(...self::TEXT2);
        $pdf->Cell(130, 4, 'Report ID: #' . $tripId, 0, 0, 'L');
        $pdf->Cell(50, 4, 'Powered by SyncRide OS', 0, 0, 'R');

        $pdf->SetXY(15, $fy + 10);
        $pdf->SetFont('Helvetica', '', 6.5);
        $pdf->SetTextColor(...self::TEXT3);
        $pdf->MultiCell(180, 3.5,
            'This report was automatically generated by SyncRide OS based on operational and GPS data collected in real time. ' .
            'All information is recorded and processed by the system and cannot be manually altered by users.',
            0, 'L');

        // ── SAVE ──────────────────────────────────────────────────
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/no_shows/';
        $fileName  = 'noshow_report_' . $tripId . '_' . time() . '.pdf';
        $pdf->Output($uploadDir . $fileName, 'F');

        return 'uploads/no_shows/' . $fileName;
    }

    private function detailCell(\FPDF $pdf, float $x, float $y, string $label, string $value, float $w): void
    {
        $pdf->SetXY($x, $y);
        $pdf->SetFont('Helvetica', 'B', 6.5);
        $pdf->SetTextColor(...self::TEXT3);
        $pdf->Cell(22, 5, strtoupper($label), 0, 0, 'L');

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(...self::TEXT1);
        $pdf->Cell($w - 22, 5, $value, 0, 0, 'L');
    }

    private function computePhotoH(string $path, float $targetW): float
    {
        [$w, $h] = getimagesize($path);
        if ($w <= 0) {
            return 80;
        }
        return round($targetW * $h / $w, 1);
    }
}
