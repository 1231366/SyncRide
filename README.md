# SyncRide

> **A operação de transfers, automatizada.** As agendas chegam sozinhas ao WhatsApp dos condutores, os clientes seguem a viagem em tempo real e as faturas saem sem ninguém tocar numa folha de cálculo.

[![CI](https://github.com/1231366/SyncRide/actions/workflows/ci.yml/badge.svg)](https://github.com/1231366/SyncRide/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg?logo=php&logoColor=white)](https://www.php.net/)
[![PSR-4](https://img.shields.io/badge/Autoload-PSR--4-blue.svg)](https://www.php-fig.org/psr/psr-4/)
[![PHPUnit](https://img.shields.io/badge/Tested%20with-PHPUnit%2010-78c041.svg?logo=php)](https://phpunit.de/)
[![License](https://img.shields.io/badge/License-Proprietary-lightgrey.svg)]()

SyncRide é uma plataforma **em produção** que gere empresas de transfers de ponta a ponta — viagens, condutores, frota, parceiros e automações. Três experiências, um sistema: o **Gestor** no computador, a **app do Condutor** no telemóvel e o **Portal do Parceiro** para agências e hostels.

---

## 👔 Gestor — controla a operação toda

Painel com viagens de hoje/semana, desempenho e próximas viagens. A lista de viagens filtra por estado e data, atribui condutores e delega serviços a empresas parceiras.

![Gestor — painel e gestão de viagens](docs/media/admin.gif)

## 📅 Quadro de Agenda — arrastar e largar

Planeamento visual: arrasta viagens por atribuir para o dia e a hora, escolhe o condutor, e alterna entre vista semanal, diária e mensal. Cada condutor tem a sua cor.

![Quadro de agenda com drag-and-drop](docs/media/schedule.gif)

## 🚗 Condutor — a agenda no bolso

App pensada para o telemóvel: o condutor vê as viagens do dia, inicia a viagem, e regista no-shows com foto e GPS. Quem trabalha para várias empresas vê a empresa de cada viagem.

<p align="center"><img src="docs/media/driver.gif" alt="App do condutor" width="280"></p>

## 🏨 Parceiro — pede e acompanha serviços

Agências e hostels pedem transfers e acompanham o estado de cada viagem e os no-shows — sem terem de telefonar.

![Portal do parceiro](public/manual/img/partner-dashboard.png)

---

## ✨ O que resolve

- **Despacho automático** — as agendas seguem para o WhatsApp dos condutores todos os dias, sozinhas
- **Colaboração entre empresas** — delega viagens a parceiros quando tens a mais, com registo de quem é cada uma para acertar contas
- **Condutores partilhados** — o mesmo condutor trabalha para várias empresas, com a agenda separada por empresa
- **Prova de no-show** — foto + GPS guardados e enviados por email automaticamente
- **Faturação e relatórios** — gerados e enviados no fim de cada serviço
- **Onboarding por link** — cria uma vaga, envia o link, a pessoa preenche os próprios dados

📖 **[Manual completo com todos os ecrãs explicados →](public/manual/)**

---

## 🛠️ Para quem quer espreitar o código

<details>
<summary>Stack & arquitetura</summary>

- **PHP 8.2**, sem framework — arquitetura MVC própria (Controllers finos → Repositories → Services → DTOs)
- **PSR-4** autoload, **PDO** com prepared statements em todo o lado
- **Multi-tenant** — cada empresa só vê os seus dados; isolamento via `company_id` em cada query
- **Segurança** — tokens CSRF, ownership guards nas mutações, `bcrypt`, `session_regenerate_id`, escape de output
- **Testes** PHPUnit + **CI** (GitHub Actions: lint + testes em PHP 8.2 e 8.3)
- **`public/`** é a única pasta exposta; tudo o resto fica fora do document root

```
app/          Controllers, Repositories, Services, Models, Support
cron/         Jobs agendados (relatório diário, agenda WhatsApp)
public/       Entry points + assets (a única pasta web-acessível)
resources/    Vistas (PHP templates) + traduções PT/EN
tests/        PHPUnit (Unit)
```

**Correr localmente:**
```bash
composer install
cp .env.example .env   # configurar DB + mail
php -S localhost:8000 -t public
composer test
```

</details>

---

**Autor:** Tiago Silva · [tiagofsilva04@gmail.com](mailto:tiagofsilva04@gmail.com)
