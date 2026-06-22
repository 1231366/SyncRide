# PRtours — Progresso & Runbook de Migração

> Documento **vivo**. Registo do que está feito e como aplicar (migrar) numa
> sessão/ambiente novo. Plano detalhado das decisões: `docs/PLANO_PRTOURS.md`.
> Branch: `feat/prtours-excel-pricing`. Última atualização: 2026-06-15.

---

## ⚡ TL;DR — como migrar num ambiente novo

Com o MySQL a correr e o `.env` apontado à BD certa (local: `SR_ATUAL`):

```bash
# 1. Schema aditivo (idempotente — seguro recorrer)
php scripts/migrate_prtours.php

# 2. Carregar os preçários para PricingRates (idempotente — limpa e reinsere)
php scripts/import_pricing.php "TABELA PREÇÁRIO para SyncRide.xlsx"

# 3. (opcional) Correr os testes — precisa do phpunit.phar (não há composer local)
curl -sSL https://phar.phpunit.de/phpunit-11.phar -o /tmp/phpunit.phar
php /tmp/phpunit.phar --testsuite Unit
```

Ambos os scripts são **idempotentes**. Nada do schema existente é alterado —
só se adicionam colunas/tabelas nullable, por isso o sistema atual continua a
funcionar antes e depois.

**Produção (cPanel):** o `.env` aponta à BD de produção; correr os mesmos 2
scripts via SSH, ou colar `database/2026_prtours.sql` no phpMyAdmin (o script
PHP é preferível por ser idempotente). Depois importar os preçários.

### Rollback
- Colunas/tabelas são aditivas; para reverter, `DROP` as colunas novas e as
  tabelas `ImportBatches`/`PricingRates` (ver lista em §"Schema").
- Qualquer **importação de serviços** desfaz-se na UI (Importar → Desfazer) ou
  via `ImportBatchRepository::undo($batchId)` — apaga só as linhas desse lote.

---

## Estado por fase

| Fase | Descrição | Estado |
|---|---|---|
| 0 | Schema aditivo | ✅ feito + **aplicado em SR_ATUAL** |
| 1 | Importador de Excel (serviços) | ✅ feito + testado end-to-end |
| 2 | Dois valores (valor_motorista) | ✅ feito |
| 3 | Motor de preçário | ✅ feito + **preçários carregados** |
| 4 | Agregar/desagregar shared | ✅ feito + testado end-to-end |
| 5 | Gestão financeira real | ✅ feito + testado end-to-end |

---

## Schema (Fase 0) — `database/2026_prtours.sql` + `scripts/migrate_prtours.php`

`Services` (+colunas, todas nullable/default): `supplier`, `grouping_ref`,
`distributor_code`, `resort`, `vehicle_label`, `leg_code`, `reference_no`,
`valor_motorista`, `pay_basis` (enum company_vehicle|own_vehicle), `hotel_extra`,
`import_notes`, `import_batch_id` + índices.

`Users` (+colunas): `driver_code`, `default_pay_basis` (enum, default
`company_vehicle`).

Tabelas novas: `ImportBatches` (auditoria/undo de imports), `PricingRates`
(rate card editável).

## Import de serviços (Fase 1)
- `app/Support/XlsxReader.php` — leitor .xlsx puro (datas/horas via estilos).
- `app/Services/ExcelServiceImporter.php` — `preview()` (sem escrever),
  `persist()` (insere candidatos já analisados) e `commit(path)` (preview+persist).
  Direção IN/OT/OW, dedup por `reference_no`, **não atribui motorista**, lê os
  dois valores do Excel.
- **Staging via SESSÃO, não ficheiros**: o `preview` lê o upload direto do
  `tmp_name` e guarda os candidatos em `$_SESSION['import_preview'][token]`; o
  `commit` lê-os da sessão e chama `persist()`. Evita dependências de pastas
  graváveis (o `storage/` pode pertencer a outro user que não o do Apache).
- `app/Repositories/ImportBatchRepository.php` — lotes + `undo()`.
- `app/Http/Controllers/Admin/ImportController.php` — preview/commit/undo.
- Vista `resources/views/admin/import/index.php`; entrypoints
  `public/admin/import{,-preview,-commit,-undo}.php`; menu "Importar".
- Testes: `tests/Unit/XlsxReaderTest.php`, `tests/Unit/ExcelServiceImporterTest.php`
  (fixture `tests/Fixtures/services_sample.xlsx`).
- **UI:** Admin → Importar → arrastar .xlsx → Analisar → rever → Confirmar.
  Lotes recentes têm botão Desfazer.

## Dois valores (Fase 2)
- `Service` model: novos campos (`valorMotorista`, `payBasis`, `supplier`, …) +
  `margin()`, `isGrouped()`.
- `ServiceRepository::create/update` gravam `valor_motorista`/`pay_basis`.
- Formulário de ride (adicionar + editar) tem campo "Valor ao motorista".

## Agregar/desagregar shared (Fase 4)
- `ServiceRepository::aggregate(array $ids)` — atribui um `grouping_ref` comum
  a 2+ serviços (reutiliza um existente se houver) e marca-os shared.
- `ServiceRepository::disaggregate(int $id)` — limpa o `grouping_ref` (voo
  atrasado → serviço fica independente).
- `RidesController::aggregate()`/`disaggregate()` → `public/admin/ride-aggregate.php`,
  `ride-disaggregate.php`.
- UI na lista de rides: badge "Grupo" na coluna Tipo; botão **Agregar** na toolbar
  (aparece com ≥2 selecionados); botão **Desagregar** (tesoura) por linha agrupada.

## Preçário (Fase 3) — `scripts/import_pricing.php`
- `app/Repositories/PricingRepository.php` — `findRate()` prefere o
  distributor/escalão específico ao wildcard (NULL).
- `app/Services/PricingEngine.php` — `driverPayout()` (por `pay_basis`),
  `serviceRevenue()` (MTS, fallback), `canonicalVehicle()`.
- Atribuição calcula o `valor_motorista`:
  `RidesController::assignDriver` (seletor "Base de pagamento" no modal) e
  `ScheduleBoardController::update` (usa `default_pay_basis` do motorista).
- `UserRepository::defaultPayBasis`, `ServiceRepository::setDriverPricing`.
- Testes: `tests/Unit/PricingEngineTest.php`.
- **Recarregar tarifas** após editar o Excel de preçário: correr de novo
  `php scripts/import_pricing.php`.
- Falta (opcional): UI admin para ver/editar tarifas sem reimportar.

---

## Gestão financeira (Fase 5)
- `app/Repositories/FinancialReportRepository.php` — `report(from,to,supplier?,driver?)`
  devolve linhas detalhadas + totais (receita/custo/margem) + sub-totais por
  fornecedor e por motorista; `suppliers()` para o filtro. Scoped à empresa.
- `FinancialController` reescrito: filtros (intervalo + fornecedor + motorista),
  cartões reais (deixa de usar a estimativa fixa de 15€/serviço), tabela
  detalhada, sub-totais, e **export CSV** (`export()` →
  `public/admin/financial-export.php`). Módulo de despesas mantido; líquido =
  margem − despesas.
- Testes: `tests/Unit/FinancialReportRepositoryTest.php`.

## Notas de operação
- BD local: `SR_ATUAL`, `root` sem password, `DB_HOST=127.0.0.1` (NÃO `localhost`).
- Sem composer/phpunit em `vendor/` — usar `phpunit.phar` (ver TL;DR). PHP CLI 8.x.
- Ficheiros-fonte do cliente na raiz do repo: `pedidos`,
  `Excel Variáveis Serviços para SyncRide.xlsx`,
  `TABELA PREÇÁRIO para SyncRide.xlsx`.
- Nada foi commitado/pushado ainda (a pedido do Tiago).

---

## 2026-06-21 — 3 novas features

### UI de tarifas
`PricingController` + view `admin/pricing/index.php` + endpoints `pricing.php/save/delete`. Tabs por cartão, edição inline contenteditable, modal para nova tarifa. Nav entry "Preçário".

### Sigla de condutor → import automático
`Users.driver_code` editável na ficha (modal users). `UserRepository::findByDriverCode()`. `ExcelServiceImporter` lê coluna `Driver` → resolve sigla → insere `Services_Rides` automaticamente. `User` model com `driverCode`/`defaultPayBasis`. `UsersController::update()` persiste ambos.

### Viagens multi-paragem (reimplementação aggregate/disaggregate)
Schema: `Services.aggregated_into`, `Services.is_aggregate_master`, tabela `ServiceStops`.
- `aggregate(ids)` → mestre + filhos + stops ordenadas.
- `disaggregate(masterId)` → liberta filhos, apaga stops (dados originais intactos).
- `getStops()` / `reorderStops()` — consulta + reordenação.
- Listagem filtra filhos (`AND s.aggregated_into IS NULL`). DT inclui `is_aggregate_master` + `stop_count`.
- Modal "Paragens" com Sortable.js drag-drop + "Salvar ordem" + "Desagregar tudo".
- 34/34 testes verdes.

**Migração pendente** (MySQL estava off): `php scripts/migrate_prtours.php`
