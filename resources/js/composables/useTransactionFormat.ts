import { useI18n } from 'vue-i18n';

/**
 * Display helpers for reward transactions.
 *
 * Wallets have a `type` (cashback | stamps) set at creation and
 * immutable. Transactions on cashback wallets use a decimal `amount`
 * in Kč; transactions on stamps wallets use an integer `amount` in
 * stamp counts. STAMP_REDEEM additionally stores the *rewards count*
 * (e.g. -1 means 1 free reward redeemed), while the *stamps cost* is
 * `|amount| * stamps_per_reward` (e.g. 10 stamps for a 10/reward shop).
 *
 * Every page that renders a transaction row should use this composable
 * instead of reimplementing the rules per page — the audit found
 * three different pages each making slightly different mistakes
 * around unit / sign / pluralization.
 *
 * `walletType` is passed per row from the page (it comes from the
 * `wallet_type` field the controller attaches to each transaction).
 * Falls back to 'cashback' for safety.
 */
export function useTransactionFormat() {
    const { t, te } = useI18n();

    /**
     * Czech plural form for "razítko": one / few / many.
     *
     * Rules (Czech):
     *  - 1 → "razítko"
     *  - 2,3,4 → "razítka"
     *  - everything else → "razítek"
     * The `n % 100` check is what distinguishes 22 ("razítka") from
     * 25 ("razítek") — the rule shifts on the tens boundary.
     */
    function pluralizeStamps(n: number): string {
        const abs = Math.abs(n);
        if (abs === 1) {
            return t('common.stamp_one');
        }
        if (abs % 100 >= 2 && abs % 100 <= 4) {
            return t('common.stamp_few');
        }
        return t('common.stamp_many');
    }

    /**
     * Czech plural form for "odměna": one / few / many.
     * Same rule as `pluralizeStamps`.
     */
    function pluralizeRewards(n: number): string {
        const abs = Math.abs(n);
        if (abs === 1) {
            return t('common.reward_one');
        }
        if (abs % 100 >= 2 && abs % 100 <= 4) {
            return t('common.reward_few');
        }
        return t('common.reward_many');
    }

    /**
     * UI sign glyph: "+" for >= 0, "−" (U+2212, not hyphen) for < 0.
     * Using U+2212 keeps the column visually aligned (same width).
     */
    function sign(n: number): string {
        return n >= 0 ? '+' : '−';
    }

    /**
     * `true` when the row belongs to a stamps wallet and the
     * transaction stores stamp counts (not Kč).
     */
    function isStampsRow(walletType: 'cashback' | 'stamps' | null | undefined): boolean {
        return walletType === 'stamps';
    }

    /**
     * Render the *primary* amount string for a transaction row.
     *
     * Cashback: "+12.50 Kč" / "−5.00 Kč" (always 2 decimal places).
     * Stamps STAMP_EARN: "+3 razítka" (integer count, pluralized).
     * Stamps STAMP_REDEEM: "−10 razítek" — the *stamps cost* (not the
     *   reward count), pluralized.
     * Stamps manual adjust: "+3 razítka" / "−1 razítko" (integer count).
     */
    function formatAmount(
        tx: { type: string; amount: string },
        walletType: 'cashback' | 'stamps' | null | undefined,
        stampsPerReward: number,
    ): string {
        const value = Number(tx.amount);

        if (!isStampsRow(walletType)) {
            return sign(value) + formatMoney(Math.abs(value)) + '\u00a0Kč';
        }

        // Stamps row. STAMP_REDEEM's stored `amount` is the negative
        // rewards count (e.g. -1 for one free reward). The stamps
        // actually spent is |amount| * stamps_per_reward (e.g. 10).
        // We keep the original (negative) sign so the row reads as a
        // debit: "−10 razítek", not "+10 razítek".
        const count = tx.type === 'stamp_redeem' ? value * stampsPerReward : value;
        return sign(count) + Math.abs(count).toString() + ' ' + pluralizeStamps(count);
    }

    /**
     * Render the `balance_after` value. Cashback keeps the Kč suffix;
     * stamps are shown as a plain integer with the pluralized unit.
     */
    function formatBalance(
        balance: string,
        walletType: 'cashback' | 'stamps' | null | undefined,
    ): string {
        if (isStampsRow(walletType)) {
            const n = Number(balance);
            return n.toString() + ' ' + pluralizeStamps(n);
        }
        return balance + '\u00a0Kč';
    }

    /**
     * Sub-line for a STAMP_REDEEM row, e.g. "= 1 odměna".
     * Returns null for any other transaction type.
     */
    function stampsEqRewards(
        tx: { type: string; amount: string },
        rewardLabel: string,
    ): string | null {
        if (tx.type !== 'stamp_redeem') {
            return null;
        }
        const rewards = Math.abs(Number(tx.amount));
        const noun = pluralizeRewards(rewards);
        return labelForStampsEq(rewards, noun, rewardLabel);
    }

    /**
     * i18n-aware "= N odměn(а) · {label}" annotation. We can't use
     * `t()` directly with the plural form (vue-i18n plurals need the
     * `tc()` choice API and a PluralizationRule), so we use three
     * dedicated keys (one / few / many) and pick the right one here.
     */
    function labelForStampsEq(rewards: number, noun: string, rewardLabel: string): string {
        const abs = Math.abs(rewards);
        let key: string;
        if (abs === 1) {
            key = 'common.stamps_eq_rewards_one';
        } else if (abs % 100 >= 2 && abs % 100 <= 4) {
            key = 'common.stamps_eq_rewards_few';
        } else {
            key = 'common.stamps_eq_rewards_many';
        }
        return t(key, { count: rewards.toString(), noun, label: rewardLabel });
    }

    /**
     * Map a transaction type enum value to its translated label.
     * Falls back to the raw enum value if the key is missing — better
     * to show "stamp_redeem" than to silently render nothing, and
     * the keys are exhaustive so the fallback should never trigger
     * in practice.
     */
    function typeLabel(type: string): string {
        const key = 'dashboard.transactions.index.type_' + type;
        return te(key) ? t(key) : type;
    }

    return {
        pluralizeStamps,
        pluralizeRewards,
        sign,
        isStampsRow,
        formatAmount,
        formatBalance,
        stampsEqRewards,
        typeLabel,
    };
}

/**
 * Format a decimal Kč value with the locale's thousand separators and
 * 2 decimal places. Locale-aware via `Intl.NumberFormat` so the
 * number reads the way the cashier expects (1 234,50 Kč in cs-CZ).
 */
export function formatMoney(value: number, locale: string = 'cs-CZ'): string {
    return new Intl.NumberFormat(locale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}
