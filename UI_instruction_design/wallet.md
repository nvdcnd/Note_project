# Wallet — balance, transfer, OTP và history

**Routes/views:** ba nhóm `user2user`, `user2organization`, `organization2user` create/verify/history. **Dùng chung:** [base.md](base.md) D2/F. Wallet overview GET chưa có; đây là contract cho lúc có DTO.

## 1. Một shell thay vì sáu màn hình khác nhau

```text
main.wallet-page
├─ header: h1 Ví cá nhân / context Organization + primary Transfer
├─ section.balance-summary
│  ├─ available balance (server value)
│  └─ actions: Transfer / Theme store
├─ section.transaction-history
│  ├─ filter type/status/date (when API supports)
│  └─ responsive history list/table
└─ transaction sheet
   ├─ step 1 transfer form
   ├─ step 2 OTP confirmation
   └─ step 3 success receipt
```

| Type | Step 1 fields source expects | Scope/permission | Step 2/3 |
| --- | --- | --- | --- |
| User → User | recipient `to`, `amount`, password | signed-in user; cannot self-send | OTP `passkey` → personal history |
| User → Organization | `organizationID`, `amount`, password | signed-in user | OTP → corresponding history |
| Organization → User | `userID`, `amount`, password | organization host only | OTP → organization history |

Email autocomplete must resolve a user id before submit; do not submit an email where controller expects id. Do not invent Message field because it is not stored. Amount uses numeric keypad, min >0 but server validates balance/identity.

## 2. Detail contract

- **Balance summary:** label “Số dư khả dụng”, amount with `xu`, last refreshed timestamp; loading is `—` skeleton, never `0`; error has Retry. Org balance appears inside its org context, not an extra primary nav.
- **History row/card:** type, counterparty, created time, signed amount, text badge Pending/Finished/Cancelled; entire item opens detail only when GET data exists. Mobile card has labels, desktop accessible table.
- **Transfer step 1:** title explicitly says direction, fields, read-only review (from/to/amount), password reveal, `Tiếp tục gửi mã`, Back/Cancel. Submit disables to prevent two pending transactions.
- **OTP step 2:** recap, masked recipient email, six-digit `passkey`, timer only if expiry received, `Xác nhận`; Cancel only if endpoint works. Do not show OTP before server has created transaction and sent email.
- **Receipt step 3:** success icon/text, transaction id/time/amount/from/to, `Xem lịch sử`/`Xong`; refetch balance/history only after server success. Wrong/expired OTP retains context, focus OTP and explains next safe action.

## 3. Motion and responsive

| Element | Animation | 320–639 | 640–1023 | ≥1024 |
| --- | --- | --- | --- | --- |
| balance amount | number crossfade 160ms after server response; no count-up | summary stack; Transfer full width | action inline | summary/action split |
| history | skeleton→fade 160ms | labeled cards | cards/table by space | table |
| sheet steps | crossfade 180ms, preserve focus/title | bottom sheet ≤90dvh, keyboard-safe | max 560px dialog | 560px dialog + optional review aside |
| OTP input/error | focus/error fade only | 1 field or 6 cells, paste works | same | same |

Credit transfers are not core product loop according to product report; label as advanced/experimental if retained, never present as cash withdrawal/deposit. No money animation before confirmed server response.

