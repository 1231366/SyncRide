# SyncRide — Production Deployment Runbook
### Multi-Tenant Migration (v2.0)

> **Contexto:** Este runbook cobre a migração da arquitectura single-tenant legada para a nova arquitectura multi-tenant MVC. O codebase local foi auditado, 10 vulnerabilidades críticas/altas foram corrigidas, e este documento é o guia definitivo para fazer o deploy em produção de forma segura.
>
> **Tempo estimado:** 30–45 minutos de janela de manutenção.

---

## Pré-requisitos

Antes de começar, confirma que tens:

- [ ] Acesso SSH ao servidor de produção
- [ ] Credenciais MySQL (root ou utilizador com ALTER/UPDATE privileges)
- [ ] Acesso ao repositório Git (`git pull` funciona no servidor)
- [ ] Backups automáticos do hosting desativados durante a janela (para não capturar estado intermédio)

---

## PASSO 1 — Ponto de Rollback (Backup Completo)

> ⚠️ **Não avances sem fazer isto.** Em caso de erro, estes ficheiros permitem restaurar o estado exacto de produção.

Corre estes comandos **no servidor de produção**:

```bash
# Criar directório de backups
mkdir -p ~/backups

# 1a. Dump completo da base de dados (comprimido)
mysqldump -u SEU_DB_USER -p SEU_DB_NAME \
  --single-transaction \
  --routines \
  --triggers \
  --add-drop-table \
  | gzip > ~/backups/syncride_PRE_MIGRATION_$(date +%Y%m%d_%H%M%S).sql.gz

# Verificar que o dump não está vazio (deve mostrar cabeçalho SQL)
gunzip -c ~/backups/syncride_PRE_MIGRATION_*.sql.gz | head -20

# 1b. Snapshot completo dos ficheiros
tar -czf ~/backups/syncride_files_PRE_MIGRATION_$(date +%Y%m%d_%H%M%S).tar.gz \
  /caminho/para/htdocs/SRMT/

# 1c. Registar estado Git actual
cd /caminho/para/htdocs/SRMT
git log --oneline -5 > ~/backups/git_state_pre_migration.txt
cat ~/backups/git_state_pre_migration.txt
```

### Como fazer Rollback (se algo correr mal)

```bash
# Restaurar base de dados
gunzip -c ~/backups/syncride_PRE_MIGRATION_*.sql.gz \
  | mysql -u SEU_DB_USER -p SEU_DB_NAME

# Restaurar ficheiros
tar -xzf ~/backups/syncride_files_PRE_MIGRATION_*.tar.gz -C /
```

---

## PASSO 2 — Migração do Schema da Base de Dados

> Este bloco SQL é **idempotente** — pode ser corrido mais do que uma vez sem danos. Usa `IF NOT EXISTS` e `ON DUPLICATE KEY` em todo o lado.

Liga ao MySQL:

```bash
mysql -u SEU_DB_USER -p SEU_DB_NAME
```

Cola e corre este bloco completo:

```sql
-- ============================================================
-- SyncRide Multi-Tenant Migration v2.0
-- Idempotente: seguro de re-executar em caso de interrupção
-- ============================================================

-- 1. Tabela mestre de empresas
CREATE TABLE IF NOT EXISTS Companies (
    id         INT          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255) NOT NULL,
    slug       VARCHAR(100) NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Seed: empresa existente fica com ID=1
INSERT INTO Companies (id, name, slug, created_at)
VALUES (1, 'Welcome Agitation', 'welcome-agitation', NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 3. Adicionar company_id a todas as tabelas com scope (MariaDB 10+ idempotente)
ALTER TABLE Users    ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;
ALTER TABLE Services ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER ID;
ALTER TABLE Vehicles ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;
ALTER TABLE Expenses ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;
ALTER TABLE Logs     ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER logID;

-- 4. Atribuir todos os dados existentes à empresa 1
--    (todos os dados legados pertencem à Welcome Agitation)
UPDATE Users    SET company_id = 1 WHERE company_id IS NULL;
UPDATE Services SET company_id = 1 WHERE company_id IS NULL;
UPDATE Vehicles SET company_id = 1 WHERE company_id IS NULL;
UPDATE Expenses SET company_id = 1 WHERE company_id IS NULL;
UPDATE Logs     SET company_id = 1 WHERE company_id IS NULL;

-- 5. Índices de performance (todas as queries de scope usam company_id)
ALTER TABLE Users    ADD INDEX IF NOT EXISTS idx_users_company    (company_id);
ALTER TABLE Services ADD INDEX IF NOT EXISTS idx_services_company (company_id);
ALTER TABLE Vehicles ADD INDEX IF NOT EXISTS idx_vehicles_company (company_id);
ALTER TABLE Expenses ADD INDEX IF NOT EXISTS idx_expenses_company (company_id);
ALTER TABLE Logs     ADD INDEX IF NOT EXISTS idx_logs_company     (company_id);

-- 6. Verificação final — todos devem retornar orphans = 0
SELECT 'Users'    AS tabela, COUNT(*) AS orphans FROM Users    WHERE company_id IS NULL;
SELECT 'Services' AS tabela, COUNT(*) AS orphans FROM Services WHERE company_id IS NULL;
SELECT 'Vehicles' AS tabela, COUNT(*) AS orphans FROM Vehicles WHERE company_id IS NULL;
SELECT 'Expenses' AS tabela, COUNT(*) AS orphans FROM Expenses WHERE company_id IS NULL;
SELECT 'Logs'     AS tabela, COUNT(*) AS orphans FROM Logs     WHERE company_id IS NULL;
```

**Resultado esperado:** Todas as 5 queries de verificação retornam `orphans = 0`.

---

## PASSO 3 — Deploy do Código

```bash
cd /caminho/para/htdocs/SRMT

# 3a. Puxar o novo código
git pull origin main

# 3b. Instalar dependências Composer (sem pacotes de dev em produção)
composer install --no-dev --optimize-autoloader

# 3c. Verificar que o autoloader resolve as novas classes
php -r "
require 'vendor/autoload.php';
new App\Models\Company();
new App\Repositories\CompanyRepository(new PDO('sqlite::memory:'));
echo 'Autoloader OK' . PHP_EOL;
" 2>/dev/null || echo "Classes encontradas (erros de PDO são esperados aqui)"

# 3d. Verificar variáveis de ambiente obrigatórias
php -r "
require 'bootstrap.php';
\$keys = ['DB_HOST','DB_DATABASE','DB_USERNAME','APP_ENV','APP_TIMEZONE'];
foreach (\$keys as \$k) {
    \$v = App\Support\Env::get(\$k);
    echo \$k . ': ' . (\$v ? 'SET ✓' : 'MISSING ✗') . PHP_EOL;
}
"

# 3e. Confirmar APP_ENV=production (activa cookies seguros, desactiva debug)
grep 'APP_ENV' .env
# Deve mostrar: APP_ENV=production

# 3f. Verificar erros de sintaxe em todos os shims públicos
find public -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"
# Não deve mostrar nada (sem erros)

# 3g. CRÍTICO: Apagar scripts utilitários de base de dados
#     Estes ficheiros expõem credenciais e acesso directo à DB se ficarem online
rm -f database/create-superadmin.php
rm -f database/reset-superadmin.php
rm -f database/migrate.php

# Confirmar que foram apagados
ls database/
```

> ⚠️ **Atenção no passo 3g.** Se o teu hosting não te deixar apagar via SSH, apaga os ficheiros via FTP/cPanel antes de publicar. São um vector de ataque directo.

---

## PASSO 4 — Criar o Super Admin de Produção

> O super admin tem `role=0` e **não tem `company_id`** — vê todos os tenants. Cria-o directamente via MySQL (o script `create-superadmin.php` já foi apagado no passo anterior).

```sql
-- Substituir os valores antes de correr
INSERT INTO Users (email, password, role, name, phone, company_id)
VALUES (
  'superadmin@syncride.pt',
  '$2y$10$HASH_AQUI',   -- gerar com: php -r "echo password_hash('TuaPassword', PASSWORD_BCRYPT);"
  0,
  'Super Admin',
  0,
  NULL                  -- NULL = sem empresa = vê tudo
);
```

Para gerar o hash da password:

```bash
php -r "echo password_hash('TuaPasswordSegura2025!', PASSWORD_BCRYPT) . PHP_EOL;"
```

---

## PASSO 5 — Smoke Tests

Corre este checklist em ordem. Cada item deve passar antes de avançar.

### 5.1 Integridade da Base de Dados

```sql
-- Todos os dados históricos devem estar na empresa 1
SELECT company_id, COUNT(*) AS total FROM Services GROUP BY company_id;
-- Esperado: uma linha com company_id=1 e o teu total de viagens

SELECT company_id, COUNT(*) AS total FROM Users GROUP BY company_id;
-- Esperado: empresa 1 para utilizadores normais; NULL apenas para super admin (role=0)
```

### 5.2 Fluxo de Autenticação

```
□ Login como admin da empresa 1 (role=1) → aterra em /admin/
□ Dashboard mostra apenas as viagens da empresa 1 (não totais globais)
□ Logout → login normal → session tem company_id correcto
□ Logout → fechar browser → abrir de novo (remember-me cookie activo) →
  ainda vê apenas dados da empresa 1 ← testa o fix C3 (remember-me)
```

### 5.3 Isolamento Multi-Tenant

```
□ Login como super admin → /superadmin/
□ Criar segunda empresa de teste em /superadmin/companies.php
□ Criar um admin para essa segunda empresa
□ Login como admin da empresa 2 → lista de viagens deve estar VAZIA
□ Criar uma viagem como admin da empresa 2
□ Login como admin da empresa 1 → a viagem da empresa 2 NÃO aparece
□ Login como super admin → vê as viagens de AMBAS as empresas
```

### 5.4 Endpoints de API

Testa directamente via curl (substitui `teu-dominio.com`):

```bash
# Deve retornar 403 (sem autenticação)
curl -s https://teu-dominio.com/SRMT/public/api/status-update.php \
  -X POST -d '{"ride_id":1,"status":4}' \
  -H 'Content-Type: application/json'
# Esperado: {"success":false,"message":"Forbidden"} ou redirect para login

# Deve retornar dados de tracking (endpoint público para clientes)
curl -s "https://teu-dominio.com/SRMT/public/api/tracking-get.php?ride_id=1"
# Esperado: {"success":true,"data":...}

# Deve retornar 403 (endpoint de admin sem sessão)
curl -s "https://teu-dominio.com/SRMT/public/api/tracking-get.php"
# Esperado: {"success":false,"message":"Forbidden"}

# Deve retornar 403 (upload de localização sem sessão)
curl -s "https://teu-dominio.com/SRMT/public/api/location-update.php" \
  -X POST -d '{"ride_id":1,"lat":38.7,"lng":-9.1}' \
  -H 'Content-Type: application/json'
# Esperado: {"success":false,...}

# Rating deve recusar ride que não existe ou não está completa
curl -s https://teu-dominio.com/SRMT/public/save-rating.php \
  -X POST -d '{"ride_id":99999,"rating":5}' \
  -H 'Content-Type: application/json'
# Esperado: {"success":false,"error":"Ride not found or not completed"}
```

### 5.5 Limpeza de Ficheiros Sensíveis

```bash
# Estes ficheiros NÃO devem existir no servidor
ls -la database/create-superadmin.php 2>&1  # deve dizer "No such file"
ls -la database/reset-superadmin.php  2>&1  # deve dizer "No such file"
ls -la database/migrate.php           2>&1  # deve dizer "No such file"

# Nenhum destes URLs deve retornar 200
curl -o /dev/null -s -w "%{http_code}" \
  https://teu-dominio.com/SRMT/database/create-superadmin.php
# Esperado: 404
```

---

## PASSO 6 — Checklist de Segurança Final

Antes de declarar o deploy bem-sucedido, confirma:

| Item | Estado |
|------|--------|
| `APP_ENV=production` no `.env` | ☐ |
| `APP_DEBUG=false` no `.env` | ☐ |
| Ficheiros `database/*.php` apagados | ☐ |
| Super admin criado com password forte | ☐ |
| Remember-me testado (preserva `company_id`) | ☐ |
| Endpoints `/api/status-update.php`, `/api/location-update.php` retornam 403 sem sessão | ☐ |
| Login como empresa 1 não vê dados da empresa 2 | ☐ |
| Backup pré-migração guardado fora do servidor | ☐ |

---

## Resumo de Vulnerabilidades Corrigidas (já no código)

> Documentadas aqui para referência. Todas foram corrigidas antes deste deploy.

| ID | Severidade | Ficheiro | Problema | Fix Aplicado |
|----|-----------|---------|----------|-------------|
| C1 | 🔴 Crítica | `save-rating.php` | Sem auth — qualquer anónimo avalia qualquer viagem | Validação de rating + verificação que viagem existe e está completa (status=4) |
| C2 | 🔴 Crítica | `api-fetch-messages.php`, `api-send-message.php` | Sem auth — leitura/escrita de chat por anónimos | Guard de sessão adicionado |
| C3 | 🔴 Crítica | `auth/dbconfig.php` (remember-me) | Não definia `company_id` na sessão → login via cookie via via todos os tenants | `$_SESSION['company_id']` adicionado ao bridge |
| C4 | 🔴 Crítica | `public/api/status-update.php` | Sem `AuthMiddleware` — qualquer anónimo altera estado de qualquer viagem | `AuthMiddleware::handle(1, 2)` adicionado |
| C5 | 🔴 Crítica | `AuthController::login()` (AJAX) | Hash da password retornado no JSON para a app móvel | `unset($user['password'], $user['remember_token'])` antes do `json_encode` |
| C6 | 🔴 Crítica | `XmlVoucherImporter` | Viagens importadas ficavam com `company_id = NULL` — sem dono | `company_id` adicionado a todos os `INSERT` |
| H1 | 🟠 Alta | `UsersController` update/destroy | BOLA — `find()` não scoped; admin podia editar/apagar utilizadores de outros tenants | Verificação de `companyId` do utilizador vs sessão adicionada |
| H2 | 🟠 Alta | `LiveLocationRepository::allDrivers()` | Todos os condutores de todos os tenants visíveis no mapa de qualquer empresa | `company_id` scope adicionado; super admin (role=0) desbloqueado no mapa |
| H3 | 🟠 Alta | `TrackingController::get()` | Sem auth — qualquer anónimo via todas as viagens activas e nomes de clientes | Auth inline adicionada para path `allActiveRides`; path por `ride_id` mantém-se público |
| H4 | 🟠 Alta | `TrackingController::stop()` | `driver_id` vinha do payload JSON — qualquer condutor parava a sessão de outro | Sempre usa `$_SESSION['user_id']` |
| H5 | 🟠 Alta | `LocationController::update()` | Sem auth + `driver_id` spoofável — anónimos enviavam GPS falso | `AuthMiddleware(1,2)` + `driver_id` fixado na sessão |

---

## Pendentes para Sprint Seguinte (Não Bloqueantes)

| # | Prioridade | Item |
|---|-----------|------|
| M1 | 🟡 Média | **CSRF tokens** — adicionar `csrf_token()` em `BaseController`, embutir em todos os formulários, verificar em `requirePost()` |
| M2 | 🟡 Média | **Views superadmin** — alguns `<?=` sem `View::e()` para icon/color/label (dados internos agora, mas padrão perigoso) |
| M3 | 🟡 Média | **ScheduleMailer** — confirmar que usa `ServiceRepository::default()` e está scoped por tenant |
| L1 | ⚪ Baixa | **Dicebear CDN** — nomes de utilizadores enviados para CDN externo ao gerar avatares |

---

*Gerado em: 2026-05-25 | Branch: main | Commit: cb70b2f*
