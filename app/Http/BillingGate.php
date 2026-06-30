<?php

declare(strict_types=1);

namespace App\Http;

use App\Repositories\CompanyRepository;
use App\Support\Session;

/**
 * Blocks access for company admins whose subscription (or trial) has lapsed.
 * Called from AuthMiddleware after the role check.
 *
 * Does NOT gate:
 *   - Super-admins (no company_id in session)
 *   - The billing page itself (avoid redirect loop)
 */
final class BillingGate
{
    private const BILLING_PATH = '/SRMT/public/admin/billing.php';

    public static function handle(): void
    {
        $companyId = Session::companyId();
        if ($companyId === null) {
            return; // super-admin
        }

        // Don't gate the billing page
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($uri, '/admin/billing.php')) {
            return;
        }

        $company = CompanyRepository::default()->find($companyId);
        if ($company === null) {
            return;
        }

        if ($company->graceAccess) {
            return;
        }

        // Trial expired check (trialing but period_end has passed)
        if ($company->subStatus === 'trialing' && $company->subCurrentPeriodEnd !== null) {
            if (strtotime($company->subCurrentPeriodEnd) < time()) {
                CompanyRepository::default()->updateBilling($companyId, ['sub_status' => 'canceled']);
                self::redirectToBilling('trial_expired');
            }
            return; // still in trial
        }

        // Active or trialing (period_end is in the future) = granted
        if (in_array($company->subStatus, ['active', 'trialing'], true)) {
            return;
        }

        // none / canceled / past_due → block
        self::redirectToBilling('subscription_required');
    }

    private static function redirectToBilling(string $reason): never
    {
        header('Location: ' . self::BILLING_PATH . '?reason=' . $reason);
        exit;
    }
}
