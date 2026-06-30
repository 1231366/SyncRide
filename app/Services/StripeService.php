<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Thin cURL wrapper around the Stripe REST API.
 * No SDK dependency — uses PHP's built-in cURL extension.
 */
final class StripeService
{
    private const API_BASE = 'https://api.stripe.com/v1';

    public function __construct(private readonly string $secretKey)
    {
    }

    public static function default(): self
    {
        $key = (string) (\App\Support\Env::get('STRIPE_SECRET_KEY', ''));
        if ($key === '') {
            throw new RuntimeException('STRIPE_SECRET_KEY is not configured.');
        }
        return new self($key);
    }

    // ── Customers ────────────────────────────────────────────────────────

    /** Find an existing customer or create one. Returns the customer ID. */
    public function findOrCreateCustomer(int $companyId, string $companyName, string $email): string
    {
        // Try to find by metadata first
        $existing = $this->get('/customers/search', [
            'query' => "metadata['company_id']:'{$companyId}'",
            'limit' => '1',
        ]);
        if (!empty($existing['data'][0]['id'])) {
            return $existing['data'][0]['id'];
        }

        $customer = $this->post('/customers', [
            'name'     => $companyName,
            'email'    => $email,
            'metadata' => ['company_id' => (string) $companyId],
        ]);
        return $customer['id'];
    }

    // ── Checkout ─────────────────────────────────────────────────────────

    /**
     * Create a Stripe Checkout Session for a new subscription.
     * Returns the session URL to redirect to.
     */
    public function createCheckoutSession(
        string $customerId,
        string $priceId,
        int    $companyId,
        string $successUrl,
        string $cancelUrl,
        int    $trialDays = 0,
    ): string {
        $params = [
            'customer'                   => $customerId,
            'mode'                       => 'subscription',
            'line_items[0][price]'       => $priceId,
            'line_items[0][quantity]'    => '1',
            'success_url'                => $successUrl,
            'cancel_url'                 => $cancelUrl,
            'subscription_data[metadata][company_id]' => (string) $companyId,
            'metadata[company_id]'       => (string) $companyId,
            'allow_promotion_codes'      => 'true',
            'billing_address_collection' => 'required',
            'tax_id_collection[enabled]' => 'true',
            'automatic_tax[enabled]'     => 'false',
            'customer_update[address]'   => 'auto',
            'customer_update[name]'      => 'auto',
            'customer_update[shipping]'  => 'never',
        ];

        if ($trialDays > 0) {
            $params['subscription_data[trial_period_days]'] = (string) $trialDays;
            // End trial immediately if payment method fails at end of trial
            $params['subscription_data[trial_settings][end_behavior][missing_payment_method]'] = 'cancel';
        }

        $session = $this->post('/checkout/sessions', $params);
        return $session['url'];
    }

    // ── Subscriptions ────────────────────────────────────────────────────

    /** Fetch a Checkout Session from Stripe. */
    public function getCheckoutSession(string $sessionId): array
    {
        return $this->get("/checkout/sessions/{$sessionId}");
    }

    /** Fetch a subscription from Stripe. */
    public function getSubscription(string $subscriptionId): array
    {
        return $this->get("/subscriptions/{$subscriptionId}");
    }

    /** Schedule subscription to cancel at the end of the current period. */
    public function cancelAtPeriodEnd(string $subscriptionId): array
    {
        return $this->post("/subscriptions/{$subscriptionId}", [
            'cancel_at_period_end' => 'true',
        ]);
    }

    /** Remove the scheduled cancellation (reactivate). */
    public function reactivate(string $subscriptionId): array
    {
        return $this->post("/subscriptions/{$subscriptionId}", [
            'cancel_at_period_end' => 'false',
        ]);
    }

    /** Immediately cancel a subscription. */
    public function cancelNow(string $subscriptionId): array
    {
        return $this->delete("/subscriptions/{$subscriptionId}");
    }

    /** Change the subscription to a different price (upgrade/downgrade). */
    public function changePlan(string $subscriptionId, string $newPriceId): array
    {
        $sub = $this->getSubscription($subscriptionId);
        $itemId = $sub['items']['data'][0]['id'] ?? null;
        if ($itemId === null) {
            throw new RuntimeException('Subscription has no line items.');
        }
        return $this->post("/subscriptions/{$subscriptionId}", [
            "items[0][id]"    => $itemId,
            "items[0][price]" => $newPriceId,
            'proration_behavior' => 'create_prorations',
        ]);
    }

    // ── Webhooks ─────────────────────────────────────────────────────────

    /**
     * Verify the Stripe-Signature header and return the parsed event payload.
     * Throws RuntimeException if the signature is invalid or the tolerance exceeded.
     *
     * @throws RuntimeException
     */
    public static function verifyWebhook(string $payload, string $sigHeader, string $secret): array
    {
        // Parse t= and v1= from the header
        $parts     = explode(',', $sigHeader);
        $timestamp = null;
        $signatures = [];
        foreach ($parts as $part) {
            [$key, $val] = array_pad(explode('=', $part, 2), 2, '');
            if ($key === 't') { $timestamp = (int) $val; }
            if ($key === 'v1') { $signatures[] = $val; }
        }
        if ($timestamp === null || $signatures === []) {
            throw new RuntimeException('Invalid Stripe-Signature header.');
        }
        if (abs(time() - $timestamp) > 300) {
            throw new RuntimeException('Webhook timestamp too old (replay attack protection).');
        }
        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
        $valid = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) { $valid = true; break; }
        }
        if (!$valid) {
            throw new RuntimeException('Webhook signature mismatch.');
        }
        $event = json_decode($payload, true);
        if (!is_array($event)) {
            throw new RuntimeException('Webhook payload is not valid JSON.');
        }
        return $event;
    }

    // ── HTTP helpers ─────────────────────────────────────────────────────

    private function get(string $path, array $query = []): array
    {
        $url = self::API_BASE . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }
        return $this->request('GET', $url, []);
    }

    private function post(string $path, array $data): array
    {
        return $this->request('POST', self::API_BASE . $path, $data);
    }

    private function delete(string $path): array
    {
        return $this->request('DELETE', self::API_BASE . $path, []);
    }

    private function request(string $method, string $url, array $data): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->secretKey . ':',
            CURLOPT_HTTPHEADER     => ['Stripe-Version: 2024-06-20'],
            CURLOPT_TIMEOUT        => 15,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            throw new RuntimeException("Stripe cURL error: {$err}");
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException("Stripe returned non-JSON (HTTP {$code}).");
        }
        if ($code >= 400) {
            $msg = $decoded['error']['message'] ?? "Stripe error (HTTP {$code})";
            throw new RuntimeException("Stripe API: {$msg}");
        }

        return $decoded;
    }
}
