<?php

/**
 * Stripe webhook endpoint.
 * URL: https://syncride.wmservers.pt/SRMT/public/webhook/stripe.php
 * Events: checkout.session.completed, customer.subscription.updated,
 *         customer.subscription.deleted, invoice.payment_failed
 */

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Repositories\CompanyRepository;
use App\Services\StripeService;
use App\Support\Env;

// Read raw body BEFORE any output or parsing
$payload   = (string) file_get_contents('php://input');
$sigHeader = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$secret    = (string) Env::get('STRIPE_WEBHOOK_SECRET', '');

if ($secret === '' || $secret === 'whsec_REPLACE_AFTER_CREATING_WEBHOOK') {
    http_response_code(500);
    echo json_encode(['error' => 'Webhook secret not configured.']);
    exit;
}

try {
    $event = StripeService::verifyWebhook($payload, $sigHeader, $secret);
} catch (\RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$companies = CompanyRepository::default();

$planConfig = require __DIR__ . '/../../config/stripe.php';
$planConfig = $planConfig['plans'];

/** Map a Stripe price_id to our plan slug ('starter'|'pro'|null). */
$resolvePlan = static function (string $priceId) use ($planConfig): ?string {
    foreach ($planConfig as $slug => $cfg) {
        if ($cfg['price_id'] === $priceId) { return $slug; }
    }
    return null;
};

/** Find the company_id from Stripe object metadata or customer lookup. */
$resolveCompany = static function (array $obj) use ($companies): ?int {
    $meta = $obj['metadata'] ?? [];
    if (!empty($meta['company_id'])) {
        return (int) $meta['company_id'];
    }
    $customerId = $obj['customer'] ?? null;
    if ($customerId !== null) {
        $company = $companies->findByStripeCustomer((string) $customerId);
        return $company?->id;
    }
    return null;
};

try {
    $type = $event['type'] ?? '';
    $data = $event['data']['object'] ?? [];

    switch ($type) {

        case 'checkout.session.completed':
            // subscription mode checkout finished
            if (($data['mode'] ?? '') !== 'subscription') { break; }
            $companyId     = $resolveCompany($data);
            $subscriptionId = (string) ($data['subscription'] ?? '');
            $customerId    = (string) ($data['customer'] ?? '');
            if ($companyId === null || $subscriptionId === '') { break; }

            // Fetch full subscription to get price details
            $stripe   = StripeService::default();
            $sub      = $stripe->getSubscription($subscriptionId);
            $priceId  = (string) ($sub['items']['data'][0]['price']['id'] ?? '');
            $plan     = $resolvePlan($priceId);
            $periodEnd = $sub['current_period_end'] ?? null;
            $periodEndDt = $periodEnd
                ? (new \DateTimeImmutable('@' . $periodEnd))->format('Y-m-d H:i:s')
                : null;

            // Use actual Stripe status — 'trialing' during trial, 'active' after
            $subStatus = match ((string) ($sub['status'] ?? 'active')) {
                'active'                        => 'active',
                'trialing'                      => 'trialing',
                'past_due'                      => 'past_due',
                'canceled'                      => 'canceled',
                'incomplete_expired', 'unpaid'  => 'past_due',
                default                         => 'active',
            };

            $companies->updateBilling($companyId, [
                'stripe_customer_id'     => $customerId,
                'stripe_subscription_id' => $subscriptionId,
                'sub_status'             => $subStatus,
                'sub_plan'               => in_array($plan, ['starter', 'pro'], true) ? $plan : null,
                'sub_price_id'           => $priceId,
                'sub_current_period_end' => $periodEndDt,
                'sub_cancel_at_end'      => 0,
            ]);
            break;

        case 'customer.subscription.updated':
            $companyId  = $resolveCompany($data);
            if ($companyId === null) { break; }

            $status     = (string) ($data['status'] ?? 'none');
            $priceId    = (string) ($data['items']['data'][0]['price']['id'] ?? '');
            $plan       = $resolvePlan($priceId);
            $periodEnd  = $data['current_period_end'] ?? null;
            $periodEndDt = $periodEnd
                ? (new \DateTimeImmutable('@' . $periodEnd))->format('Y-m-d H:i:s')
                : null;
            $cancelAtEnd = (bool) ($data['cancel_at_period_end'] ?? false);

            // Map Stripe statuses to our enum
            $dbStatus = match ($status) {
                'active'           => 'active',
                'trialing'         => 'trialing',
                'past_due'         => 'past_due',
                'canceled'         => 'canceled',
                'incomplete_expired','unpaid' => 'past_due',
                default            => 'none',
            };

            $companies->updateBilling($companyId, [
                'sub_status'             => $dbStatus,
                'sub_plan'               => $plan,
                'sub_price_id'           => $priceId,
                'sub_current_period_end' => $periodEndDt,
                'sub_cancel_at_end'      => (int) $cancelAtEnd,
            ]);
            break;

        case 'customer.subscription.deleted':
            $companyId = $resolveCompany($data);
            if ($companyId === null) { break; }
            $companies->updateBilling($companyId, [
                'sub_status'             => 'canceled',
                'sub_cancel_at_end'      => 0,
                'sub_current_period_end' => null,
            ]);
            break;

        case 'invoice.payment_failed':
            $companyId = $resolveCompany($data);
            if ($companyId === null) { break; }
            $companies->updateBilling($companyId, ['sub_status' => 'past_due']);
            break;
    }

    http_response_code(200);
    echo json_encode(['received' => true]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
