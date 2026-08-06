# Create organization

**Route/view:** `/create-organization` → `create-organization`. **Dùng chung:** [base.md](base.md) D2/F.

```text
main.create-organization-page
├─ header: Back to Organization
└─ form.organization-form-card
   ├─ intro: h1 + Host role explanation
   ├─ field Name (required)
   ├─ field Description (required)
   ├─ info: Paper Pop is default (not a field)
   └─ footer: Cancel / Create organization
```

## Element contract

- Name autofocus; Description textarea with example. Both map exactly to controller `name`, `description`; label, required hint and server error directly below.
- “Bạn sẽ là Host” is static explanation; do not show Theme selector/logo uploader because current create controller has no field/behavior for either.
- Cancel follows safe Back. Submit disables twice, spinner says `Đang tạo…`; success redirects workspace id and toast. Validation/error retains text and focuses error.
- Empty form has no confirmation; dirty Back/Cancel asks “Bỏ thay đổi?” only when user entered a field.

| Element | Motion | 320–639 | ≥640 |
| --- | --- | --- | --- |
| form card | enter fade/translateY 8px 180ms | full width/lề 16; CTA stacked or safe sticky footer | max 640px; actions right aligned |
| fields/error | focus ring 120ms/error fade 160ms | textarea min 140px, keyboard-safe | textarea min 160px |
| submit | spinner, no resize | full width if actions stack | intrinsic width |

