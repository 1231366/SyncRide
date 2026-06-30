<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'trial_days'     => 7,
    'secret_key'     => (string) Env::get('STRIPE_SECRET_KEY', ''),
    'publishable_key'=> (string) Env::get('STRIPE_PK', ''),
    'webhook_secret' => (string) Env::get('STRIPE_WEBHOOK_SECRET', ''),

    'test_plan' => [
        'price_id' => (string) Env::get('STRIPE_PRICE_TEST', 'price_1TlKFaKM1HOa2PphpFySFaXZ'),
        'name'     => 'TESTE (1 cênt)',
        'price'    => 1,
        'currency' => 'eur',
        'badge'    => 'Dev only',
        'desc'     => 'Plano de teste — não usar em produção.',
        'features' => ['Acesso completo (teste)'],
    ],

    'plans' => [
        'starter' => [
            'price_id' => (string) Env::get('STRIPE_PRICE_STARTER', ''),
            'name'     => 'Starter',
            'price'    => 35_00,   // cents
            'currency' => 'eur',
            'badge'    => null,
            'desc'     => 'Para quem está a começar a organizar a operação.',
            'features' => [
                'Gestão de serviços e motoristas',
                'App mobile para motoristas',
                'Relatórios financeiros',
                'Mapa ao vivo',
                'Suporte por email',
            ],
        ],
        'pro' => [
            'price_id' => (string) Env::get('STRIPE_PRICE_PRO', ''),
            'name'     => 'PRO',
            'price'    => 59_00,
            'currency' => 'eur',
            'badge'    => 'Mais popular',
            'desc'     => 'Para operações que querem tudo no automático.',
            'features' => [
                'Tudo do Starter',
                'Schedule board com drag & drop',
                'Importação Excel (PRtours, MTS...)',
                'Precários automáticos',
                'Análise preditiva de receita',
                'Suporte prioritário',
            ],
        ],
    ],
];
