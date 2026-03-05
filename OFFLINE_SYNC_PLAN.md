# Offline Sync Plan (POS)

Current state:
- PWA cache is enabled (pages/assets open without internet).
- Full transactional offline mode for Livewire actions is not implemented yet.

To achieve full "work offline + auto sync to DB" reliably, implement:

1. Move critical POS operations to API commands.
- Open/close shift
- Add/remove cart item
- Discount item/cart
- Checkout/return
- Debt payment
- Expense add

2. Create local action queue in IndexedDB on client.
- Each action has `uuid`, `type`, `payload`, `created_at`, `status`.
- UI applies optimistic update from local state.

3. Add sync endpoint with idempotency.
- Server accepts array of actions.
- Uses `uuid` to avoid duplicates.
- Processes actions in order and returns per-action result.

4. Add conflict strategy.
- Out-of-stock handling
- Closed shift handling
- Product deleted/changed handling

5. Add background sync worker.
- Flush queue when internet returns.
- Retry with exponential backoff.

6. Add audit logs and reconciliation view.
- Show unsynced actions
- Allow manual retry / discard

