# Cashback calculation

The cashback calculation lives in
`app/Services/Reward/RewardTransactionService::logPurchase()`. This
document explains the math, the rounding rules, and the edge cases
staff should know about.

## Formula

For every `logPurchase` call the system computes:

```text
cashback = purchaseAmount * cashbackRate / 100
```

- `purchaseAmount` — the pre-cashback amount the customer paid, in
  the store's currency. Always a positive `BigDecimal`. Stored as
  `purchase_amount` on the transaction row.
- `cashbackRate` — the current program rate, expressed as a percentage
  (10 = 10%). Sourced from the `cashback_rate` setting. Default is 10.
- The result is rounded to **2 decimal places** using
  `Brick\Math\RoundingMode::HalfUp`. 1.005 → 1.01; 1.004 → 1.00.

## Examples

| Purchase (Kč) | Rate | Cashback (Kč) | Why                              |
| ------------- | ---- | ------------- | -------------------------------- |
| 50.00         | 10%  | 5.00          | 50 × 10 / 100                    |
| 33.33         | 10%  | 3.33          | 33.33 × 10 / 100 = 3.333 → 3.33  |
| 33.36         | 10%  | 3.34          | 33.36 × 10 / 100 = 3.336 → 3.34  |
| 200.00        | 25%  | 50.00         | with a custom rate from settings |
| 0.01          | 10%  | 0.00          | 0.01 × 10 / 100 = 0.001 → 0.00   |

> Note: a 0.01 Kč purchase at a 10% rate credits 0 Kč of rewards.
> The minimum purchase to credit any rewards is 1.00 Kč at 10%
> (or 0.50 Kč at 20%, etc).

## What's stored

Each `purchase_cashback` transaction row carries the inputs as well
as the result, so the ledger is fully self-describing:

| Column            | Meaning                                                                                               |
| ----------------- | ----------------------------------------------------------------------------------------------------- |
| `purchase_amount` | The amount the customer paid                                                                          |
| `cashback_rate`   | The rate at the moment of purchase (snapshot)                                                         |
| `amount`          | The cashback value, signed (+ for credits, − for debits)                                              |
| `balance_before`  | The wallet balance before the credit                                                                  |
| `balance_after`   | The wallet balance after the credit                                                                   |
| `cashback_rate`   | Captures the rate at the time so historical redemptions / rate changes don't rewrite old transactions |

Changing the `cashback_rate` setting only affects **future** purchases.
Old transactions keep the rate they were credited at.

## Lifecycle

1. Staff opens the customer's wallet (scan or manual).
2. Staff enters the pre-cashback `purchase_amount` and submits.
3. The controller validates the amount (`>= 0.01`).
4. The service opens a DB transaction and `SELECT … FOR UPDATE`s the
   wallet row to serialize concurrent staff actions on the same
   customer.
5. The service computes `cashback`, inserts the ledger row, and
   updates the wallet's `rewards_balance` + `lifetime_earned`.
6. The wallet's `last_used_at` is touched.
7. The transaction commits; the controller flashes a success message
   and the staff layout re-renders the new balance.

## Edge cases & guard rails

- **Non-positive purchase** is rejected by the validity
  (`purchase_amount` min `0.01`). A `0` or negative amount never
  reaches the service.
- **Disabled wallets** are not blocked by the service, but the staff
  UI disables every action button on disabled wallets to prevent
  accidental writes.
- **Concurrent staff on the same wallet**: the row lock guarantees
  `balance_before` and `balance_after` are coherent; two staff pressing
  "log purchase" simultaneously will not double-credit (one waits
  for the other's transaction to commit).
- **Currency**: the `decimal:2` cast on every money column means
  values are zero-padded (5 → 5.00). Comparisons in code are done
  with `BigDecimal` to avoid float drift; conversions to string for
  storage use `->__toString()`.
- **Rounding mode**: `HalfUp` is used everywhere (cashback, manual
  adjustments, redeem floor). Banker-style rounding is intentionally
  not used to match the staff's mental model.

## Where it shows up

- **Staff**: the dashboard's "Cashback today" tile sums today's
  `purchase_cashback.amount`; the wallet detail page shows the
  per-purchase `purchase_amount` and `cashback_rate`.
- **Customer**: the wallet page shows lifetime_earned (sum of
  positive `amount`s) and the most-recent cashback entries with
  their original purchase amount.
- **Transactions log**: the staff /staff/transactions page filters
  by `type=purchase_cashback` to focus on the credit trail.

## Manual adjustments

Manual credits, debits, and set-balance operations all live in
`RewardTransactionService` and follow the same DB-transaction +
row-lock + ledger pattern:

- `manualAdd(wallet, amount, note, staff)` — credits `amount` and
  records a `manual_add` row. `note` is required and is the only
  place a manual operation is explained to future staff.
- `manualSubtract(wallet, amount, note, staff)` — same shape but
  debits. The service throws if the wallet doesn't have enough
  balance to avoid going negative.
- `manualSet(wallet, amount, note, staff)` — overwrites the wallet
  balance to `amount` and records a `manual_set` row. Use sparingly:
  the audit trail still includes the previous balance via
  `balance_before`.

The cashback math above does not apply to manual operations — they
are explicit staff actions and never derive a value from a rate.
