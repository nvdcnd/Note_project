# Organization — workspace, note team và quản trị nhanh

**Route/view:** `/organization/{id}` → `organization`. **Dùng chung:** [base.md](base.md) toàn bộ; reuse Home paper/deck, không tạo UI task board mới.

## 1. Cây element

```text
main.organization-page
├─ header.topbar: org name / Create note / management overflow
├─ section.organization-hero
│  ├─ banner-or-fallback + logo-or-initial
│  ├─ h1, description, Host badge
│  └─ action row (Invite, Dashboard, Manage)
├─ nav.org-tabs: Notes / Members (+ pending count host)
├─ section.tab-panel--notes
│  └─ same Home workspace constrained to this org
├─ section.tab-panel--members
│  └─ member list/cards + host management actions
├─ invite sheet; edit-org sheet; host-transfer sheet
└─ leave/delete confirmation modal
```

## 2. Element action contract

| Element | Children | Người thấy | Thao tác/kết quả |
| --- | --- | --- | --- |
| Hero | banner, fallback initial, name/description, host badge | mọi người có quyền | banner/logo URL lỗi → fallback; không layout shift |
| Notes tab | create paper/sheet, current paper, queue | member theo API | create scope khóa organization `{id}`; no org switch trong form |
| Members tab | avatar/name/email/status/time/menu | host: full; member: own/basic | active/pending text badge; member không tự thấy/quản lý data nhạy cảm |
| Invite | email chip input, validation list, Send | host | maps `user_list`; remove duplicate; no-account reports dependency signup invite |
| Manage | Edit, Transfer host, Delete | host | Edit sheet; transfer 2 step; delete typed-name confirm |
| Leave | explanation | non-host member | danger confirm → Home success; host sees Transfer host first, matching controller |

- Tab is a real tablist (`aria-selected`, `aria-controls`) or URL query; preserve selected tab on refresh. `Notes` default. Dashboard only host; never invoke member data by guessing role.
- Invite/host transfer actions sent from email must open a safe contextual endpoint before accept/decline; source lacks the GET deep links, so mail copy can say `Cần route lời mời` rather than render broken CTA.
- Member removal and org deletion say cascade consequence plainly. Do not rely on red alone.

## 3. Animation and continuity

| Element | Trigger | Animation | Guardrail |
| --- | --- | --- | --- |
| banner/logo | load | 160ms fade only | reserve aspect height, no CLS |
| tab indicator/panel | tab change | indicator translate 160ms; panel fade 120ms | no slide that moves focus; keyboard arrows supported |
| member rows | refetch | skeleton→fade 160ms | do not reorder while menu open |
| invite chips | add/remove | opacity/scale 120ms | invalid chip has text error, not shake |
| transfer state | submit | sheet content crossfade 180ms | remains pending until server response |
| destructive modal | open/close | base modal | never immediate delete |

## 4. Responsive matrix

| Width | Hero/actions | Notes | Members |
| --- | --- | --- | --- |
| 320–639 | banner max 180px; Invite primary, Manage overflow | one current note, create sheet | stacked cards, bottom-sheet menus |
| 640–1023 | actions wrap; Dashboard secondary | same home tablet rules | table only if columns fit; otherwise cards |
| ≥1024 | hero text/action 2 columns | create/current two papers from Home | table with action menu; management sheets max 560px |

Missing/not-authorized route must show safe state with `Về Home`, not an empty organization shell.

