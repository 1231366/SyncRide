<?php
/** @var ?App\Models\Company $company */
/** @var array<string,array<string,mixed>> $plans */
/** @var ?array<string,mixed> $testPlan */
/** @var ?string $flash @var ?string $error @var bool $canceled */

use App\Http\View;

$flashMsgs = [
    'subscribed'   => 'Subscrição ativada com sucesso! Bem-vindo ao SyncRide OS.',
    'canceled'     => 'Subscrição cancelada — acesso mantido até ao fim do período.',
    'reactivated'  => 'Subscrição reativada.',
    'plan_changed' => 'Plano alterado com sucesso.',
];
$isWelcome = isset($_GET['welcome']);

$planKey = $company?->subPlan ?? 'starter';
$planCfg = $plans[$planKey] ?? $plans['starter'];
$periodEndFmt = $company?->subCurrentPeriodEnd
    ? date('d \d\e F \d\e Y', strtotime($company->subCurrentPeriodEnd))
    : null;

// Free trial = trialing with no Stripe subscription yet (no card required)
$isFreeTrial = $company !== null
    && $company->subStatus === 'trialing'
    && $company->stripeSubscriptionId === null;

$trialDaysLeft = ($isFreeTrial && $company->subCurrentPeriodEnd !== null)
    ? max(0, (int) ceil((strtotime($company->subCurrentPeriodEnd) - time()) / 86400))
    : 0;

$reason = $_GET['reason'] ?? null;

$reasonMsgs = [
    'trial_expired'          => 'O teu trial gratuito terminou. Escolhe um plano para retomar o acesso.',
    'subscription_required'  => 'A tua subscrição está inativa. Escolhe um plano para continuar.',
];

ob_start(); // extraHead
?>
<style>
.billing-overlay {
    position: fixed; inset: 0; z-index: 900;
    background: rgba(0,0,0,.55); backdrop-filter: blur(6px);
    display: flex; align-items: flex-end;
    opacity: 0; pointer-events: none; transition: opacity .25s;
}
.billing-overlay.open { opacity: 1; pointer-events: all; }
@media (min-width: 640px) {
    .billing-overlay { align-items: center; justify-content: center; }
}
.billing-modal {
    background: var(--glass-bg, rgba(255,255,255,.92));
    backdrop-filter: blur(40px); -webkit-backdrop-filter: blur(40px);
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 28px 28px 0 0;
    padding: 8px 24px 36px;
    width: 100%; max-width: 480px;
    transform: translateY(24px); transition: transform .3s cubic-bezier(.34,1.56,.64,1);
}
[data-theme="dark"] .billing-modal {
    background: rgba(18,18,20,.95);
    border-color: rgba(255,255,255,.09);
}
@media (min-width: 640px) {
    .billing-modal { border-radius: 28px; transform: scale(.96); padding: 28px; }
    .billing-overlay.open .billing-modal { transform: scale(1); }
}
.billing-overlay.open .billing-modal { transform: translateY(0) scale(1); }
.billing-drag-handle {
    width: 36px; height: 4px; border-radius: 2px;
    background: rgba(0,0,0,.15); margin: 0 auto 20px;
}
[data-theme="dark"] .billing-drag-handle { background: rgba(255,255,255,.15); }
</style>
<?php $billingHead = ob_get_clean(); ?>

<?php ob_start(); // extraScripts ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if ($flash !== null && isset($flashMsgs[$flash])): ?>
    if (typeof toastr !== 'undefined') toastr.success("<?= addslashes($flashMsgs[$flash]) ?>");
    <?php endif; ?>
    <?php if ($canceled): ?>
    if (typeof toastr !== 'undefined') toastr.info("Checkout cancelado. Podes subscrever quando quiseres.");
    <?php endif; ?>
    <?php if ($error !== null): ?>
    if (typeof toastr !== 'undefined') toastr.error("Ocorreu um erro. Tenta novamente ou contacta o suporte.");
    <?php endif; ?>
});

function openCancelModal() {
    document.getElementById('cancelOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeCancelModal() {
    document.getElementById('cancelOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
function submitCancel() {
    document.getElementById('cancelForm').submit();
}

function openChangePlanModal(planKey, planName, price) {
    document.getElementById('changePlanKey').value = planKey;
    document.getElementById('changePlanName').textContent = planName;
    document.getElementById('changePlanPrice').textContent = '€' + price + '/mês';
    document.getElementById('changePlanOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeChangePlanModal() {
    document.getElementById('changePlanOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
function submitChangePlan() {
    document.getElementById('changePlanForm').submit();
}
</script>
<?php $billingScripts = ob_get_clean(); ?>

<?php
View::layout('layouts.admin', [
    'title'        => 'Subscrição — SyncRide OS',
    'active'       => 'billing',
    'extraHead'    => $billingHead,
    'extraScripts' => $billingScripts,
]);
?>

<section class="px-4 md:px-6 mt-6 max-w-4xl mx-auto pb-20">

    <!-- Header -->
    <div class="mb-7">
        <h1 class="text-xl font-black"><?= $isWelcome ? 'Bem-vindo ao SyncRide OS! 🎉' : 'Subscrição' ?></h1>
        <p class="text-[10px] text-zinc-500 font-semibold mt-0.5">
            <?= $isWelcome ? 'Escolhe um plano para começar os teus 7 dias grátis — sem ser cobrado agora.' : 'Gestão do plano SyncRide OS' ?>
        </p>
    </div>

    <?php if ($isWelcome): ?>
    <div class="glass rounded-2xl p-5 mb-6 flex gap-4 items-start" style="border-left:3px solid #2563eb">
        <i data-lucide="gift" class="w-6 h-6 flex-shrink-0 mt-0.5" style="color:#2563eb"></i>
        <div>
            <h3 class="font-black text-sm" style="color:#2563eb">7 dias grátis, cartão necessário</h3>
            <p class="text-xs text-zinc-400 mt-1">
                Introduz o teu cartão para ativar o trial. <strong class="text-zinc-200">Não é cobrado nada agora.</strong>
                Ao fim de 7 dias, começas a ser cobrado no plano que escolheres — cancelas quando quiseres.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($reason !== null && isset($reasonMsgs[$reason])): ?>
    <div class="glass rounded-2xl p-4 mb-5 flex gap-3 items-center" style="border-left:3px solid #ef4444">
        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 text-red-400"></i>
        <p class="text-sm font-semibold"><?= View::e($reasonMsgs[$reason]) ?></p>
    </div>
    <?php endif; ?>

    <?php if ($company !== null && $company->graceAccess): ?>
    <!-- ── Grace access ──────────────────────────────────────────── -->
    <div class="glass rounded-2xl p-5 mb-6 flex gap-4 items-start" style="border-left:3px solid #10b981">
        <i data-lucide="shield-check" class="w-6 h-6 flex-shrink-0 mt-0.5" style="color:#10b981"></i>
        <div>
            <h3 class="font-black text-sm" style="color:#10b981">Acesso Especial Ativo</h3>
            <p class="text-xs text-zinc-400 mt-1">A tua conta tem acesso completo à plataforma gerido manualmente pela equipa SyncRide. Não é necessário qualquer pagamento.</p>
        </div>
    </div>

    <?php elseif ($isFreeTrial): ?>
    <!-- ── Free trial countdown card ────────────────────────────── -->
    <div class="glass rounded-2xl p-5 mb-6" style="border-left:3px solid #2563eb">
        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,99,235,.12)">
                <i data-lucide="clock" class="w-5 h-5" style="color:#2563eb"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="font-black text-sm" style="color:#2563eb">Trial gratuito ativo</h3>
                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full" style="background:rgba(37,99,235,.12);color:#2563eb">
                        <?= $trialDaysLeft <= 1 ? 'Último dia' : $trialDaysLeft . ' dias restantes' ?>
                    </span>
                </div>
                <p class="text-xs text-zinc-400 mt-1.5">
                    <?php if ($trialDaysLeft <= 3): ?>
                        O acesso gratuito termina em <strong class="text-zinc-300"><?= $periodEndFmt ?></strong>. Escolhe um plano abaixo para não perder o acesso.
                    <?php else: ?>
                        Acesso completo até <strong class="text-zinc-300"><?= $periodEndFmt ?></strong>. Podes subscrever quando quiseres.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <?php elseif ($company !== null && in_array($company->subStatus, ['active', 'trialing'], true)): ?>
    <!-- ── Active subscription card ─────────────────────────────── -->
    <div class="glass rounded-2xl p-5 mb-4">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Plano atual</span>
                    <?php if ($company->isCancelingAtEnd()): ?>
                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full" style="background:rgba(239,68,68,.1);color:#ef4444">Cancela a <?= View::e($periodEndFmt ?? '—') ?></span>
                    <?php else: ?>
                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full" style="background:rgba(16,185,129,.1);color:#10b981">● Ativo</span>
                    <?php endif; ?>
                </div>
                <h2 class="text-2xl font-black"><?= View::e($planCfg['name']) ?></h2>
                <p class="text-sm font-bold mt-1" style="color:#10b981">
                    €<?= number_format($planCfg['price'] / 100, 2) ?>/mês
                </p>
                <?php if ($periodEndFmt !== null): ?>
                    <p class="text-[11px] text-zinc-500 mt-1.5">
                        <?= $company->isCancelingAtEnd() ? 'Acesso garantido até' : 'Renova em' ?>
                        <strong class="<?= $company->isCancelingAtEnd() ? 'text-red-400' : '' ?>">
                            <?= View::e($periodEndFmt) ?>
                        </strong>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-2">
                <?php if ($company->isCancelingAtEnd()): ?>
                    <form method="POST" action="/SRMT/public/admin/billing.php?action=reactivate">
                        <button type="submit" class="glass rounded-xl px-5 py-2.5 text-xs font-bold flex items-center gap-2" style="color:#10b981;touch-action:manipulation">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Reativar subscrição
                        </button>
                    </form>
                <?php else: ?>
                    <button onclick="openCancelModal()" class="glass rounded-xl px-5 py-2.5 text-xs font-bold flex items-center gap-2 text-red-400" style="touch-action:manipulation">
                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Cancelar subscrição
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Change plan row -->
    <?php
    $otherPlanKey = ($planKey === 'pro') ? 'starter' : 'pro';
    $otherPlan    = $plans[$otherPlanKey];
    ?>
    <div class="glass rounded-2xl p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
            <p class="text-[9px] font-black uppercase tracking-widest text-zinc-500 mb-0.5">Mudar de plano</p>
            <h4 class="font-black text-sm">
                <?= View::e($otherPlan['name']) ?>
                <span class="text-zinc-400 font-semibold"> — €<?= number_format($otherPlan['price'] / 100, 0) ?>/mês</span>
            </h4>
            <p class="text-[10px] text-zinc-500 mt-0.5"><?= View::e($otherPlan['desc']) ?></p>
        </div>
        <button onclick="openChangePlanModal('<?= View::e($otherPlanKey) ?>', '<?= View::e($otherPlan['name']) ?>', '<?= number_format($otherPlan['price'] / 100, 0) ?>')"
                class="glass rounded-xl px-5 py-2.5 text-xs font-bold flex items-center gap-2" style="color:#8b5cf6;touch-action:manipulation">
            <i data-lucide="arrow-up-circle" class="w-3.5 h-3.5"></i>
            Mudar para <?= View::e($otherPlan['name']) ?>
        </button>
    </div>

    <?php elseif ($company !== null && $company->subStatus === 'past_due'): ?>
    <div class="glass rounded-2xl p-5 mb-6" style="border-left:3px solid #ef4444">
        <div class="flex gap-4 items-start">
            <i data-lucide="alert-circle" class="w-6 h-6 flex-shrink-0 mt-0.5 text-red-500"></i>
            <div>
                <h3 class="font-black text-sm text-red-500">Pagamento falhado</h3>
                <p class="text-xs text-zinc-400 mt-1">O último pagamento não foi processado. Atualiza o método de pagamento para manter o acesso.</p>
                <p class="text-xs text-zinc-500 mt-2">Contacta <a href="mailto:suporte@syncride.pt" class="text-blue-400">suporte@syncride.pt</a> se precisares de ajuda.</p>
            </div>
        </div>
    </div>

    <?php elseif ($company !== null && $company->subStatus === 'canceled'): ?>
    <div class="glass rounded-2xl p-5 mb-6" style="border-left:3px solid #94a3b8">
        <div class="flex gap-4 items-start">
            <i data-lucide="moon" class="w-6 h-6 flex-shrink-0 mt-0.5 text-zinc-400"></i>
            <div>
                <h3 class="font-black text-sm text-zinc-400">Subscrição cancelada</h3>
                <p class="text-xs text-zinc-500 mt-1">Subscreve novamente abaixo para recuperar o acesso completo.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php
    $showCards = $company === null
        || $isFreeTrial
        || (!$company->graceAccess && !in_array($company->subStatus, ['active', 'trialing'], true));
    ?>
    <?php if ($showCards): ?>
    <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-4">
        <?php
        if ($isFreeTrial) echo 'Subscreve durante o trial';
        elseif (in_array($company?->subStatus ?? '', ['canceled', 'past_due'], true)) echo 'Subscrever novamente';
        else echo 'Escolhe o teu plano';
        ?>
    </p>
    <div class="grid md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($plans as $key => $plan): ?>
        <?php $isPro = $key === 'pro'; ?>
        <div class="glass rounded-2xl p-6 flex flex-col <?= $isPro ? 'ring-2 ring-violet-500/40' : '' ?> relative">
            <?php if ($plan['badge'] !== null): ?>
                <div class="absolute -top-3 left-6">
                    <span class="text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-full" style="background:#8b5cf6;color:#fff">
                        <?= View::e($plan['badge']) ?>
                    </span>
                </div>
            <?php endif; ?>
            <div class="mb-4">
                <h3 class="text-lg font-black"><?= View::e($plan['name']) ?></h3>
                <p class="text-[11px] text-zinc-400 mt-0.5"><?= View::e($plan['desc']) ?></p>
            </div>
            <div class="mb-5">
                <span class="text-3xl font-black <?= $isPro ? 'text-violet-400' : 'text-emerald-400' ?>">€<?= number_format($plan['price'] / 100, 0) ?></span>
                <span class="text-xs text-zinc-500 font-semibold">/mês</span>
            </div>
            <ul class="space-y-2 mb-6 flex-1">
                <?php foreach ($plan['features'] as $feature): ?>
                <li class="flex items-center gap-2 text-[11px] font-semibold">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 flex-shrink-0 <?= $isPro ? 'text-violet-400' : 'text-emerald-400' ?>"></i>
                    <?= View::e($feature) ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <form method="POST" action="/SRMT/public/admin/billing.php?action=checkout">
                <input type="hidden" name="plan" value="<?= View::e($key) ?>">
                <button type="submit" class="w-full rounded-xl py-3 font-black text-sm transition-all"
                    style="background:<?= $isPro ? '#8b5cf6' : 'rgba(16,185,129,0.15)' ?>;color:<?= $isPro ? '#fff' : '#10b981' ?>;border:<?= $isPro ? 'none' : '1px solid rgba(16,185,129,0.3)' ?>;touch-action:manipulation">
                    Subscrever <?= View::e($plan['name']) ?>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($testPlan !== null): ?>
    <div class="glass rounded-2xl p-4 flex items-center justify-between gap-4" style="border:1px dashed rgba(234,179,8,.4)">
        <div>
            <p class="text-[9px] font-black uppercase tracking-widest" style="color:#ca8a04">Dev · Plano de teste</p>
            <p class="text-xs font-bold mt-0.5">€0,01 / mês — para verificar o fluxo de checkout e webhook</p>
        </div>
        <form method="POST" action="/SRMT/public/admin/billing.php?action=checkout">
            <input type="hidden" name="plan" value="test">
            <button type="submit" class="glass rounded-xl px-4 py-2 text-xs font-bold" style="color:#ca8a04;touch-action:manipulation">
                Testar checkout →
            </button>
        </form>
    </div>
    <?php endif; ?>

    <p class="text-center text-[10px] text-zinc-500 mt-5">
        Pagamentos seguros via <strong>Stripe</strong> · Cancela a qualquer momento · Sem compromissos
    </p>
    <?php endif; ?>

</section>

<!-- ── Cancel confirmation modal ─────────────────────────────────────── -->
<div id="cancelOverlay" class="billing-overlay" onclick="if(event.target===this)closeCancelModal()">
    <div class="billing-modal">
        <div class="billing-drag-handle md:hidden"></div>

        <!-- Icon -->
        <div class="flex justify-center mb-5">
            <div class="w-14 h-14 rounded-full flex items-center justify-center" style="background:rgba(239,68,68,.1)">
                <i data-lucide="x-circle" class="w-7 h-7 text-red-500"></i>
            </div>
        </div>

        <!-- Copy -->
        <h3 class="text-[17px] font-black text-center mb-2">Cancelar subscrição?</h3>
        <p class="text-[13px] text-zinc-500 text-center leading-relaxed mb-1">
            Mantens acesso completo à plataforma até ao fim do período atual.
        </p>
        <?php if ($periodEndFmt !== null): ?>
        <p class="text-[13px] font-bold text-center mb-6" style="color:#ef4444">
            Acesso termina em <?= View::e($periodEndFmt) ?>
        </p>
        <?php else: ?>
        <div class="mb-6"></div>
        <?php endif; ?>

        <!-- Checklist -->
        <div class="glass rounded-2xl p-4 mb-6 space-y-2.5">
            <?php foreach ([
                ['Acesso mantido até ao fim do período pago',   'check', '#10b981'],
                ['Não será cobrado nenhum valor adicional',     'check', '#10b981'],
                ['Podes reativar a qualquer momento',           'check', '#10b981'],
                ['Dados e historial preservados após cancelar', 'check', '#10b981'],
            ] as [$txt, $icon, $color]): ?>
            <div class="flex items-center gap-3">
                <i data-lucide="<?= $icon ?>-circle-2" class="w-4 h-4 flex-shrink-0" style="color:<?= $color ?>"></i>
                <span class="text-[12px] font-semibold"><?= $txt ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Buttons -->
        <button onclick="submitCancel()"
                class="w-full rounded-2xl py-3.5 font-black text-sm text-white mb-3 transition-all active:scale-95"
                style="background:#ef4444;touch-action:manipulation">
            Cancelar subscrição
        </button>
        <button onclick="closeCancelModal()"
                class="w-full glass rounded-2xl py-3.5 font-black text-sm transition-all active:scale-95"
                style="touch-action:manipulation">
            Manter subscrição ativa
        </button>

        <form id="cancelForm" method="POST" action="/SRMT/public/admin/billing.php?action=cancel" style="display:none"></form>
    </div>
</div>

<!-- ── Change plan modal ──────────────────────────────────────────────── -->
<div id="changePlanOverlay" class="billing-overlay" onclick="if(event.target===this)closeChangePlanModal()">
    <div class="billing-modal">
        <div class="billing-drag-handle md:hidden"></div>

        <div class="flex justify-center mb-5">
            <div class="w-14 h-14 rounded-full flex items-center justify-center" style="background:rgba(139,92,246,.1)">
                <i data-lucide="arrow-up-circle" class="w-7 h-7" style="color:#8b5cf6"></i>
            </div>
        </div>

        <h3 class="text-[17px] font-black text-center mb-1">Mudar para <span id="changePlanName">—</span></h3>
        <p class="text-[13px] font-bold text-center mb-6" style="color:#8b5cf6" id="changePlanPrice">—</p>

        <div class="glass rounded-2xl p-4 mb-6 space-y-2.5">
            <?php foreach ([
                'O valor será ajustado proporcionalmente (prorated)',
                'Mudança imediata — sem interrupção de serviço',
                'A data de faturação mantém-se igual',
            ] as $txt): ?>
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-4 h-4 flex-shrink-0" style="color:#8b5cf6"></i>
                <span class="text-[12px] font-semibold"><?= $txt ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <button onclick="submitChangePlan()"
                class="w-full rounded-2xl py-3.5 font-black text-sm text-white mb-3 transition-all active:scale-95"
                style="background:#8b5cf6;touch-action:manipulation">
            Confirmar mudança de plano
        </button>
        <button onclick="closeChangePlanModal()"
                class="w-full glass rounded-2xl py-3.5 font-black text-sm transition-all active:scale-95"
                style="touch-action:manipulation">
            Cancelar
        </button>

        <form id="changePlanForm" method="POST" action="/SRMT/public/admin/billing.php?action=change_plan" style="display:none">
            <input type="hidden" name="plan" id="changePlanKey" value="">
        </form>
    </div>
</div>
