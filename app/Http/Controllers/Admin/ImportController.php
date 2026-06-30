<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\ImportBatchRepository;
use App\Repositories\LogRepository;
use App\Services\ExcelServiceImporter;

/**
 * Importação de serviços a partir do Excel da PRtours.
 *
 * Fluxo em dois passos para o utilizador poder rever antes de gravar:
 *   1. preview() — upload + análise (sem escrever); guarda o ficheiro num
 *      temporário identificado por token e devolve a pré-visualização (JSON).
 *   2. commit()  — confirma o token, importa de facto e regista um lote
 *      (ImportBatches) que pode depois ser desfeito em undo().
 */
final class ImportController extends BaseController
{
    private const MAX_BYTES = 10 * 1024 * 1024; // 10 MB
    private const PREVIEW_ROWS = 200;            // limite de linhas mostradas na pré-visualização

    private ImportBatchRepository $batches;
    private LogRepository         $logs;

    public function __construct()
    {
        $this->batches = ImportBatchRepository::default();
        $this->logs    = LogRepository::default();
    }

    /** GET /admin/import.php */
    public function index(): void
    {
        $this->view('admin.import.index', [
            'recentBatches' => $this->batches->recent(10),
            'flash'         => $_GET['success'] ?? null,
            'error'         => $_GET['error']   ?? null,
        ]);
    }

    /** POST /admin/import-preview.php — analisa sem escrever, devolve JSON. */
    public function preview(): never
    {
        $this->requirePost();

        $file = $this->validateUpload();

        // Lê o ficheiro de upload DIRETAMENTE (já é legível pelo PHP) — sem o
        // mover para nenhuma pasta, evitando dependências de permissões.
        try {
            $result = ExcelServiceImporter::default()->preview((string) $file['tmp_name']);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Não foi possível ler o ficheiro: ' . $e->getMessage()], 422);
        }

        // Guarda os candidatos analisados na sessão; o commit usa-os depois.
        $token = bin2hex(random_bytes(8));
        $_SESSION['import_preview'][$token] = $result['rows'];

        $rows = array_map(
            static fn(array $r): array => [
                'status'   => $r['_status'],
                'reason'   => $r['_reason'] ?? null,
                'date'     => $r['serviceDate'],
                'time'     => $r['serviceStartTime'] ? substr((string) $r['serviceStartTime'], 0, 5) : null,
                'client'   => $r['NomeCliente'],
                'pickup'   => $r['serviceStartPoint'],
                'dropoff'  => $r['serviceTargetPoint'],
                'pax'      => (int) $r['paxADT'] + (int) $r['paxCHD'] + (int) $r['paxBBY'],
                'type'     => $r['serviceType'] === 0 ? 'Shared' : 'Private',
                'supplier' => $r['supplier'],
                'flight'   => $r['FlightNumber'],
                'price'    => $r['total_price'],
                'driver'   => $r['valor_motorista'],
            ],
            array_slice($result['rows'], 0, self::PREVIEW_ROWS)
        );

        $this->json([
            'success'   => true,
            'token'     => $token,
            'summary'   => $result['summary'],
            'rows'      => $rows,
            'truncated' => count($result['rows']) > self::PREVIEW_ROWS,
        ]);
    }

    /** POST /admin/import-commit.php — confirma e importa. */
    public function commit(): never
    {
        $this->requirePost();

        $token = (string) $this->input('token', '');
        if (!preg_match('/^[a-f0-9]{16}$/', $token)) {
            $this->json(['success' => false, 'message' => 'Token inválido.'], 422);
        }
        $rows = $_SESSION['import_preview'][$token] ?? null;
        if (!is_array($rows)) {
            $this->json(['success' => false, 'message' => 'Sessão de importação expirada. Volte a carregar o ficheiro.'], 410);
        }

        $filename = (string) $this->input('filename', 'import.xlsx');
        $res = ExcelServiceImporter::default()->persist($rows, $filename);
        unset($_SESSION['import_preview'][$token]);

        $this->logs->record(
            "Excel import #{$res['batch_id']}: {$res['inserted']} inseridos, {$res['skipped']} ignorados, {$res['failed']} falhados"
        );

        $this->json([
            'success'  => true,
            'batch_id' => $res['batch_id'],
            'inserted' => $res['inserted'],
            'skipped'  => $res['skipped'],
            'failed'   => $res['failed'],
        ]);
    }

    /** POST /admin/import-undo.php — desfaz um lote. */
    public function undo(): never
    {
        $this->requirePost();

        $batchId = (int) $this->input('batch_id', 0);
        if ($batchId <= 0) {
            $this->json(['success' => false, 'message' => 'Lote inválido.'], 422);
        }

        if ($this->batches->hasPastOrTodayServices($batchId)) {
            $this->json(['success' => false, 'message' => 'Não é possível desfazer importações do dia atual ou de dias passados.'], 403);
        }

        $deleted = $this->batches->undo($batchId);
        $this->logs->record("Excel import #{$batchId} desfeito ({$deleted} serviços removidos)");

        $this->json(['success' => true, 'deleted' => $deleted]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Valida o upload e devolve o array de $_FILES. Não move o ficheiro — o
     * preview lê-o diretamente do tmp_name do upload.
     * @return array<string,mixed>
     */
    private function validateUpload(): array
    {
        $file = $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Nenhum ficheiro carregado.'], 400);
        }
        if (strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
            $this->json(['success' => false, 'message' => 'Apenas ficheiros .xlsx são aceites.'], 400);
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            $this->json(['success' => false, 'message' => 'O ficheiro excede o limite de 10 MB.'], 400);
        }
        return $file;
    }
}
