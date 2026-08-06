# Overview, page inventory và quyết định tích hợp

## 1. Nguồn chân lý và thứ tự ưu tiên

1. Route/controller/model/schema hiện tại quyết định **function, field, quyền và route** có thật.
2. `report/06-08-26 uiux_report.md`, `report/04-08-26 overview_report.md`, `prompt&skill/06-08-26 skilluiux.md` quyết định **product direction/UX contract**.
3. `resources/testing_view/index.html`, `testing2.html`, `resources/view/testing/*` quyết định **Paper Pop, shared components, swipe và responsive baseline**.
4. Sketch tay người dùng cung cấp quyết định **information architecture của Home**: mobile một note; desktop hai paper; status/org filter top; rail/bottom nav.
5. Nếu xung đột: function/permission luôn theo source; visual/interaction theo sketch + report. Không tự phát minh endpoint/field để khớp prototype.

## 2. Danh sách page cần thiết

| Page file | Route/view thực tế | Vai trò | Functions được nhúng thay vì page mới |
| --- | --- | --- | --- |
| [base.md](base.md) | shared | token, shell, note, motion, a11y | nav, drawer, modal, toast, pin menu |
| [authentication.md](authentication.md) | login/signup/forgot/reset | đăng nhập/tài khoản | show password, alert, OTP reset cùng form flow |
| [home.md](home.md) | `/`/`welcome` | note-to-do cá nhân và shared | create note paper/sheet, filter, pin actions, Undo, account drawer |
| [view_note.md](view_note.md) | `/note/{id}`/`note` | đọc và trao đổi một note | edit sheet, share sheet, delete confirm, reply composer |
| [organization.md](organization.md) | `/organization/{id}`/`organization` | workspace chung | create org-note, invite, edit org, leave/delete, host-transfer sheet |
| [create_organization.md](create_organization.md) | `/create-organization` | tạo workspace | validation/success inline; không cần success page |
| [organization_dashboard.md](organization_dashboard.md) | dashboard/current/pending member | admin/host overview | member list là tab/filter chung; remove/accept/decline confirm inline |
| [wallet.md](wallet.md) | 3 create/verify/history routes | credit & transaction history | create form + OTP step + receipt trong cùng shell; không tạo 6 UI khác nhau |
| [theme_store.md](theme_store.md) | Theme entry cần GET; buy POST | preview/mua/apply theme | preview drawer, password → OTP purchase sheet |
| [theme_request.md](theme_request.md) | create request/success | gửi design brief | success state trong form, không trang success độc lập |

## 3. Chức năng không cần tạo view riêng

| Function/source | Vị trí UI tích hợp | Vì sao và state cần có |
| --- | --- | --- |
| create/edit/delete note, mark/undo, reply | Home/Note detail | tạo/edit = paper modal; delete = confirm; done = swipe/button + Undo; reply = composer, không mở page con |
| filter done/undone + organization | topbar Home/Organization | select/segmented controls cập nhật deck/query, không phải view |
| share note/pin radial actions | detail hoặc pin menu Home | menu anchored; share/open edit là sheet; chỉ hiện action server cho phép |
| invite/accept/decline/remove member | Organization/member tab | invitation editor & confirm; email deep-link cần route/token trước khi action thật |
| host transfer | Organization management sheet | choose email → confirm → pending; accept/decline từ email context |
| user↔user, user↔org, org↔user transaction | Wallet sheet/route template | form step 1 → password/server create → OTP step 2 → receipt/history; route verify render cùng pattern |
| buy personal/org theme | Theme Store sheet | preview không trừ tiền; password → server request → OTP; state pending/error/success inline |
| create theme request success | theme request form | redirect/flash mở success summary, không thêm page layout |
| profile/settings/your-theme menu | account drawer disabled/hidden | prototype có menu nhưng source không có route/data mutation; không tạo view giả |

## 4. Trạng thái thiếu hoặc rủi ro source phải hiển thị trung thực

- Controller tạo/delete/fetch note chưa có route trong `web.php`; UI chỉ mô tả integration contract, không được giả rằng click hoạt động.
- Theme Store/Wallet overview thiếu GET/read route; purchase/apply/ownership có field không nhất quán. UI cần server DTO trước khi production.
- Email invitation/reset/OTP hiện không đủ GET action/templates ở mọi trường hợp; deep-link chỉ thiết kế sau khi server làm route an toàn.
- Report cũ mâu thuẫn một phần với working tree hiện tại. Đặc tả theo source hiện tại; report chỉ dùng cho product/UX direction, không khẳng định bug cũ còn tồn tại.

## 5. Tiêu chí nghiệm thu design trước code

- Page tree phải map được tới HTML semantic; mỗi action có trigger, feedback, error và vị trí focus sau thao tác.
- Test 360/768/1280px, keyboard-only, mouse/touch swipe, reduced-motion, loading/empty/error/success/pending và host/member/pending.
- Không có UI action thiếu function/route mà lại trông như hoạt động; đặt `Sắp có` hoặc nêu dependency backend.

