<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\CompanyRepository;
use App\Services\StripeService;
use App\Support\Env;
use App\Support\Session;

final class BillingController extends BaseController
{
    private CompanyRepository $companies;
    private array             $plans;
    private ?array            $testPlan;

    public function __construct()
    {
        $this->companies = CompanyRepository::default();
        $cfg             = require __DIR__ . '/../../../../config/stripe.php';
        $this->plans     = $cfg['plans'];
        $this->testPlan  = $cfg['test_plan'] ?? null;
    }

    /** GET /admin/billing.php */
    public function index(): void
    {
        $companyId = Session::companyId();
        $company   = $companyId ? $this->companies->find($companyId) : null;

        $flash    = $_GET['success']  ?? null;
        $error    = $_GET['error']    ?? null;
        $canceled = isset($_GET['canceled']);

        // Sync subscription from Stripe immediately after checkout (works without webhook)
        if ($flash === 'subscribed' && isset($_GET['session_id']) && $companyId !== null) {
            try {
                $stripe    = StripeService::default();
                $session   = $stripe->getCheckoutSession((string) $_GET['session_id']);
                $subId     = (string) ($session['subscription'] ?? '');
                $custId    = (string) ($session['customer'] ?? '');
                if ($subId !== '') {
                    $sub       = $stripe->getSubscription($subId);
                    $priceId   = (string) ($sub['items']['data'][0]['price']['id'] ?? '');
                    $periodEnd = $sub['current_period_end'] ?? null;
                    $cfg       = require __DIR__ . '/../../../../config/stripe.php';
                    $plan      = null;
                    foreach (array_merge($cfg['plans'], ['test' => $cfg['test_plan']]) as $slug => $p) {
                        if ($p['price_id'] === $priceId) { $plan = $slug; break; }
                    }
                    // Use actual Stripe status — 'trialing' during trial, 'active' after
                    $stripeStatus = (string) ($sub['status'] ?? 'active');
                    $dbStatus = match ($stripeStatus) {
                        'active'   => 'active',
                        'trialing' => 'trialing',
                        'past_due' => 'past_due',
                        'canceled' => 'canceled',
                        default    => 'active',
                    };
                    $this->companies->updateBilling($companyId, [
                        'stripe_customer_id'     => $custId,
                        'stripe_subscription_id' => $subId,
                        'sub_status'             => $dbStatus,
                        'sub_plan'               => in_array($plan, ['starter', 'pro'], true) ? $plan : null,
                        'sub_price_id'           => $priceId,
                        'sub_current_period_end' => $periodEnd
                            ? (new \DateTimeImmutable('@' . $periodEnd))->format('Y-m-d H:i:s')
                            : null,
                        'sub_cancel_at_end'      => 0,
                    ]);
                    $company = $this->companies->find($companyId); // reload
                }
            } catch (\Throwable) {
                // webhook will handle it if session sync fails
            }
        }

        $isDev = in_array(\App\Support\Env::get('APP_ENV', 'production'), ['local', 'development'], true);

        $this->view('admin.billing.index', [
            'company'  => $company,
            'plans'    => $this->plans,
            'testPlan' => $isDev ? $this->testPlan : null,
            'flash'    => $flash,
            'error'    => $error,
            'canceled' => $canceled,
        ]);
    }

    /** POST /admin/billing.php?action=checkout */
    public function checkout(): void
    {
        $this->requirePost();

        $plan     = (string) ($this->input('plan') ?? '');
        $isDev    = in_array(\App\Support\Env::get('APP_ENV', 'production'), ['local', 'development'], true);
        $allPlans = $this->plans;
        if ($isDev && $this->testPlan !== null) {
            $allPlans['test'] = $this->testPlan;
        }
        if (!isset($allPlans[$plan])) {
            $this->redirect('/SRMT/public/admin/billing.php?error=invalid_plan');
        }

        $companyId = Session::companyId();
        if ($companyId === null) {
            $this->abort(403, 'No company in session.');
        }

        $company  = $this->companies->find($companyId);
        if ($company === null) {
            $this->abort(404, 'Company not found.');
        }

        $stripe   = StripeService::default();
        $priceId  = $allPlans[$plan]['price_id'];

        // Find admin email for the customer
        $admins = $this->companies->adminsForCompany($companyId);
        $email  = $admins[0]['email'] ?? 'billing@syncride.pt';

        $customerId = $company->stripeCustomerId
            ?? $stripe->findOrCreateCustomer($companyId, $company->name, $email);

        // Persist customer ID immediately so we can find the company on webhook
        if ($company->stripeCustomerId === null) {
            $this->companies->updateBilling($companyId, ['stripe_customer_id' => $customerId]);
        }

        $base       = rtrim((string) Env::get('APP_URL', ''), '/');
        $successUrl = $base . '/public/admin/billing.php?success=subscribed&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = $base . '/public/admin/billing.php?canceled=1';

        // 7-day trial for first-time subscribers (no prior Stripe subscription)
        $cfg      = require __DIR__ . '/../../../../config/stripe.php';
        $trialDays = ($company->stripeSubscriptionId === null) ? (int) ($cfg['trial_days'] ?? 7) : 0;

        $url = $stripe->createCheckoutSession($customerId, $priceId, $companyId, $successUrl, $cancelUrl, $trialDays);

        header("Location: {$url}");
        exit;
    }

    /** POST /admin/billing.php?action=cancel */
    public function cancel(): void
    {
        $this->requirePost();

        $companyId = Session::companyId();
        $company   = $companyId ? $this->companies->find($companyId) : null;

        if ($company?->stripeSubscriptionId === null) {
            $this->redirect('/SRMT/public/admin/billing.php?error=no_subscription');
        }

        $stripe = StripeService::default();
        $stripe->cancelAtPeriodEnd($company->stripeSubscriptionId);

        $this->companies->updateBilling($companyId, ['sub_cancel_at_end' => 1]);

        $this->redirect('/SRMT/public/admin/billing.php?success=canceled');
    }

    /** POST /admin/billing.php?action=reactivate */
    public function reactivate(): void
    {
        $this->requirePost();

        $companyId = Session::companyId();
        $company   = $companyId ? $this->companies->find($companyId) : null;

        if ($company?->stripeSubscriptionId === null) {
            $this->redirect('/SRMT/public/admin/billing.php?error=no_subscription');
        }

        $stripe = StripeService::default();
        $stripe->reactivate($company->stripeSubscriptionId);

        $this->companies->updateBilling($companyId, ['sub_cancel_at_end' => 0]);

        $this->redirect('/SRMT/public/admin/billing.php?success=reactivated');
    }

    /** POST /admin/billing.php?action=change_plan */
    public function changePlan(): void
    {
        $this->requirePost();

        $plan = (string) ($this->input('plan') ?? '');
        if (!isset($this->plans[$plan])) {
            $this->redirect('/SRMT/public/admin/billing.php?error=invalid_plan');
        }

        $companyId = Session::companyId();
        $company   = $companyId ? $this->companies->find($companyId) : null;

        if ($company?->stripeSubscriptionId === null) {
            $this->redirect('/SRMT/public/admin/billing.php?error=no_subscription');
        }

        $stripe  = StripeService::default();
        $priceId = $this->plans[$plan]['price_id'];
        $stripe->changePlan($company->stripeSubscriptionId, $priceId);

        $this->companies->updateBilling($companyId, [
            'sub_plan'     => $plan,
            'sub_price_id' => $priceId,
        ]);

        $this->redirect('/SRMT/public/admin/billing.php?success=plan_changed');
    }

}
