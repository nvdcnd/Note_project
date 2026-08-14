# Báo cáo toàn diện dự án Noteket — sau commit "UI add and fix" + "Theme Aplied function" (11-08-2026, bản V3)

| Mục | Nội dung |
|---|---|
| **Ngày** | 11-08-2026 |
| **Người thực hiện** | Senior Laravel PHP Full-stack Developer (audit read-only) |
| **Nhánh làm việc** | `fix/audit-and-refactor` (HEAD = `af3de00`, working tree sạch) |
| **Phạm vi** | Toàn bộ back-end Laravel + front-end Blade production + hạ tầng (CI, migration, tooling) + đối chiếu báo cáo V2 cùng ngày |
| **Nguyên tắc** | Chỉ đọc code / commit / báo cáo; **không sửa** bất kỳ file hiện có — chỉ tạo báo cáo này |
| **Điểm khác biệt so với V2** | Lần này môi trường **đã có `vendor/`** → mọi con số đều được **chạy thật** (`php artisan test`, `route:list`, `pint --test`, `phpstan`, `migrate:status`, route matcher) chứ không suy ra từ README |

> 📌 **Vì sao có báo cáo này?**
> Báo cáo V2 (`11-08-26-Bao-Cao-Toan-Dien-Du-An-Sau-Refactor.md`) được viết trong commit `123078b` và mô tả trạng thái tại thời điểm đó. Sau đó có thêm **2 commit** thay đổi trạng thái dự án:
> - `123078b` "UI add and fix" — bổ sung bản mobile (`content-mobile`) cho 5 màn còn thiếu, thêm **bộ lọc note** ở trang chủ, đảo thứ tự note thành hàng đợi (cũ trước / mới sau), fix E18 (mất nội dung khi sửa note dài), thêm `NoteFilterTest` (6 test).
> - `af3de00` "Theme Aplied function" — thêm 2 method áp dụng theme (`set_theme_4user`, `setTheme4org`) + 1 cột `themeID` cho bảng `organizations`.
>
> Commit `af3de00` là commit **mới nhất và có vấn đề nghiêm trọng nhất** trong toàn bộ dự án hiện tại: 30 dòng thêm vào nhưng **không dòng nào chạy được** (chi tiết mục 8.1 — E-A1…E-A6). Báo cáo này ghi nhận cả tiến bộ lẫn khối nợ mới đó.

### Nguồn tham chiếu chính

| Nguồn | Vai trò |
|---|---|
| `report/11-08-26-Bao-Cao-Toan-Dien-Du-An-Sau-Refactor.md` | Báo cáo V2 (form mẫu + danh sách BE-*/FE-*/E-* để đối chiếu) |
| `report/11-08-26-Bao-Cao-Toan-Dien-Du-An.md` | Báo cáo V1 (đã superseded) |
| `AGENTS.md` (mục "Noteket project-specific context") | Ghi chú bàn giao — trùng khớp tốt với thực tế, dùng làm checklist |
| `README.md` | Mô tả sản phẩm, setup, queue, branch workflow |
| Kết quả chạy thật: `php artisan test`, `route:list`, `migrate:status`, `pint --test`, `phpstan`, route matcher | Đối chiếu thực tế (không suy đoán) |

### Kết quả kiểm chứng nhanh (chạy thật, 11-08-2026)

| Hạng mục | Lệnh | Kết quả |
|---|---|---|
| Test suite | `php artisan test` | ✅ **40 tests / 133 assertions PASS** (4.1s) — tăng từ 34 → 40 nhờ `NoteFilterTest` |
| Routes | `php artisan route:list` | **79 route** (V2 ghi 75) |
| Code style | `./vendor/bin/pint --test` | ❌ **FAIL — 9 file lệch chuẩn**, trong đó 2 controller mới của `af3de00` mỗi file dính **10 fixer** |
| Static analysis | `./vendor/bin/phpstan analyse` | ❌ **1 error** — và error đó nằm đúng trong code mới `af3de00` (`Theme4userController:50`). Ngoài ra PHPStan **crash ở memory limit 128M** mặc định, phải chạy `--memory-limit=1G` |
| Migration | `php artisan migrate:status` | ⚠️ **5 migration Pending** trên DB dev (replynotes, decimal, otp_attempts, theme4ID, org_done) |
| Schema thực tế | `Schema::getColumnListing('organizations')` | ⚠️ **Không có cột `themeID`** — xác nhận lỗi sửa migration cũ tại chỗ (E-A4) |
| Route matcher | `Route::match('/themes/org')` | ❌ Trả về **`themes.show` (Theme4userController@show)**, không phải `themes.org.index` → **cửa hàng theme cho tổ chức không truy cập được** (E-B1) |
| Blade | đếm file | **41 view** (33 production + 8 email) — không đổi |

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

Định vị không đổi so với V2. Điểm mới về mặt sản phẩm ở đợt này là **trang chủ trở thành một hàng đợi công việc** (note cũ nổi lên trước, xử lý xong thì note mới trồi lên) kèm **5 bộ lọc**, thay vì một feed newest-first thuần túy.

### 1.2. Stack công nghệ

| Tầng | Công nghệ |
|---|---|
| Runtime | PHP 8.3+, Laravel Framework **v13.8** |
| ORM / DB | Eloquent, SQLite (dev), 27 migrations (**5 chưa chạy trên DB dev**) |
| Auth | Session-based + `password` cast `hashed` |
| Mail | Laravel Mailable (8 email Blade), một phần queue database |
| Frontend build | Vite + Tailwind v4 (thực tế dự án dùng CSS tĩnh riêng `public/css/noteket.css`) |
| UI production | Blade + Bootstrap 5.3.8 (CDN) + Font Awesome 6.5.2 (CDN) + Google Font Caveat |
| UI prototype | `resources/view/` — 4 thư mục `test`, `test1`, `test2`, `testing` |
| Test | Pest v5 — **40 tests / 133 assertions (đã chạy, xanh)** |
| Tooling | Laravel Pint (**đang fail**), PHPStan (**1 error + crash memory mặc định**), Laravel Boost, Pail |

### 1.3. Cấu trúc thư mục quan trọng

```
app/
  Http/Controllers/   → 23 controller (auth, note, org, transaction, theme, settings…)
  Models/             → 21 model
  Mail/               → 12 mailable
database/migrations/  → 27 migrations
database/factories/   → 3 factories (User, Note, Organization)
database/seeders/     → chỉ tạo 1 user Test User (chưa có demo data)
resources/views/      → 33 Blade production + 8 email
resources/view/       → 4 thư mục prototype HTML tĩnh (test, test1, test2, testing)
public/css/noteket.css (705 dòng) + public/js/noteket.js (555 dòng)
routes/web.php        → 79 route
tests/Feature/        → CoreWorkflowsTest, PageSmokeTest, UserExperienceImprovementsTest, NoteFilterTest
.github/workflows/    → CI Laravel (chỉ push/PR vào main)
report/               → 17 file báo cáo
.freebuff/            → ⚠️ 13.9 MB DB công cụ bị commit vào repo
cookies.txt           → ⚠️ session cookie thật bị commit vào repo
```

### 1.4. Lịch sử phát triển (cập nhật)

| Giai đoạn | Nội dung |
|---|---|
| Đầu dự án | Scaffold Laravel + domain note/org/transaction; nhiều lỗi PSR-4, import, migration |
| 03–05/08 | Audit lớn: đổi tên class, fix route/controller, migration |
| 08/08 | Cải thiện UX: OTP retry, share note dedupe, invite resilient, mail templates |
| 10/08 | Prototype UI test2 hoàn thiện |
| 11/08 (11:22–11:42) | **Đại tu**: Blade production đầy đủ, refactor controller/model, decimal + OTP attempts, 34 tests |
| **11/08 14:55 (`123078b`)** | **UI add and fix**: 5 màn có bản mobile, bộ lọc note, đảo thứ tự thành hàng đợi, fix E18, +6 test |
| **11/08 17:16 (`af3de00`)** | **Theme Aplied function**: 2 method áp dụng theme + 1 cột `themeID` — **toàn bộ không chạy được** (mục 8.1) |
| **Hiện tại** | Happy path chạy tốt hơn V2; nhưng **module Theme thoái lui**: cửa hàng org không vào được, chức năng áp dụng theme là dead code lỗi |

### 1.5. Trạng thái tổng thể (đánh giá senior)

| Tiêu chí | Mức | So với V2 | Ghi chú ngắn |
|---|---|---|---|
| Domain model / ý tưởng sản phẩm | ★★★★☆ | = | Rõ ràng, đủ module cốt lõi |
| Backend logic | ★★★☆☆ | ▼ (từ ★★★★☆) | Core vẫn tốt, nhưng code mới nhất hạ chuẩn rõ rệt |
| Production UI (Blade) | ★★★★☆ | ▲ (từ ★★★☆☆) | Mobile parity đã đủ, bộ lọc note là cải tiến UX thật |
| Prototype UI (test2) | ★★★★☆ | = | Vẫn là styleguide; 4 thư mục prototype vẫn chưa dọn |
| Test coverage | ★★★☆☆ | ▲ nhẹ | 40 tests xanh; vẫn **0 test** cho theme buy/apply, race, JS |
| Bảo mật tiền tệ | ★★★★☆ | = | decimal + lock + OTP 5 lần + throttle — không đổi |
| **Kỷ luật kỹ thuật (Pint/PHPStan/migration)** | ★★☆☆☆ | ▼▼ | Pint fail 9 file, PHPStan 1 error, 5 migration pending, secret trong repo |
| Sẵn sàng production | **Chưa** | ▼ | Ngoài các nợ V2, giờ thêm module Theme hỏng + secret rò rỉ cần xử lý trước |

> **Nhận định tổng:** dự án đi **hai hướng ngược nhau** trong cùng một ngày. Commit `123078b` là công việc **đúng chuẩn** (có test kèm, fix đúng bug đã báo cáo, code sạch). Commit `af3de00` là công việc **chưa hoàn thành đã commit** (không route, không test, không chạy Pint, sai tên cột, sửa migration cũ tại chỗ). Ưu tiên số 1 không phải viết thêm tính năng mà là **kéo `af3de00` về chuẩn của `123078b`**.

---

## 2. Tổng quan về back-end của dự án

### 2.1. Kiến trúc tổng quát

- **Monolith Laravel classic**, toàn bộ route trong `routes/web.php`: public (login/signup/forgot) + một group `middleware(['auth'])` lớn. Tổng **79 route**.
- **Controller vẫn "fat"**: chưa có Form Request / Policy / Service layer; validate bằng `$request->validate()` trong action.
- **Model Eloquent** đầy đủ fillable, casts (`decimal:2`, `hashed`), relationships, scope.
- **OTP**: 5 loại giao dịch + reset password dùng `random_int` + `Hash::make`, TTL 10 phút, MAX_ATTEMPTS = 5, retry-friendly.
- **Contract response**: web dùng **form → redirect + flash** thống nhất… **trừ 2 method mới trong `af3de00` trả `response()->json`** — quay lại đúng cái pattern lẫn lộn mà V2 đã ghi nhận là "đã fix". Cộng với 6 method `*_fetch` dead code trong `Controller.php`, hiện có **8 method trả JSON không đúng convention**.

### 2.2. Module nghiệp vụ

| Module | Controller chính | Trạng thái |
|---|---|---|
| **Auth** | `AuthenticationController` | ✅ Chạy tốt. ⚠️ 3 method `signup40acc_*` vẫn không có route (dead feature, từ V2) |
| **Note** | `NoteController`, `MarkAsDoneController`, `ReplyNoteController`, `PivotForNoteController` | ✅ Tốt nhất dự án. Mới thêm bộ lọc 5 chế độ + có test |
| **Organization** | `OrganizationsController`, `OrganizationsMemberController`, `PivotChangeHostOrganizationController` | ✅ Chạy. ⚠️ `show()` đổi sang `oldest()->take(20)` → note mới nhất **biến mất** khi org > 20 note (E-B2) |
| **Transaction** | `User2user*`, `User2organization*`, `Organization2user*` | ✅ Chắc chắn nhất về mặt bảo mật. Không đổi |
| **Theme** | `Theme4user/orgController`, `Theme4user/orgWalletController`, `ThemeRequestController` | 🔴 **Module yếu nhất**: cửa hàng org không vào được, apply theme không chạy, 2 StyleController rỗng hoàn toàn, layout không đọc theme |
| **Settings** | `SettingsController`, `BalanceController` | ✅ Đủ. Mới có bản mobile |
| **Password** | `PasswordChangeRequestController` | ✅ Không đổi |

### 2.3. Routing (hiện trạng)

- **79 route** (tăng 4 so với V2 — chủ yếu do đếm lại chính xác bằng `route:list` thay vì đếm tay).
- **9 route có `throttle:5,1`** — phủ login/signup/forgot + toàn bộ verify OTP. ⚠️ Route **create transaction vẫn không throttle** (E7 từ V2, chưa xử lý).
- 🔴 **Lỗi thứ tự route mới phát hiện**: `routes/web.php:147` đăng ký `/themes/{id}` **trước** `:148` `/themes/org`. Laravel match theo thứ tự đăng ký → `/themes/org` rơi vào `themes.show` với `$id = 'org'` → `Theme4user::find('org')` = null → **404**. Đã kiểm chứng bằng route matcher. **Toàn bộ cửa hàng theme cho tổ chức không có lối vào** (E-B1).
- **Method không route**: `signup40acc_note`, `signup40acc_host_org`, `signup40acc_member_org`, 6 method `*_fetch`, **và 2 method mới `set_theme_4user`, `setTheme4org`** → tổng **11 method dead**.

### 2.4. Database

- 27 migrations. ⚠️ **5 Pending trên DB dev**: `create_replynotes_table`, `change_balances_to_decimal`, `add_otp_attempts_to_transactions`, `add_theme4id_to_theme4org_wallets`, `add_org_done_to_note_if_missing`.
  → Nghĩa là **DB dev đang thiếu cả `balance` decimal lẫn `otp_attempts`** — hai fix bảo mật quan trọng nhất. Test vẫn xanh vì `RefreshDatabase` migrate lại từ đầu; nhưng nếu ai đó chạy app bằng `artisan serve` trên DB này sẽ gặp lỗi cột thiếu. Cần `php artisan migrate` ngay.
- 🔴 **`af3de00` thêm cột bằng cách sửa migration đã chạy** (`2026_08_01_081542_create_organizations_table.php` +1 dòng `themeID`) thay vì tạo migration mới. Đã kiểm chứng: bảng `organizations` trong DB hiện tại **không có cột `themeID`**. Mọi môi trường đã migrate (dev, staging, và CI nếu có cache) sẽ không bao giờ có cột này (E-A4).
- ⚠️ **Quy ước cột không đồng nhất** — không đổi từ V2: camelCase (`hostID`, `userID`, `organizationID`, `noteID`, `theme4ID`, `themeID`, `current_hostID`) lẫn snake_case (`creater_id`, `note_id`, `shared_with`, `org_done`, `theme4_id`). Đợt này còn **tệ hơn**: cùng khái niệm "theme id" giờ tồn tại **4 biến thể** — `theme4ID` (bảng wallet), `theme4_id` (bảng users), `themeID` (bảng organizations + bảng theme4org_transactions), và `theme_id` (chỉ tồn tại trong code `af3de00`, **không có trong DB**). Đây chính là nguyên nhân trực tiếp của E-A2/E-A3.

### 2.5. Views Blade production

41 view (33 non-email + 8 email) — số lượng không đổi so với V2, nhưng **chất lượng tăng**: `123078b` bổ sung `@section('content-mobile')` cho 5 view còn thiếu (`settings`, `organizations/create`, `organizations/settings`, `themes/request`, `themes/request_success`) + empty state cho `themes/index` mobile → **mobile parity coi như hoàn tất**.

### 2.6. Testing backend

- **40 tests / 133 assertions — đã chạy thật, tất cả PASS trong 4.1s.**
- 4 file feature: `CoreWorkflowsTest` (21), `PageSmokeTest` (~8), `UserExperienceImprovementsTest` (3), **`NoteFilterTest` (6 — mới)**.
- `NoteFilterTest` là **file test chất lượng cao nhất dự án**: phủ đủ 5 filter + case fallback giá trị lạ, dùng factory, assert cả `assertSee` lẫn `assertDontSee`. Đây là chuẩn nên nhân rộng.
- ⚠️ **Vùng trắng test hoàn toàn**: mua theme (user & org), áp dụng theme, org member đầy đủ, race condition giao dịch, và toàn bộ JS.

---

## 3. Tổng quan về front-end của dự án

### 3.1. Hai lớp front-end

| Lớp | Vị trí | Vai trò |
|---|---|---|
| **A. Production Blade** | `resources/views/` | 33 view — front-end chính thức, render dữ liệu thật, **desktop + mobile parity đã đủ** |
| **B. Prototype demo** | `resources/view/` | 4 thư mục HTML tĩnh (`test`, `test1`, `test2`, `testing`) — styleguide, **vẫn chưa dọn** |

### 3.2. Kiến trúc front-end production

- `layouts/app.blade.php`: dual-shell (sidebar desktop + bottom-nav mobile + FAB), toast container, flash → toast, `@csrf` meta, `lang="vi"`.
- `public/css/noteket.css` **705 dòng** (+114 so với V2) + `public/js/noteket.js` **555 dòng** (+54) — vẫn 1 file mỗi loại.
- ⚠️ Layout **không đọc theme của user/org ở bất kỳ đâu** — không có `<style>` động, không có class theo theme, không có link CSS theme. Xác nhận: kể cả khi sửa hết bug của `af3de00`, **áp dụng theme vẫn không thay đổi gì trên màn hình** (E-A6).

### 3.3. Trạng thái UI sau đợt này

**Cải thiện thật:**
- **Bộ lọc note** ở trang chủ: dropdown 5 chế độ (Tất cả / Chưa hoàn thành / Hoàn tất / Do tôi tạo / Được chia sẻ với tôi), có `active` state, fallback an toàn khi giá trị lạ.
- **Mô hình hàng đợi**: note cũ trước, xử lý xong thì note mới trồi lên — hợp lý cho use case to-do, có comment giải thích rõ trong code.
- **E18 (mất nội dung note dài) đã fix đúng cách**: thêm `<span class="d-none note-full-description">{{ $note->description }}</span>` vào card, JS `noteket.js:107` đọc từ span đầy đủ thay vì `Str::limit(...,200)`, và cập nhật lại span sau khi lưu (`:152`). Fix sạch, không hack.
- **Mobile parity**: 5 màn cuối đã có `content-mobile`, đầy đủ `@csrf`, `@method('DELETE')`, `onsubmit="return confirm(...)"` cho hành động nguy hiểm.

**Chưa cải thiện:** CDN (FE-1), email tiếng Anh (FE-2), OTP countdown/resend (FE-3), a11y (FE-4), trùng lặp desktop/mobile (FE-6 — đợt này còn **tăng** vì thêm 5 khối `content-mobile`), prototype chưa dọn (FE-7), không test JS (FE-8).

---

## 4. Những điểm tốt trong back-end của dự án

1. **Test suite thật sự xanh và đã được kiểm chứng** — 40 tests / 133 assertions, chạy 4.1s. Đây không còn là con số chép từ README như báo cáo V2 mà là kết quả chạy trực tiếp. Suite nhanh → không có lý do bỏ qua trước khi commit.

2. **`NoteFilterTest` là hình mẫu viết test cho dự án**: 6 test phủ đủ nhánh, dùng helper `makeDoneNote()`, dùng factory, có case negative (`filter=bogus` → fallback `all`). Nếu mọi tính năng mới đều kèm test kiểu này thì chất lượng dự án sẽ ổn định.

3. **Bộ lọc note được implement đúng kỹ thuật phòng thủ**:
   ```php
   $filter = $request->query('filter', 'all');
   if (! array_key_exists($filter, $noteFilters)) { $filter = 'all'; }
   ```
   Whitelist trước, `match` sau — không có đường cho input lạ chui vào query. Đây là cách làm đúng.

4. **An toàn tiền tệ giữ nguyên chất lượng**: `decimal(15,2)`, `DB::transaction` + `lockForUpdate()`, OTP `random_int` + `Hash::make` + TTL 10 phút + MAX_ATTEMPTS 5 + `throttle:5,1` trên verify, cancel flow đủ 3 loại. Đây là phần được viết cẩn thận nhất và **không bị commit mới làm hỏng**.

5. **Auth chặt chẽ**: `unique:users,email`, `password|confirmed|min:8`, cast `hashed`, `session()->regenerate()`, logout invalidate, forgot-password trả message generic chống enumeration.

6. **Authorization phủ đủ** trên note (creator/shared), org (host/member), transaction (from / current_hostID).

7. **Dọn dẹp nhỏ nhưng đúng trong `123078b`**: thay 2 chỗ FQCN inline `\App\Models\PivotChangeHostOrganization::` bằng `use` statement ở đầu file. Chi tiết nhỏ nhưng cho thấy có để ý code style.

8. **`AGENTS.md` có mục "Noteket project-specific context" rất giá trị** — ghi lại đúng 8 cạm bẫy của dự án (signup-invite chưa wire, `*_fetch` dead code, `lockForUpdate` no-op trên SQLite, naming lẫn lộn, 2 thư mục views…). Đối chiếu với code: **các ghi chú này đều chính xác**. Đây là tài sản bàn giao tốt.

9. **CI GitHub Actions** cấu hình đúng (PHP 8.5 + sqlite + `php artisan test`) — chỉ còn vướng chuyện trigger (mục 8.4).

---

## 5. Những điểm tốt trong front-end của dự án

1. **Mobile parity đã hoàn tất** — 5 view cuối được bổ sung `content-mobile`. Không còn màn nào "vào từ điện thoại là trắng".

2. **Bộ lọc note là cải tiến UX có giá trị thật**, không phải trang trí: người dùng nhiều note giờ tách được "việc chưa xong" khỏi "việc đã xong", và "của tôi" khỏi "được chia sẻ".

3. **Mô hình hàng đợi có chủ đích và được ghi chú trong code** — comment tiếng Việt giải thích rõ *vì sao* đảo thứ tự. Người sau đọc sẽ không tưởng là bug.

4. **Fix E18 làm đúng bản chất** — thay vì hack JS, họ đưa dữ liệu đầy đủ vào DOM (`note-full-description`) rồi đọc từ đó, và đồng bộ lại sau khi lưu. Fix bền.

5. **XSS vẫn được kiểm soát**: `escapeHtml()` cho mọi chỗ chèn vào `innerHTML`, toast dùng `textContent`, POST từ JS luôn kèm CSRF token.

6. **Form mới đều đúng chuẩn**: `@csrf` đầy đủ, `@method('DELETE')` cho xóa, `onsubmit="return confirm(...)"` cho vùng nguy hiểm, `type` tường minh trên nút.

7. **Empty state được bổ sung tiếp** (`themes/index` mobile: icon 🎨 + CTA "Yêu cầu chủ đề đầu tiên") — nhất quán với phần còn lại.

8. **Toàn bộ giao diện tiếng Việt**, giữ trọn cá tính sticky-note (vàng `#FACC15`/`#FFE86E`, Caveat, card bo tròn), micro-interaction drag/swap-mode/toast vẫn nguyên.

---

## 6. Những điểm chưa tốt của back-end của dự án (kèm phương án đề xuất)

### 6.1. Kiến trúc & tổ chức code

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-1 | **Quy ước cột không đồng nhất — đã xấu đi**: riêng khái niệm "theme id" giờ có 4 biến thể (`theme4ID`, `theme4_id`, `themeID`, `theme_id`). Đây là nguyên nhân trực tiếp gây E-A2/E-A3 | Ưu tiên cao hơn V2. Chốt **snake_case**, lập bảng mapping, migration rename theo kế hoạch. Trước mắt: **thêm bảng đối chiếu cột vào `AGENTS.md`** để chặn lỗi lặp lại |
| BE-2 | **Controller vẫn fat**, pattern verify-OTP + `lockForUpdate` lặp ở 5 controller; OTP generator lặp 4 lần | Tách `OtpService` + `WalletTransactionService` |
| BE-3 | Không có **Form Request / Policy** | Dần tách `FormRequest` (transaction, org, settings) + `Policy` (Note, Organization) |
| BE-4 | **Chưa paginate** ở đâu cả — `grep paginate app/Http/Controllers/` trả về **0 kết quả**. Home `take(20)`, org show `take(20)` | `paginate()` / `cursorPaginate()`. Đặc biệt gấp cho org (xem E-B2) |
| BE-15 | **2 controller rỗng hoàn toàn** (`Theme4userStyleController`, `Theme4orgStyleController`) — 7 method scaffold `//` trống mỗi file. Bảng `theme4user_styles`/`theme4org_styles` có 6 cột JSON nhưng **không có gì đọc/ghi** | Quyết định: implement (đây là phần khiến theme thực sự đổi giao diện) hoặc xóa cả controller lẫn bảng để không gây hiểu nhầm |

### 6.2. Bảo mật & tính đúng đắn

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-16 | 🔴 **Secret bị commit vào repo**: `cookies.txt` chứa **session cookie Laravel thật** (`laravel-session`, hạn 2026-08-11+) đang nằm trong git. Kèm theo `.freebuff/` — 13.9 MB DB công cụ (`desktop-v2.db` + `-shm` + `-wal`) cũng bị commit | Xóa khỏi tracking (`git rm --cached`), thêm vào `.gitignore` (`cookies.txt`, `.freebuff/`), **rotate `APP_KEY`** để vô hiệu session cũ. Nếu repo từng public → coi cookie là đã lộ |
| BE-5 | **Thứ tự lock không nhất quán** giữa các loại giao dịch | Chuẩn hóa lock theo ID tăng dần; lưu ý `lockForUpdate` là **no-op trên SQLite** |
| BE-6 | **OTP generator quét O(n)** toàn bảng pending với `Hash::check` từng dòng | Bỏ quét toàn bảng; retry 1–2 vòng là đủ |
| BE-7 | **Dead feature signup-invite** (`signup40acc_*` không route, `shared_with` là user ID chứ không phải email) | Wire đầy đủ hoặc gỡ + sửa README |
| BE-8 | `ThemeRequestController` validation chỉ `required` | Thêm `price => numeric|min:0`, `catalog_link => nullable|url` |
| BE-9 | **i18n chưa thống nhất** — flash backend tiếng Anh trong UI tiếng Việt | `__()`/`trans()` hoặc viết lại tiếng Việt |
| BE-10 | `edit_note` qua fetch: **vẫn chưa kiểm tra `response.ok`**. `noteket.js:141-144` chỉ xử lý `r.redirected` rồi `r.json()` → 422 vẫn rơi vào `.then()` và toast "🎉 Đã lưu thay đổi!" | Thêm `if (!r.ok) throw new Error(...)` trước `r.json()`; hiển thị lỗi validation |
| BE-17 | 🔴 **Code `af3de00` không được kiểm chứng gì trước khi commit** — không route, không test, không Pint, không PHPStan, sai 3 tên cột, sửa migration đã chạy | Xem toàn bộ mục 8.1. Về quy trình: bắt buộc `pint && phpstan && test` trước mọi commit (xem 6.3) |

### 6.3. Vận hành & chất lượng

| # | Vấn đề | Đề xuất |
|---|---|---|
| BE-18 | 🟠 **Pint đang FAIL 9 file** — `AuthenticationController`, `Theme4orgController` (10 fixer), `Theme4userController` (10 fixer), 5 model transaction, `routes/web.php` | Chạy `./vendor/bin/pint` (tự fix), commit riêng "style: apply pint". Sau đó **thêm `pint --test` vào CI** để không tái diễn |
| BE-19 | 🟠 **PHPStan có 1 error thật** tại `Theme4userController:50` — `Call to an undefined method Illuminate\Contracts\Auth\Authenticatable::save()`. Đây không phải false positive: `Auth::user()` trả về interface, không đảm bảo có `save()` | Dùng `$user = User::find(Auth::id())` hoặc `Auth::user()->refresh()` với type hint đúng. **Thêm PHPStan vào CI** |
| BE-20 | 🟡 **PHPStan crash ở memory limit mặc định 128M** — người mới clone chạy `phpstan` sẽ thấy "process crashed", dễ tưởng hỏng cấu hình | Thêm `memoryLimitFile` hoặc ghi `--memory-limit=1G` vào `composer.json` script + README |
| BE-21 | 🟠 **5 migration Pending trên DB dev** — bao gồm 2 migration bảo mật quan trọng (decimal + otp_attempts) | Chạy `php artisan migrate` ngay; cân nhắc thêm bước check vào README/onboarding |
| BE-11 | Chưa có monitoring mail/queue | Tài liệu hóa `queue:work`; Horizon hoặc log + retry |
| BE-12 | `encore/laravel-admin` trong composer nhưng **vẫn chưa dùng** ở đâu | Gỡ hoặc quyết định dùng |
| BE-13 | **Seeder vẫn chỉ tạo 1 user** — không có org / note / theme mẫu | Seed demo data (đã có sẵn 3 factory) |
| BE-14 | `phpstan-report.json` (152 KB) vẫn là file cũ UTF-16 từ máy khác | Xóa — giờ đã chạy được PHPStan thật |

---

## 7. Những điểm chưa tốt của front-end của dự án (kèm theo phương án đề xuất)

| # | Vấn đề | Đề xuất |
|---|---|---|
| FE-1 | **Phụ thuộc CDN** (Bootstrap 5.3.8, FA 6.5.2, Google Font Caveat) | Self-host hoặc bundle qua Vite trước khi deploy |
| FE-2 | **Email templates tiếng Anh** trong app tiếng Việt | Việt hóa + thống nhất brand |
| FE-3 | **Chưa có OTP countdown/resend** (spec có sẵn ở `UI_instruction_design/otp_typing.md`) | Timer 10 phút + nút gửi lại (cần route resend) |
| FE-4 | **Accessibility hạn chế** — tương phản vàng/nền thấp, nút icon thiếu `aria-label`, focus ring mờ | Checklist WCAG AA; `focus-visible` |
| FE-5 | `noteket.js` dùng `innerHTML = ...map()` — đã escape nhưng khó bảo trì | Chuyển sang `createElement` + `textContent` |
| FE-6 | **Trùng lặp desktop/mobile — đã tăng thêm**: `123078b` thêm 5 khối `content-mobile` gần như copy y hệt bản desktop (`settings.blade.php` +73 dòng, `organizations/settings` +52, `themes/request` +38…). Mỗi lần sửa form phải sửa 2 nơi, rất dễ lệch | Tách form thành `@include('partials.…')` dùng chung cho cả 2 section — refactor này trả nợ nhanh và rủi ro thấp |
| FE-7 | **4 thư mục prototype** (`test`, `test1`, `test2`, `testing`) vẫn nằm trong repo | Giữ `test2` làm styleguide, xóa/archive 3 cái còn lại |
| FE-8 | **Không có test JS** — trong khi JS giờ 555 dòng và chứa logic quan trọng (edit note, share, drag) | Ít nhất 1 smoke E2E: login → tạo note → sửa note dài → chuyển tiền |
| FE-9 | ✅ *(đã kiểm chứng — không phải lỗi)* Bộ lọc note đặt trong `@section('topbar-actions')`, mà `layouts/app.blade.php` include `partials.topbar` ở **cả 2 shell** (dòng 56 desktop, dòng 66 mobile) → **filter hoạt động trên cả desktop lẫn mobile**. Ghi lại ở đây vì đây là cách đặt đúng, nên nhân rộng cho các action theo trang khác | Không cần làm gì |
| FE-10 | 🟡 **Theme đã mua không hiển thị ở đâu** — không có mục "Theme của tôi" trong Settings, không có nút "Áp dụng" | Cần thiết kế màn quản lý theme trước khi implement lại E-A (mục 8.1) |

---

## 8. Những lỗi còn tồn tại ảnh hưởng tới người dùng, hệ thống (kèm phương án đề xuất)

> Nhóm theo mức độ: 🔴 chặn tính năng / mất tiền / bảo mật · 🟠 hỏng luồng UX · 🟡 chất lượng / nợ kỹ thuật.
> **Mã E-A\*** = lỗi phát sinh từ commit `af3de00`. **Mã E-B\*** = lỗi mới phát hiện trong đợt audit này. Mã **E1–E20** kế thừa từ báo cáo V2.

### 8.1. 🔴 Commit `af3de00` "Theme Aplied function" — 30 dòng, 6 lỗi, 0% chạy được

> Đây là phát hiện quan trọng nhất của báo cáo này. Cả hai method mới **đều không thể chạy**, và kể cả khi sửa hết vẫn **không tạo ra hiệu ứng nào cho người dùng**. Xin trình bày chi tiết vì tính năng này đang được hiểu là "đã xong".

| # | Mức | Mô tả | Bằng chứng | Đề xuất |
|---|---|---|---|---|
| **E-A1** | 🔴 | **Cả 2 method không có route** — `set_theme_4user` và `setTheme4org` không xuất hiện trong `routes/web.php`. Không có cách nào gọi được từ trình duyệt | `grep -i theme routes/web.php` → chỉ có index/show/buy/request. `route:list --path=themes` → 4 route, không có set | Thêm route `POST /theme/user/apply/{id}` + `POST /theme/org/apply/{theme}/{org}` **sau khi** sửa E-A2…E-A5 |
| **E-A2** | 🔴 | **Sai tên cột — query sẽ ném SQL error**. `Theme4userWallet::where('themeID', $id)` nhưng bảng `theme4user_wallets` có cột **`theme4ID`**. Tương tự `Theme4orgWallet::where('themeID',...)->where('OrganizationID',...)` — cột thật là **`theme4ID`** và **`organizationID`** | Migration `create_theme4user_wallets_table`: `foreignId('theme4ID')`. Model `Theme4userWallet::$fillable = ['userID','theme4ID']` | Sửa thành `theme4ID` / `organizationID`. Xem BE-1 về gốc rễ |
| **E-A3** | 🔴 | **Gán sai cột VÀ sai giá trị**. `$user->theme_id = $theme->id;` — (a) cột trong bảng `users` là **`theme4_id`**, không phải `theme_id`; (b) `$theme` là bản ghi **ví (wallet)**, nên `$theme->id` là **ID dòng ví**, không phải ID theme. Đúng ra phải là `$theme->theme4ID`. Hai lỗi chồng nhau: kể cả sửa tên cột thì vẫn lưu sai số | Migration users: `foreignId('theme4_id')`. `User::$fillable` cũng khai `'theme4_id'` | `$user->theme4_id = $theme->theme4ID;` |
| **E-A4** | 🔴 | **Thêm cột bằng cách sửa migration đã chạy** — `create_organizations_table.php` được thêm dòng `$table->foreignID('themeID')->nullable();`. Migration này đã ở trạng thái `Ran`, nên **không môi trường nào đã migrate sẽ có cột này**. Kiểm chứng: `Schema::getColumnListing('organizations')` trả về 9 cột, **không có `themeID`** → `$org->themeID = ...; $org->save()` sẽ lỗi | `migrate:status` cho thấy migration này `Ran`; column listing không có `themeID` | **Revert dòng đó**, tạo migration mới `add_themeID_to_organizations`. (Phụ: dùng `foreignId()` chứ không phải `foreignID()`, và cân nhắc `constrained()` để có FK thật) |
| **E-A5** | 🟠 | **Lỗi phụ về chất lượng**: (a) `Organization::find($org_id)` **không kiểm tra null** → truyền id sai là 500 do `$org->hostID` trên null; (b) trả `response()->json` — **phá vỡ convention redirect+flash** mà V2 ghi nhận là "đã thống nhất"; (c) dùng HTTP **500** cho lỗi nghiệp vụ/phân quyền, đúng ra là **403/404**; (d) **PHPStan báo lỗi thật** `Authenticatable::save()` không tồn tại; (e) **Pint fail 10 fixer/file** (thiếu space, dùng nháy kép, brace sai dòng, thiếu newline cuối file) | `phpstan analyse` → 1 error tại `Theme4userController:50`. `pint --test` → 10 fixer mỗi file | Thêm null-guard + `abort(404)`/`abort(403)`; đổi sang redirect+flash; `User::findOrFail(Auth::id())`; chạy `pint` |
| **E-A6** | 🟠 | **Kể cả sửa hết 5 lỗi trên, người dùng vẫn không thấy gì thay đổi** — `layouts/app.blade.php` không đọc `theme4_id`/`themeID` ở bất kỳ đâu; không có `<style>` động, không có class theo theme. Hai `*StyleController` (nơi lẽ ra chứa CSS của theme) **rỗng hoàn toàn**. Bảng `theme4user_styles` có 6 cột JSON nhưng không có gì ghi vào | `grep -n "theme" layouts/app.blade.php` → chỉ có route link. `Theme4userStyleController` → 7 method `//` trống | **Đây là việc lớn hơn 30 dòng code.** Cần: seed style cho theme → layout đọc style của theme đang áp dụng → render CSS variable. Nên tách thành task riêng có thiết kế trước |

> **Khuyến nghị xử lý E-A:** vì tính năng chưa từng chạy nên **không có người dùng nào phụ thuộc vào nó**. Phương án rẻ nhất và sạch nhất là **revert `af3de00`** (`git revert af3de00`), rồi làm lại thành một PR hoàn chỉnh có: migration mới đúng cách, tên cột đúng, route, redirect+flash, layout đọc theme, và **test**. Làm lại từ đầu ở đây rẻ hơn vá từng lỗi.

### 8.2. Tính năng / luồng end-to-end

| # | Mức | Mô tả | Ảnh hưởng | Đề xuất |
|---|---|---|---|---|
| **E-B1** | 🔴 | **Cửa hàng theme cho tổ chức không truy cập được**. `routes/web.php:147` đăng ký `/themes/{id}` trước `:148` `/themes/org` → Laravel match theo thứ tự, `/themes/org` rơi vào `themes.show` với `$id='org'` → `Theme4user::find('org')` = null → **abort(404)** | Toàn bộ luồng mua theme cho org mất lối vào. Link "← Quay lại cửa hàng" trong `themes/org/show.blade.php` cũng 404 | **Fix 1 dòng**: đưa `Route::get('/themes/org', ...)` lên **trước** `/themes/{id}`. Hoặc thêm `->where('id','[0-9]+')` cho route show. Nên thêm 1 smoke test cho `/themes/org` |
| **E-B2** | 🟠 | **Note mới nhất của tổ chức biến mất khi org có > 20 note**. `OrganizationsController::show()` đổi từ `latest()` sang `oldest()->take(20)` → lấy 20 note **cũ nhất**, note mới tạo **không bao giờ hiển thị**. (Trang chủ không dính lỗi này vì vẫn `latest()->take(20)` rồi mới `sortBy` trong PHP) | Thành viên org tạo note xong không thấy note của mình → tưởng mất dữ liệu | Đổi thành `latest()->take(20)->get()->sortBy('created_at')` giống pattern ở `NoteController::home()`, hoặc paginate luôn (BE-4) |
| E1 | 🟠 | **Flow "share cho user chưa đăng ký" chưa hoàn chỉnh** — `signup40acc_*` không route, email mời không có link | Tính năng README quảng cáo nhưng không chạy | Wire route + link, hoặc gỡ + sửa README |
| E2 | 🟠 | `edit_note` từ JS: validation fail vẫn báo "đã lưu" (**chưa fix**, xem BE-10) | User tưởng đã lưu | Kiểm tra `response.ok` |
| E3 | 🟠 | **Không phân trang ở đâu cả** (`grep paginate` → 0 kết quả) | User nhiều note không xem được hết | `paginate()` |

### 8.3. Bảo mật / toàn vẹn dữ liệu

| # | Mức | Mô tả | Đề xuất |
|---|---|---|---|
| **E-B3** | 🔴 | **Session cookie thật bị commit vào repo** (`cookies.txt`, cookie `laravel-session` còn hạn). Kèm `.freebuff/*.db` 13.9 MB dữ liệu công cụ | `git rm --cached cookies.txt .freebuff/`, thêm vào `.gitignore`, **rotate `APP_KEY`**. Nếu repo public → coi như đã lộ, cần purge history |
| **E-B4** | 🟠 | **DB dev thiếu 5 migration**, trong đó có `change_balances_to_decimal` và `add_otp_attempts_to_transactions` — tức 2 lớp bảo vệ tiền quan trọng nhất **chưa có trên DB đang chạy**. Test xanh vì `RefreshDatabase` migrate lại từ đầu, che mất vấn đề | `php artisan migrate` ngay. Cân nhắc thêm cảnh báo pending migration vào quy trình onboarding |
| E5 | 🟡 | Thứ tự `lockForUpdate` không thống nhất; `lockForUpdate` là **no-op trên SQLite** | Chuẩn hóa + test race trên MySQL/Postgres |
| E6 | 🟡 | OTP generator quét O(n) toàn bảng pending | Tối ưu (BE-6) |
| E7 | 🟡 | **Route create transaction vẫn không throttle** (chỉ verify có) | Thêm `throttle` cho create |
| E8 | 🟢 | `signup40acc_note` dùng `shared_with` (user ID) làm email | Sửa trước khi wire |
| E17 | 🟢 | 6 method `*_fetch` trong `Controller.php` — dead code, query cột `note.user_id` không tồn tại | Gỡ hoặc sửa query |

### 8.4. UX / chất lượng / nợ quy trình

| # | Mức | Mô tả | Đề xuất |
|---|---|---|---|
| **E-B5** | 🟠 | **Pint fail 9 file + PHPStan 1 error trên nhánh làm việc** — codebase đang không qua được chính công cụ chất lượng nó cấu hình | `./vendor/bin/pint`, fix PHPStan error, rồi **đưa cả 2 vào CI** |
| **E-B6** | 🟡 | **CI vẫn chỉ trigger trên `main`** — nhánh `fix/audit-and-refactor` (nơi toàn bộ công việc đang diễn ra) **chưa từng được CI kiểm chứng**. Nếu CI có chạy, `af3de00` đã bị chặn bởi PHPStan | Cho workflow trigger trên mọi branch, hoặc mở PR sớm |
| E9 | 🟠 | Flash message tiếng Anh trong UI tiếng Việt | i18n (BE-9) |
| E18 | ✅ | ~~Sửa note dài bị mất nội dung~~ | **ĐÃ FIX** trong `123078b` |
| E20 | 🟡 | `organizations/settings.blade.php:35` — **form ẩn chết `changeHostRealForm` vẫn thiếu `@csrf`**, vẫn không có JS nào tham chiếu | Gỡ form chết |
| E10 | 🟡 | Email tiếng Anh, chưa theo brand | Việt hóa (FE-2) |
| E11 | 🟡 | Không có OTP countdown/resend | Pattern có sẵn trong `UI_instruction_design/` |
| E12 | 🟡 | CDN ngoài — offline vỡ UI | Self-host (FE-1) |
| E13 | 🟡 | 4 thư mục prototype vẫn song song | Dọn, giữ test2 |
| E19 | 🟡 | Email mời org / đổi host không có link accept-decline | Thêm link + token |
| E15 | 🟡 | **Commit message vẫn không mô tả nội dung** — "Hello", "Next one", "UI add and fix", "Theme Aplied function" (có typo). Không thể tra lịch sử | Convention `feat:`/`fix:`/`refactor:` + mô tả |
| E14 | 🟡 | Đã có **3 báo cáo toàn diện cùng ngày 11-08** (V1, V2, bản này) — dễ đọc nhầm bản cũ | Đánh dấu V1/V2 "superseded"; `AGENTS.md` đã trỏ V2, **cần cập nhật trỏ sang bản V3 này** |

---

## 9. Kết luận điều hành và lộ trình đề xuất

### 9.1. Kết luận điều hành

| Câu hỏi | Trả lời ngắn |
|---|---|
| Dự án tiến hay lùi so với V2? | **Cả hai.** `123078b` là bước tiến thật (mobile parity, bộ lọc, fix E18, +6 test). `af3de00` là bước lùi rõ (module theme hỏng, Pint fail, PHPStan error, migration sai cách). |
| Tính năng "Theme Applied" đã xong chưa? | **Chưa chạy được một dòng nào.** 6 lỗi độc lập (E-A1…E-A6); kể cả sửa hết thì giao diện vẫn không đổi vì layout không đọc theme. Khuyến nghị **revert và làm lại có thiết kế**. |
| Có ship production được không? | **Chưa.** Ngoài nợ cũ, giờ phải xử lý trước: rò rỉ session cookie (E-B3), cửa hàng theme org 404 (E-B1), note org bị ẩn (E-B2), migration dev pending (E-B4). |
| Bảo mật tiền tệ có bị ảnh hưởng không? | **Không** — phần transaction/OTP không bị commit mới chạm vào, vẫn là phần chắc nhất của dự án. Nhưng **DB dev đang thiếu 2 migration bảo mật** — cần `migrate` ngay. |
| Nguyên nhân gốc của đợt lùi này? | **Không có cổng chất lượng.** CI chỉ chạy trên `main`, còn `pint`/`phpstan` chưa nằm trong CI. Nếu có, `af3de00` đã không lọt qua. |
| Việc quan trọng nhất tuần tới? | **P0 bên dưới** — đặc biệt 3 việc rẻ mà tác động lớn: đổi thứ tự 1 dòng route (E-B1), xóa `cookies.txt` + rotate key (E-B3), bật CI trên branch + thêm pint/phpstan (E-B5, E-B6). |

### 9.2. Đánh giá riêng về chất lượng 2 commit gần nhất

| | `123078b` "UI add and fix" | `af3de00` "Theme Aplied function" |
|---|---|---|
| Có test kèm? | ✅ 6 test mới, phủ đủ nhánh | ❌ Không |
| Có route để dùng? | ✅ | ❌ Không |
| Qua Pint? | ⚠️ Không đụng file lệch | ❌ 10 fixer/file |
| Qua PHPStan? | ✅ | ❌ 1 error |
| Tên cột đúng schema? | ✅ | ❌ Sai 3/3 |
| Migration đúng cách? | — | ❌ Sửa migration đã chạy |
| Fix đúng bug đã báo cáo? | ✅ E18 | — |
| **Kết luận** | **Chuẩn — nên lấy làm mẫu** | **Chưa hoàn thành đã commit — nên revert** |

### 9.3. Lộ trình ưu tiên đề xuất

```text
P0 — Chặn chảy máu (1–2 ngày, phần lớn là fix rất nhỏ)
  1. 🔴 Xóa cookies.txt + .freebuff/ khỏi git, thêm .gitignore, rotate APP_KEY        (E-B3)
  2. 🔴 Đảo thứ tự route: /themes/org LÊN TRƯỚC /themes/{id} — 1 dòng               (E-B1)
  3. 🔴 Quyết định af3de00: revert (khuyến nghị) hoặc fix đủ 6 lỗi E-A1…E-A6        (8.1)
  4. 🟠 Đổi OrganizationsController::show() về latest()->take(20)->sortBy()          (E-B2)
  5. 🟠 php artisan migrate — DB dev đang thiếu 5 migration                          (E-B4)
  6. 🟠 ./vendor/bin/pint (tự fix 9 file) + fix PHPStan error                        (E-B5)
  7. 🟠 CI trigger trên mọi branch + thêm bước pint --test & phpstan                 (E-B6)
       → Đây là việc quan trọng nhất về dài hạn: nó ngăn cả 6 việc trên tái diễn.

P1 — Hoàn thiện happy path & nhất quán (1 tuần)
  8.  Quyết định + xử lý flow signup-via-invite                                       (E1, E8, BE-7)
  9.  i18n flash message → tiếng Việt                                                 (E9, BE-9)
  10. Fix JS edit_note kiểm tra response.ok                                            (E2, BE-10)
  11. Paginate home + org notes                                                        (E3, BE-4)
  12. Gỡ form chết changeHostRealForm; gỡ 6 method *_fetch                             (E20, E17)

P2 — Làm lại Theme cho tử tế (1–2 tuần, cần thiết kế trước)
  14. Thiết kế: theme lưu style thế nào, layout đọc ra sao, màn "Theme của tôi"        (FE-10, BE-15)
  15. Implement Theme4user/orgStyleController + seed style mẫu
  16. Route apply + redirect+flash + layout render CSS variable                         (E-A6)
  17. Test: mua theme, áp dụng theme, quyền host khi apply cho org

P3 — Bền vững kỹ thuật (2–3 tuần)
  18. Migration rename cột → snake_case chuẩn (ưu tiên gộp 4 biến thể theme id)        (BE-1)
  19. Tách OtpService + WalletTransactionService; thống nhất thứ tự lock                (BE-2, BE-5, E5)
  20. Tối ưu OTP generator + throttle route create transaction                          (BE-6, E7)
  21. Tách partial dùng chung cho desktop/mobile — hết copy 2 bản form                  (FE-6)
  22. Self-host Bootstrap/FA/font; OTP countdown+resend; việt hóa email                 (FE-1, FE-2, FE-3)
  23. Seed demo data; quyết định encore/laravel-admin; xóa phpstan-report.json          (BE-13, BE-12, BE-14)
  24. Dọn 3 thư mục prototype; a11y checklist; smoke E2E JS                             (E13, FE-4, FE-8)
```

---

## Phụ lục A — Đối chiếu với báo cáo V2 (11-08, sau đại tu)

> Mã trong bảng này là **mã của báo cáo V2**. Cột "Trạng thái" phản ánh kiểm chứng thật ngày 11-08 sau 2 commit mới.

| Mã V2 | Nội dung | Trạng thái hiện tại |
|---|---|---|
| E18 | Sửa note dài bị mất nội dung sau ký tự 200 | ✅ **Đã fix** (`note-full-description` + `noteket.js:107,152`) |
| E1 | Flow signup-via-invite chưa hoàn chỉnh | ❌ Chưa xử lý |
| E2 / BE-10 | `edit_note` không kiểm tra `response.ok` | ❌ Chưa xử lý (đã xác minh lại code) |
| E3 / BE-4 | Không phân trang | ❌ Chưa xử lý (`grep paginate` = 0) |
| E5 / BE-5 | Thứ tự lock không nhất quán | ❌ Chưa xử lý |
| E6 / BE-6 | OTP generator O(n) | ❌ Chưa xử lý |
| E7 | Route create transaction không throttle | ❌ Chưa xử lý |
| E9 / BE-9 | Flash tiếng Anh trong UI tiếng Việt | ❌ Chưa xử lý |
| E13 / FE-7 | 4 thư mục prototype song song | ❌ Chưa xử lý |
| E16 / E-B6 | CI chỉ chạy trên `main` | ❌ Chưa xử lý |
| E17 | 6 method `*_fetch` dead code | ❌ Chưa xử lý |
| E19 | Email mời không có link accept/decline | ❌ Chưa xử lý |
| E20 | Form chết `changeHostRealForm` thiếu `@csrf` | ❌ Chưa xử lý (xác minh tại `:35`) |
| BE-1 | Quy ước cột không đồng nhất | ⬇️ **Xấu đi** — thêm biến thể `themeID`/`theme_id` |
| BE-2 / BE-3 | Controller fat, không Form Request/Policy | ❌ Chưa xử lý |
| BE-12 | `encore/laravel-admin` chưa dùng | ❌ Chưa xử lý |
| BE-13 | Seeder chỉ 1 user | ❌ Chưa xử lý |
| BE-14 | `phpstan-report.json` cũ | ❌ Chưa xử lý (giờ đã chạy được PHPStan thật → nên xóa) |
| FE-1…FE-5, FE-8 | CDN, email EN, OTP countdown, a11y, innerHTML, test JS | ❌ Chưa xử lý |
| FE-6 | Trùng lặp desktop/mobile | ⬇️ **Tăng thêm** (+5 khối `content-mobile`) |
| — | Test suite | ▲ **34 → 40 tests, 115+ → 133 assertions, đã chạy thật** |
| — | Mobile parity | ▲ **Hoàn tất** |

## Phụ lục B — Thống kê nhanh codebase (11-08-2026, sau `af3de00`)

| Hạng mục | Số lượng | So với V2 |
|---|---|---|
| Controllers | 23 | = |
| Models | 21 | = |
| Mailables | 12 | = |
| Migrations | 27 (**5 Pending trên DB dev**) | = |
| Routes | **79** (đếm bằng `route:list`) | 75 → 79 |
| Blade production (non-email) | 33 | = |
| Email Blade | 8 | = |
| Factories | 3 (User, Note, Organization) | = |
| Thư mục prototype | 4 (`test`, `test1`, `test2`, `testing`) | = |
| CSS/JS dùng chung | `noteket.css` **705** dòng + `noteket.js` **555** dòng | 591 → 705, 501 → 555 |
| Feature/Unit tests | ✅ **40 tests / 133 assertions PASS (4.1s — chạy thật)** | 34 → 40 |
| Pint | ❌ **FAIL — 9 file** | mới đo |
| PHPStan | ❌ **1 error** (+ crash ở memory limit mặc định) | mới đo |
| Method không có route | **11** (3 signup40acc + 6 `*_fetch` + 2 set-theme) | 9 → 11 |
| Controller rỗng hoàn toàn | 2 (`Theme4userStyleController`, `Theme4orgStyleController`) | mới ghi nhận |
| CI | `.github/workflows/laravel.yml` (chỉ push/PR → `main`) | = |
| Báo cáo trong `report/` | **17** file | 16 → 17 |
| File không nên có trong repo | `cookies.txt` (session thật), `.freebuff/` (13.9 MB), `phpstan-report.json` (152 KB) | mới ghi nhận |

## Phụ lục C — Tài liệu nên đọc theo thứ tự

1. **Báo cáo này (V3)** — nguồn sự thật hiện tại, mọi số liệu đã chạy thật
2. `AGENTS.md` mục "Noteket project-specific context" — 8 cạm bẫy của dự án, đã đối chiếu là chính xác (⚠️ cần cập nhật để trỏ sang V3 thay vì V2)
3. `README.md` — setup, queue, mail, branch workflow, CI
4. `report/11-08-26-Bao-Cao-Toan-Dien-Du-An-Sau-Refactor.md` (V2) — nền tảng của báo cáo này, đọc để hiểu bối cảnh các mã BE-*/FE-*/E-*
5. `report/11-08-26-Bao-Cao-Toan-Dien-Du-An.md` (V1) — lịch sử, **đã superseded 2 lần**
6. `UI_instruction_design/` — spec design (cần khi implement OTP countdown, theme style)

---

## Phụ lục D — Lệnh kiểm chứng lại báo cáo này

```bash
php artisan test                                    # kỳ vọng: 40 passed, 133 assertions
php artisan route:list | wc -l                      # kỳ vọng: 79 route
php artisan migrate:status | grep Pending           # kỳ vọng: 5 dòng
./vendor/bin/pint --test                            # kỳ vọng: FAIL 9 file
./vendor/bin/phpstan analyse --memory-limit=1G      # kỳ vọng: 1 error tại Theme4userController:50
php artisan tinker --execute="echo json_encode(Schema::getColumnListing('organizations'));"
                                                    # kỳ vọng: KHÔNG có 'themeID'
```

---

*Báo cáo được lập ở chế độ **read-only** ngày 11-08-2026 trên nhánh `fix/audit-and-refactor` (HEAD `af3de00`). Không chỉnh sửa source code ngoài việc tạo file báo cáo này. Khác với báo cáo V2, môi trường lần này **đã có `vendor/`** nên toàn bộ số liệu (test, route, migration, pint, phpstan, schema, route matching) đều là **kết quả chạy trực tiếp**, có thể tái kiểm chứng bằng Phụ lục D. Mọi đề xuất mang tính kỹ thuật tham khảo cho team phát triển tiếp theo.*
