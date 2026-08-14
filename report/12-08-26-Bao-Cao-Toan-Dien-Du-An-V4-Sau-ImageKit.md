# Báo cáo toàn diện dự án Noteket — bản V4, sau đợt "Đại tu" và tích hợp ImageKit

| Mục | Nội dung |
|---|---|
| **Ngày** | 12-08-2026 |
| **Người thực hiện** | Senior Laravel PHP Full-stack Developer (audit read-only) |
| **Nhánh** | `fix/audit-and-refactor`, HEAD = `643b47d` "Controller fix", working tree sạch |
| **Phạm vi** | Toàn bộ back-end + Blade production + hạ tầng (CI, migration, composer, .env), đối chiếu với báo cáo V3 ngày 11-08 |
| **Nguyên tắc** | Chỉ đọc code và chạy lệnh kiểm tra. **Không sửa một dòng code nào.** |
| **Cách kiểm chứng** | Mọi con số dưới đây đều chạy thật trên máy: `php artisan test`, `route:list`, `migrate:status`, `pint --test`, `phpstan`, và render thử Blade qua `tinker` |

## Tóm tắt cho người bận

Từ lúc báo cáo V3 chốt sổ đến giờ có hai đợt việc rất khác nhau về chất lượng.

Đợt tối 11-08 (commit `251e2bc` "Đại tu project") là đợt tốt nhất từ đầu dự án. Toàn bộ 6 lỗi nghiêm trọng V3 nêu đích danh đều đã xử lý xong, kèm 37 test mới. Module Theme từ chỗ hỏng hoàn toàn giờ chạy thật và đổi được giao diện. Luồng mời người chưa có tài khoản — thứ bị treo từ đầu dự án — đã hoàn chỉnh. CI đã có cổng chất lượng chặn trước khi test.

Đợt sáng 12-08 (4 commit, tích hợp ImageKit) đi ngược lại. 138 dòng thêm vào, và **không một dòng nào trong tính năng upload chạy được**. Cả ba đường vào đều chết: sai tên class ở route, sai tên class trong controller, thiếu tham số hàm, thiếu biến môi trường. Kèm theo đó là hai lỗi Blade làm hỏng trang cho người dùng đã có ảnh.

Nghịch lý đáng chú ý nhất: **77 test vẫn xanh hết trong khi tính năng mới hỏng 100%**. Lý do là mọi lỗi đều nằm sau điều kiện "người dùng đã có ảnh" hoặc "tổ chức đã có banner", mà không test nào dựng ra trạng thái đó. Test xanh ở đây không có nghĩa gì cả.

CI của nhánh này đang đỏ. Pint fail 7 file, PHPStan fail 10 lỗi, `route:list` crash không chạy nổi. Cổng chất lượng vừa dựng hôm qua đã bị vượt qua ngay hôm sau.

---

## Kết quả kiểm chứng, chạy thật ngày 12-08-2026

| Hạng mục | Lệnh | V3 (11-08) | V4 (hôm nay) |
|---|---|---|---|
| Test suite | `php artisan test` | 40 test / 133 assertion | ✅ **77 test / 257 assertion PASS** (21,7s) |
| Migration | `php artisan migrate:status` | ⚠️ 5 pending | ✅ **30/30 đã chạy**, không còn pending |
| Route order `/themes/org` | route matcher | ❌ rơi vào `themes.show` | ✅ **khớp đúng `themes.org.index`** |
| Code style | `./vendor/bin/pint --test` | ❌ 9 file | ❌ **7 file** — 6/7 là file sửa hôm nay |
| Static analysis | `./vendor/bin/phpstan` | ❌ 1 lỗi | ❌ **10 lỗi** — 10/10 nằm trong code hôm nay |
| Liệt kê route | `php artisan route:list` | chạy được, 79 route | 🔴 **CRASH** — `Class "OrgnizationController" does not exist` |
| Secret trong repo | đọc `.gitignore` | ❌ `cookies.txt` bị commit | ✅ **đã gỡ và ignore** |
| Dung lượng rác | `.freebuff/` | ❌ 13,9 MB trong repo | ✅ **đã xóa** |

Quy mô hiện tại: 24 controller, 22 model, 12 mailable, 42 Blade, 84 khai báo route trong `web.php`, 30 migration, 748 dòng CSS và 592 dòng JS tự viết.

---

## Mục lục

1. [Việc đã làm được kể từ báo cáo V3](#1-việc-đã-làm-được-kể-từ-báo-cáo-v3)
2. [Việc làm hôm nay và tình trạng thật](#2-việc-làm-hôm-nay-và-tình-trạng-thật)
3. [Tổng quan back-end](#3-tổng-quan-back-end)
4. [Tổng quan front-end](#4-tổng-quan-front-end)
5. [Điểm tốt](#5-điểm-tốt)
6. [Lỗi còn tồn tại, xếp theo mức nghiêm trọng](#6-lỗi-còn-tồn-tại-xếp-theo-mức-nghiêm-trọng)
7. [Nên cải tiến](#7-nên-cải-tiến)
8. [Nên bỏ đi](#8-nên-bỏ-đi)
9. [Kết luận và thứ tự việc](#9-kết-luận-và-thứ-tự-việc)

---

## 1. Việc đã làm được kể từ báo cáo V3

Báo cáo V3 viết tại commit `af3de00`. Ngay sau đó commit `251e2bc` đổ vào 93 file, 3.477 dòng thêm. Đây là phần việc chưa từng được ghi nhận trong báo cáo nào, nên tôi đối chiếu lại từng lỗi V3 đã nêu.

### 1.1. Sáu lỗi V3 nêu đích danh, tất cả đã xử lý

**E-B1 — cửa hàng theme cho tổ chức không vào được.** V3 phát hiện `/themes/{id}` đăng ký trước `/themes/org` nên Laravel nuốt mất route sau. Nay `routes/web.php:172-179` đã đảo thứ tự, thêm ràng buộc `->where('id', '[0-9]+')` làm lớp chặn thứ hai, và có comment giải thích tại chỗ để người sau không đảo ngược lại. Tôi chạy lại route matcher: `/themes/org` khớp đúng `themes.org.index`. Có 2 test khóa hành vi này trong `PaginationAndRoutingTest`.

**E-B2 — note mới nhất của tổ chức biến mất.** `OrganizationsController::show()` trước dùng `oldest()->take(20)`, tổ chức quá 20 note thì note mới không bao giờ hiện. Nay đã chuyển sang `paginate(NoteController::NOTES_PER_PAGE)->withQueryString()`. Có test dựng tổ chức nhiều hơn một trang rồi kiểm tra note mới nhất vẫn tới được.

**E-A1…E-A6 — module Theme là dead code.** Đây là phần được làm lại kỹ nhất. Hai method `set_theme_4user` / `setTheme4org` không route, sai tên cột, trả JSON lệch convention đã bị thay hoàn toàn bằng `apply_theme` / `reset_theme` cho cả user lẫn org, có route, có redirect + flash đúng chuẩn, và có 17 test trong `ThemeApplyTest`.

**E-A4 — sửa migration đã chạy.** Cột `themeID` trước được thêm bằng cách sửa thẳng file `create_organizations_table` đã migrate, nên không DB nào có cột đó. Nay có migration riêng `2026_08_11_000005_add_applied_theme_to_organizations`. Tôi kiểm tra schema thật: `organizations` đã có cột `themeID`.

**E7 — route tạo giao dịch không throttle.** Mỗi lần tạo đều sinh OTP và gửi mail, là đường spam rất rẻ. Nay cả 3 route create của user2user, user2organization, organization2user đều có `throttle:5,1`, kèm comment giải thích lý do.

**5 migration pending.** Đã chạy hết. `migrate:status` trả về 30/30 Ran.

### 1.2. Theme giờ đổi giao diện thật

Đây không phải sửa lỗi, đây là tính năng làm mới từ đầu và làm đúng.

`App\Support\ThemeStyle` là một class 120 dòng làm ba việc: lọc dữ liệu `style` trong DB xuống còn mã hex hợp lệ, gộp lên bộ mặc định, và quyết định theme nào thắng. Quy tắc là theme tổ chức thắng theme cá nhân khi đang xem trong phạm vi tổ chức.

Phần đáng khen là `sanitize()`. Giá trị `style` đi thẳng vào thẻ `<style>` trong layout, nên nếu tin dữ liệu DB thì đây là lỗ CSS injection. Hàm này chỉ nhận đúng regex `^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$`, khóa lạ bị bỏ, giá trị lạ rơi về mặc định. Có test riêng đặt tên thẳng là *drops style values that are not plain hex colours so css cannot be injected*. Người viết đã nghĩ tới rủi ro trước khi nó thành sự cố.

Cách nối vào layout cũng gọn: một `View::composer('layouts.app')` trong `AppServiceProvider` tính theme một lần, không controller nào phải tự truyền xuống. `drag_type` được chuẩn hóa về 1/2/3 rồi đặt lên `<body data-drag-type>`, `noteket.js:50` đọc lại từ đó.

### 1.3. Luồng mời người chưa có tài khoản đã hoàn chỉnh

Từ đầu dự án, chia sẻ note cho email lạ chỉ gửi mail mà trong mail không có link nào. Ba method `signup40acc_*` viết ra để nhận lời mời thì không có route. V3 xếp đây là dead feature.

Nay có bảng `invitations`, model `Invitation`, `InvitationController`, mailable `OrganizationInvitation` và view `invitations/accept`. Token băm SHA-256 trước khi lưu, dùng một lần, hết hạn sau 7 ngày. Ba method chết đã bị xóa.

Chi tiết bảo mật quan trọng nhất nằm ở `InvitationController::accept()`: email lấy từ bản ghi lời mời, **không** lấy từ form. Nếu lấy từ form thì người nhận link có thể đổi sang email bất kỳ để chiếm quyền truy cập nội dung. Trong code có comment ghi rõ điều này, và trong test có case đặt tên *ignores any email supplied in the form and always uses the invited email*. 12 test cho riêng luồng này.

### 1.4. Hạ tầng và kỷ luật

CI (`.github/workflows/laravel.yml`) đổi từ chỉ chạy trên `main` sang chạy trên mọi nhánh, và chèn hai bước Pint + PHPStan **trước** bước test. Comment trong file nói thẳng: đây chính là bước lẽ ra đã chặn được commit `af3de00`.

`composer.json` có thêm script `lint`, `phpstan`, `test`, và `check` gộp cả ba.

Rác trong repo đã dọn: `.freebuff/` (13,9 MB gồm cả file DB nhị phân), `cookies.txt` chứa cookie phiên thật, và `phpstan-report.json`. Cả ba đã vào `.gitignore` kèm comment giải thích tại sao.

`AGENTS.md` được bổ sung một mục "Noteket project-specific context" khá dày. Phần ghi chú về đặt tên cột đặc biệt hữu ích: "theme id" trong dự án này có bốn cách viết khác nhau (`theme4ID` ở bảng ví, `theme4_id` ở users, `themeID` ở organizations và theme4org_transactions), và ghi chú nói đúng rằng viết sai chỗ này chính là thứ làm hỏng lần áp dụng theme đầu tiên.

### 1.5. Test tăng gần gấp đôi

40 → 77 test, 133 → 257 assertion. Ba file mới: `InvitationSignupTest` (12), `ThemeApplyTest` (17), `PaginationAndRoutingTest` (8). Đọc tên test thấy rõ chúng khóa đúng những lỗi V3 mô tả, chứ không phải test cho có. Ví dụ *applies a theme the user owns and stores the theme id, not the wallet row id* — đó chính là bug E-A3.

---

## 2. Việc làm hôm nay và tình trạng thật

Bốn commit sáng 12-08:

| Commit | Giờ | Nội dung | Chạy được? |
|---|---|---|---|
| `7327115` | 10:51 | ImageKit integration — 9 file | ❌ |
| `889eff9` | 10:55 | ImageKit integration — SettingsController | ❌ |
| `98b624b` | 11:24 | Banner added — 3 view | ❌ |
| `643b47d` | 11:59 | Controller fix — đổi 2 dòng trống thành `//` | (không ảnh hưởng) |

Ý tưởng đúng. Chuyển từ lưu file local sang CDN ImageKit là hướng hợp lý, và cách đăng ký singleton trong `AppServiceProvider` với named argument là cách làm chuẩn Laravel. Vấn đề nằm ở chỗ toàn bộ phần còn lại chưa từng được chạy thử một lần nào.

### Chuỗi lỗi trên đường vào tính năng upload logo tổ chức

Tôi lần theo đúng thứ tự một request sẽ đi qua. Nó chết ở bước đầu tiên và còn ba cái bẫy nữa phía sau.

**Bước 1, route.** `routes/web.php:74` viết `OrgnizationController::class` — thiếu chữ `a`. Dòng 75 viết `OrganizationController::class` — thiếu chữ `s`. Class thật tên `OrganizationsController`. Cả hai đều không có trong khối `use` ở đầu file, nên PHP hiểu chúng là class ở namespace gốc và không báo lỗi lúc đăng ký route. Hậu quả xuất hiện muộn hơn: `php artisan route:list` crash thẳng với `ReflectionException: Class "OrgnizationController" does not exist`. Không liệt kê nổi route thì cũng không debug được gì bằng công cụ chuẩn.

**Bước 2, nếu route đúng.** `OrganizationsController.php:293` và `:317` gọi `Oragnization::find($id)` — đảo chữ, class không tồn tại. PHPStan bắt được cả hai.

**Bước 3, kiểm tra quyền.** `if ($org->hostID != Auth::user()->$id)`. Dấu `$` thừa biến đây thành truy cập thuộc tính động: PHP đọc `$id` (giá trị là id tổ chức, ví dụ `"5"`) rồi lấy `Auth::user()->{"5"}`, luôn trả `null`. Nếu hai lỗi trên được sửa, dòng này trở thành lỗ hổng phân quyền thật: `$org->hostID != null` gần như luôn đúng, nên **mọi người dùng đều bị chặn**, kể cả host. Còn nếu ai đó "sửa" bằng cách đảo điều kiện thì nó thành ai cũng đổi được logo của tổ chức bất kỳ.

**Bước 4, redirect.** `redirect()->route("organization.settings")` gọi route cần tham số `{id}` mà không truyền. Laravel ném `UrlGenerationException`. Lỗi này nằm ở cả 4 nhánh return của cả hai method — kể cả nhánh thành công.

Riêng `OrgBannerUpload` còn thiếu hẳn tham số `$id` trong chữ ký hàm nhưng dùng `$id` ba lần bên trong. PHPStan báo 3 lỗi `Undefined variable: $id`. Method này cũng upload banner vào thư mục `/org/logo/`.

### Upload avatar: hỏng vì thiếu cấu hình

`SettingsController::AvatarUpload` viết đúng hơn hai method kia, nhưng vẫn không chạy được vì lý do khác.

`AppServiceProvider` đọc `config('services.imagekit.public_key')` từ ba biến `IMAGEKIT_PUBLIC`, `IMAGEKIT_PRIVATE`, `IMAGEKIT_ENDPOINT`. **Không biến nào có trong `.env`, cũng không có trong `.env.example`.** Tôi đọc thẳng constructor của SDK tại `vendor/imagekit/imagekit/src/ImageKit/ImageKit.php:57`: nếu publicKey rỗng thì `throw new InvalidArgumentException('Missing publicKey during ImageKit initialization')`. Nghĩa là mọi request vào `POST /settings/avatar` đều 500 ngay ở khâu resolve container, chưa kịp vào controller.

Trong method còn sót lại `dump($user->avatar_image_url)` ở dòng 57. Đây là code debug, in HTML ra giữa response rồi mới trả redirect.

Validate cũng quá lỏng: `'avatar' => 'required'` và `'file' => 'required'`. Không giới hạn loại file, không giới hạn dung lượng. Người dùng gửi file 500 MB hay file `.php` đều lọt qua tầng validate.

### Hai lỗi Blade từ commit "Banner added"

Đây là phần tôi kiểm chứng bằng cách render thử, vì suy luận trên giấy dễ sai.

**Lỗi 1 — `organizations/index.blade.php`, hai chỗ.** Code viết `{{organization->banner_url}}`, thiếu dấu `$`. Tôi cho Blade compile chuỗi này rồi eval thử:

```
COMPILED: <?php echo e(organization->banner_url, false); ?>
EVAL FAIL => Error: Undefined constant "organization"
```

PHP 8 coi `organization` là hằng số chưa định nghĩa và ném `Error`. Đây là throwable, không phải warning, nên trang tổ chức trả 500. Điều kiện kích hoạt là `@if ($organization->banner_url)` — nghĩa là ngay khi bất kỳ tổ chức nào có banner, danh sách tổ chức sập. Lỗi nằm ở cả vòng lặp tổ chức mình host lẫn vòng lặp tổ chức mình là thành viên.

**Lỗi 2 — `topbar.blade.php:19`.** Điều kiện kiểm tra `auth()->user()->avatar_image_url` nhưng thẻ `img` lại đọc `$user->avatar_image_url`. Biến `$user` chỉ tồn tại ở view `settings`, còn topbar được include vào mọi trang. Tôi render thử partial này với một user có avatar:

```
IMG TAG: <img src="" style="width: 40px; ..." alt="Avatar của Test" class="rounded-circle">
```

Không crash — PHP chỉ cảnh báo rồi trả chuỗi rỗng. Kết quả thực tế là ảnh đại diện hiện đúng ở trang Cài đặt và vỡ ở tất cả các trang khác. Đây là loại lỗi khó chịu vì nó im lặng: log không đỏ, test không fail, chỉ người dùng thấy ô trống.

### Vì sao 77 test vẫn xanh

Cả bốn lỗi trên đều nấp sau một điều kiện mà test suite không bao giờ dựng ra: user phải có `avatar_image_url`, tổ chức phải có `banner_url`. Factory không sinh hai trường này, và không có test nào cho tính năng upload. Suite chạy qua toàn bộ nhánh `@else`.

Bài học rút ra không phải "test vô dụng", mà là: **tính năng mới commit mà không kèm test thì suite xanh chỉ chứng minh code cũ chưa hỏng.**

### Vài thứ khác trong ngày

`config/filesystems.php` đã gỡ disk `r2`, nhưng `.env` vẫn còn nguyên 5 biến `R2_*` trỏ tới giá trị placeholder. `.env` cũng có 8 khóa trùng lặp: `FILESYSTEM_DISK` và 7 biến `MAIL_*` xuất hiện hai lần, khối sau ghi đè khối trước.

`layouts/app.blade.php` giờ hiển thị flash `success` bằng **hai** cách cùng lúc: một `alert alert-success` của Bootstrap và một toast. Trong khi `error` và `warning` vẫn chỉ có toast. Người dùng làm thành công một thao tác sẽ thấy thông báo hai lần ở hai chỗ. Ba div `data-toast` cũng bị đổi từ `display: none` sang `display: block`, dù chúng là marker rỗng cho JS đọc chứ không có nội dung để hiện.

Commit `643b47d` tên "Controller fix" chỉ thay hai dòng trống trong `Controller.php` bằng `//`. File này vẫn còn 6 import không dùng, trong đó `use Illuminat\Http\Request;` sai chính tả namespace (`Illuminat` thiếu `e`). Pint vẫn báo `no_unused_imports` cho đúng file này.

---

## 3. Tổng quan back-end

Monolith Laravel 13.23 chạy PHP 8.3, toàn bộ route trong một file `web.php` với 84 khai báo. Kiến trúc vẫn là controller "béo": chưa có Form Request, chưa có Policy, chưa có Service layer. Validate nằm trong action, kiểm tra quyền viết tay bằng `if` lặp lại ở gần như mọi method.

Trạng thái từng module:

| Module | Đánh giá | Ghi chú |
|---|---|---|
| Transaction (3 loại) | ★★★★☆ | Vẫn là phần chắc nhất. `decimal:2`, `lockForUpdate`, OTP băm, tối đa 5 lần thử, TTL 10 phút, giờ có thêm throttle ở cả bước create |
| Note | ★★★★☆ | Bộ lọc 5 chế độ, phân trang giữ query string, quyền xem rõ ràng |
| Invitation | ★★★★☆ | Mới, làm đúng. Token băm, dùng một lần, email lấy từ lời mời |
| Theme | ★★★★☆ | Từ ★☆☆☆☆ ở V3. Sanitize hex, view composer, 17 test |
| Organization | ★★★☆☆ | Phần cũ tốt, hai method upload mới kéo điểm xuống |
| Settings | ★★☆☆☆ | Upload avatar không chạy, còn `dump()` |
| Auth / Password | ★★★★☆ | Không đổi, ổn định |

Về database: 30 migration, tất cả đã chạy. Quy ước đặt tên vẫn lẫn lộn camelCase và snake_case như `AGENTS.md` mô tả — đây là nợ kỹ thuật thật, nhưng sửa lúc này rủi ro cao hơn lợi ích, nên tôi xếp vào nhóm "ghi nhận, chưa động".

Điểm cần biết khi lên production: `lockForUpdate()` là no-op trên SQLite. Bảo vệ race condition trong giao dịch chỉ thật sự có hiệu lực trên MySQL hoặc Postgres.

---

## 4. Tổng quan front-end

Blade + Bootstrap 5.3.8 từ CDN, Font Awesome 6.5.2, font Caveat. CSS và JS tự viết đặt trong `public/`, không đi qua Vite: 748 dòng CSS, 592 dòng JS. Vite và Tailwind vẫn nằm trong `package.json` nhưng thực tế không dùng cho giao diện production.

Mỗi màn có hai bản: `@yield('content')` cho desktop và `@yield('content-mobile')` cho mobile, chọn bằng CSS media query. Cách này đơn giản và đang hoạt động, đổi lại là nội dung bị viết hai lần ở nhiều view.

Việc theme đổi được giao diện thật là bước tiến lớn của front-end trong đợt này. Nhưng nó chỉ hoạt động ở những chỗ Blade dùng `var(--nk-yellow)` thay vì mã hex cứng. `AGENTS.md` có ghi chú đúng về điều này. Tôi chưa rà hết 42 view để đếm chính xác còn bao nhiêu chỗ hard-code hex — đây là việc nên làm nhưng nằm ngoài phạm vi lần audit này.

Ba vấn đề front-end đang mở: lỗi `{{organization->banner_url}}`, lỗi `$user` trong topbar, và flash `success` hiển thị trùng hai lần.

---

## 5. Điểm tốt

Xếp theo mức đáng ghi nhận.

**Sửa lỗi có test đi kèm.** Đây là thay đổi thói quen rõ rệt nhất. 37 test mới đều bám vào lỗi cụ thể V3 đã mô tả, tên test viết bằng câu mô tả hành vi chứ không phải `test_function_works`. Đọc danh sách test giờ gần như đọc được đặc tả.

**Nghĩ tới bảo mật trước khi bị nhắc.** `ThemeStyle::sanitize()` chặn CSS injection, `Invitation` băm token và ép email từ server. Cả ba đều không nằm trong danh sách yêu cầu của báo cáo V3 — người viết tự nhận ra.

**Comment giải thích "tại sao", không phải "cái gì".** Ví dụ ở `routes/web.php:169-171` giải thích tại sao thứ tự route quan trọng, ở `OrganizationsController:65-66` giải thích tại sao phải paginate thay vì `take(20)`. Đây là loại comment giữ được giá trị sau 6 tháng.

**Dọn secret khỏi repo.** `cookies.txt` chứa cookie phiên thật đã bị gỡ và ignore.

**CI có cổng thật.** Pint và PHPStan chạy trước test, trên mọi nhánh. Thiết kế đúng — vấn đề chỉ là hiện tại nó đang đỏ mà commit vẫn đi tiếp.

---

## 6. Lỗi còn tồn tại, xếp theo mức nghiêm trọng

### Chặn merge

**E-C1 — Sai tên class ở `routes/web.php:74` và `:75`.**
`OrgnizationController` và `OrganizationController` đều không tồn tại. Class thật là `OrganizationsController`, và cũng chưa được import. Hậu quả trực tiếp: `php artisan route:list` crash. Đề xuất: sửa cả hai thành `OrganizationsController::class`, class này đã có trong khối `use` sẵn ở dòng 9.

**E-C2 — `Oragnization::find()` ở `OrganizationsController:293` và `:317`.**
Sai chính tả tên model. Đề xuất: đổi thành `Organization::find($id)` và thêm kiểm tra null ngay sau, vì hiện tại `$org->hostID` sẽ nổ nếu không tìm thấy tổ chức.

**E-C3 — `Auth::user()->$id` ở `:294` và `:318`.**
Dấu `$` thừa. Đề xuất: đổi thành `Auth::user()->id`, hoặc gọn hơn là `Auth::id()`. Đây là lỗi phân quyền, cần rà kỹ khi sửa.

**E-C4 — `OrgBannerUpload` thiếu tham số `$id`.**
Chữ ký hiện tại là `(Request $request, Imagekit $imagekit)` nhưng thân hàm dùng `$id` ba lần. Đề xuất: thêm `$id` vào chữ ký, khớp với `OrgLogoUpload`.

**E-C5 — `{{organization->banner_url}}` ở `organizations/index.blade.php`, hai chỗ.**
Thiếu `$`, gây `Error: Undefined constant` và trả 500. Đề xuất: thêm `$`. Nên viết thêm một test render trang tổ chức với `banner_url` đã set, vì đây đúng là kẽ hở mà suite hiện tại không phủ.

**E-C6 — Thiếu biến môi trường ImageKit.**
`.env` và `.env.example` đều không có `IMAGEKIT_PUBLIC`, `IMAGEKIT_PRIVATE`, `IMAGEKIT_ENDPOINT`. SDK ném `InvalidArgumentException` khi key rỗng. Đề xuất: thêm ba khóa vào `.env.example` để trống làm tài liệu, điền giá trị thật vào `.env` local. Cân nhắc bọc phần resolve trong try/catch để thiếu cấu hình trả về thông báo thân thiện thay vì 500 trắng.

### Ảnh hưởng người dùng

**E-C7 — `$user` không tồn tại trong `topbar.blade.php:19`.**
Ảnh đại diện chỉ hiện ở trang Cài đặt, vỡ ở mọi trang khác. Đề xuất: đổi thành `auth()->user()->avatar_image_url` cho khớp với chính điều kiện `@if` ngay phía trên.

**E-C8 — `dump()` còn sót ở `SettingsController:57`.**
Đề xuất: xóa. Nếu cần theo dõi, dùng `Log::debug()`.

**E-C9 — Validate upload quá lỏng.**
Cả `'avatar' => 'required'` lẫn `'file' => 'required'` đều không chặn loại file và dung lượng. Đề xuất: `['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']`.

**E-C10 — `redirect()->route('organization.settings')` thiếu tham số.**
Bốn chỗ trong hai method upload. Đề xuất: truyền `$id`.

**E-C11 — Banner lưu vào thư mục logo.**
`OrgBannerUpload` dùng `'folder' => '/org/logo/'`. Đề xuất: đổi thành `/org/banner/`.

**E-C12 — Flash `success` hiện hai lần.**
`layouts/app.blade.php` render cả alert Bootstrap lẫn toast cho `success`, trong khi `error` và `warning` chỉ có toast. Đề xuất: chọn một kiểu và áp dụng cho cả ba. Đồng thời trả ba div `data-toast` về `display: none` — chúng là marker cho JS, không phải phần tử hiển thị.

### Nợ kỹ thuật, không chặn

**E-C13 — CI đang đỏ.** Pint fail 7 file, PHPStan fail 10 lỗi. Sửa xong E-C1…E-C4 sẽ hết phần lớn lỗi PHPStan; Pint chỉ cần chạy `composer lint:fix`.

**E-C14 — `.env` có 8 khóa trùng lặp** và 5 biến `R2_*` không còn dùng.

**E-C15 — `Controller.php` còn 6 import thừa**, trong đó `Illuminat\Http\Request` sai chính tả namespace.

**E-C16 — Không có test nào cho tính năng upload.**

---

## 7. Nên cải tiến

**Gộp ba method upload thành một chỗ.** `AvatarUpload`, `OrgLogoUpload`, `OrgBannerUpload` lặp lại gần như nguyên xi: validate, `fopen`, `uploadFile`, kiểm tra `->error`, gán URL, save, redirect. Tách ra một `App\Services\ImageUploadService` với chữ ký kiểu `upload(UploadedFile $file, string $folder, string $fileName): string` thì ba controller còn lại mỗi cái 5 dòng, và có một chỗ duy nhất để viết test.

**Lưu `fileId` mà ImageKit trả về.** Hiện code chỉ lấy `->result->url`. Mỗi lần đổi avatar, file cũ nằm lại trên ImageKit vĩnh viễn, không có cách nào xóa vì không biết id. Sau vài tháng đây là tiền thật. Thêm cột `avatar_file_id` và xóa file cũ sau khi upload thành công.

**Bọc lời gọi ImageKit trong try/catch.** SDK dùng Guzzle bên dưới. Mạng chập, ImageKit downtime hay timeout đều ném exception chứ không trả về object có `->error`. Hiện tại nhánh đó dẫn thẳng tới trang 500.

**Đưa upload vào queue.** Dự án đã cấu hình `QUEUE_CONNECTION=database`. Upload đồng bộ khiến người dùng ngồi chờ round-trip tới ImageKit trong lúc request web bị giữ.

**Tách Form Request và Policy.** Kiểm tra "có phải host không" hiện được viết lại bằng tay ở khoảng chục method trong `OrganizationsController`, và chính chỗ đó là nơi E-C3 phát sinh. Một `OrganizationPolicy` gom lại thành một điểm sửa duy nhất.

**Thêm test cho upload.** Dùng `Storage::fake()` và mock `ImageKit` qua container — đã có singleton nên `$this->app->instance(ImageKit::class, $mock)` là đủ. Ba test tối thiểu: upload thành công cập nhật URL, người không phải host bị chặn, file sai định dạng bị từ chối.

**Rà hex cứng trong Blade.** Theme chỉ ăn vào những chỗ dùng `var(--nk-*)`. Nên grep một lượt và chuyển các mã hex còn lại trong view production sang biến CSS. View email giữ nguyên hex vì mail client không hỗ trợ CSS variable.

**Ghi chú thẳng vào README** rằng `lockForUpdate()` không có tác dụng trên SQLite. Hiện điều này chỉ nằm trong `AGENTS.md`, mà người deploy thì đọc README.

---

## 8. Nên bỏ đi

**`encore/laravel-admin` trong `composer.json`.** Tôi kiểm tra `bootstrap/cache/packages.php`: package này đang được auto-discover và `AdminServiceProvider` nạp vào mọi request. Không có một dòng code nào trong `app/`, `routes/`, `config/` dùng tới nó. Một admin panel không dùng nhưng vẫn nạp là vừa chậm vừa thừa bề mặt tấn công. Gỡ bằng `composer remove encore/laravel-admin`.

**`hernol/uploadthing-php`.** Không có tham chiếu nào trong code. Có lẽ là thư viện thử trước khi chọn ImageKit. Giờ đã chọn xong thì gỡ.

**Năm biến `R2_*` trong `.env`.** Disk `r2` đã bị xóa khỏi `config/filesystems.php` hôm nay, các biến này không còn ai đọc.

**Tám khóa trùng trong `.env`.** `FILESYSTEM_DISK` và 7 biến `MAIL_*` khai báo hai lần. Giữ một khối, xóa khối kia.

**Sáu import thừa trong `Controller.php`.** Cả file chỉ có một class abstract rỗng. Xóa hết import, giữ lại `namespace` và khai báo class.

**`dump()` ở `SettingsController:57`.**

**Bốn thư mục prototype trong `resources/view/` (số ít):** `test`, `test1`, `test2`, `testing`, cộng `testing_view`, `css`, `js`. Blade thật nằm ở `resources/views/` (số nhiều). Hai đường dẫn chỉ khác nhau một chữ cái là bẫy đọc nhầm. Nếu `test2` vẫn còn dùng làm styleguide thì đổi tên thành `docs/ui-styleguide/` cho rõ; ba thư mục còn lại thì xóa, git vẫn giữ lịch sử.

**Ba method `*_fetch` và các method chết còn lại trong controller** — V3 đã liệt kê, đợt `251e2bc` xử lý được phần theme và `signup40acc_*`. Nên rà nốt một lượt cuối bằng cách đối chiếu danh sách method public với `route:list` (sau khi sửa E-C1 để lệnh này chạy được).

**`.freebuff/` và `phpstan-report.json`** đã xóa và ignore rồi, ghi lại ở đây để đóng mục.

---

## 9. Kết luận và thứ tự việc

### So sánh với V3

| Tiêu chí | V3 (11-08) | V4 (12-08) | |
|---|---|---|---|
| Domain model | ★★★★☆ | ★★★★☆ | = |
| Backend logic | ★★★☆☆ | ★★★★☆ | ▲ |
| Module Theme | ★☆☆☆☆ | ★★★★☆ | ▲▲▲ |
| Luồng mời thành viên | ★★☆☆☆ | ★★★★☆ | ▲▲ |
| Production UI | ★★★★☆ | ★★★☆☆ | ▼ (2 lỗi Blade mới) |
| Test coverage | ★★★☆☆ | ★★★★☆ | ▲ (40 → 77) |
| Bảo mật tiền tệ | ★★★★☆ | ★★★★☆ | = |
| Kỷ luật kỹ thuật | ★★☆☆☆ | ★★☆☆☆ | = (CI có cổng nhưng đang đỏ) |
| Tính năng upload | — | ☆☆☆☆☆ | mới, chưa chạy được |
| Sẵn sàng production | Chưa | **Chưa** | |

### Nhận định

Đợt `251e2bc` cho thấy khi có thời gian và có checklist rõ, chất lượng code ở đây lên rất nhanh. Sáu lỗi nghiêm trọng xử lý gọn, 37 test mới bám đúng vào lỗi, hai tính năng khó nhất — theme và invitation — đều làm đúng phần bảo mật ngay từ đầu. Đó là mức làm việc của người đã hiểu dự án.

Đợt sáng nay là chuyện khác. Không phải vấn đề năng lực: `Oragnization`, `OrgnizationController`, `->$id` đều là lỗi gõ, không phải lỗi tư duy. Vấn đề là **code được commit mà chưa chạy thử lần nào**. Một lần bấm nút upload trên trình duyệt sẽ lộ ra E-C1 trong ba giây. Một lần chạy `composer check` sẽ lộ ra 10 lỗi PHPStan. Cổng chất lượng đã dựng xong hôm qua, nhưng nó chỉ có tác dụng nếu được chạy trước khi commit.

Đây cũng là lý do tôi không đánh giá đợt hôm nay là bước lùi về kỹ năng. Nó là bước lùi về quy trình, và quy trình thì sửa được trong một ngày.

### Thứ tự việc đề xuất

**Trước tiên, khoảng 30 phút, để CI xanh trở lại:**

1. `routes/web.php:74-75` — sửa hai tên class thành `OrganizationsController::class` (E-C1)
2. `OrganizationsController:293, 317` — `Oragnization` → `Organization`, thêm kiểm tra null (E-C2)
3. `OrganizationsController:294, 318` — `Auth::user()->$id` → `Auth::id()` (E-C3)
4. `OrgBannerUpload` — thêm tham số `$id` (E-C4)
5. `organizations/index.blade.php` — thêm `$` vào hai chỗ (E-C5)
6. `topbar.blade.php:19` — `$user` → `auth()->user()` (E-C7)
7. Xóa `dump()` ở `SettingsController:57` (E-C8)
8. Chạy `composer lint:fix` rồi `composer check`

**Sau đó, để tính năng chạy thật:**

9. Thêm ba khóa `IMAGEKIT_*` vào `.env` và `.env.example` (E-C6)
10. Sửa validate upload cho cả ba method (E-C9)
11. Sửa 4 chỗ `redirect()->route('organization.settings')` thiếu tham số (E-C10)
12. Sửa folder banner (E-C11)
13. Thống nhất cách hiển thị flash (E-C12)
14. Mở trình duyệt, upload thử một ảnh cho cả avatar, logo và banner

**Rồi mới đến phần dọn dẹp và nâng chất:**

15. Viết 3 test cho luồng upload
16. Gỡ `encore/laravel-admin` và `hernol/uploadthing-php`
17. Dọn `.env` (khóa trùng + R2), dọn import thừa trong `Controller.php`
18. Tách `ImageUploadService`, lưu `fileId`, bọc try/catch

**Một thay đổi quy trình đáng giá hơn cả 18 việc trên:** chạy `composer check` trước mỗi lần commit. Cả 10 lỗi PHPStan và 7 file Pint hôm nay đều bị bắt trong 40 giây nếu lệnh đó được chạy. Nếu muốn tự động hóa, thêm một git hook `pre-commit` gọi `composer lint` là đủ cho phần lớn trường hợp.

---

*Báo cáo lập ngày 12-08-2026 trên commit `643b47d`, nhánh `fix/audit-and-refactor`. Toàn bộ số liệu lấy từ lệnh chạy thật, không suy đoán từ tài liệu. Không có file nào của dự án bị sửa trong quá trình lập báo cáo.*
