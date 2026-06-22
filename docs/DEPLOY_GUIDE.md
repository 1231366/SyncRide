# Deploy Guide — feat/prtours-excel-pricing

Branch: `feat/prtours-excel-pricing` → `main`

---

## Ordem de operações

### 1. Backup da DB (antes de tudo)

```bash
mysqldump -u root -p SR_PROD > backup_pre_deploy_$(date +%Y%m%d).sql
```

### 2. Subir os ficheiros (ver lista abaixo)

### 3. Correr a migração

```bash
php scripts/migrate_prtours.php
```

Confirmar que termina com: `✅ Migração concluída sem erros.`

Cria/altera:
- `Services.aggregated_into` (INT NULL)
- `Services.is_aggregate_master` (TINYINT)
- `Users.driver_code` (VARCHAR 12)
- `Users.default_pay_basis` (ENUM)
- `UserInvites.driver_meta` (TEXT NULL)
- `ServiceStops` (nova tabela)
- `ImportBatches` (nova tabela)
- `PricingRates` (nova tabela)

### 4. Verificar em prod

- [ ] `/admin/rides` — tabela carrega, modal de stops abre, drag funciona
- [ ] `/admin/pricing` — preçário carrega, search bar filtra, edição inline guarda
- [ ] `/admin/import` — upload .xlsx, preview, commit, undo
- [ ] `/admin/financial` — filtros, tabela, export CSV
- [ ] `/admin/users` — criar condutor mostra sigla + base pag; invite por link idem
- [ ] `/admin/schedule-board` — abre em mobile sem scroll horizontal
- [ ] `/driver/` — viagens agregadas aparecem como uma só com múltiplas paragens

---

## Lista de ficheiros

### Backend — Controllers

```
app/Http/Controllers/Admin/RidesController.php
app/Http/Controllers/Admin/InvitesController.php
app/Http/Controllers/Admin/UsersController.php
app/Http/Controllers/Admin/ImportController.php          ← novo
app/Http/Controllers/Admin/PricingController.php         ← novo
app/Http/Controllers/Admin/FinancialController.php
app/Http/Controllers/Admin/ScheduleBoardController.php
app/Http/Controllers/Admin/SettingsController.php
app/Http/Controllers/SuperAdmin/CompaniesController.php
app/Http/Controllers/InviteController.php
```

### Backend — Repositories

```
app/Repositories/ServiceRepository.php
app/Repositories/UserRepository.php
app/Repositories/UserInviteRepository.php
app/Repositories/PricingRepository.php                   ← novo
app/Repositories/FinancialReportRepository.php           ← novo
app/Repositories/ImportBatchRepository.php               ← novo
app/Repositories/TenantSettingsRepository.php
```

### Backend — Models + Services + Support

```
app/Models/Service.php
app/Models/User.php
app/Services/ExcelServiceImporter.php                    ← novo
app/Services/PricingEngine.php                           ← novo
app/Support/XlsxReader.php                               ← novo
```

### Endpoints públicos

```
public/admin/ride-aggregate.php
public/admin/ride-disaggregate.php
public/admin/ride-stops.php
public/admin/ride-stops-reorder.php
public/admin/ride-stops-save.php                         ← novo
public/admin/import.php                                  ← novo
public/admin/import-preview.php                          ← novo
public/admin/import-commit.php                           ← novo
public/admin/import-undo.php                             ← novo
public/admin/pricing.php                                 ← novo
public/admin/pricing-save.php                            ← novo
public/admin/pricing-delete.php                          ← novo
public/admin/financial-export.php                        ← novo
```

### Views

```
resources/views/admin/rides/index.php
resources/views/admin/users/index.php
resources/views/admin/import/index.php                   ← novo
resources/views/admin/pricing/index.php                  ← novo
resources/views/admin/financial/index.php
resources/views/admin/schedule-board/index.php
resources/views/admin/settings/index.php
resources/views/driver/dashboard/index.php
resources/views/layouts/admin.php
resources/views/layouts/superadmin.php
resources/views/superadmin/companies/index.php
resources/views/superadmin/dashboard/index.php
```

### i18n + Scripts

```
resources/lang/pt.php
resources/lang/en.php
scripts/migrate_prtours.php
```

---

## Não subir para prod

```
docs/                          → documentação local
tests/                         → só CI
"Excel Variáveis Serviços…"    → ficheiro de trabalho local
"TABELA PREÇÁRIO…"             → idem
pedidos/                       → local
wpp-service/                   → verificar separadamente se aplicável
```

---

## Rollback de emergência

Se algo correr mal após migração:

```bash
# Restaurar DB
mysql -u root -p SR_PROD < backup_pre_deploy_YYYYMMDD.sql

# Reverter para commit anterior
git checkout main
git revert HEAD  # ou git reset conforme o caso
```

As colunas adicionadas são todas `NULL` ou têm default — não quebram dados existentes ao fazer rollback de código sem reverter a DB.
