<?php
/** @var string|null $errorCode */
/** @var array $old */
$errorCode = $errorCode ?? null;
$old       = $old ?? [];

$errors = [
    'terms'             => 'Tens de aceitar os Termos de Serviço.',
    'company_name'      => 'Nome da empresa inválido (mínimo 2 caracteres).',
    'name'              => 'O teu nome é obrigatório (mínimo 2 caracteres).',
    'email'             => 'Endereço de email inválido.',
    'email_taken'       => 'Este email já está registado. <a href="/SRMT/public/" style="color:#2563eb">Entrar →</a>',
    'password_weak'     => 'A password deve ter pelo menos 8 caracteres.',
    'password_mismatch' => 'As passwords não coincidem.',
    'server_error'      => 'Erro interno. Tenta novamente.',
];
$errMsg = $errorCode ? ($errors[$errorCode] ?? 'Erro desconhecido.') : null;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f1f5f9">
    <title>Criar conta — SyncRide</title>
    <link rel="icon" type="image/png" href="/SRMT/public/assets/images/icons/Syncride.png"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 24px 20px;
            background: radial-gradient(circle at 50% -10%, #bfdbfe 0%, #f1f5f9 65%);
            background-attachment: fixed;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        .accent { position: fixed; border-radius: 50%; filter: blur(8px); z-index: 0; pointer-events: none; }
        .accent-1 { width: 320px; height: 320px; top: -90px; left: -80px; background: rgba(37,99,235,0.10); }
        .accent-2 { width: 380px; height: 380px; bottom: -120px; right: -110px; background: rgba(37,99,235,0.07); }

        .card {
            position: relative; z-index: 10;
            width: 100%; max-width: 460px;
            background: rgba(255,255,255,0.72);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.8);
            border-radius: 28px; padding: 40px 36px;
            box-shadow: 0 24px 64px rgba(15,23,42,0.12), 0 2px 8px rgba(15,23,42,0.04);
        }
        .header { text-align: center; margin-bottom: 28px; }
        .header img { width: 72px; margin-bottom: 12px; filter: drop-shadow(0 6px 12px rgba(37,99,235,0.18)); }
        .brand { font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
        .brand span { color: #2563eb; }

        .trial-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1.5px solid #bfdbfe;
            color: #1d4ed8; font-size: 12px; font-weight: 700;
            padding: 6px 14px; border-radius: 100px; margin-top: 14px;
        }
        .trial-badge i { font-size: 13px; color: #22c55e; }

        .section-label {
            font-size: 10px; font-weight: 800; text-transform: uppercase;
            letter-spacing: .12em; color: #94a3b8; margin: 20px 0 12px;
        }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #64748b; margin-bottom: 6px; }
        .input-wrap { position: relative; }
        .input-wrap i.ico { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; pointer-events: none; }
        .input-wrap input {
            width: 100%; height: 48px; border-radius: 13px; padding: 0 42px 0 40px;
            background: rgba(255,255,255,0.7); border: 1.5px solid rgba(15,23,42,0.10);
            color: #0f172a; font-size: 14px; font-weight: 600; font-family: inherit;
            transition: all .2s;
        }
        .input-wrap input:focus {
            outline: none; border-color: #2563eb; background: #fff;
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
        }
        .toggle-pass {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; cursor: pointer; font-size: 15px; background: none; border: none; padding: 4px;
        }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .error-box {
            background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px;
            padding: 12px 14px; font-size: 13px; font-weight: 600; color: #dc2626;
            margin-bottom: 18px; display: flex; align-items: flex-start; gap: 8px;
        }
        .error-box i { margin-top: 1px; flex-shrink: 0; }

        .terms-row {
            display: flex; align-items: flex-start; gap: 10px;
            margin: 16px 0 22px;
        }
        .terms-row input[type=checkbox] {
            width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;
            accent-color: #2563eb; cursor: pointer;
        }
        .terms-row label {
            font-size: 13px; color: #475569; font-weight: 500; cursor: pointer; line-height: 1.5;
        }
        .terms-row label a { color: #2563eb; font-weight: 600; text-decoration: none; }
        .terms-row label a:hover { text-decoration: underline; }

        .btn {
            width: 100%; height: 52px; border: none; border-radius: 14px;
            background: #2563eb; color: #fff; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all .2s; font-family: inherit;
            box-shadow: 0 10px 24px rgba(37,99,235,0.28);
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 14px 30px rgba(37,99,235,0.34); }
        .btn:active { transform: translateY(0); }
        .btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        .login-link {
            text-align: center; margin-top: 20px;
            font-size: 13px; color: #64748b; font-weight: 500;
        }
        .login-link a { color: #2563eb; font-weight: 700; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }

        .divider { display: flex; align-items: center; gap: 10px; margin: 18px 0 0; }
        .divider hr { flex: 1; border: none; border-top: 1.5px solid rgba(15,23,42,0.08); }
        .divider span { font-size: 11px; color: #94a3b8; font-weight: 700; white-space: nowrap; }

        @media (max-width: 480px) {
            .card { padding: 32px 22px; }
            .row2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="accent accent-1"></div>
    <div class="accent accent-2"></div>

    <div class="card">
        <div class="header">
            <img src="/SRMT/public/assets/images/icons/Syncride.png" alt="SyncRide">
            <div class="brand">SyncRide<span> OS</span></div>
            <div class="trial-badge">
                <i class="bi bi-check-circle-fill"></i>
                7 dias grátis &bull; Cartão necessário para ativar
            </div>
        </div>

        <?php if ($errMsg): ?>
        <div class="error-box">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= $errMsg ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="/SRMT/public/auth/register.php" id="regForm">

            <p class="section-label">A tua empresa</p>

            <div class="field">
                <label>Nome da empresa</label>
                <div class="input-wrap">
                    <i class="bi bi-building ico"></i>
                    <input type="text" name="company_name" placeholder="Ex: Transfers Lisboa Lda."
                           value="<?= htmlspecialchars($old['companyName'] ?? '', ENT_QUOTES) ?>"
                           required autofocus autocomplete="organization">
                </div>
            </div>

            <p class="section-label" style="margin-top:18px">A tua conta</p>

            <div class="field">
                <label>Teu nome</label>
                <div class="input-wrap">
                    <i class="bi bi-person-fill ico"></i>
                    <input type="text" name="name" placeholder="João Silva"
                           value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>"
                           required autocomplete="name">
                </div>
            </div>

            <div class="field">
                <label>Email profissional</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope-fill ico"></i>
                    <input type="email" name="email" placeholder="joao@empresa.pt"
                           value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES) ?>"
                           required autocomplete="email">
                </div>
            </div>

            <div class="row2">
                <div class="field">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill ico"></i>
                        <input type="password" name="password" id="pw" placeholder="Min. 8 caracteres"
                               required autocomplete="new-password">
                        <button type="button" class="toggle-pass" onclick="togglePw('pw','tgPw')">
                            <i class="bi bi-eye-fill" id="tgPw"></i>
                        </button>
                    </div>
                </div>
                <div class="field">
                    <label>Confirmar</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill ico"></i>
                        <input type="password" name="confirm" id="pw2" placeholder="Repetir password"
                               required autocomplete="new-password">
                        <button type="button" class="toggle-pass" onclick="togglePw('pw2','tgPw2')">
                            <i class="bi bi-eye-fill" id="tgPw2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="terms-row">
                <input type="checkbox" name="terms" id="terms" <?= $errorCode === 'terms' ? '' : 'checked' ?>>
                <label for="terms">
                    Aceito os <a href="/SRMT/public/terms.html" target="_blank">Termos de Serviço</a>
                    e a <a href="/SRMT/public/privacy.html" target="_blank">Política de Privacidade</a>
                </label>
            </div>

            <button type="submit" class="btn" id="submitBtn">
                <i class="bi bi-rocket-takeoff-fill"></i>
                Começar trial grátis
            </button>
        </form>

        <div class="divider"><hr><span>já tens conta?</span><hr></div>

        <div class="login-link">
            <a href="/SRMT/public/">Entrar na minha conta →</a>
        </div>
    </div>

    <script>
        function togglePw(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash-fill';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye-fill';
            }
        }

        document.getElementById('regForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span style="display:inline-block;width:18px;height:18px;border:2.5px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite"></span> A criar conta...';
        });
    </script>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</body>
</html>
