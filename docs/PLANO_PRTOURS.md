# Plano de Personalização SyncRide → PRtours

> Estado: proposta técnica. Base: email de Ana & Gonçalo (19/05) + `Excel Variáveis
> Serviços para SyncRide.xlsx` + `TABELA PREÇÁRIO para SyncRide.xlsx`.
> Última atualização: 2026-06-15.

## Decisões fixadas (Tiago, 2026-06-15)
1. **Retrocompatibilidade total** — o sistema funciona com os dados atuais (sem campos extra) E
   com os novos do Excel. Tudo aditivo/nullable; nada do existente se reescreve.
2. **Import não atribui motorista** — serviços importados entram **sem motorista** (`Services_Rides`
   vazio). Atribuição é feita depois, no board/lista. ⇒ a Fase 1 ignora a coluna "Driver" e os
   códigos (FerD, PAs…) para já.
3. **Pick-Up Time = hora *esperada* de recolha** → `serviceStartTime`. Pode ser atrasada depois
   (delay), mas no import é a hora esperada.
4. **Preço pelos códigos do preçário** — cada ride traz Resort + Distributor Code + Vehicle +
   Fornecedor, que resolvem no preçário. Ver caveats na secção 3.1.

## 1. O que o cliente pediu (resumo do email `pedidos`)

| # | Pedido | Estado atual no SyncRide |
|---|--------|--------------------------|
| 1 | Importar um Excel com toda a informação dos serviços e gerá-los automaticamente | **A construir.** Existe `XmlVoucherImporter` (molde), falta o de Excel |
| 2 | Cada serviço com **dois valores**: preço do serviço **e** valor a pagar ao motorista | **Parcial.** `total_price` existe; falta `valor_motorista` |
| 3 | Serviços **agregados** quando são *shared* e vão no mesmo carro/carrinha | **A construir.** `serviceType` distingue shared/private mas não agrupa bookings |
| 4 | **Desagregar** serviços shared (voos atrasados) | **A construir.** Depende de #3 |
| 5 | **Gestão financeira**: extrair lista de serviços por intervalo, por fornecedor, por motorista e totais | **Parcial/fraco.** `FinancialController` usa estimativa fixa de 15€/serviço |
| 6 | **Drag-and-drop** para criar/alterar a escala e horários | **Praticamente feito.** Schedule board com FullCalendar (`eventDrop` + cards arrastáveis) |

**Conclusão:** o #6 já está; o #2 e #5 estão meio-caminho; #1, #3, #4 são novos. A espinha
dorsal (tabela `Services`, `Services_Rides`, repositório, tenancy por empresa) já suporta tudo
com poucas colunas novas.

---

## 2. Anatomia dos ficheiros do cliente

### 2.1 `Excel Variáveis Serviços` — folha `booking-item-list`
Lista diária de bookings (1 linha = 1 serviço/pax). Colunas relevantes e mapeamento:

| Coluna Excel | Campo `Services` | Notas |
|---|---|---|
| Start Date | `serviceDate` | data (serial Excel) |
| Fornecedor | `supplier` *(nova)* | MTS, Get-e, Dreamtravel, PRtours |
| Grouping Id | `grouping_ref` *(nova)* | mesmo valor em >1 linha ⇒ serviço **shared agregado** |
| Distributor Code | `distributor_code` *(nova)* | chave de preçário (LOVP, SUNTR, EXP…) |
| Reference No | `reference_no` *(nova)* | chave de deduplicação + voucher |
| Lead Pax | `NomeCliente` | prefixos "1/2-", "2/2-" indicam membros de um grupo shared |
| Adults / Children / Infants | `paxADT` / `paxCHD` / `paxBBY` | |
| Vehicle | `vehicle_label` *(nova)* + `serviceType` | "Shared" ⇒ type 0; resto ⇒ type 1 |
| Service Base Code | `leg_code` *(nova)* | **IN** (chegada: aeroporto→hotel), **OT** (saída: hotel→aeroporto), **OW** (one-way/tour) |
| Flight | `FlightNumber` | |
| Dep/Arr Airport + Time | (deriva pickup/dropoff e ETA) | usado para calcular a hora de recolha |
| Pick-Up Time (col Q, texto) | — | hora **calculada** a partir do voo |
| Pick-Up Time (col R, hora) | `serviceStartTime` | hora **real** agendada (a usar) — **confirmar com cliente** |
| Driver | (resolve para `Users`) | códigos: FerD, PAs, HC, Gv, THf, JV… → `Services_Rides` |
| Stay Hotel | `serviceStartPoint`/`serviceTargetPoint` | conforme `leg_code` |
| Resort | `resort` *(nova)* | chave de preçário (Lisbon, Cascais, Sintra…) |
| Notes | `import_notes` *(nova)* | texto livre (ex.: "NO SHOW MOTORISTA") |
| Valor Serviço | `total_price` | receita |
| Valor Motorista | `valor_motorista` *(nova)* | custo (pagamento ao motorista) |

Direção (a partir de `leg_code`):
- **IN**  → pickup = `Dep/Arr Airport` (aeroporto de chegada), dropoff = `Stay Hotel`
- **OT**  → pickup = `Stay Hotel`, dropoff = aeroporto
- **OW**  → tour/one-way; pickup = `Stay Hotel`, dropoff = `Notes`/destino

### 2.2 `TABELA PREÇÁRIO` — 5 folhas (o *rate card*)
- **PREÇARIO_MTS** — o que a MTS paga à PRtours. Dois blocos: *Shared* (Resort + Distributor
  Code → preço 1 pax) e *Private* (Resort + Distributor Code → Standard / Mini Van / Private
  Luxury Car / Private Luxury Minibus). Vigência "Até 31/12/2026".
- **PREÇARIO_PRTours** — tabela de venda a cliente final da PRtours (por Resort/destino × veículo).
- **PREÇARIO_MOTORISTAS PRtours** — quanto a PRtours paga ao motorista **com viatura PRtours**
  (Resort → Standard/Shared + "Hotel Extra" +2€).
- **PREÇARIO_MOTORISTAS Parceiros** — quanto paga ao **motorista parceiro (viatura própria)**;
  shared tem escala por pax (2/3/4 pax).
- **NOTAS_PREÇARIOS** — regras: (a) motorista parceiro + shared = valor do resort (até 2 pax) +
  extra por pax até 4 pax; (b) "Hotel Extra = True" ⇒ paga +2€ ao motorista.

**Papel do preçário:** fonte de verdade do sistema para o preço — cada ride traz os códigos
(Resort + Distributor Code + Vehicle + Fornecedor) que resolvem na tabela.

### 3.1 "Os valores são sempre associáveis pelo preçário?" — concordo, com 2 caveats
- ✅ **Preço do serviço (receita)** — SIM para MTS: Resort + Distributor Code + Vehicle resolvem
  em `PREÇARIO_MTS` (com fallback "all others" no distributor). Robusto.
- ⚠️ **Caveat 1 — fornecedores não-MTS** (Get-e, Dreamtravel, PRtours): não têm cartão tipo-MTS.
  Nas linhas de exemplo vêm com valor **já preenchido** no Excel (ex.: Get-e 22€, tour PRtours
  250€). Solução: cada fornecedor com o seu cartão em `PricingRates`, e **fallback ao valor
  explícito da coluna do Excel** quando não há cartão. Por isso o import lê sempre as colunas
  Valor Serviço/Motorista como fonte primária e usa o preçário para preencher em falta/validar.
- ⚠️ **Caveat 2 — valor do MOTORISTA depende do tipo de motorista**, não só da ride. Há **dois**
  cartões: viatura PRtours vs motorista parceiro (e no shared varia por nº de pax). Como decidimos
  **não atribuir motorista no import**, o valor-motorista pelo preçário só fica 100% determinado
  **na atribuição**. Estratégia: no import grava-se o `valor_motorista` da coluna do Excel se
  existir; o `PricingEngine` recalcula/confirma o custo do motorista no momento em que o motorista
  é atribuído (sabendo aí se é viatura própria ou parceiro). A receita fica fechada no import; o
  custo do motorista fecha na atribuição.

### 3.2 Tipo de motorista / base de pagamento (carro da empresa vs carro próprio)
Existem dois modos, que mapeiam exatamente os dois cartões do preçário:
- **`company_vehicle`** → motorista da empresa com viatura PRtours → `PREÇARIO_MOTORISTAS PRtours`
  (+2€ se "Hotel Extra").
- **`own_vehicle`** → motorista que leva o **próprio carro** (ocasional/parceiro) →
  `PREÇARIO_MOTORISTAS Parceiros` (no shared, escala por nº de pax: 2/3/4).

Modelação em dois níveis (porque "ocasionalmente" ⇒ varia por serviço):
- `Users.default_pay_basis` — o modo **habitual** do motorista (preenche por omissão).
- `Services.pay_basis` — o modo **deste serviço** concreto; ao atribuir o motorista, a UI
  pré-seleciona o default mas permite trocar (ex.: hoje levou o carro dele). É este valor que o
  `PricingEngine` usa para calcular o `valor_motorista`.

> ⚠️ **Não confundir** com o "partner" já existente (`role = 3` = agência parceira, para delegação
> de viagens). Isto é um eixo **diferente**: refere-se à viatura usada pelo motorista, não a uma
> empresa terceira. São conceitos independentes e coexistem.

---

## 3. Alterações de base de dados (Fase 0)

> Regra do projeto: **nomes de colunas/tabelas ficam como estão / em PT quando já existem**; novas
> colunas em snake_case PT/EN coerente com a tabela. Schema de produção não se reescreve — só se
> adicionam colunas/tabelas (aditivo, sem quebrar nada).

`ALTER TABLE Services` — adicionar (todas NULLABLE, default seguro):
```sql
supplier          VARCHAR(40)  NULL,
grouping_ref      VARCHAR(40)  NULL,        -- agregação shared
distributor_code  VARCHAR(20)  NULL,        -- chave preçário
resort            VARCHAR(60)  NULL,        -- chave preçário
vehicle_label     VARCHAR(40)  NULL,        -- rótulo original do Excel
leg_code          VARCHAR(4)   NULL,        -- IN / OT / OW
reference_no      VARCHAR(40)  NULL,        -- dedup + voucher
valor_motorista   DECIMAL(8,2) NULL,        -- pagamento ao motorista
hotel_extra       TINYINT(1)   NOT NULL DEFAULT 0,
import_notes      TEXT         NULL,
import_batch_id   INT          NULL,        -- liga ao lote de importação (undo/auditoria)
INDEX idx_services_grouping (grouping_ref),
INDEX idx_services_reference (reference_no),
INDEX idx_services_supplier (supplier);
```

`ALTER TABLE Users`:
- `driver_code VARCHAR(12) NULL` (mapeia "FerD", "PAs"… do Excel — uso futuro) + índice;
- `default_pay_basis ENUM('company_vehicle','own_vehicle') NOT NULL DEFAULT 'company_vehicle'` —
  modo **habitual** do motorista (carro da empresa vs carro próprio). Ver 3.2.

`ALTER TABLE Services` — somar à lista acima:
- `pay_basis ENUM('company_vehicle','own_vehicle') NULL` — base de pagamento **deste serviço**
  (pode divergir do default do motorista; "ocasionalmente leva o próprio carro"). Define qual
  preçário-motorista se aplica. `NULL` = ainda sem motorista atribuído.

Novas tabelas:
```sql
CREATE TABLE ImportBatches (              -- auditoria + undo de cada importação
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NULL,
  filename VARCHAR(255), source ENUM('excel','xml') DEFAULT 'excel',
  rows_total INT, rows_inserted INT, rows_skipped INT, rows_failed INT,
  created_by INT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE PricingRates (               -- rate card editável (substitui os Excel de preçário)
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NULL,
  card ENUM('mts','prtours_retail','driver_company_vehicle','driver_own_vehicle'),
  supplier VARCHAR(40) NULL,
  resort VARCHAR(60) NULL,
  distributor_code VARCHAR(20) NULL,
  vehicle_label VARCHAR(40) NULL,         -- Standard / Mini Van / Luxury Car / Minibus / Shared
  pax_tier TINYINT NULL,                  -- p/ shared parceiro (2/3/4)
  price DECIMAL(8,2) NULL,
  valid_until DATE NULL,
  UNIQUE KEY uq (company_id, card, supplier, resort, distributor_code, vehicle_label, pax_tier)
);
```

Entrega: um `.sql` versionado em `database/` + um script idempotente `scripts/migrate_prtours.php`
(segue o estilo de `scripts/`). Todas as colunas nullable ⇒ o sistema atual continua a funcionar
sem alterações enquanto se desenvolve.

---

## 4. Fases de implementação

Cada fase é autónoma, testável e entregável (filosofia "uma página de cada vez, com qualidade").

### Fase 1 — Importação do Excel de Serviços  *(o pedido central, #1)*
1. **`App\Support\XlsxReader`** — leitor XLSX leve (ZipArchive + SimpleXML sobre
   `xl/sharedStrings.xml` e `xl/worksheets/sheetN.xml`). Trata: shared strings, inline strings,
   **datas** (serial Excel → `Y-m-d`) e **horas** (fração de dia → `H:i:s`). Sem dependência
   pesada — coerente com o `XmlVoucherImporter` feito à mão. *(Alternativa documentada:
   `phpoffice/phpspreadsheet` via composer, caso se prefira robustez sobre leveza.)*
2. **`App\Services\ExcelServiceImporter`** — espelha o `XmlVoucherImporter`:
   - mapeia colunas (secção 2.1) com cabeçalho tolerante (procura por nome, não por índice fixo);
   - resolve direção/pickup/dropoff via `leg_code`;
   - **não atribui motorista** (decisão fixada #2) — ride entra sem `Services_Rides`. A coluna
     "Driver" é ignorada para já (no futuro pode mapear-se via `driver_code`);
   - **deduplicação** por `reference_no` (fallback: tupla data+hora+cliente+voo, como o XML);
   - grava `grouping_ref` (a Fase 4 usa-o para agregar);
   - valores: usa `Valor Serviço`/`Valor Motorista` da folha como fonte primária; se vazios, chama
     o `PricingEngine` (Fase 3). Custo do motorista pelo preçário só se finaliza na atribuição
     (ver 3.1, caveat 2);
   - regista tudo num `ImportBatches` (para undo).
3. **UI de importação (preview → confirmar)**: nova página admin `import-services.php`
   (controller `Admin\ImportController`). Upload → **pré-visualização** (tabela com o que vai ser
   criado, avisos de motoristas não encontrados / duplicados) → botão **Confirmar**. Botão
   **Desfazer último lote** (apaga por `import_batch_id`). Reutiliza o componente de upload já
   existente em `upload-xml.php`.
4. **Testes**: `tests/` com o próprio Excel do cliente como *fixture* (linhas conhecidas →
   asserts de mapeamento, datas, shared, valores).

### Fase 2 — Dois valores por serviço (#2)
- `Service` (model): adicionar `valorMotorista`, `supplier`, `grouping_ref`, etc. nos accessors.
- `ServiceRepository`: `create()`/`update()` passam a gravar `valor_motorista` e novas colunas.
- Formulário de ride (`resources/views/admin/rides/`): novo campo "Valor motorista" ao lado de
  "Valor serviço" (i18n `pt`/`en`).
- Vistas de detalhe/edição e a app do motorista mostram o valor do motorista onde fizer sentido.

### Fase 3 — Motor de preçário (`PricingEngine`)
- Importar as 4 folhas de preçário para `PricingRates` (script único `scripts/import_pricing.php`
  + UI de gestão em `admin/pricing.php` para editar sem mexer no Excel).
- `App\Services\PricingEngine::quote(supplier, resort, distributor_code, vehicle, pax, hotelExtra,
  driverIsPartner)` → devolve `[total_price, valor_motorista]` aplicando as regras de NOTAS
  (shared parceiro por tiers de pax; +2€ Hotel Extra).
- Usado pelo importador (valores em falta) e pela criação manual de rides (auto-sugerir preço).

### Fase 4 — Agregar / desagregar shared (#3, #4)
- Modelo: serviços com o mesmo `grouping_ref` formam um grupo (mesmo carro). A app do motorista e
  o board mostram-nos juntos (1 viagem, vários pax/hotéis).
- **Agregar manualmente**: selecionar 2+ serviços shared compatíveis → atribuir um `grouping_ref`
  comum (e o mesmo motorista/veículo). Repo: `aggregate(array $ids)`.
- **Desagregar** (voo atrasado): botão "Desagregar" num grupo → `disaggregate(rideId)` retira o
  serviço do grupo (limpa `grouping_ref`), ficando como serviço independente para reatribuir.
- UI: na lista de rides e no schedule board, badge de grupo + ações agregar/desagregar.

### Fase 5 — Gestão financeira a sério (#5)
- Reescrever `FinancialController`/`FinancialReportRepository`:
  - filtros: **intervalo de datas**, **fornecedor**, **motorista** (e empresa, via tenancy);
  - tabela detalhada de serviços + **totais**: receita (Σ `total_price`), custo (Σ
    `valor_motorista`), **margem**;
  - sub-totais por fornecedor e por motorista;
  - **exportação** CSV/Excel (reutiliza `XlsxReader`/escrita simples) — fecha o ciclo com o cliente,
    que vive em Excel.
- Mantém o módulo de despesas atual; troca a "estimativa 15€/serviço" pelos valores reais.

### Fase 6 — Polimento do drag-and-drop (#6, já funcional)
- Opcional: vista por **faixas de motorista** (resource timeline) para ver a escala de cada um
  lado a lado; arrastar um serviço para cima de outro para **agregar** (liga à Fase 4).

---

## 5. Ordem recomendada e dependências
```
Fase 0 (schema) ─┬─ Fase 1 (import) ──┬─ Fase 4 (agregação)
                 ├─ Fase 2 (2 valores)┤
                 └─ Fase 3 (preçário) ─┴─ Fase 5 (financeiro)
                                          Fase 6 (board, independente)
```
Entrega incremental sugerida: **0 → 2 → 1 → 3 → 4 → 5 → 6**. (Fazer os "dois valores" antes do
import permite que o import já grave o `valor_motorista` desde o primeiro dia.)

## 6. Riscos / decisões técnicas
- **Leitor XLSX próprio vs PhpSpreadsheet**: recomendo o próprio (leve, sem bloat em shared
  hosting cPanel, controlo total das datas/horas). PhpSpreadsheet fica como fallback se surgirem
  ficheiros com formatações exóticas.
- **Datas/horas Excel** são o ponto sensível (serial numbers). Cobrir com testes usando o ficheiro
  real do cliente.
- **Aditivo e reversível**: colunas nullable + `ImportBatches` com undo ⇒ zero risco para os dados
  atuais durante o desenvolvimento.

## 7. Perguntas a confirmar com Ana & Gonçalo (já não bloqueiam a Fase 0/1)
> Resolvidas por Tiago (ver "Decisões fixadas"): códigos de motorista (ignorar no import),
> Pick-Up Time (hora esperada), valores (vêm no Excel + preçário). Restam, para as fases 3–4:
1. **Veículos** — lista canónica (Standard/Private Taxi, Mini Van, Private Luxury Car, Private
   Luxury Minibus, Shared) e "Mini Van + Private Taxi" (combinação) — como tratar?
2. **Hotel Extra**: confirmar a regra (True/False ⇒ +2€ ao motorista) e onde vem no Excel de
   serviços (não está nas colunas atuais — só nas notas do preçário).
3. **Fornecedores além de MTS** (Get-e, Dreamtravel) têm preçário próprio, ou usam o valor que já
   vem preenchido na coluna do Excel?
4. **Frequência** de importação (diária?) e: reimportar o mesmo dia deve **atualizar** ou **ignorar**
   os já existentes?

## 8. Ficheiros que vão ser criados/alterados (mapa rápido)
- **Novos**: `app/Support/XlsxReader.php`, `app/Services/ExcelServiceImporter.php`,
  `app/Services/PricingEngine.php`, `app/Repositories/PricingRepository.php`,
  `app/Repositories/FinancialReportRepository.php`, `app/Http/Controllers/Admin/ImportController.php`,
  `app/Http/Controllers/Admin/PricingController.php`, vistas correspondentes,
  `database/2026_prtours.sql`, `scripts/migrate_prtours.php`, `scripts/import_pricing.php`, testes.
- **Alterados**: `app/Models/Service.php`, `app/Repositories/ServiceRepository.php`,
  `app/Http/Controllers/Admin/FinancialController.php`, `app/Http/Controllers/Admin/RidesController.php`,
  vistas de rides/financeiro, ficheiros i18n `pt`/`en`, e routing em `public/admin/`.
</content>
</invoke>
