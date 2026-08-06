# Theme Store — catalogue, preview, buy và apply

**Entry:** Theme nav; GET catalogue/ownership/apply contract still required. **POST source:** personal/org purchase + OTP verify. **Dùng chung:** [base.md](base.md) B–F.

## 1. Cây element

```text
main.theme-store
├─ header: h1 + Request theme link
├─ section.store-toolbar
│  ├─ balance badge
│  └─ tablist Personal / Organization
├─ section.theme-grid
│  └─ article.theme-card ×n
│     ├─ miniature theme preview
│     ├─ name, description, drag type, scope, price
│     ├─ ownership/applied badge
│     └─ Preview / Buy / Apply action
├─ preview drawer
└─ purchase sheet (password → OTP → receipt)
```

## 2. Card/action contract

- Preview is a miniature **Home card + note + button/input** using theme tokens, not a single color block. State text: `Đang áp dụng`, `Đã sở hữu`, price/`Miễn phí`, scope `Cá nhân` or `Tổ chức`, and drag type. Color alone never communicates state.
- Scope tab: Personal default. Organization disabled with explanatory text if no org selected or user is not host; no click path that inevitably fails.
- Preview drawer: page samples using CSS variables only; `Đóng`, and optional demo gesture. Preview never changes persisted current theme or balance. Motion starts only after user opens it; reduced-motion static.
- Buy sheet step 1 shows theme, scope, price, current/after balance, password; sends server transaction. Step 2 follows success only: masked email, `passkey`, timer, Verify. Step 3 receipt refetches ownership/balance. Error remains in current step.
- Apply is enabled only when server supplies ownership/apply endpoint. Until then, UI says `Đã mua — chưa thể áp dụng trong phiên bản hiện tại`; do not fake application by changing whole app token.

## 3. Motion and responsive

| Element | Animation | 320–639 | 640–1023 | ≥1024 |
| --- | --- | --- | --- | --- |
| scope tabs | indicator 160ms | full width two cells | intrinsic | intrinsic |
| theme grid | cards fade 160ms after load | 1 col | 2 col | 3 col |
| preview | drawer fade/slide 220ms | bottom sheet | side/center 560px | side drawer 480–560px |
| purchase | step crossfade 180ms | bottom sheet safe-area CTA | modal | modal |
| mini preview hover | transform 120ms | no hover; focus press state | hover/focus | hover/focus |

Keep success/warning/danger semantic tokens even in paid theme previews. Content has loading/empty/error states because source catalogue GET does not exist yet.

