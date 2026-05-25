# SyncRide — Deploy para cPanel (syncride.wmservers.pt)

> Guia passo a passo via File Manager + phpMyAdmin. Sem SSH necessário.
> Tempo estimado: 20–30 minutos.

---

## Visão Geral

O objectivo é chegar a esta estrutura no servidor:

```
/home/SEU_USERNAME/
├── syncride/               ← TODOS os ficheiros do projecto ficam aqui
│   ├── app/
│   ├── bootstrap.php
│   ├── .env                ← crias este ficheiro no servidor
│   ├── vendor/             ← incluis no ZIP (não está no Git)
│   ├── public/             ← aponta o domínio para ESTA pasta
│   │   ├── index.php
│   │   ├── admin/
│   │   ├── api/
│   │   └── ...
│   └── ...
└── public_html/            ← NÃO mexes aqui
```

O `public/` funciona como document root — os ficheiros sensíveis (.env,
app/, vendor/, database/) ficam fora do alcance web.

---

## PASSO 1 — Preparar o ZIP localmente

No teu Mac, abre o Terminal e corre:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs

# Instalar dependências de produção (sem pacotes de dev)
cd SRMT && composer install --no-dev --optimize-autoloader && cd ..

# Criar o ZIP (inclui vendor, exclui .env e ficheiros desnecessários)
zip -r syncride_deploy.zip SRMT \
  --exclude "SRMT/.git/*" \
  --exclude "SRMT/.env" \
  --exclude "SRMT/storage/logs/*" \
  --exclude "SRMT/storage/cache/*" \
  --exclude "SRMT/storage/sessions/*" \
  --exclude "SRMT/public/uploads/*" \
  --exclude "SRMT/*.zip" \
  --exclude "SRMT/node_modules/*"

echo "ZIP criado: $(du -sh syncride_deploy.zip | cut -f1)"
```

O ficheiro `syncride_deploy.zip` fica em `/Applications/XAMPP/xamppfiles/htdocs/`.

---

## PASSO 2 — Criar a base de dados no cPanel

1. Entra no cPanel → **MySQL Databases**
2. Em "Create New Database", escreve o nome (ex: `syncride_prod`) → **Create Database**
3. Em "MySQL Users", cria um utilizador (ex: `syncride_user`) com password forte → **Create User**
4. Em "Add User To Database", selecciona o user e a DB → **Add** → marca **ALL PRIVILEGES** → **Make Changes**
5. Guarda: hostname, database name, username e password — precisas no Passo 5

---

## PASSO 3 — Alterar o document root do domínio

> Este passo é crucial. Sem ele o .env e o código ficam expostos na web.

1. cPanel → **Domains** (ou **Subdomains**)
2. Encontra `syncride.wmservers.pt`
3. Clica em **Manage** → **Document Root**
4. Muda para: `syncride/public`
   (o cPanel vai completar automaticamente para `/home/SEU_USERNAME/syncride/public`)
5. Guarda

Se não encontrares a opção "Document Root", faz assim:
1. Apaga o subdomínio actual
2. Cria de novo: Subdomain = `syncride`, Document Root = `syncride/public`

---

## PASSO 4 — Fazer upload dos ficheiros

1. cPanel → **File Manager**
2. Navega para a tua pasta home (normalmente abre aqui por omissão — deve ver `public_html/`, `mail/`, etc.)
3. Clica em **Upload** (no topo)
4. Faz upload de `syncride_deploy.zip` (o ficheiro criado no Passo 1)
5. Após o upload terminar, clica em **Go Back to...** para voltar ao File Manager
6. Clica no `syncride_deploy.zip` com o botão direito → **Extract**
7. Extrai para `/home/SEU_USERNAME/` (a pasta home, NÃO dentro de public_html)
8. Deves ver a pasta `SRMT/` criada na raiz
9. **Renomeia** a pasta `SRMT` para `syncride`:
   - Clica com o botão direito em `SRMT` → **Rename** → `syncride`

Verifica: navega para `syncride/public/` — deve ter `index.php`, `admin/`, `api/`, etc.

---

## PASSO 5 — Criar o ficheiro .env no servidor

1. No File Manager, navega para `syncride/`
2. Clica em **New File** → nome: `.env`
3. Clica com o botão direito no `.env` criado → **Edit**
4. Cola o conteúdo abaixo, substituindo os valores reais:

```env
APP_NAME=SyncRide
APP_ENV=production
APP_DEBUG=false
APP_URL=https://syncride.wmservers.pt
APP_TIMEZONE=Europe/Lisbon

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=SEU_USERNAME_syncride_prod
DB_USERNAME=SEU_USERNAME_syncride_user
DB_PASSWORD=A_TUA_PASSWORD_FORTE
DB_CHARSET=utf8mb4
DB_PERSISTENT=false

SESSION_NAME=syncride_session
SESSION_LIFETIME=86400
REMEMBER_ME_LIFETIME=2592000
APP_KEY=

CRON_ENABLED=false

MAIL_FROM_ADDRESS=noreply@syncride.wmservers.pt
MAIL_FROM_NAME=SyncRide
```

> **Nota:** No cPanel os nomes de DB e user têm normalmente o prefixo do teu username (ex: `joao_syncride_prod`). O cPanel mostra o nome completo quando criaste no Passo 2.

5. Guarda o ficheiro

---

## PASSO 6 — Importar o schema da base de dados

1. cPanel → **phpMyAdmin**
2. Selecciona a tua DB (`SEU_USERNAME_syncride_prod`) na lista à esquerda
3. Clica no separador **SQL**
4. Cola e corre este SQL completo:

```sql
-- SyncRide Multi-Tenant Migration v2.0
-- Idempotente: seguro de correr mais do que uma vez

CREATE TABLE IF NOT EXISTS Companies (
    id         INT          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255) NOT NULL,
    slug       VARCHAR(100) NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO Companies (id, name, slug, created_at)
VALUES (1, 'Welcome Agitation', 'welcome-agitation', NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

ALTER TABLE Users    ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;
ALTER TABLE Services ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER ID;
ALTER TABLE Vehicles ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;
ALTER TABLE Expenses ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;
ALTER TABLE Logs     ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER logID;

UPDATE Users    SET company_id = 1 WHERE company_id IS NULL;
UPDATE Services SET company_id = 1 WHERE company_id IS NULL;
UPDATE Vehicles SET company_id = 1 WHERE company_id IS NULL;
UPDATE Expenses SET company_id = 1 WHERE company_id IS NULL;
UPDATE Logs     SET company_id = 1 WHERE company_id IS NULL;

ALTER TABLE Users    ADD INDEX IF NOT EXISTS idx_users_company    (company_id);
ALTER TABLE Services ADD INDEX IF NOT EXISTS idx_services_company (company_id);
ALTER TABLE Vehicles ADD INDEX IF NOT EXISTS idx_vehicles_company (company_id);
ALTER TABLE Expenses ADD INDEX IF NOT EXISTS idx_expenses_company (company_id);
ALTER TABLE Logs     ADD INDEX IF NOT EXISTS idx_logs_company     (company_id);
```

5. Clica **Go** — todas as linhas devem mostrar verde

> Se a DB estava vazia (nova instalação), os `UPDATE` mostram "0 rows affected" — é normal.
> Se tinhas dados existentes, os `UPDATE` aplicam o `company_id = 1` a tudo.

---

## PASSO 7 — Criar o Super Admin

Abre no browser:

```
https://syncride.wmservers.pt/database/create-superadmin.php
```

- Preenche nome, email e password (mínimo 8 caracteres)
- Clica **Criar Super Admin**
- Guarda as credenciais num local seguro

---

## PASSO 8 — Smoke Test (verificação)

Abre no browser:

```
https://syncride.wmservers.pt/scripts/smoke-test.php
```

Todos os checks devem estar verdes **excepto** os 3 de "Security" (ficheiros ainda existem — apagamos a seguir).

Se alguma coisa aparecer vermelha:
- **DB connection failed** → confirma DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD no .env
- **Tabela X não encontrada** → corre o SQL do Passo 6 de novo
- **Super admin não existe** → repete o Passo 7

---

## PASSO 9 — Apagar ficheiros sensíveis

No File Manager, navega para `syncride/database/` e apaga:
- `migrate.php`
- `create-superadmin.php`
- `reset-superadmin.php` (se existir)

E apaga também: `syncride/scripts/deploy.sh`

> ⚠️ Estes ficheiros dão acesso directo à DB. Apagar é obrigatório.

---

## PASSO 10 — Confirmar que tudo funciona

```
□ https://syncride.wmservers.pt/          → página de login
□ Login com super admin                    → redireciona para /superadmin/
□ Login como admin da empresa              → redireciona para /admin/
□ https://syncride.wmservers.pt/.env       → deve dar 403 ou 404 (nunca o ficheiro)
□ https://syncride.wmservers.pt/app/       → deve dar 403
□ Corre o smoke test de novo               → todos os checks verdes
```

---

## Problemas Comuns

### "No input file specified" ao abrir o site
O document root não está a apontar para `syncride/public/`. Volta ao Passo 3.

### Erro de ligação à DB
- `DB_HOST` em cPanel é geralmente `localhost` (não `127.0.0.1`)
- Confirma que o user tem permissões na DB (Passo 2)
- Verifica o nome completo da DB (inclui prefixo do username)

### Pasta extraída chama-se "SRMT" não "syncride"
Normal — o ZIP vem do repositório com o nome SRMT. Renomeia no File Manager (Passo 4).

### PHP version error
O projecto requer PHP 8.1+. No cPanel → **MultiPHP Manager**, muda para PHP 8.2 ou 8.3.

### "Class not found" ou autoload errors
O `vendor/` não foi incluído no ZIP. Garante que o ZIP foi feito DEPOIS do `composer install`.

---

*Gerado em: 2026-05-25 | Versão: 1.0*
