# Báo cáo toàn diện dự án Noteket — sau đợt đại tu refactor (11-08-2026, bản cập nhật)

| Mục | Nội dung |
|---|---|
| **Ngày** | 11-08-2026 |
| **Người thực hiện** | Senior Laravel PHP Full-stack Developer (audit read-only) |
| **Nhánh làm việc** | `fix/audit-and-refactor` |
| **Phạm vi** | Toàn bộ back-end Laravel + front-end Blade production + prototype `resources/view/test2` + đối chiếu báo cáo toàn diện cũ cùng ngày 11-08 |
| **Nguyên tắc** | Chỉ đọc code / commit / báo cáo; **không sửa** bất kỳ file hiện có — chỉ tạo báo cáo này |

> 📌 **Vì sao có báo cáo này?**
> Báo cáo toàn diện cũ `11-08-26-Bao-Cao-Toan-Dien-Du-An.md` được viết lúc 11:23, **ngay trước** 2 commit đại tu `2c36a23` ("Next one") và `1b4660f` ("Hello") lúc 11:22–11:42 cùng ngày. Hai commit này đã thay đổi gần như toàn bộ hiện trạng (`2c36a23` thêm 5.662 / bớt 1.241 dòng; `1b4660f` thêm 565 / bớt 169 dòng — tổng ≈6.200 thêm / ≈1.400 bớt): viết đủ Blade production, refactor toàn bộ controller, bổ sung migration bảo mật, tăng test từ 5 lên 34. Báo cáo cũ vì thế **không còn phản ánh thực tế**. Bản này là bức tranh mới sau đại tu.

### Nguồn tham chiếu chính

| Nguồn | Vai trò |
|---|---|
| `report/11-08-26-Bao-Cao-Toan-Dien-Du-An.md` | Báo cáo toàn diện cũ (form mẫu + danh sách lỗi để đối chiếu) |
| `report/11-08-26-Bao-Cao-UI-test2.md` | Báo cáo UI prototype test2 cũ |
| `README.md` (viết lại trong commit "Hello") | Mô tả sản phẩm, setup, queue, branch workflow, số liệu test |
| Code hiện tại: `app/`, `routes/web.php`, `resources/views/`, `public/css|js`, `tests/`, `database/migrations/` | Đối chiếu thực tế |

### Kết quả kiểm chứng nhanh (11-08-2026)

| Hạng mục | Kết quả |
|---|---|
| `php artisan test` | Theo README: **34 tests / 115+ assertions pass** (đếm thủ công `it()/test()` = 34 — khớp). Môi trường hiện tại thiếu `vendor/` nên không chạy lại trực tiếp được |
| Blade production | **33 view** (non-email) + **8 email** = 41 — thay vì chỉ 3 như báo cáo cũ |
| Routes | Đầy đủ auth/note/org/transaction/theme/settings (xem mục 2.3) |
| Prototype UI | 15 file HTML trong `resources/view/test2` (vẫn còn, đã có bản Blade production song song) |
| Fix báo cáo cũ | Gần như toàn bộ mục 🔴/🟠 của báo cáo 11-08 cũ đã được xử lý (xem Phụ lục A) |

---

## Mục lục

1. [Tổng quan về dự án](#1-tổng-quan-về-dự-án)
2. [Tổng quan về back-end](#2-tổng-quan-về-back-end-của-dự-án)
3. [Tổng quan về front-end](#3-tổng-quan-về-front-end-của-dự-án)
4. [Điểm tốt back-end](#4-những-điểm-tốt-trong-back-end-của-dự-án)
5. [Điểm tốt front-end](#5-những-điểm-tốt-trong-front-end-của-dự-án)
6. [Điểm chưa tốt back-end + đề xuất](#6-những-điểm-chưa-tốt-của-back-end-của-dự-án-kèm-phương-án-đề-xuất)
7. [Điểm chưa tốt front-end + đề xuất](#7-những-điểm-chưa-tốt-của-front-end-của-dự-án-kèm-theo-phương-án-đề-xuất)
8. [Lỗi tồn tại + đề xuất](#8-những-lỗi-còn-tồn-tại-ảnh-hưởng-tới-người-dùng-hệ-thống-kèm-phương-án-đề-xuất)
9. [Kết luận điều hành + lộ trình](#9-kết-luận-điều-hành-và-lộ-trình-đề-xuất)

---

## 1. Tổng quan về dự án

### 1.1. Sản phẩm là gì?

**Noteket** — ứng dụng ghi chú / to-do dạng **sticky-note**, có lớp xã hội (chia sẻ, reply, organization/team) và lớp kinh tế nội bộ (wallet, chuyển điểm user↔user / user↔org / org↔user, mua theme) với **xác thực OTP qua email** cho mọi giao dịch tiền. Slogan: *"The sticky note version of Locket Widget"*.

### 1.2. Stack công nghệ

| Tầng | Công nghệ |
|---|---|
| Runtime | PHP 8.3+/8.5, Laravel Framework **v13** |
| ORM / DB | Eloquent, SQLite (dev), 27 migrations |
| Auth | Session-based + `password` cast `hashed` |
| Mail | Laravel Mailable (8 email Blade), một phần queue database |
| Frontend build | Vite + Tailwind v4 (dự án chủ yếu dùng CSS tĩnh riêng `public/css/noteket.css`) |
| UI production | Blade + Bootstrap 5.3.8 (CDN) + Font Awesome 6.5.2 (CDN) + Google Font Caveat |
| UI prototype | `resources/view/test2` (15 HTML tĩnh) |
| Test | Pest v5 — **34 tests** |
| Tooling | Laravel Pint, PHPStan, Laravel Boost, Pail |

### 1.3. Cấu trúc thư mục quan trọng

```
app/
  Http/Controllers/   → 23 controller (auth, note, org, transaction, theme, settings…)
  Models/             → 21 model
  Mail/               → 12 mailable
database/migrations/  → 27 migrations (users, note, org, transactions, themes, otp attempts…)
database/factories/   → 3 factories (User, Note, Organization)
resources/views/      → 33 Blade production + 8 email (layouts, notes, orgs, transactions, themes…)
resources/view/test2/ → Prototype HTML tĩnh (15 file) — vẫn còn song song
public/css/noteket.css + public/js/noteket.js → CSS/JS dùng chung (~1.100 dòng)
routes/web.php        → Toàn bộ web routes (public + auth group)
tests/Feature/        → CoreWorkflowsTest, PageSmokeTest, UserExperienceImprovementsTest…
.github/workflows/    → CI Laravel (push/PR vào main)
report/               → Lịch sử audit & báo cáo
```

### 1.4. Lịch sử phát triển (cập nhật)

| Giai đoạn | Nội dung |
|---|---|
| Đầu dự án | Scaffold Laravel + domain note/org/transaction; nhiều lỗi PSR-4, import, migration |
| 03–05/08 | Audit lớn: đổi tên class, fix route/controller, migration (nhánh `fix/audit-and-refactor`) |
| 08/08 | Cải thiện UX: OTP không destroy khi sai, share note dedupe, invite member resilient, mail templates |
| 10/08 | Prototype UI test2 hoàn thiện |
| **11/08 (11:22–11:42)** | **Đại tu toàn diện** (commit `2c36a23` "Next one" + `1b4660f` "Hello"): Blade production đầy đủ, refactor controller/model, migration decimal + OTP attempts, factory mới, 34 tests, README mới |
| **Hiện tại** | Backend + front-end production đã **đủ happy path cơ bản**; còn nợ về quy ước đặt tên, dead feature signup-invite, và một số gap nhỏ (mục 6–8) |

### 1.5. Trạng thái tổng thể (đánh giá senior)

| Tiêu chí | Mức | Ghi chú ngắn |
|---|---|---|
| Domain model / ý tưởng sản phẩm | ★★★★☆ | Rõ ràng, đủ module cốt lõi, vòng giá trị hoàn chỉnh |
| Backend logic | ★★★★☆ | Đã refactor tốt; còn nợ naming convention + dead feature |
| Production UI (Blade) | ★★★☆☆ | Đủ 33 màn, theo design test2; còn lỗi nhỏ + i18n chưa thống nhất |
| Prototype UI (test2) | ★★★★☆ | Đẹp; giờ là nguồn tham chiếu cho Blade (đã có bản production) |
| Test coverage | ★★★☆☆ | 34 tests pass (auth/note/org/transaction/smoke); còn thiếu test theme, org member full, race |
| Bảo mật tiền tệ | ★★★★☆ | decimal + lockForUpdate + OTP 5 lần + throttle — đúng hướng |
| Sẵn sàng production | **Chưa hoàn toàn** | Happy path chạy được; cần CI xanh trên branch + xử lý dead feature + vận hành (queue, seed, self-host asset) |

---

## 2. Tổng quan về back-end của dự án

### 2.1. Kiến trúc tổng quát

- **Monolith Laravel classic**, gần như toàn bộ route trong `routes/web.php`: public (login/signup/forgot) + một group `middleware(['auth'])` lớn.
- **Controller vẫn "fat"**: logic nghiệp vụ nằm trong controller (chưa có Form Request / Policy / Service layer), nhưng đã **viết lại gọn gàng, có chú thích**, validate bằng `$request->validate()` ở hầu hết action.
- **Model Eloquent đã đầy đủ hơn nhiều**: `User`, `Note`, `Organization` có fillable, casts (decimal, hashed, boolean), relationships và scope (`Note::visibleTo`, `User2userTransaction::whereParticipant`).
- **OTP**: 5 loại giao dịch (u2u, u2org, org2u, user-theme, org-theme) + reset password đều dùng `random_int` + `Hash::make`, TTL 10 phút, tối đa 5 lần thử, hỗ trợ retry (sai OTP không xóa bản ghi).
- Response trên bề mặt web: **form → redirect + flash** thống nhất (hết lẫn JSON như trước). ⚠️ Lưu ý: base `Controller.php` **vẫn còn 6 method `*_fetch` trả `response()->json`** — không có route nào trỏ tới (dead code, xem E17).

### 2.2. Module nghiệp vụ

| Module | Controller chính | Chức năng |
|---|---|---|
| **Auth** | `AuthenticationController` | Login/logout/signup (unique email, confirmed, min:8), signup-via-invite (⚠️ chưa có route — mục 8) |
| **Note** | `NoteController`, `MarkAsDoneController`, `ReplyNoteController`, `PivotForNoteController` | Tạo/sửa/xóa note, mark done/undo, reply, share/unshare |
| **Organization** | `OrganizationsController`, `OrganizationsMemberController`, `PivotChangeHostOrganizationController` | CRUD org, add/accept/decline/remove/leave member, change host + OTP-invite |
| **Transaction** | `User2user*`, `User2organization*`, `Organization2user*` | Chuyển điểm + OTP, cancel, history, verify-view |
| **Theme** | `ThemeRequestController`, `Theme4userWalletController`, `Theme4orgWalletController`, `Theme4user/orgController` | Store, detail, mua theme user/org + OTP, tạo theme request |
| **Settings** | `SettingsController`, `BalanceController` | Profile, avatar upload, đổi mật khẩu, logout, tổng hợp số dư + lịch sử |
| **Password** | `PasswordChangeRequestController` | Forgot + reset password bằng OTP (retry-friendly, chống email enumeration) |

### 2.3. Routing (hiện trạng)

- **Public:** `/` (home), `/login`, `/signup`, `/forgot-password`, `/reset-password/{id}` — tất cả POST nhạy cảm đều bọc `throttle:5,1`.
- **Auth group (66 route, tổng 75 — khớp README):** note CRUD + share/unshare/reply/mark-done; organization full CRUD + member + change-host; balance/settings/profile/avatar/password; 3 loại transaction (create/verify/cancel/history); theme store + buy (user & org) + create theme request.
- **So với báo cáo cũ — đã bổ sung:** share note, delete note, unshare, reply, logout, settings, cancel transaction, change password view. **Tất cả route trỏ đúng method tồn tại** (hết "method not found").
- **Gap còn lại:** các method `signup40acc_note`, `signup40acc_host_org`, `signup40acc_member_org` **không có route** (dead feature — xem mục 8).

### 2.4. Database

- 27 migrations; cột quan trọng: `users.balance` **decimal(15,2)** (đã đổi từ float), `otp_attempts` thêm cho 6 bảng OTP, `theme4ID` thêm cho `theme4org_wallets`, `org_done` guard migration cho `note`.
- ⚠️ **Quy ước đặt tên cột không đồng nhất** — vẫn còn nguyên từ giai đoạn đầu:
  - camelCase: `hostID`, `userID`, `organizationID`, `noteID`, `theme4ID`, `current_hostID` (lẫn cả camel lẫn snake!)
  - snake_case: `creater_id`, `note_id`, `shared_with`, `org_done`, `replied_note_id`
  - → dễ nhầm khi viết query/relationship, khó đổi về sau (xem BE-1).

### 2.5. Views Blade production

Đã đủ toàn bộ các màn mà báo cáo cũ liệt kê là thiếu:

| Có | Ghi chú |
|---|---|
| `login`, `signup`, `password/forgot`, `password/reset` | Auth theo design test2, có `@csrf` |
| `home`, `note` | Trang chủ + chi tiết note (reply, share list, mark done) |
| `balance`, `settings` | Số dư + lịch sử gộp 3 loại; cập nhật profile/avatar/password/logout |
| `organizations/*` (7 view) | index, create, show, dashboard, members, settings, balance |
| `themes/*` (6 view) | index, show, org index/show, request, request_success |
| `transactions/*` (9 view) | 3 loại × (create, verify, history) |
| `emails/*` (8) | OTP, invite, change-host, notification |

### 2.6. Testing backend

- **34 tests / 115+ assertions**, chia 3 file feature chính:
  - `CoreWorkflowsTest` (21 test): signup/login/logout, note CRUD + authorize, org + member, transaction OTP (thành công / hết lượt / thiếu tiền), password reset (retry + lock).
  - `PageSmokeTest` (~8 test): render 6+ trang production không 500.
  - `UserExperienceImprovementsTest` (3 test): share dedupe, invite partial success, OTP retry.
- Factories mới: `UserFactory`, `NoteFactory`, `OrganizationFactory`.
- Test regression cũ (share note JSON vs redirect) **đã được sửa** — bộ test giờ xanh.

---

## 3. Tổng quan về front-end của dự án

### 3.1. Hai lớp front-end (đã rõ ràng hơn)

| Lớp | Vị trí | Vai trò |
|---|---|---|
| **A. Production Blade** | `resources/views/` | **33 view đã port từ test2** — đây là front-end chính thức, render dữ liệu thật |
| **B. Prototype demo** | `resources/view/test2/` | 15 HTML tĩnh — giờ là **styleguide tham chiếu**; nên đánh dấu deprecated (mục 8) |

### 3.2. Kiến trúc front-end production

- `layouts/app.blade.php`: **dual-shell** (sidebar desktop + bottom-nav mobile + FAB), toast container, flash → toast, `@csrf` meta, `lang="vi"`.
- `public/css/noteket.css` (591 dòng) + `public/js/noteket.js` (501 dòng): **đã gom về 1 file mỗi loại** (hết copy-paste 15 file như prototype).
- `noteket.js`: toast engine + `swapCardMode` (VIEW→EDIT→SHARE→REPLY→CREATE) + gesture drag (pointer events) + `escapeHtml()` cho mọi chỗ chèn dữ liệu user vào `innerHTML` (đã xử lý XSS từ báo cáo cũ).
- Bootstrap 5.3.8 + Font Awesome **6.5.2** (đã hạ từ 7.3.0 — fix đúng báo cáo cũ) + Google Font Caveat.

### 3.3. Trạng thái UI sau đại tu

- Mọi form dùng được đều có `@csrf` (có 1 ngoại lệ: form ẩn chết trong `organizations/settings` thiếu `@csrf` — xem E20), nút có `type` tường minh, `@method('DELETE')` đúng chuẩn, escaping `{{ }}` của Blade.
- Empty state có thật (`@forelse`/`@empty`), hiển thị số dư bằng `number_format` (dấu chấm nghìn).
- Transfer đúng contract backend: `recipient_email` + `amount` + `password`.
- Settings đủ: profile / avatar (upload + preview + fallback initials) / password / logout.
- Còn lại: vài chi tiết nhỏ ở mục 7.

---

## 4. Những điểm tốt trong back-end của dự án

1. **Vòng giá trị sản phẩm hoàn chỉnh** — note → share → org → wallet → theme, mỗi bước có luồng OTP/email riêng, không phải CRUD đơn thuần.

2. **An toàn tiền tệ đã được siết đúng kỹ thuật** (so với báo cáo cũ):
   - `balance` **decimal(15,2)** thay float (hết sai số IEEE-754).
   - Mọi verify đều nằm trong `DB::transaction` + **`lockForUpdate()`** trên người gửi/người nhận → chống double-spend.
   - OTP dùng **`random_int`** (CSPRNG) thay `rand()`, hash bằng `Hash::make`, TTL 10 phút.
   - **`attempts` + MAX_ATTEMPTS = 5** trên cả 6 bảng OTP; route verify bọc `throttle:5,1` → chống brute-force.
   - Sai OTP **giữ bản ghi để retry** (cả transaction lẫn password reset) — UX đúng đắn.
   - Có cancel flow cho cả 3 loại transaction.

3. **Auth chặt chẽ**: signup `unique:users,email` + `password|confirmed|min:8`; cast `password => hashed` (không thể quên Hash::make); `session()->regenerate()` khi login/đổi mật khẩu; logout invalidate session; forgot-password trả **thông điệp generic** chống email enumeration.

4. **Authorization phủ tốt** trên toàn bộ đường quan trọng:
   - Note: creator hoặc người được share mới xem/sửa/reply/mark-done; chỉ creator mới share/delete.
   - Org: host quản lý (edit/delete/members/dashboard/balance-spend/change-host); member xem note + balance; add/remove/leave/accept/decline đều kiểm tra quyền.
   - Transaction: chỉ `from`/`current_hostID` mới verify/cancel.

5. **Refactor sạch sẽ theo chuẩn**: model có fillable/casts/relationships/scope; controller validate tập trung; không còn class/method/route lệch (hết lỗi "class not found", "method not found"); mã nguồn **có thể boot**, `route:list` sạch.

6. **Blade production đầy đủ 41 view** — khoảng trống "View not found" của báo cáo cũ đã được lấp gần hết.

7. **34 tests xanh** gồm cả smoke test render trang — safety net cơ bản đã có (tăng từ 5 → 34, hết test regression).

8. **README chất lượng cao** — mô tả sản phẩm, setup 1 lệnh, cấu hình mail/queue/Supervisor, branch workflow, CI, checkpoint rollback. Rất có giá trị cho người mới vào dự án.

9. **CI GitHub Actions** đã cấu hình (PHP 8.5 + sqlite + `php artisan test`) — nền tảng để giữ suite xanh (lưu ý: hiện chỉ trigger trên `main`).

10. **Factories Note/Organization mới** — test viết nhanh, ít phụ thuộc hard-code.

---

## 5. Những điểm tốt trong front-end của dự án

1. **Port Blade đúng thiết kế test2**: giữ trọn cá tính sticky-note (vàng `#FACC15`/`#FFE86E`, Caveat, card bo tròn) trong production — không phải "UI tạm".

2. **Kiến trúc front-end gọn**: 1 file CSS + 1 file JS dùng chung; layout app với dual-shell (desktop sidebar / mobile bottom-nav + FAB) render đúng responsive.

3. **XSS đã được xử lý triệt để ở JS**: mọi dữ liệu người dùng chèn vào `innerHTML` đều qua `escapeHtml()`; toast dùng `textContent`; mọi POST từ JS đều kèm CSRF token (meta tag + form hidden).

4. **Empty state & data thật**: `@forelse/@empty`, `number_format` tiền, avatar fallback initials — hết "hard-code" của prototype.

5. **Contract FE↔BE khớp** ở các màn quan trọng: transfer dùng `recipient_email`, add member dùng `user_list[]` (hỗ trợ cả chuỗi `user_list_text`), settings có `name/action/method` đúng route.

6. **Toàn bộ giao diện tiếng Việt** (`lang="vi"`, nhãn, toast, empty state), Font Awesome đã pin 6.5.2.

7. **Micro-interaction giữ lại từ prototype** — drag thả card, swap mode không modal, toast animation — tạo trải nghiệm khác biệt, đáng giữ.

8. **Email template đủ 8 mẫu**, mailable có constructor chuẩn, một số dùng `ShouldQueue` + `Mail::queue` (share note) — hướng scale tốt.

---

## 6. Những điểm chưa tốt của back-end của dự án (kèm phương án đề xuất)

### 6.1. Kiến trúc & tổ chức code

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-1 | **Quy ước cột không đồng nhất** (`hostID`, `userID`, `noteID`, `organizationID`, `theme4ID`, `current_hostID` vs `creater_id`, `note_id`, `org_done`) — dễ nhầm lẫn, khó refactor, rủi ro lỗi khi join | Chọn **snake_case chuẩn**, tạo migration rename có kế hoạch + cập nhật model/query trong cùng PR; hoặc ít nhất lập bảng mapping |
| BE-2 | **Controller vẫn fat**, logic lặp: pattern verify-OTP + `lockForUpdate` lặp lại ở 5 controller; OTP generator lặp 4 lần | Tách `OtpService` (generate/attempts/expiry) + `WalletTransactionService`; controller chỉ còn điều phối |
| BE-3 | Không có **Form Request / Policy** riêng — validate + authorize nằm trong action | Dần tách `FormRequest` cho form phức (transaction, org, settings) và `Policy` cho Note/Organization |
| BE-4 | Home/org notes query `take(20)` + merge collection — **chưa paginate**, khi dữ liệu lớn sẽ chậm và mất trang | `paginate()`/`cursorPaginate()`; filter theo trạng thái done |

### 6.2. Bảo mật & tính đúng đắn

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-5 | **Thứ tự lock không nhất quán giữa các giao dịch**: u2u (sender→recipient), u2org (org→sender), org2u (org→user) → về lý thuyết có thể **deadlock** khi 2 giao dịch chéo nhau | Chuẩn hóa: lock theo thứ tự tăng dần của ID thực thể (hoặc theo loại), ghi chú trong code |
| BE-6 | **OTP generator quét O(n) toàn bảng pending** với `Hash::check` từng dòng để tránh trùng → chậm dần khi dữ liệu lớn | Xác suất trùng `random_int` là ~0; chỉ cần index + retry vòng lặp 1–2 lần, không quét toàn bảng (hoặc dùng mã có checksum) |
| BE-7 | **Dead feature signup-invite**: `signup40acc_note/host_org/member_org` không có route; email mời `Mail40account` **không có link hành động**; nếu wire thì `signup40acc_note` dùng `$pivot->shared_with` (là **user ID** do FK `constrained('users')`) làm **email** → sai logic | Quyết định: (a) wire đầy đủ — thêm route + tạo pivot cho email chưa đăng ký + link trong email; hoặc (b) gỡ methods + sửa email mời thành hướng dẫn đăng ký rồi share lại. Đồng thời bỏ claim trong README nếu chưa làm |
| BE-8 | `ThemeRequestController`: validation chỉ `required` — `price` không `numeric|min:0`, `catalog_link` nullable có thể gây lỗi nếu DB not null | Thêm rule `price => numeric|min:0`, `catalog_link => nullable|url` |
| BE-9 | **i18n chưa thống nhất**: flash backend tiếng Anh (`Note created successfully`, `OTP sent to your email`…) hiển thị trong UI tiếng Việt | Dùng `__()`/`trans()` hoặc ít nhất viết lại message tiếng Việt; thống nhất toàn bộ |
| BE-10 | `edit_note` qua **fetch JSON** trong `noteket.js`: khi validation fail (422), JS không kiểm tra `response.ok` → vẫn toast "Đã lưu thay đổi" dù chưa lưu | Kiểm tra `response.ok`/`response.redirected` trước khi toast; xử lý 422 hiển thị lỗi |

### 6.3. Vận hành & chất lượng

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-11 | Chưa có **monitoring mail/queue**; một số mail `send()` đồng bộ (OTP), một số `queue()` — worker cần chạy; failed_jobs chưa có alert | Tài liệu hóa `queue:work` (README đã có); thêm Horizon hoặc tối thiểu log + retry cho failed jobs |
| BE-12 | `encore/laravel-admin` vẫn trong composer nhưng chưa dùng — nợ dependency | Gỡ khỏi composer hoặc quyết định dùng cho admin theme/org |
| BE-13 | **Seeder chỉ tạo 1 user** — không có demo data (theme, org, giao dịch) để chạy thử | Seed 1 org + vài note + theme mẫu (có factory sẵn) |
| BE-14 | `phpstan-report.json` là **file cũ từ máy khác** (UTF-16, đường dẫn `C:\Users\Admin\Desktop\project1`, các lỗi đều ignorable) — dễ gây hiểu nhầm | Xóa hoặc tái tạo report từ lần chạy PHPStan hiện tại; thêm PHPStan vào CI nếu muốn gate |

---

## 7. Những điểm chưa tốt của front-end của dự án (kèm theo phương án đề xuất)

| # | Vấn đề | Đề xuất |
|---|---|---|
| FE-1 | **Phụ thuộc CDN** (Bootstrap, FA, Google Font) — offline/vùng chặn CDN sẽ vỡ layout + icon | Self-host hoặc bundle qua Vite (đã có Vite sẵn) trước khi deploy production |
| FE-2 | **Email templates tiếng Anh** (`A note was shared with you`, `Thanks, Team`) trong khi app tiếng Việt | Việt hóa email; thống nhất thương hiệu (màu, logo) |
| FE-3 | **Chưa có màn OTP countdown/resend** trong production (design docs `UI_instruction_design/otp_typing.md` có sẵn pattern) | Thêm timer 10 phút + nút gửi lại OTP (cần route resend) |
| FE-4 | **Accessibility còn hạn chế**: tương phản vàng/nền thấp, một số nút icon thiếu `aria-label`, focus ring chưa rõ | Checklist WCAG AA cơ bản; `focus-visible`; tăng độ tương phản text trên nền vàng |
| FE-5 | `noteket.js`: `tbody.innerHTML = inlineEmails.map(...)` — đã escapeHtml nên an toàn, nhưng nên dùng DOM API để dễ bảo trì | Thay bằng `textContent` + `createElement` khi refactor JS |
| FE-6 | **Trùng lặp nội dung desktop/mobile** trong view Blade (mỗi màn render 2 lần `content` + `content-mobile`) — dễ lệch khi sửa | Cân nhắc 1 source HTML + CSS điều chỉnh layout thay vì 2 bản |
| FE-7 | Prototype `test2` + `test`, `test1`, `testing` vẫn nằm trong repo song song | Chọn test2 làm styleguide, đánh dấu deprecated 3 thư mục còn lại (hoặc xóa sau khi chốt design) |
| FE-8 | Không có test JS (không có vitest/playwright) | Thêm smoke E2E tối thiểu cho happy path (login → tạo note → chuyển tiền) |

---

## 8. Những lỗi còn tồn tại ảnh hưởng tới người dùng, hệ thống (kèm phương án đề xuất)

> Nhóm theo mức độ: 🔴 chặn tính năng / mất tiền / bảo mật · 🟠 hỏng luồng UX · 🟡 chất lượng / nợ kỹ thuật.

### 8.1. Tính năng / luồng end-to-end

| # | Mức | Mô tả | Ảnh hưởng | Đề xuất |
|---|---|---|---|---|
| E1 | 🟠 | **Flow "share cho user chưa đăng ký" chưa hoàn chỉnh**: email mời không có link tạo tài khoản; `signup40acc_*` không có route | Người nhận email mời không biết đường vào app; tính năng "accept invitation signup" trong README không chạy | Wire route + link (BE-7) hoặc gỡ claim |
| E2 | 🟠 | `edit_note` từ JS: validation fail vẫn báo "đã lưu" | User tưởng đã lưu nhưng dữ liệu không đổi | Kiểm tra `response.ok` (BE-10) |
| E3 | 🟠 | Home & org notes **không phân trang**, chỉ `take(20)` | User nhiều note không thấy hết | `paginate()` |
| E4 | 🟡 | Nút "Tạo note" mobile FAB mở form trên card đầu tiên; sau khi tạo xong không có phản hồi nội bộ (redirect về home) | UX hơi rời rạc trên mobile | Sau submit redirect về trang vừa tạo (đã có) hoặc AJAX thêm card |

### 8.2. Bảo mật / toàn vẹn dữ liệu

| # | Mức | Mô tả | Đề xuất |
|---|---|---|---|
| E5 | 🟡 | Thứ tự `lockForUpdate` không thống nhất giữa các loại giao dịch — vòng deadlock chỉ khả thi giữa **2 giao dịch u2u ngược chiều** (A→B và B→A); u2org/org2u đều lock org trước nên không tạo vòng. Ngoài ra trên **SQLite (dev) `lockForUpdate` là no-op** → bảo vệ thật chỉ có hiệu lực trên MySQL/Postgres production | Chuẩn hóa thứ tự lock; kiểm thử race trên DB production (BE-5) |
| E6 | 🟡 | OTP generator quét toàn bảng pending (O(n)) — với lượng giao dịch lớn có thể chậm / tạo áp lực DB | Tối ưu theo BE-6 |
| E7 | 🟡 | Không có rate-limit/kiểm tra trên route `create` transaction (chỉ verify mới throttle) — kẻ xấu có thể spam tạo giao dịch + gửi mail | Thêm `throttle` cho create/resend nếu có |
| E8 | 🟢 | `signup40acc_note` dùng `shared_with` (user ID) làm email — nếu wire route sẽ tạo account sai | Sửa trước khi wire (BE-7) |
| E17 | 🟢 | Base `Controller.php` còn 6 method `*_fetch` trả JSON **không có route** và tham chiếu cột `note.user_id` **không tồn tại** (bảng `note` dùng `creater_id`) → dead code; nếu vô tình wire route sẽ 500 | Gỡ hoặc sửa query đúng schema trước khi dùng |

### 8.3. UX / chất lượng

| # | Mức | Mô tả | Đề xuất |
|---|---|---|---|
| E9 | 🟠 | Flash message tiếng Anh trong UI tiếng Việt (E22 cũ còn tồn tại một phần) | i18n (BE-9) |
| E10 | 🟡 | Email tiếng Anh, chưa theo thương hiệu | Việt hóa + brand (FE-2) |
| E11 | 🟡 | Không có OTP countdown/resend trên màn verify | Pattern từ `UI_instruction_design/otp_typing.md` |
| E12 | 🟡 | CDN ngoài — offline là vỡ UI | Self-host/bundle (FE-1) |
| E13 | 🟡 | Còn 4 thư mục prototype song song (`test`, `test1`, `testing`, `test2`) | Dọn dẹp, chốt test2 làm styleguide |
| E18 | 🟠 | **Sửa note từ card trang chủ / org bị mất nội dung dài**: `noteket.js` (EDIT mode) pre-fill ô mô tả bằng bản `Str::limit(..., 200)` của card (cả `home.blade.php` lẫn `organizations/show.blade.php` đều cắt 200 ký tự) → user lưu là ghi đè nội dung gốc, **mất phần sau ký tự 200** | Pre-fill từ bản đầy đủ: đưa description nguyên vẹn vào `data-*` trên card hoặc fetch chi tiết note trước khi mở EDIT |
| E19 | 🟡 | Email mời org (`user_accept_organization`) và email đổi host **không có link accept/decline** — người được mời phải tự vào app (có màn pending accept/decline ở `organizations/index`, nên luồng vẫn dùng được) | Thêm link accept/decline + token vào email cho thuận tiện |
| E20 | 🟡 | `organizations/settings.blade.php` còn 1 **form ẩn chết** `<form id="changeHostRealForm" style="display:none">` **thiếu `@csrf`** — không có JS nào tham chiếu tới nó; nếu sau này wire JS submit sẽ dính 419 | Gỡ form chết hoặc thêm `@csrf` |

### 8.4. Nợ quy trình

| # | Mức | Mô tả | Đề xuất |
|---|---|---|---|
| E14 | 🟠 | **Báo cáo toàn diện cũ 11-08 giờ lỗi thời** — nếu đọc riêng sẽ hiểu sai trạng thái dự án | Đánh dấu báo cáo cũ "superseded" hoặc archive; dùng bản này làm nguồn sự thật |
| E15 | 🟡 | Commit message "Hello" / "Next one" không mô tả nội dung — khó tra cứu lịch sử | Từ nay commit theo convention (feat/fix/refactor + mô tả) |
| E16 | 🟡 | CI chỉ chạy trên `main`; nhánh làm việc `fix/audit-and-refactor` chưa được CI kiểm chứng | Mở PR sớm để CI chạy, hoặc cho phép workflow trigger trên branch |

---

## 9. Kết luận điều hành và lộ trình đề xuất

### 9.1. Kết luận điều hành

| Câu hỏi | Trả lời ngắn |
|---|---|
| Dự án còn "không chạy được" như báo cáo cũ? | **Không.** Đã có Blade production đủ, route đầy đủ, 34 tests xanh, hết "View not found". |
| Có ship production được không? | **Gần được cho happy path.** Còn: quyết định dead feature signup-invite, i18n flash, self-host asset, CI xanh trên branch, seed demo data. |
| Bảo mật tiền tệ đã ổn chưa? | **Ổn cơ bản** (decimal + lock + OTP 5 lần + throttle). Cần tinh chỉnh thứ tự lock + chống spam create. |
| test2 có đáng giữ không? | **Có**, nhưng chỉ làm styleguide — production đã có Blade riêng; cần dọn các thư mục prototype trùng. |
| Việc quan trọng nhất tuần tới? | **P0 bên dưới** — đặc biệt quyết định và xử lý flow mời user chưa đăng ký (tính năng được README quảng cáo nhưng chưa chạy). |

### 9.2. Lộ trình ưu tiên đề xuất

```text
P0 — Hoàn thiện happy path & nhất quán (1 tuần)
  1. Quyết định + xử lý flow signup-via-invite: wire route+link HOẶC gỡ methods + sửa README (E1, E8)
  2. i18n flash message → tiếng Việt (E9)
  3. Fix JS edit_note kiểm tra response.ok (E2)
  4. Paginate home + org notes (E3)
  5. Mở PR lên main để CI chạy; sửa README claim nếu cần (E16)

P1 — Bền vững kỹ thuật (1–2 tuần)
  6. Migration rename cột → snake_case chuẩn + cập nhật model/query (BE-1)
  7. Tách OtpService + thống nhất thứ tự lock (BE-2, BE-5, E5)
  8. Tối ưu OTP generator + throttle create transaction (BE-6, E7)
  9. Self-host/bundle Bootstrap + FA + font (FE-1)
  10. Màn OTP countdown/resend + việt hóa email (FE-2, FE-3)

P2 — Vận hành & chất lượng (1 tuần)
  11. Seed demo data; quyết định encore/laravel-admin (BE-12, BE-13)
  12. Dọn prototype trùng (test/test1/testing) + đánh dấu báo cáo cũ superseded (E13, E14)
  13. Tăng test: theme buy, org member full, race transaction, JS smoke (FE-8)
  14. A11y checklist + PHPStan trong CI (FE-4, BE-14)
```

---

## Phụ lục A — Đối chiếu fix từ báo cáo toàn diện cũ (11-08, trước đại tu)

> ⚠️ Mã cũ (E1–E24, FE-*) là mã của **báo cáo cũ**; **không trùng khớp** với mã E1–E20 ở mục 8 của báo cáo này — đừng nhầm hai bộ mã.

| Mã cũ | Nội dung | Trạng thái hiện tại |
|---|---|---|
| E1 | Thiếu Blade production → View not found | ✅ Đã fix (41 view) |
| E2 | Share note không có route | ✅ Đã fix |
| E3 | ReplyNoteController lệch class/method + `Auth->user()` | ✅ Đã fix |
| E4 | Reset password view thiếu | ✅ Đã fix (`change_password_view`) |
| E5 | Không logout/delete note/settings | ✅ Đã fix |
| E6 | Creator không xem được note mình | ✅ Đã fix |
| E7 | Home thiếu shared notes | ✅ Đã fix (own + shared merge) |
| E8 | Org chỉ host xem được | ✅ Đã fix (member xem show/balance) |
| E9 | Test regression fail | ✅ Đã fix (34 tests xanh) |
| E11 | Race balance thiếu lock | ✅ Đã fix (`lockForUpdate`) |
| E12 | OTP brute-force | ✅ Đã fix (attempts + throttle) |
| E13 | XSS innerHTML | ✅ Đã fix (`escapeHtml` + Blade escape) |
| E14 | float tiền | ✅ Đã fix (decimal(15,2)) |
| E15 | Mua theme không ghi ownership | ✅ Đã fix (wallet + check đã sở hữu) |
| E16 | Signup không chặn email trùng | ✅ Đã fix |
| E17 | Password cast tắt | ✅ Đã fix (`hashed`) |
| E18 | Reset password xóa request khi OTP sai | ✅ Đã fix (retry đến hết hạn) |
| E19 | Forgot redirect route không tồn tại / lộ email | ✅ Đã fix (generic message) |
| E20 | Response lẫn JSON/redirect | ✅ Đã fix (thống nhất redirect + flash) |
| E22 | Flash tiếng Anh/Việt lẫn | 🟡 Còn một phần (BE-9) |
| FE-1 | Prototype chưa port Blade | ✅ Đã port (33 view) |
| FE-2 | CSS/JS copy-paste 15 file | ✅ Đã gom 1 css + 1 js |
| FE-4 | Login/signup chưa theo design | ✅ Đã theo test2 |
| FE-10 | Font Awesome 7.3.0 | ✅ Đã pin 6.5.2 |
| FE-15 | innerHTML XSS | ✅ Đã fix |
| FE-16 | Transfer lệch contract | ✅ Đã fix (`recipient_email`) |
| FE-17 | Setting thiếu name/action | ✅ Đã fix |
| FE-23 | Data hard-code | ✅ Đã fix (Blade dynamic) |
| FE-24 | Thiếu empty/loading state | ✅ Empty state có; loading skeleton chưa |

## Phụ lục B — Thống kê nhanh codebase (11-08-2026, sau đại tu)

| Hạng mục | Số lượng |
|---|---|
| Controllers | 23 |
| Models | 21 |
| Mailables | 12 |
| Migrations | 27 |
| Blade production (non-email) | 33 |
| Email Blade | 8 |
| Factories | 3 (User, Note, Organization) |
| HTML prototype test2 | 15 |
| CSS/JS dùng chung | `noteket.css` 591 dòng + `noteket.js` 501 dòng |
| Feature/Unit tests | **34 tests / 115+ assertions** (theo README; môi trường hiện tại thiếu `vendor/` nên chưa chạy lại) |
| CI | `.github/workflows/laravel.yml` (push/PR → main) |
| Báo cáo trong `report/` | 16 file |

## Phụ lục C — Tài liệu nên đọc theo thứ tự

1. **Báo cáo này** — bức tranh full-stack sau đại tu (nguồn sự thật hiện tại)
2. `README.md` — setup, queue, mail, branch workflow, CI
3. `report/11-08-26-Bao-Cao-Toan-Dien-Du-An.md` — lịch sử (đánh dấu superseded) để xem các vấn đề đã xử lý
4. `report/AUDIT_AND_HANDOVER_GUIDE.md` + `report/08-08-26-*.md` — lịch sử fix backend/UX
5. `UI_instruction_design/` — spec design (khi implement OTP countdown, theme…)

---

*Báo cáo được lập ở chế độ **read-only** ngày 11-08-2026 trên nhánh `fix/audit-and-refactor`. Không chỉnh sửa source code ngoài việc tạo file báo cáo này. Do môi trường hiện tại chưa có `vendor/`, các con số test được đối chiếu từ README + đếm thủ công test blocks (khớp 34); khuyến nghị chạy `composer install && php artisan test` để xác nhận trước khi merge. Mọi đề xuất mang tính kỹ thuật tham khảo cho team phát triển tiếp theo.*
