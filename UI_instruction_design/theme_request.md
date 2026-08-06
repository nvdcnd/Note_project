# Theme request — brief form và kết quả

**Route/view:** `/create/theme/request` → `create_theme_request`; success route uses same design state. **Dùng chung:** [base.md](base.md) D2/F.

```text
main.theme-request-page
├─ header: Back Theme Store
├─ section.request-layout
│  ├─ form.request-card
│  │  ├─ intro (h1 + brief guidance)
│  │  ├─ Name field
│  │  ├─ Description field
│  │  ├─ Style field
│  │  ├─ drag_type select
│  │  ├─ price number + xu
│  │  ├─ catalog_link optional
│  │  ├─ email (guest only) / readonly account email
│  │  └─ Submit
│  └─ aside.brief-tips (desktop)
└─ success state (same card)
```

## Contract

- Required by controller: `name`, `description`, `style`, `drag_type`, `price`; optional `catalog_link`. Style is its own textarea, not silently merged into description. Guest email is shown because controller uses it when unauthenticated; authenticated user sees readonly delivery email.
- Price is numeric ≥0 and label clarifies budget/credit unit, not a payment now. Drag select explains behavior in human text, but only list options supported by future theme contract.
- Submit has spinner/disable. Redirect back flash or success `{id}` changes same card to success summary: request id, submitted name, email, `Về Theme Store`, `Gửi yêu cầu khác`; no standalone success layout. Field error preserves other data/focus.

| Element | Motion | 320–639 | 640–1023 | ≥1024 |
| --- | --- | --- | --- | --- |
| form card | fade/translateY 180ms | full width, textarea ≥140px | max 720px | 720px form + 280px tips aside |
| select/field errors | focus 120ms/error fade 160ms | stacked | stacked | stacked |
| success state | crossfade 180ms, retain card location | CTA stack | inline when fits | inline |

No color picker/custom CSS upload: request stores a brief, not executable style. Close/back with dirty form asks discard confirmation.

