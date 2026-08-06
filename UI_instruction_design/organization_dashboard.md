# Organization dashboard và member management

**Routes/views:** `/organization/dashboard/{id}` → `organization.dashboard`; `/current/member` → `organization.current_member`; `/pending/member` → `organization.pending_member`. **Dùng chung:** [base.md](base.md).

## 1. Dashboard tree (chỉ Host)

```text
main.organization-dashboard
├─ header: Back workspace + “Bảng điều hành”
├─ section.dashboard-intro: h1, updated time
├─ section.stat-grid
│  └─ stat card ×4 (Open notes, Done, Members, Pending)
└─ section.dashboard-panels
   ├─ progress panel (only truthful source data)
   ├─ pending invitation preview → Members tab
   └─ current theme/balance panel (when DTO exists)
```

- Each stat card is a link only if it has destination; otherwise static. Source currently derives `done_note=0`; never render a fictitious completion percentage. Explain `Chưa có dữ liệu tiến độ` instead.
- No swipe action anywhere in dashboard. Host unauthorized redirects/state to workspace.

## 2. Member list is one design with two filters

```text
main.member-management
├─ header Back Dashboard
├─ tablist: Active (n) / Pending (n)
└─ list/table
   └─ member row: avatar, name/email, status, invited/accepted time, overflow action
       └─ remove / resend / cancel only where route exists
```

- Current and pending routes select the matching tab, never use two visually different pages. `aria-selected` and tab focus work with arrow keys.
- Remove member → confirmation modal; accepting/declining invitation is only available in contextual invitation flow, because current routes are POST and lack a safe GET landing screen. Pending “resend/cancel” remains unavailable until endpoints exist.

## 3. States/motion/responsive

| Area | Motion | 320–639 | 640–1023 | ≥1024 |
| --- | --- | --- | --- | --- |
| stat grid | count only changes after response; fade 160ms | 1 col | 2 col | 4 col |
| panels | no chart auto animation | stack | 2 col only if 320px each | 3 col |
| tabs | indicator 160ms | horizontally scroll only if labels cannot fit | normal | normal |
| member data | skeleton/fade 160ms | labeled cards | cards/table by width | table + overflow menu |

No real-time reorder. Show loading/empty/error on each data group; after mutation refetch counts/list.

