<?php

declare(strict_types=1);

namespace App\Models;

final class Company
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $slug,
        public readonly string  $createdAt,
        // ── billing ──────────────────────────────────────────────────────
        public readonly ?string $stripeCustomerId     = null,
        public readonly ?string $stripeSubscriptionId = null,
        public readonly string  $subStatus            = 'none',
        public readonly ?string $subPlan              = null,
        public readonly ?string $subPriceId           = null,
        public readonly ?string $subCurrentPeriodEnd  = null,
        public readonly bool    $subCancelAtEnd       = false,
        public readonly bool    $graceAccess          = false,
    ) {
    }

    /** Company is allowed to use the platform. */
    public function hasAccess(): bool
    {
        return $this->graceAccess
            || in_array($this->subStatus, ['active', 'trialing'], true);
    }

    /** Active subscription that will cancel at period end. */
    public function isCancelingAtEnd(): bool
    {
        return in_array($this->subStatus, ['active', 'trialing'], true)
            && $this->subCancelAtEnd;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id:                   (int)    $row['id'],
            name:                 (string) $row['name'],
            slug:                 (string) $row['slug'],
            createdAt:            (string) ($row['created_at'] ?? ''),
            stripeCustomerId:     ($row['stripe_customer_id']     ?? null) ?: null,
            stripeSubscriptionId: ($row['stripe_subscription_id'] ?? null) ?: null,
            subStatus:            (string) ($row['sub_status']    ?? 'none'),
            subPlan:              ($row['sub_plan']               ?? null) ?: null,
            subPriceId:           ($row['sub_price_id']           ?? null) ?: null,
            subCurrentPeriodEnd:  ($row['sub_current_period_end'] ?? null) ?: null,
            subCancelAtEnd:       (bool) ($row['sub_cancel_at_end'] ?? 0),
            graceAccess:          (bool) ($row['grace_access']    ?? 0),
        );
    }
}
