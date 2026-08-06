# Base — hệ thống UI dùng chung

> Mọi file page phải áp dụng file này trước. Đặc tả riêng chỉ được bổ sung, không được ghi đè mâu thuẫn.

## A. Cơ sở thiết kế bắt buộc

- **Product:** note-to-do mobile-first, cảm giác gần gũi như Locket nhưng không phải chat hay project-management; note là đơn vị chính. Balance là credit nội bộ cho theme; Organization là lớp cộng tác nhỏ.
- **Prototype bắt buộc giữ:** Paper Pop, note giấy có ghim/góc gấp, create-note và current-note đặt cạnh nhau khi desktop, filter trên cùng, sidebar/rail desktop và bottom navigation mobile. Swipe là shortcut có nút/keyboard/Undo tương đương.
- **Sketch tay:** mobile chỉ có một tờ giấy ở giữa; thanh trên gồm Home, filter trạng thái, avatar; thanh đáy Note/Org/Theme. Desktop có filter trạng thái + organization trên đầu, rail trái, *tờ Create* bên trái và *tờ Current note* bên phải. Không thay bằng dashboard/card grid nặng.
- **Nguồn chi tiết:** `report/06-08-26 uiux_report.md`, `report/04-08-26 overview_report.md`, `prompt&skill/06-08-26 skilluiux.md`, `resources/testing_view/index.html`, `resources/testing_view/testing2.html`.

## B. Token, chữ và kích thước

| Token Paper Pop | Giá trị | Dùng cho |
| --- | --- | --- |
| `--canvas` | `#F8F7F2` | nền ứng dụng |
| `--surface` | `#FFFFFF` | nav, dialog, panel |
| `--note` | `#FFF4A8` | giấy note |
| `--ink` / `--muted` | `#20212A` / `#676875` | chữ chính/phụ |
| `--accent` | `#4F46E5` | CTA, focus, active |
| `--success/warning/danger` | `#15803D/#B45309/#B91C1C` | trạng thái semantic |

- Theme chỉ thay token visual (`canvas/surface/ink/accent/note/radius/shadow/drag_type`); không đổi nghĩa Done, Pending, Error và không hạ contrast dưới 4.5:1.
- Font `Inter, system-ui, sans-serif`; body 16px/1.5, label 14px, metadata 12–14px. Chỉ một `h1`; title note tối đa 2 dòng ở list và đầy đủ ở detail.
- Nhịp spacing 4, 8, 12, 16, 24, 32, 48px. Target tương tác ≥44×44px. Radius: 12px control, 20px card/dialog. Không dùng khoảng trắng bằng margin chồng chéo; sibling dùng `gap`.

## C. Breakpoint và vùng hiện diện

| Tên | Width | Shell | Element bắt buộc hiện | Element ẩn/chuyển vị trí |
| --- | ---: | --- | --- | --- |
| Mobile | 320–639 | topbar + bottom nav | một note, action text, FAB/tạo note | sidebar, cột phụ, create panel cố định |
| Tablet | 640–1023 | icon rail + topbar | note chính; form/dialog 560px max | nhãn rail chỉ tooltip; panel phụ xuống dưới nếu mỗi cột <320px |
| Desktop | ≥1024 | sidebar 248px + main | create paper + current paper song song, sidebar label | bottom nav/FAB mobile |

- Nội dung main max 1280px, lề `16/24/48px` theo breakpoint. Không khóa zoom hay scroll body. Dành đáy mobile `88px + env(safe-area-inset-bottom)`.
- 360px, 768px, 1280px là ba kích thước kiểm thử tối thiểu; không có overflow ngang trừ bảng được chuyển list card.

## D. Cây shell và element chung

```text
body
├─ a.skip-link → #main
├─ .app-shell
│  ├─ aside.sidebar (tablet/desktop) | nav.bottom-nav (mobile)
│  │  ├─ Brand/Home
│  │  ├─ Nav: Note, Organization, Theme, Balance
│  │  └─ Avatar button → account drawer
│  ├─ header.topbar
│  │  ├─ context/back button
│  │  ├─ page title or filters
│  │  └─ one primary action + overflow when needed
│  └─ main#main
├─ #toast-region[aria-live=polite]
└─ modal/drawer portal (khi mở)
```

### D1. Navigation

- Nav item gồm icon, text, hit area; route hiện tại có `aria-current="page"`, nền accent nhạt và text accent. Không dùng icon/màu một mình.
- Sidebar: avatar button mở account drawer; focus item đầu khi mở; Esc/overlay/nút X đóng và focus quay avatar. Các menu Profile/Settings/Your theme chưa có route thì **không link**: hiển thị disabled `Sắp có` hoặc không render.
- Mobile bottom nav có đúng Note, Org, Theme, Balance theo sketch/prototype; không che CTA. Create là FAB/topbar action, không phải tab thứ năm.

### D2. Button, form, feedback

| Element | Con | Trạng thái/thao tác | Motion |
| --- | --- | --- | --- |
| Button primary | icon tùy chọn + text + spinner | click/Enter submit; loading disabled chống gửi đôi | hover background 160ms; spinner không làm nhảy width |
| Button secondary/danger | text rõ ràng | secondary navigation; danger chỉ action phá hủy | hover/focus 160ms, không nảy |
| Field | label, input, hint, error | label click focus; server error dưới field; password toggle | focus ring opacity 120ms; không shake cả form |
| Modal/sheet | title, description, body, action row | focus trap; Esc/overlay/close; return focus | overlay fade 160ms, panel translateY 220ms; reduced-motion = fade tức thì |
| Toast | status text, optional Undo | 5s; Undo rollback; aria-live polite | slide/fade 200ms, không cover bottom nav |

- Inputs: `autocomplete` đúng loại, password toggle có accessible name, OTP `inputmode=numeric` + paste toàn mã. Client validation chỉ gợi ý; server response là nguồn lỗi cuối.
- Loading: skeleton đúng diện tích; Empty: icon/illustration nhỏ + lý do + CTA; Error: nội dung dễ hiểu + Retry. Không đổi dữ liệu/số dư trước khi server xác nhận trừ tác vụ có Undo/rollback.

## E. Paper note component (dùng ở Home/Organization/detail)

```text
article.note-paper[aria-label]
├─ button.pin-menu (chỉ khi có action)
├─ header.note-paper__head
│  ├─ scope badge
│  └─ creator + timestamp
├─ section.note-paper__body
│  ├─ h2/h3 title
│  └─ p description
├─ footer.note-paper__foot
│  ├─ textual gesture hint (list only)
│  └─ button.corner-action (submit/create OR view detail)
└─ .paper-fold[aria-hidden]
```

- Note list dùng `overflow: hidden`, line-clamp; detail không clamp. Ghim là anchor trực quan nhưng **không** là sole menu: menu button có aria-expanded, các action như Edit/Done/Delete/Reply/Share tùy quyền.
- Gấp góc chỉ trang trí; nút trong góc có hit target 44px và text accessible. Không attach swipe vào input, textarea, button, link, select, label hoặc pin menu.

### E1. Gesture contract không được thay đổi tùy page

- Theme Paper Pop: kéo ngang **trái** = `Để sau`, **phải** = `Hoàn thành`; threshold 92px. `pointerdown` giữ một pointer; chỉ bắt đầu transform khi `abs(dx)>10`; `touch-action: pan-y` giữ scroll dọc.
- Khi kéo 0–91px: card translateX/rotate nhẹ, preview badge bên tương ứng opacity 1 sau 32px. Khi thả dưới 92px hoặc `pointercancel`: spring về vị trí cũ 200ms. Khi ≥92px: disable action, card exit theo hướng kéo 260ms, note kế vào 180–240ms, toast Undo 5s.
- Button `Để sau`/`Hoàn thành`, phím ←/→ (khi focus không nằm trong form), và menu pin là các thay thế bắt buộc. Cập nhật `aria-live` sau action. `prefers-reduced-motion` không translate/rotate; đổi card tức thì và vẫn toast.
- Không swipe ở detail khi đang đọc nội dung dài. Không dùng swipe dọc khác contract của Paper Pop; `drag_type` theme khác chỉ được áp dụng khi theme đã có định nghĩa và preview rõ.

## F. Accessibility, quyền và hiệu năng

- Có skip link, header/nav/main/aside semantic, heading tuần tự, focus-visible 3px accent, text status + icon. Table mobile thành list label. Không `innerHTML` cho note/reply người dùng; Blade dùng `{{ }}`.
- Host/member/pending/owner phải hiện text badge. UI ẩn action không được server cho phép; server vẫn là kiểm tra cuối. Unauthorized/not found là page state có Back Home, không phải màn rỗng.
- Animate duy nhất transform/opacity, không animate `height/left/top/filter`; duration 120–320ms, 60fps. `will-change: transform` chỉ lúc dragging, xóa khi kết thúc. Không auto-refresh/reorder card lúc user đang kéo, gõ hoặc mở menu.
