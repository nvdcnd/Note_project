# Báo cáo toàn diện dự án Noteket (Note Project)

| Mục | Nội dung |
|---|---|
| **Ngày** | 11-08-2026 |
| **Người thực hiện** | Senior Laravel PHP Full-stack Developer (audit read-only) |
| **Nhánh làm việc** | `fix/audit-and-refactor` |
| **Phạm vi** | Toàn bộ back-end Laravel + front-end demo `resources/view/test2` + đối chiếu các báo cáo lịch sử (đặc biệt báo cáo UI 11-08-2026) |
| **Nguyên tắc** | Chỉ đọc code / commit / báo cáo; **không sửa** bất kỳ file hiện có — chỉ tạo báo cáo này |

### Nguồn tham chiếu chính

| Nguồn | Vai trò |
|---|---|
| `report/11-08-26-Bao-Cao-UI-test2.md` | Báo cáo UI mới nhất (hôm nay) — prototype test2 |
| `report/AUDIT_AND_HANDOVER_GUIDE.md` | Audit backend 05-08 + bàn giao |
| `report/08-08-26-*-report.md` | Cải thiện UX: OTP retry, share note, member invite, mail |
| `report/Bao_Cao_Kiem_Tra_Source_Code.md` / `Bao_Cao_Cac_Loi_Da_Sua.md` | Lịch sử lỗi ban đầu & đã sửa |
| Code hiện tại: `app/`, `routes/web.php`, `resources/view/test2/`, `resources/views/`, `tests/` | Đối chiếu thực tế |

### Kết quả kiểm chứng nhanh (11-08-2026)

| Hạng mục | Kết quả |
|---|---|
| `php artisan route:list` | ~62 routes biên dịch được |
| `php artisan test --compact` | **4 passed, 1 failed** (test share note kỳ vọng redirect nhưng controller trả JSON) |
| Blade production | Chỉ `login`, `signup`, `welcome` + 8 email templates |
| Prototype UI | 15 file HTML trong `resources/view/test2` (đã dọn lỗi 🔴 một phần ngày hôm nay) |

---

## Mục lục

1. [Tổng quan về dự án](#1-tổng-quan-về-dự-án)
2. [Tổng quan về back-end](#2-tổng-quan-về-back-end-của-dự-án)
3. [Tổng quan về front-end](#3-tổng-quan-về-front-end-của-dự-án)
4. [Điểm tốt back-end](#4-những-điểm-tốt-trong-back-end-của-dự-án)
5. [Điểm tốt front-end](#5-những-điểm-tốt-trong-front-end-của-dự-án)
6. [Điểm chưa tốt back-end + đề xuất](#6-những-điểm-chưa-tốt-của-back-end-của-dự-án-kèm-phương-án-đề-xuất)
7. [Điểm chưa tốt front-end + đề xuất](#7-những-điểm-chưa-tốt-của-front-end-của-dự-án-kèm-theo-phương-án-đề-xuất)
8. [Lỗi tồn tại / ảnh hưởng UX–hệ thống + đề xuất](#8-những-lỗi-tồn-tại-quanh-dự-án-hoặc-những-lỗi-ảnh-hưởng-tới-trải-nghiệm-người-dùng-hệ-thống-kèm-theo-phương-án-đề-xuất)

---

## 1. Tổng quan về dự án

### 1.1. Sản phẩm là gì?

**Noteket** (còn gọi Note Project) là ứng dụng ghi chú / to-do mang cảm hứng **sticky-note × Locket Widget**:

- **Đơn vị trung tâm:** note (ghi chú / việc cần làm), có thể chia sẻ, trả lời, đánh dấu hoàn thành.
- **Lớp xã hội nhỏ:** share note theo email, mời thành viên, organization (team).
- **Lớp cá nhân hóa & kinh tế nội bộ:** theme (user/org), wallet/balance, giao dịch điểm (user↔user, user↔org, org→user) có xác thực OTP qua email.

Slogan trong prototype: *"The sticky note version of Locket Widget"*.

### 1.2. Stack công nghệ

| Tầng | Công nghệ |
|---|---|
| Runtime | PHP 8.3+ / 8.5, Laravel Framework **v13** |
| ORM / DB | Eloquent, SQLite (dev) + migrations (~23 file) |
| Auth | Session-based (`Auth::attempt`, `Hash`) |
| Mail | Laravel Mailable + queue (database driver) |
| Frontend build | Vite, Tailwind CSS v4 (chưa dùng sâu cho UI app) |
| UI prototype | HTML tĩnh + Bootstrap 5.3.8 + Font Awesome + Google Font Caveat |
| Test | Pest v5 (hiện ~5 tests) |
| Tooling | Laravel Pint, PHPStan, Laravel Boost |
| Khác | `encore/laravel-admin` (có trong composer, chưa dùng thực sự) |

### 1.3. Cấu trúc thư mục quan trọng

```
app/
  Http/Controllers/   → ~21 controller (auth, note, org, transaction, theme…)
  Models/             → ~21 model
  Mail/               → ~12 mailable
database/migrations/  → schema users, note, org, transactions, themes…
resources/
  views/              → Blade production (login/signup/welcome + emails/)
  view/test2/         → Prototype UI demo (15 HTML) ← trọng tâm front-end
routes/web.php        → Toàn bộ HTTP routes (~60+)
tests/Feature/        → ExampleTest + UserExperienceImprovementsTest
report/               → Lịch sử audit & báo cáo
UI_instruction_design/→ Design specs markdown
```

### 1.4. Lịch sử phát triển (tóm tắt theo báo cáo & commit)

| Giai đoạn | Nội dung |
|---|---|
| Đầu dự án | Scaffold Laravel + domain note/org/transaction; nhiều lỗi PSR-4, import, migration |
| ~03–05/08 | Audit lớn: đổi tên class, fix route/controller, migration, PHPStan 0 error trên branch `fix/audit-and-refactor` |
| ~08/08 | Cải thiện UX backend: OTP không destroy khi sai, share note dedupe, invite member resilient, mail templates |
| ~10–11/08 | UI demo `test2` (Final UI demo), dọn lỗi prototype (CSRF, code chết, ID trùng, route create note…) |
| **Hiện tại** | Backend logic khá đầy đủ về mặt “có code”, nhưng **thiếu Blade production** → hầu hết GET route sẽ `View not found`. Prototype test2 đẹp nhưng **chưa nối** backend. |

### 1.5. Trạng thái tổng thể (đánh giá senior)

| Tiêu chí | Mức | Ghi chú ngắn |
|---|---|---|
| Domain model / ý tưởng sản phẩm | ★★★★☆ | Rõ ràng, đủ module cốt lõi |
| Backend logic | ★★★☆☆ | Có luồng, đã audit; còn lỗ hổng bảo mật & gap route/view |
| Production UI (Blade) | ★☆☆☆☆ | Gần như chưa có |
| Prototype UI (test2) | ★★★★☆ | Đẹp, đầy màn hình; chưa production-ready |
| Test coverage | ★☆☆☆☆ | Rất mỏng; 1 test regression đang fail |
| Sẵn sàng production | **Không** | Cần port Blade + fix gap bảo mật/route + nối contract FE–BE |

---

## 2. Tổng quan về back-end của dự án

### 2.1. Kiến trúc tổng quát

Backend theo mô hình **Laravel monolith classic**:

- **Routing:** gần như toàn bộ trong `routes/web.php` (closure GET view + POST controller), bọc `middleware(['auth'])` cho khu vực sau login.
- **Controller fat:** logic nghiệp vụ nằm trực tiếp trong controller (không có Service / Form Request / Policy / API Resource).
- **Model Eloquent:** 1 model / 1 bảng domain; một số quan hệ đã khai báo (`User::notes()`, `Note::creater()`…), nhiều model còn “trống” (`Organization` không `$fillable` / relationships).
- **Mail OTP:** hầu hết thao tác nhạy cảm (chuyển tiền, mua theme, đổi host, mời member, reset password) gửi OTP/link qua email.
- **Không có API versioning / SPA backend** — contract hiện tại là form POST + redirect (một số chỗ trả JSON lẫn lộn).

### 2.2. Module nghiệp vụ

| Module | Controller chính | Chức năng |
|---|---|---|
| **Auth** | `AuthenticationController` | Login, signup, signup-via-invite (share note / host org / member) |
| **Note** | `NoteController`, `MarkAsDoneController`, `ReplynoteController`, `PivotForNoteController` | Tạo/sửa note, mark done, reply, share |
| **Organization** | `OrganizationsController`, `OrganizationsMemberController`, `PivotChangeHostOrganizationController` | CRUD org, mời/accept/decline/remove member, đổi host |
| **Transaction** | `User2user*`, `User2organization*`, `Organization2user*` | Chuyển điểm + OTP 10 phút |
| **Theme** | `ThemeRequestController`, `Theme4userWalletController`, `Theme4orgWalletController` | Yêu cầu theme, mua theme user/org + OTP |
| **Password** | `PasswordChangeRequestController` | Forgot password + đổi mật khẩu bằng OTP |

### 2.3. Routing (hiện trạng)

- **Public:** `/`, `/login`, `/signup`, forgot/reset password.
- **Auth group:** note, org dashboard/members, 3 loại transaction, theme buy/request, change host…
- **Đã bổ sung gần đây (11-08):** `POST /create/note`, `POST /create/note/organization/{id}`.
- **Thiếu route quan trọng (xem mục 6 & 8):**
  - Share note (`PivotForNoteController@share_note`) — **không có route**
  - Delete note, Logout, Update profile/avatar
  - `change_password_view` được khai báo route nhưng **method không tồn tại** trên controller

### 2.4. Database

- ~23 migrations; bảng chính: `users` (có `balance` float, avatar, theme), `note`, `pivot_for_note`, `organizations`, `organizations_members`, các bảng `*transactions*`, theme user/org, `replynotes`, `mark_as_dones`, `password_change_requests`.
- Convention đặt tên **không đồng nhất** (camelCase cột `hostID` / `organizationID` / `creater_id` / `note_id` lẫn snake_case).
- Tiền tệ dùng **`float`** cho `balance` — không phù hợp domain tài chính.

### 2.5. Views Blade production

| Có | Thiếu (route đang trỏ tới) |
|---|---|
| `welcome`, `login`, `signup` | `note`, `organization`, `organization.dashboard`, `current_member`, `pending_member` |
| `emails/*` (8 template) | Toàn bộ transaction views, theme request views, reset password view |

→ **Hệ quả:** user đăng nhập xong vẫn vào home; hầu hết deep-link GET sau auth sẽ ném `View [xxx] not found`.

### 2.6. Testing backend

- `UserExperienceImprovementsTest`: 3 scenario (share dedupe, invite member, OTP retry).
- **1 test fail** vì `share_note` đổi sang `response()->json(...)` trong khi test vẫn `expect($response->isRedirect())->toBeTrue()`.
- Không có feature test cho auth, balance race, authorization, delete org cascade…

---

## 3. Tổng quan về front-end của dự án

### 3.1. Hai lớp front-end

| Lớp | Vị trí | Vai trò |
|---|---|---|
| **A. Production Blade** | `resources/views/` | Login/signup sơ sài; welcome mặc định Laravel (rất dài, không theo design Noteket) |
| **B. Prototype demo** | `resources/view/test2/` | Bộ UI đầy đủ 15 màn — **đây là front-end “thật” về mặt thiết kế**, nhưng tĩnh, chưa Blade |

> Ghi chú: thư mục là `resources/view` (số ít) — **không** phải `resources/views` của Laravel. Prototype **không** được engine Blade render trừ khi copy/port.

### 3.2. Cấu trúc `resources/view/test2`

```
test2/
├── index.html          Login / Sign up (modal)
├── home.html           Inbox note + drag/swipe card
├── balance.html        Ví user + modal Transfer
├── setting.html        Cài đặt user
├── org/
│   ├── index.html      Danh sách tổ chức
│   ├── home.html       Notes trong org
│   ├── dashboard.html  Thống kê org
│   ├── balance.html    Ví org
│   ├── member.html     Thành viên
│   ├── setting.html    Cài đặt org / đổi host
│   └── theme.html      Theme org
└── theme/
    ├── index.html      Theme store (user)
    ├── view.html       Chi tiết theme
    └── org/            Theme store & view cho org
```

### 3.3. Stack & phong cách UI (test2)

- **Bootstrap 5.3.8** (CDN) + **Font Awesome 7.3.0** (CDN) + **Caveat** (chữ viết tay).
- Màu thương hiệu: vàng `#FACC15` / `#FFE86E`, hover hồng `#FFC0CB`.
- Layout **dual-shell:** desktop sidebar trái + topbar; mobile bottom-nav + FAB; breakpoint `992px`.
- Interaction đặc trưng: **kéo-thả note card** (xoay, scale, overlay “Buông để Hoàn thành / Lưu”), `swapCardMode` (VIEW → EDIT → SHARE → REPLY → CREATE) không mở modal.
- Dữ liệu **hard-code** (`Org ID.3`, `$300`, John Doe, dummyimage…).

### 3.4. Trạng thái sau đợt sửa 11-08-2026 (theo báo cáo UI)

Đã làm: route create note, xóa ~3740 dòng code chết, `@csrf` 31 form, fix ID trùng, href rỗng, thẻ `</h3>` sai, thống nhất tiếng Việt UI, empty/loading state, title trang.

Còn lại (~17 hạng mục): XSS `innerHTML`, contract transfer/profile, `syle=`, `rows="2.5"`, `type="button"` thiếu, FA version, WebKit prefix, port Blade, v.v.

### 3.5. Design docs song song

- `UI_instruction_design/*` và `report/06-08-26 uiux_report.md` mô tả design system “Paper Pop” (Inter, indigo primary) — **khác** palette Caveat/vàng của test2.
→ Hiện có **hai hướng thẩm mỹ**; cần thống nhất trước khi port production.

---

## 4. Những điểm tốt trong back-end của dự án

1. **Domain business đủ “xương sống sản phẩm”**  
   Note + share + org + wallet + theme + OTP tạo một vòng giá trị hoàn chỉnh (capture → share → team → personalize), không chỉ CRUD note đơn thuần.

2. **Đã qua vòng audit lớn (05-08)**  
   PSR-4, import, route/method mismatch, migration order, mailable constructor, PHPStan 0 error — codebase **có thể boot** và `route:list` sạch, khác hẳn giai đoạn “CRITICAL SYSTEM FAILURE” ban đầu.

3. **Bảo vệ khu vực sau login bằng middleware `auth`**  
   Hầu hết route nhạy cảm nằm trong group auth; login có `session()->regenerate()`.

4. **Luồng giao dịch có xác thực 2 lớp (password + OTP email, TTL 10 phút)**  
   Pattern này đúng hướng cho thao tác trừ tiền; OTP được hash (`Hash::make`) không lưu plain text.

5. **Đã bọc `DB::transaction` cho verify giao dịch** (commit “Add DB::transaction for all transaction feature”)  
   Giảm rủi ro mất tiền một phía khi lỗi giữa chừng (dù vẫn thiếu `lockForUpdate` — xem mục 6).

6. **UX backend được cải thiện có chủ đích (08-08)**  
   - OTP sai **không xóa** transaction → user retry được.  
   - Share note / invite member: **dedupe + partial success** (bỏ qua email invalid, không fail cả batch).  
   - Mail templates thật trong `resources/views/emails/`.

7. **Authorization cơ bản có mặt ở nhiều chỗ**  
   Ví dụ: chỉ host edit/delete org; mark-as-done chỉ creator hoặc người được share; unshare chỉ creator.

8. **Validation đầu vào tối thiểu trên hầu hết action**  
   `title/description required`, `password/to/amount required`, `user_list required`… — nền tảng để siết rule chặt hơn.

9. **Có regression test cho 3 luồng UX quan trọng**  
   Hướng đi đúng (Pest + RefreshDatabase + Mail::fake), dù coverage còn mỏng và 1 test lệch contract.

10. **Mailable + queue mail**  
    Share note dùng `Mail::queue` — hướng scale tốt hơn `send` đồng bộ.

---

## 5. Những điểm tốt trong front-end của dự án

1. **Ý tưởng UI rõ, có cá tính thương hiệu**  
   Sticky-note + Caveat + vàng giấy nhớ tạo cảm giác “Noteket” khác biệt app note công cụ khô khan.

2. **Phủ đủ information architecture sản phẩm**  
   15 màn map gần hết domain: auth, home notes, balance, settings, org (list/home/dashboard/member/balance/theme/setting), theme store/detail (user & org).

3. **Responsive được thiết kế có chủ đích (không “co cột tự nhiên”)**  
   Desktop sidebar vs mobile bottom-nav + FAB — đúng mindset product mobile-first / dual platform.

4. **Micro-interaction phong phú, gần sản phẩm thật**  
   Drag physics, overlay hướng dẫn, toast, animation đổi mode card — giảm friction so với form CRUD cổ điển.

5. **Pattern `swapCardMode` thay modal cho note actions**  
   Phù hợp mobile (ít bước, giữ context trên card) — đây là insight UX tốt, nên giữ khi port Blade.

6. **Đợt dọn 11-08 nâng chất lượng prototype rõ rệt**  
   Hết code chết gây nhiễu, hết nhiều lỗi chết script, form có `@csrf`, ID duy nhất, empty/loading placeholder, UI tiếng Việt thống nhất, sidebar link nội bộ.

7. **Phân tầng màn theo quyền (host vs member) đã được mock**  
   Org dashboard / change host / set theme tách khỏi view member — nền tảng tốt cho policy UI.

8. **Bootstrap làm nền tảng ổn định**  
   Modal, form, grid, dropdown sẵn — rút ngắn thời gian prototype và dễ port sang Blade component.

9. **Có design instruction markdown** (`UI_instruction_design/`, báo cáo UIUX 06-08)  
   Giúp team/AI sau này không “đoán” layout khi implement production.

10. **Login/signup Blade production đã có `@csrf`**  
    Dù UI sơ sài, ít nhất form auth production không dính 419 CSRF ngay từ đầu.

---

## 6. Những điểm chưa tốt của back-end của dự án (kèm phương án đề xuất)

### 6.1. Kiến trúc & tổ chức code

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-1 | **Fat controller + closure route** chứa query/authorize/view — khó test, khó tái sử dụng | Tách Form Request, Policy, Action/Service; GET list/detail chuyển sang controller method |
| BE-2 | **Không có API Resource / versioning**; response lẫn redirect HTML và JSON trong cùng flow | Thống nhất: web form → redirect+flash; nếu cần SPA/mobile → API riêng `/api/v1` |
| BE-3 | Model thiếu relationships / `$fillable` (vd `Organization`) | Bổ sung `members()`, `notes()`, `host()`, fillable, casts |
| BE-4 | Đặt tên cột/class không convention (`creater_id`, `hostID`, `ReplynoteController` vs import `ReplyNoteController`) | Chuẩn hóa snake_case DB + alias/migration rename có kế hoạch; PSR-4 strict |

### 6.2. Bảo mật & tiền tệ

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-5 | **`balance` kiểu `float`** — sai số IEEE | Đổi `decimal(15,2)` hoặc integer “xu”; luôn format qua money helper |
| BE-6 | Giao dịch **không `lockForUpdate()`** trên user/org balance → race condition 2 tab | Trong `DB::transaction`: lock row sender + recipient trước khi ± balance |
| BE-7 | OTP dùng **`rand()`** (không CSPRNG); quét O(n) toàn bảng pending để tránh trùng | `random_int(100000,999999)` + `exists()` một query; rate-limit verify |
| BE-8 | **Không giới hạn số lần thử OTP** → brute-force 6 số | Cột `attempts`, khóa sau 5 lần; cooldown resend |
| BE-9 | Mua theme verify OTP **không tạo `Theme4userWallet` / ghi sở hữu**; có thể mua lại theme đã có | Kiểm tra ownership; tạo wallet record; idempotent finish |
| BE-10 | Signup **không unique email validation**, không `password_confirmation`, `remember` bắt buộc `required` (checkbox bỏ tick = fail) | Rule `email|unique:users`, `password|confirmed|min:8`; `remember` optional boolean |
| BE-11 | Login **không throttle** | `throttle:5,1` middleware trên login/OTP/forgot |
| BE-12 | `User` casts **không hash password** (`// 'password' => 'hashed'` bị comment) — dễ quên Hash::make ở chỗ mới | Bật cast `hashed`; review mọi chỗ gán password |
| BE-13 | `decline_member` chỉ set `status=false` → record pending “ma” | Soft state `declined` hoặc `delete()` |
| BE-14 | `delete_organization` **không cascade** member/notes/transactions | DB FK cascade hoặc service xóa theo thứ tự trong transaction |
| BE-15 | Authorize xem note: creator **không** vào được note của chính mình nếu không có pivot; nhánh unauthorized **không return** rõ (rơi rỗng) | Cho phép `creater_id === Auth::id()`; else `abort(403)` |

### 6.3. Route / method gap

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-16 | **`PivotForNoteController@share_note` không có route** → share note không gọi được từ web | `POST /share/note/{id}` + middleware auth + authorize creator |
| BE-17 | **`delete_note_request` không route**; không logout / update profile | Thêm route + controller methods tương ứng |
| BE-18 | Route `password.reset.view` trỏ `change_password_view` — **method không tồn tại** | Implement view method hoặc bỏ route GET |
| BE-19 | Route reply: import `ReplyNoteController` + method `reply_note` nhưng class thật là `ReplynoteController::replynote` + **`Auth->user()` syntax error** | Đổi tên PSR-4 chuẩn, sửa `Auth::user()`, đồng bộ route |
| BE-20 | `user2user_transaction_cancel` **bị comment** nhưng route vẫn trỏ method | Uncomment & refactor, hoặc gỡ route |
| BE-21 | Welcome link `/dashboard` (nếu còn) / nhiều view name không tồn tại | Port Blade từ test2 hoặc stub view tạm + feature flag |

### 6.4. Validation & logic nghiệp vụ

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-22 | `amount` chỉ `required`, không `numeric\|min:1`; recipient không check lúc create | Validate sớm; fail fast nếu `to` không tồn tại / balance không đủ |
| BE-23 | `create_note_in_organization` **không kiểm tra** user là member/host của org | Policy: must be active member |
| BE-24 | `delete_note_request` xóa theo pivot id, **không check** ownership rõ trước destroy chain | Authorize rồi xóa note + pivots + mark_as_done trong transaction |
| BE-25 | History transaction query `orWhere` **không group** → có thể lộ transaction người khác khi kết hợp điều kiện khác | `(from = me OR to = me)` trong group; scope model |
| BE-26 | Password reset sai OTP **destroy request** → user phải forgot lại từ đầu (trái với hướng retry của transaction) | Giữ request, tăng attempts, hết hạn mới xóa |

### 6.5. Chất lượng & vận hành

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-27 | Test coverage thấp; **1 test fail** do lệch contract JSON vs redirect | Sửa test **hoặc** thống nhất response; thêm test race/authz/balance |
| BE-28 | Không có Horizon/monitoring mail fail; queue database cần worker | Document `queue:work`; failed_jobs alert |
| BE-29 | `laravel-admin` trong composer nhưng chưa integrate | Gỡ dependency hoặc scaffold admin theme/request duyệt |

---

## 7. Những điểm chưa tốt của front-end của dự án (kèm theo phương án đề xuất)

### 7.1. Kiến trúc front-end

| # | Vấn đề | Đề xuất |
|---|---|---|
| FE-1 | **Prototype nằm ngoài Blade** (`resources/view` ≠ `resources/views`) → zero integration | Port 15 file → Blade layouts/components; xóa dần HTML tĩnh hoặc giữ làm styleguide |
| FE-2 | **CSS/JS copy-paste** ~400–900 dòng × 15 file | `resources/css/noteket.css` + `resources/js/noteket.js` (toast, drag, swapCardMode); Vite bundle |
| FE-3 | **Hai design language** (test2 vàng/Caveat vs UI_instruction indigo/Inter) | Chọn 1 design system; document token CSS variables |
| FE-4 | Production `login`/`signup` Blade **không theo design** Noteket | Thay UI bằng port từ `test2/index.html` |

### 7.2. Chất lượng HTML/CSS/JS (còn lại sau 11-08)

| # | Vấn đề | Đề xuất |
|---|---|---|
| FE-5 | `syle=` typo trong `index.html` | Sửa `style=` |
| FE-6 | `rows="2.5"` không hợp lệ | `rows="3"` |
| FE-7 | `textarea value="Hello"` không hoạt động | Nội dung giữa thẻ `<textarea>…</textarea>` |
| FE-8 | Nhiều nút thiếu `type="button"` → submit form ngoài ý muốn | Thêm `type="button"` / `type="submit"` tường minh |
| FE-9 | `--webkit-backdrop-filter` sai prefix | `-webkit-backdrop-filter` |
| FE-10 | Font Awesome **7.3.0** — cần xác minh CDN; rủi ro icon vỡ | Pin FA 6.5.x stable nếu 7.x không serve |
| FE-11 | `<th>` không bọc `<tr>`; một số div không cân | Chạy HTML validator; fix structure |
| FE-12 | `lang="en"` trong khi UI tiếng Việt | `lang="vi"` |
| FE-13 | Caveat cho **toàn bộ body** → khó đọc nội dung dài / a11y | Caveat cho brand/title; body dùng Inter / Be Vietnam Pro |
| FE-14 | Accessibility: thiếu `aria-label`, focus ring, contrast | Checklist WCAG AA; focus-visible; alt mô tả |

### 7.3. Bảo mật & contract với backend

| # | Vấn đề | Đề xuất |
|---|---|---|
| FE-15 | **`innerHTML` với dữ liệu user** (edit/share email list) → XSS khi có data thật | `textContent` / `escapeHtml` / DOM APIs |
| FE-16 | Modal Transfer **không khớp** backend (`password`, `to` = user id, `amount`) — FE gửi email + point_value | Redesign form theo contract; lookup email→id qua endpoint |
| FE-17 | Setting forms **thiếu `name` / `action` / method** | Gắn route profile update + avatar upload |
| FE-18 | Org setting “Change Host” dùng modal ID trùng ngữ nghĩa share | Modal id riêng `#changeHostModal` |
| FE-19 | Add member chưa gửi `user_list[]` đúng format | Form array + POST `/share/organization/{id}` |
| FE-20 | Delete/Set theme/Áp dụng còn `href="#"` giả | Form POST + `@csrf` + `@method` tới route thật |
| FE-21 | Signup thiếu `password_confirmation` | Thêm field khớp validation backend |
| FE-22 | Không có màn OTP countdown / resend trong test2 (dù design docs có) | Port pattern `otp_typing` + poll/resend route |

### 7.4. UX dữ liệu & trạng thái

| # | Vấn đề | Đề xuất |
|---|---|---|
| FE-23 | Toàn bộ data hard-code | `@foreach` từ controller; `number_format` balance |
| FE-24 | Empty/loading chỉ placeholder CSS `display:none` | Wire thật: empty khi collection rỗng; skeleton khi fetch |
| FE-25 | Đơn vị tiền lẫn `$`, points, xu | Một đơn vị “điểm/xu” + formatter |
| FE-26 | Không phân quyền UI runtime (host-only actions hiện với mọi mock) | `@can` / Blade `@if($isHost)` |
| FE-27 | Ảnh dummyimage / gstatic phụ thuộc ngoài | Storage avatar/logo; fallback initials |

---

## 8. Những lỗi tồn tại quanh dự án hoặc những lỗi ảnh hưởng tới trải nghiệm người dùng, hệ thống (kèm theo phương án đề xuất)

> Nhóm theo mức độ: 🔴 chặn hệ thống / mất tiền / bảo mật · 🟠 hỏng luồng UX · 🟡 chất lượng / nợ kỹ thuật.

### 8.1. Lỗi / khoảng trống chặn trải nghiệm end-to-end

| # | Mức | Mô tả | Ảnh hưởng UX / hệ thống | Đề xuất |
|---|---|---|---|---|
| E1 | 🔴 | **Hầu hết Blade view production không tồn tại** trong khi route GET đã khai báo | Click sâu sau login → 500 View not found | Port test2 → Blade theo lộ trình 5 giai đoạn (báo cáo UI mục 8.4) |
| E2 | 🔴 | **Share note không có route** dù controller đã viết | Tính năng “xã hội” cốt lõi không dùng được qua HTTP | Thêm `POST /share/note/{id}` + test feature |
| E3 | 🔴 | **Reply note route/method/class lệch + `Auth->user()` invalid** | Reply 500 / class not found (Linux case-sensitive) | Chuẩn hóa `ReplyNoteController`, method `reply_note`, `Auth::user()` |
| E4 | 🔴 | **Reset password GET view method thiếu** | User bấm link email reset → error | Implement `change_password_view` + Blade form |
| E5 | 🔴 | **Không có logout / xóa note / update profile** trên route map | User kẹt session; setting UI “có nút” nhưng backend im lặng | Bổ sung route + controller; invalidate session |
| E6 | 🔴 | Creator **không xem được note của mình** (logic `/note/{id}`) | Tạo note xong redirect `route('note', id)` có thể fail authz | Sửa điều kiện authorize (mục BE-15) |
| E7 | 🟠 | Home chỉ `Note::where(creater_id)->take(5)` — **không** shared notes, không pagination, không filter done | Inbox “câm” / thiếu note được share | Query `whereHas` shared + mark status; paginate |
| E8 | 🟠 | Org page **chỉ host** xem được; member bị từ chối | Member invite accept xong không vào workspace | Phân quyền view: host full, member notes + limited settings |
| E9 | 🟠 | Test regression share note **fail** (JSON vs redirect) | CI đỏ / mất niềm tin vào safety net | Align contract; chạy lại `UserExperienceImprovementsTest` |
| E10 | 🟠 | Prototype & backend **lệch contract form** (transfer, member, setting) | “UI đẹp nhưng bấm là hỏng” khi ghép | Bảng mapping field FE↔BE trước khi port; contract test |

### 8.2. Lỗi bảo mật / toàn vẹn dữ liệu ảnh hưởng hệ thống

| # | Mức | Mô tả | Ảnh hưởng | Đề xuất |
|---|---|---|---|---|
| E11 | 🔴 | Race condition balance (thiếu lock) | Double-spend / số dư âm | `lockForUpdate` + validate amount > 0 |
| E12 | 🔴 | OTP brute-force không rate-limit | Chiếm giao dịch pending | attempts + throttle + lockout |
| E13 | 🔴 | XSS tiềm ẩn trên prototype `innerHTML` khi gắn data thật | Session hijack / defacement | Escape mặc định Blade `{{ }}`; cấm raw user HTML |
| E14 | 🟠 | `float` money | Sai lệch số dư theo thời gian | decimal/integer |
| E15 | 🟠 | Mua theme không ghi ownership / có thể trừ tiền nhiều lần về lý thuyết | User mất tiền, không có theme | Wallet row + unique (user, theme) |
| E16 | 🟠 | Signup không chặn email trùng | Lỗi SQL unique / account conflict | `unique:users,email` |
| E17 | 🟡 | Password cast hashed bị tắt | Hồi quy bảo mật khi dev quên Hash::make | Bật cast |

### 8.3. Lỗi UX cụ thể (kể cả đã “cải thiện” một phần)

| # | Mức | Mô tả | Đề xuất |
|---|---|---|---|
| E18 | 🟠 | Sai OTP password-reset **xóa request** (khác transaction retry-friendly) | Đồng bộ UX: cho retry đến hết hạn |
| E19 | 🟠 | `forgot_password` redirect `login_view` khi user not found — **route name không tồn tại** | `redirect()->route` đúng `/login`; message generic chống email enumeration |
| E20 | 🟠 | Transaction create trả **JSON error** trong một số nhánh nhưng success **redirect** — FE form khó xử lý thống nhất | Một kiểu response |
| E21 | 🟠 | Không resend OTP / countdown trên UI | API resend + UI timer 10 phút |
| E22 | 🟡 | Toast/alert tiếng Anh–Việt lẫn ở flash backend (`Note created successfully`) trong khi UI test2 đã Việt hóa | i18n `__()` hoặc thống nhất locale |
| E23 | 🟡 | Drag card + `touch-action` có thể chặn scroll mobile | Chỉ capture pointer sau threshold; `prefers-reduced-motion` |
| E24 | 🟡 | Icon FA / CDN ngoài — offline hoặc version sai → UI “ô vuông” | Self-host subset icons hoặc FA 6.x pin |

### 8.4. Lỗi / nợ quy trình phát triển

| # | Mức | Mô tả | Đề xuất |
|---|---|---|---|
| E25 | 🟠 | Báo cáo cũ (03-08 “CRITICAL FAILURE”) **lỗi thời** so với branch hiện tại — dễ mislead | Lấy báo cáo 05-08 + 08-08 + **11-08** làm nguồn sự thật; archive báo cáo cũ |
| E26 | 🟡 | README vẫn là Laravel default — không mô tả Noteket | Viết README product: setup, queue, mail, branch làm việc |
| E27 | 🟡 | `resources/testing_view` + `test1` + `test2` song song | Chọn **test2** làm source of truth UI; đánh dấu deprecated cái còn lại |
| E28 | 🟡 | Factory chỉ có `UserFactory` — test org/theme khó | Factories cho Note, Organization, Transaction |

### 8.5. Lộ trình ưu tiên đề xuất (gộp FE + BE)

```text
P0 — Chạy được happy path user (1–2 tuần)
  1. Fix authorize note creator (E6)
  2. Fix ReplyNoteController + route share note (E2, E3)
  3. Port Blade layout + home + login/signup từ test2 (E1, FE-1)
  4. Logout + create/edit/mark-done wire thật
  5. Sửa test fail + thêm 5–10 feature tests cốt lõi

P1 — Org + an toàn tiền (1–2 tuần)
  6. Org member view + add member contract
  7. lockForUpdate + decimal balance + OTP attempts (E11, E12, E14)
  8. Transfer UI đúng field + màn OTP

P2 — Theme + polish (1 tuần)
  9. Theme buy ownership + store UI
  10. Settings/avatar, a11y, i18n, empty/error states thật
  11. Gỡ HTML tĩnh hoặc chuyển styleguide; CI green
```

### 8.6. Kết luận điều hành

| Câu hỏi | Trả lời ngắn |
|---|---|
| Backend có “chết” như báo cáo 03-08? | **Không còn.** Đã boot được, route list sạch, nhiều fix audit. |
| Có ship production được không? | **Chưa.** Thiếu Blade, thiếu route share/reply chuẩn, gap bảo mật tiền, prototype chưa nối. |
| test2 có đáng giữ không? | **Có** — đây là UI reference tốt nhất; cần port có hệ thống, không copy nguyên 15 file. |
| Việc quan trọng nhất tuần tới? | **P0:** authorize note + share/reply route + Blade home/auth + test xanh. |

---

## Phụ lục A — Bản đồ màn test2 ↔ backend

| Màn test2 | Route / controller gợi ý | Trạng thái ghép |
|---|---|---|
| `index.html` | `login`, `signup` | Form action gần đúng; UI chưa port Blade |
| `home.html` | `home`, `create.note`, `edit.note`, `mark.done`, share/reply | Create route đã có; share/reply/delete gap |
| `balance.html` | `user2user_*`, history | Modal lệch contract |
| `setting.html` | *(chưa có)* profile/logout | Backend thiếu |
| `org/index|home|dashboard|member|…` | `organization*`, `share.organization` | View Blade thiếu; member partial |
| `theme/*` | `theme.user.buy*`, `create_theme_request` | Buy logic thiếu ownership record |

## Phụ lục B — Thống kê nhanh codebase (11-08-2026)

| Hạng mục | Số lượng (xấp xỉ) |
|---|---|
| Controllers | 21 |
| Models | 21 |
| Mailables | 12 |
| Migrations | 23 |
| Routes (route:list) | ~62 |
| Blade production (non-email) | 3 |
| Email Blade | 8 |
| HTML test2 | 15 |
| Feature/Unit tests | 5 (1 failing) |
| Báo cáo trong `report/` | 15+ |

## Phụ lục C — Tài liệu nên đọc theo thứ tự

1. `report/11-08-26-Bao-Cao-UI-test2.md` — hiện trạng UI demo & việc đã/ chưa sửa hôm nay  
2. Báo cáo này — bức tranh full-stack  
3. `report/AUDIT_AND_HANDOVER_GUIDE.md` — lịch sử fix backend 05-08  
4. `report/08-08-26-*.md` — các fix UX resilience  
5. `UI_instruction_design/` — spec chi tiết từng màn (khi implement)

---

*Báo cáo được lập ở chế độ **read-only** ngày 11-08-2026 trên nhánh `fix/audit-and-refactor`. Không chỉnh sửa source code ngoài việc tạo file báo cáo này. Mọi đề xuất mang tính kỹ thuật tham khảo cho team phát triển tiếp theo.*
