#!/usr/bin/env bash
# =============================================================================
# SyncRide — Production Deploy Script
# Usage: bash scripts/deploy.sh
#
# What it does (in order):
#   1. Read DB credentials from .env
#   2. Backup DB (mysqldump, gzip)
#   3. Backup files (tar.gz)
#   4. git pull origin main
#   5. composer install --no-dev --optimize-autoloader
#   6. Run PHP migration (database/migrate.php)
#   7. Print next steps
#
# Run from the project root: bash scripts/deploy.sh
# =============================================================================

set -euo pipefail

# ── Colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

ok()   { echo -e "${GREEN}✓${RESET} $*"; }
fail() { echo -e "${RED}✗${RESET} $*"; }
info() { echo -e "${CYAN}→${RESET} $*"; }
warn() { echo -e "${YELLOW}⚠${RESET}  $*"; }
step() { echo -e "\n${BOLD}── $* ──────────────────────────────────────────${RESET}"; }

# ── Resolve project root ──────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${PROJECT_ROOT}"

echo -e "\n${BOLD}SyncRide — Production Deploy${RESET}"
echo "Project: ${PROJECT_ROOT}"
echo "Date:    $(date '+%Y-%m-%d %H:%M:%S')"

# ── 1. Read .env ──────────────────────────────────────────────────────────────
step "1. Lendo credenciais do .env"

if [[ ! -f ".env" ]]; then
    fail ".env não encontrado em ${PROJECT_ROOT}"
    exit 1
fi

# Parse .env — ignores blank lines and comments
parse_env() {
    local key="$1"
    grep -E "^${key}=" .env | head -1 | cut -d'=' -f2- | tr -d '"' | tr -d "'"
}

DB_HOST="$(parse_env DB_HOST)"
DB_PORT="$(parse_env DB_PORT)"
DB_NAME="$(parse_env DB_DATABASE)"
DB_USER="$(parse_env DB_USERNAME)"
DB_PASS="$(parse_env DB_PASSWORD)"
APP_URL="$(parse_env APP_URL)"
APP_ENV="$(parse_env APP_ENV)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

if [[ -z "${DB_NAME}" || -z "${DB_USER}" ]]; then
    fail "DB_DATABASE ou DB_USERNAME estão vazios no .env"
    exit 1
fi

ok "DB: ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"
ok "APP_ENV: ${APP_ENV}"

if [[ "${APP_ENV}" != "production" ]]; then
    warn "APP_ENV não é 'production'. Muda o .env antes de ir para produção!"
fi

# ── 2. Backup da base de dados ────────────────────────────────────────────────
step "2. Backup da base de dados"

BACKUP_DIR="${PROJECT_ROOT}/../backups"
mkdir -p "${BACKUP_DIR}"

TIMESTAMP="$(date '+%Y%m%d_%H%M%S')"
DB_DUMP="${BACKUP_DIR}/syncride_PRE_DEPLOY_${TIMESTAMP}.sql.gz"

info "A fazer dump para ${DB_DUMP} ..."

# Build mysqldump args — avoid password in process list
MYSQL_PASS_ARG=""
if [[ -n "${DB_PASS}" ]]; then
    MYSQL_PASS_ARG="-p${DB_PASS}"
fi

if mysqldump \
    -h "${DB_HOST}" \
    -P "${DB_PORT}" \
    -u "${DB_USER}" \
    ${MYSQL_PASS_ARG} \
    --single-transaction \
    --routines \
    --triggers \
    --add-drop-table \
    "${DB_NAME}" 2>/dev/null \
    | gzip > "${DB_DUMP}"; then
    DUMP_SIZE=$(du -sh "${DB_DUMP}" 2>/dev/null | cut -f1)
    ok "DB backup: ${DB_DUMP} (${DUMP_SIZE})"
else
    fail "mysqldump falhou. Verifica as credenciais no .env"
    exit 1
fi

# ── 3. Backup dos ficheiros ───────────────────────────────────────────────────
step "3. Backup dos ficheiros"

FILES_DUMP="${BACKUP_DIR}/syncride_files_PRE_DEPLOY_${TIMESTAMP}.tar.gz"
info "A comprimir ${PROJECT_ROOT} ..."

# Exclude vendor, node_modules, and the backup dir itself
tar -czf "${FILES_DUMP}" \
    --exclude="./vendor" \
    --exclude="./node_modules" \
    --exclude="./.git" \
    -C "$(dirname "${PROJECT_ROOT}")" \
    "$(basename "${PROJECT_ROOT}")" 2>/dev/null || true

FILES_SIZE=$(du -sh "${FILES_DUMP}" 2>/dev/null | cut -f1)
ok "Ficheiros backup: ${FILES_DUMP} (${FILES_SIZE})"

# ── 4. git pull ───────────────────────────────────────────────────────────────
step "4. git pull origin main"

if git pull origin main; then
    COMMIT="$(git log --oneline -1)"
    ok "HEAD: ${COMMIT}"
else
    fail "git pull falhou"
    exit 1
fi

# ── 5. Composer ───────────────────────────────────────────────────────────────
step "5. composer install --no-dev --optimize-autoloader"

if command -v composer &>/dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
    ok "Composer OK"
else
    warn "composer não encontrado no PATH — a saltar"
fi

# ── 6. Migração da base de dados ─────────────────────────────────────────────
step "6. Migração da base de dados"

if [[ ! -f "database/migrate.php" ]]; then
    warn "database/migrate.php não existe — a saltar migração"
else
    info "A correr database/migrate.php ..."
    if php database/migrate.php; then
        ok "Migração concluída com sucesso"
    else
        fail "Migração falhou — verifica os erros acima"
        echo ""
        warn "O backup pré-deploy está em:"
        warn "  DB:       ${DB_DUMP}"
        warn "  Ficheiros: ${FILES_DUMP}"
        echo ""
        warn "Para fazer rollback:"
        echo "  gunzip -c ${DB_DUMP} | mysql -u ${DB_USER} ${DB_NAME}"
        exit 1
    fi
fi

# ── 7. Resumo e próximos passos ───────────────────────────────────────────────
APP_BASE="${APP_URL:-http://your-server/SRMT}"

echo ""
echo -e "${GREEN}${BOLD}════════════════════════════════════════${RESET}"
echo -e "${GREEN}${BOLD}  ✅  Deploy concluído com sucesso!     ${RESET}"
echo -e "${GREEN}${BOLD}════════════════════════════════════════${RESET}"
echo ""
echo -e "${BOLD}Backups guardados em:${RESET}"
echo "  DB:        ${DB_DUMP}"
echo "  Ficheiros: ${FILES_DUMP}"
echo ""
echo -e "${BOLD}Próximos passos manuais:${RESET}"
echo ""
echo -e "  ${CYAN}1.${RESET} Criar o super admin de produção:"
echo -e "     ${APP_BASE}/database/create-superadmin.php"
echo ""
echo -e "  ${CYAN}2.${RESET} Verificar integridade com o smoke test:"
echo -e "     ${APP_BASE}/scripts/smoke-test.php"
echo ""
echo -e "  ${CYAN}3.${RESET} Apagar scripts utilitários (vectores de ataque!):"
echo "     rm -f database/create-superadmin.php"
echo "     rm -f database/reset-superadmin.php"
echo "     rm -f database/migrate.php"
echo ""
echo -e "  ${CYAN}4.${RESET} Confirmar no .env:"
echo "     APP_ENV=production"
echo "     APP_DEBUG=false"
echo ""
