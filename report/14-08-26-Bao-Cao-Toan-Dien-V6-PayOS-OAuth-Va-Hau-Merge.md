# Báo cáo toàn diện dự án Noteket — bản V6: PayOS, OAuth, và hậu quả của merge

| Mục | Nội dung |
|---|---|
| **Ngày** | 14-08-2026 (chiều, sau khi merge `fix/queue-reliability` vào `main` lúc 15:06) |
| **Người thực hiện** | Senior Laravel PHP Full-stack Developer (audit read-only) |
| **Nhánh** | `main`, HEAD = `c37f13b` (merge commit) |
| **Phạm vi** | Toàn bộ dự án, trọng tâm là 17 commit kể từ V5: nhánh queue-reliability, PayOS payment, Google OAuth, rate limiting, luồng share mới, và hai lần merge tay |
| **Nguyên tắc** | Chỉ đọc code và chạy lệnh kiểm tra. Không sửa một dòng code nào. |
| **Cách kiểm chứng** | `php artisan test`, `pint --test`, `phpstan --memory-limit=1G`, `route:list`, `php -l`, đối chiếu từng commit bằng `git show`, và một script tái hiện đúng cơ chế Laravel nâng warning thành exception trong HTTP request |

## Tóm tắt cho người bận

Hai chuyện xảy ra cùng lúc kể từ V5, và chúng kéo dự án về hai hướng ngược nhau.

Hướng tốt: nhánh `fix/queue-reliability` (ba commit tối 12-08) đóng gần như trọn checklist V5 — `UserEmail` đã sửa đúng, có test render cho cả 12 mailable, có listener `JobFailed`, có schedule dọn `failed_jobs`, bốn mail thông báo đã chuyển sang queue, OTP có try/catch kèm dọn giao dịch mồ côi, vòng quét bcrypt vô nghĩa đã xóa, PHPStan từ 10 lỗi xuống 1. Đây là đợt trả nợ kỹ thuật tốt nhất từ đầu dự án.

Hướng xấu: các commit tay 13–14/08 (PayOS, OAuth, share bằng link, rate limiting, "Fix Id on Database migration") cộng với hai lần merge tay đã đưa `main` về trạng thái tệ nhất từng ghi nhận: **83 trong 85 test lỗi** — không phải fail assertion, mà chết ngay từ bước migrate vì bảng `users` có hai primary key. Nghĩa là mọi máy mới, mọi lần CI chạy, `migrate:fresh` đều đổ vỡ. Trong khi đó cả ba tính năng mới — nạp point qua PayOS, đăng nhập Google, chia sẻ bằng link — **không có tính năng nào chạy được đến bước thứ hai**: payment không có route nào trỏ tới controller, nút bấm JS chết vì `getElementId` không tồn tại, OAuth thì route gọi `oauth_redirect` còn controller đặt tên `auth_redirect`. Và một lỗi âm thầm hơn cả: rate limiter `transaction` viết `$request->user->id` (thiếu ngoặc), khiến **mọi route giao dịch trả 500 kể từ trưa 13-08**.

Điều đáng nói nhất không phải từng lỗi — mà là không lỗi nào trong số đó sống sót nổi qua một phút bấm thử trên trình duyệt. Chúng đều nằm ở lần chạy đầu tiên. Chuỗi bốn commit "Fix PHPStan" trong 24 phút sáng nay cho thấy quy trình hiện tại là đẩy lên CI rồi chờ màu đỏ, thay vì chạy tại chỗ trước khi commit.

---

## Kết quả kiểm chứng, chạy thật chiều 14-08-2026

| Hạng mục | Lệnh / cách đo | Kết quả | So với V5 |
|---|---|---|---|
| Test suite | `php artisan test` | 🔴 **2 pass / 83 error trên 85 test** — chết từ bước migrate | V5: 77/77 pass |
| Nguyên nhân test chết | SQL do Laravel sinh | `table "users" has more than one primary key` | mới, từ commit `059f184` |
| Code style | `pint --test` | ❌ 10 file lệch | V5: 7 file |
| Static analysis | `phpstan` | ❌ 1 lỗi (`cnullable()` — migration users:27) | V5: 10 lỗi — cải thiện lớn |
| Liệt kê route | `php artisan route:list` | ✅ 87 route, hết crash | V5: crash — **đã sửa** |
| Cổng Pint trên CI | `.github/workflows/laravel.yml` | 🔴 đã bị **comment tắt** ở commit "Fix CI" | V5: đang bật |
| Route cho PaymentController | grep toàn bộ lịch sử `routes/` | **0 — chưa từng tồn tại ở bất kỳ commit nào** | |
| Khóa `PAYOS_*`, `GOOGLE_*` | grep `.env` và `.env.example` | **0 khóa** ở cả hai file | |
| Limiter `transaction` | tái hiện cơ chế error-handler HTTP | `$request->user->id` ném `ErrorException` | hỏng từ `a411d66` |

Quy mô phần khảo sát: 17 commit (3 thuộc nhánh queue, 8 commit tay, 3 merge, còn lại là fix CI), 4 file controller mới/sửa lớn, 25 file migration bị sửa hàng loạt, ~90 dòng JS mới.

---

## Mục lục

1. [Thay đổi kể từ V5](#1-thay-đổi-kể-từ-v5)
2. [Lỗi nghiêm trọng](#2-lỗi-nghiêm-trọng)
3. [Lỗi chấp nhận được](#3-lỗi-chấp-nhận-được)
4. [Lỗi nhỏ nhặt](#4-lỗi-nhỏ-nhặt)
5. [Lỗi chỉ lộ khi scale](#5-lỗi-chỉ-lộ-khi-scale)
6. [Trạng thái nợ cũ V4/V5](#6-trạng-thái-nợ-cũ-v4v5)
7. [Đánh giá trình độ qua phần code tự viết](#7-đánh-giá-trình-độ-qua-phần-code-tự-viết)
8. [Điểm tốt](#8-điểm-tốt)
9. [Khuyến nghị](#9-khuyến-nghị)
10. [Kết luận](#10-kết-luận)

---

## 1. Thay đổi kể từ V5

Chia làm ba nhóm, vì chất lượng ba nhóm khác nhau một trời một vực.

**Nhóm A — nhánh `fix/queue-reliability` (tối 12-08, ba commit `157c28d`, `110021e`, `5838f5d`).** Làm đúng theo lộ trình V5, bước 0 đến bước 2: sửa `UserEmail` sang public kèm constructor nhận cả model lẫn id; thêm `MailableRenderTest` (render đủ 12 mailable qua đúng vòng serialize → unserialize → render của worker) và `MailHardeningTest`; listener `JobFailed` ghi log tên mailable; `Schedule::command('queue:prune-failed')`; worker flags `--tries=3 --backoff=10 --timeout=60` vào cả `composer dev` lẫn README kèm giải thích vì sao timeout phải nhỏ hơn retry_after; bốn mail thông báo chuyển sang `->queue()`; OTP bọc try/catch, gửi hỏng thì xóa giao dịch vừa tạo; share/invite có trần 20 email và throttle 5,1. Đồng thời đóng sáu lỗi chặn CI của V4. PHPStan nhờ đó từ 10 lỗi còn 1.

**Nhóm B — các commit tay 13–14/08** (`a411d66` → `e6270f2`): thêm PayOS SDK v2 + Socialite vào composer, `PaymentController` 111 dòng, `OauthAuthenticationController` 32 dòng, ba `RateLimiter::for` trong routes, luồng share-bằng-link thay cho share-bằng-email (phần email bị comment out), modal payment + ~40 dòng JS, và loạt sửa migration. Đây là phần mà mục 2 dành gần trọn cho nó.

**Nhóm C — hai lần merge tay** (`d3745fb` lúc 13:04 và `3d1c5d0` + `c37f13b` lúc 15:05–15:06). Merge có conflict và cách giải quyết conflict để lại ba vết thương sẽ nói rõ ở E-E8: mất throttle của share route, tái nhập typo `cnullable`, và cặp route–method lệch nhau.

---

## 2. Lỗi nghiêm trọng

Bảy lỗi. Tiêu chí xếp loại: chặn CI, làm chết tính năng đang có, hoặc sai ở chỗ dính đến tiền.

### E-E1 — Migration hai primary key: 83/85 test chết, mọi máy mới không cài được app

Commit `059f184` ("Fix Id on Database migration") sửa **25 file migration** từ `$table->id()` (vốn đã đúng) thành:

```php
$table->id()->autoIncrement()->unique()->primary();
```

`id()` trong Laravel đã là BIGINT auto-increment primary key. Chuỗi thêm phía sau khiến schema builder phát sinh thêm mệnh đề `primary key ("id")` thứ hai — SQLite từ chối thẳng: `table "users" has more than one primary key`. MySQL cũng sẽ từ chối với `Multiple primary key defined`. Hệ quả đo được: `php artisan test` còn đúng 2 test xanh (2 test không đụng database), 83 test error ngay bước migrate. `migrate:fresh` chết trên mọi môi trường mới — máy dev mới, CI, và production tương lai. Máy hiện tại chỉ "sống" vì database.sqlite đã migrate từ trước bằng bản migration cũ.

Trớ trêu kép: commit mang tên "Fix Id" nhưng phá id; và toàn bộ giá trị của hai file test mới từ nhánh queue (191 dòng, viết đúng bài) bị vô hiệu chỉ vài giờ sau khi merge. Sửa: bỏ cả ba `->autoIncrement()->unique()->primary()`, trả về `$table->id()`, ở đủ 25 file.

### E-E2 — Rate limiter `transaction`: mọi route giao dịch trả 500 kể từ trưa 13-08

`routes/web.php:46`:

```php
RateLimiter::for('transaction', function (Request $request){
    return Limit::perMinute(30)->by($request->user->id);
});
```

`$request->user` (không ngoặc) không phải là user đang đăng nhập — nó là magic accessor đọc **input tên `user`**, và form giao dịch không có field nào tên như vậy, nên trả `null`. `null->id` phát warning "Attempt to read property on null", mà trong HTTP request Laravel nâng mọi warning thành `ErrorException` (tôi đã tái hiện đúng cơ chế error-handler này, kết quả: throw). Limiter chạy trên **mọi** request thuộc group `throttle:transaction` — nghĩa là toàn bộ 20 route giao dịch, mua theme, verify OTP đều trả 500 từ commit `a411d66`. Đây chính là loại lỗi mà 77 test cũ bắt được ngay (CoreWorkflowsTest có test giao dịch end-to-end) — nhưng E-E1 đã giết test trước khi chúng kịp lên tiếng.

Sửa một dòng: `->by($request->user()?->id ?: $request->ip())`. Lưu ý cùng pattern `$request->user->id` còn lặp ở `share_note_link`, `delete_share_note`, `share_add_member_link`, `payment_complete_bill` — sửa cả cụm.

### E-E3 — Nạp point qua PayOS: không tồn tại đường nào từ nút bấm đến controller

Tính năng trung tâm của đợt này chết ở cả ba tầng, tầng nào cũng chết ngay bước đầu:

1. **Không có route.** Grep toàn bộ lịch sử git: chưa từng có commit nào khai báo route cho `PaymentController`. JS gọi `fetch('/point/payment/create/')` → 404 vĩnh viễn. Bốn method của controller (111 dòng) là code không thể chạm tới.
2. **JS chết trước cả khi fetch.** `noteket.js:622`: `document.getElementId('points_input')` — sai tên hàm (`getElementById`) *và* sai id (`point_input`, không có `s`). Dòng 633: `if (response.redirected)` nhưng biến fetch được gán tên `api` — `response` chưa từng khai báo. Mọi cú bấm "Pay for it!" rơi vào `catch` và alert lỗi. Ba lỗi này hiện nguyên hình trong console ở lần bấm đầu tiên.
3. **Controller chết ở dòng dựng dữ liệu.** `payment_for_point` gọi `route('payment.success')` và `route('payment.cancel')` — hai route name không tồn tại → `RouteNotFoundException` ném **ngoài** khối try, sau khi đã save bản ghi `Payment` trạng thái Pending. Kể cả có route, bản ghi cũng không lưu nổi: controller truyền `'points' => $points` trong khi model khai `$fillable = [... 'point']` (không `s`) — giá trị bị mass-assignment lặng lẽ bỏ, cột `point` NOT NULL → insert nổ.

Ngoài ra `.env` lẫn `.env.example` không có khóa `PAYOS_*` nào, nên singleton PayOS đang được dựng bằng credentials null.

### E-E4 — `payment_verify`: hàm cầm tiền thật sai gần như mọi dòng

Hàm này hiện không có route (nằm trong E-E3) nên chưa gây hại — nhưng nó là webhook cộng tiền, và nếu ngày nào đó được nối route mà giữ nguyên nội dung thì hậu quả bằng tiền thật. Điểm danh theo dòng trong `PaymentController.php`:

- Dòng 68: `Payment::find($id)->first()` — `find($id)` đã trả về model; gọi tiếp `->first()` mở một query mới và trả về **bản ghi đầu tiên của cả bảng**, bất kể `$id`. Mọi webhook đều verify nhầm vào payment cũ nhất.
- Dòng 69: `User::find('id', $order->user_id)` — sai chữ ký (`find` nhận khóa chính, không nhận cặp cột-giá trị) và sai tên cột (bảng dùng `userID`, không phải `user_id`), rồi lại `->first()` kiểu trên.
- Dòng 86: `increment('balance', $payment->amount)` — cộng **tiền VND** vào số dư **point**. Người nạp 10 point (10.000đ) sẽ được cộng 10.000 vào balance: sai lệch đúng 1.000 lần.
- Dòng 78–93: `return redirect()` nằm **trong** closure của `DB::transaction`, còn hàm ngoài không `return` kết quả transaction — response bị nuốt, webhook nhận body rỗng.
- `back()` dùng trong ngữ cảnh webhook: bên gọi là server PayOS, không có session, không có referer. Webhook cần trả JSON, cần nằm ngoài CSRF, và không cần `auth` — cả ba điều kiện đều chưa được chuẩn bị.

Điểm sáng duy nhất: ý tưởng `lockForUpdate` trong transaction và điều kiện `status == "Pending"` chống cộng trùng là đúng hướng — phần khung đúng, phần ruột sai.

### E-E5 — Đăng nhập Google: chết từ tên method, và sẽ nhân bản user nếu qua được

Chuỗi lỗi độc lập, phải sửa hết mới chạy:

1. Route khai `[OauthAuthenticationController::class, 'oauth_redirect']` và `'oauth_callback'`; controller đặt tên `auth_redirect` / `auth_callback`. Bấm "Đăng nhập Google" → 500 method-not-exist.
2. `config/services.php` dùng khóa `redirect_url` — Socialite đọc khóa `redirect`. Google sẽ báo thiếu `redirect_uri`.
3. `.env` và `.env.example` không có `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI`.
4. `User` model: `#[Fillable]` không chứa `provider_id`, `provider_name` — `updateOrCreate` lặng lẽ bỏ hai cột định danh, nên điều kiện tìm-theo-provider không bao giờ khớp lại: đăng nhập lần hai tạo user mới trùng email → nổ unique constraint.
5. `auth_callback` kết thúc bằng `Auth::login($new_user);` không return gì — đăng nhập xong nhìn trang trắng. Cũng không `session()->regenerate()` (chống session fixation), và `{provider}` nhận chuỗi tự do nên `/oauth/redirect/abc` trả 500 thay vì 404.
6. Trên máy mới còn vướng E-E6: cột `theme4_id` thành NOT NULL nên insert user thiếu cột này sẽ nổ.

### E-E6 — Typo `cnullable()` quay lại `main` qua merge: bảng users sai schema trên mọi DB mới

`0001_01_01_000000_create_users_table.php:27`: `$table->foreignId('theme4_id')->cnullable()`. Fluent của Laravel nuốt method lạ không báo lỗi — hậu quả là `nullable` không được áp, `theme4_id` thành NOT NULL trên database mới, làm signup lẫn OAuth đều không insert nổi user. Đây là lỗi duy nhất PHPStan còn báo, tức công cụ đã chỉ tận nơi. Điểm quy trình đáng chú ý: bản của commit `059f184` viết đúng `nullable()`; bản lỗi đến từ nhánh `b40802a` (11-08) và người giải quyết conflict lúc 15:05 đã **chọn nhầm bên**.

### E-E7 — Chia sẻ note và mời thành viên: tính năng cũ đã chết, tính năng mới chưa sinh

Commit `989ec27` comment out `share_note`, `mail_for_no_account` và `add_member` để chuyển sang chia sẻ bằng link. Nhưng:

- `routes/web.php` vẫn trỏ `share.note` → `share_note` và `share.organization` → `add_member` — hai method không còn tồn tại. Submit form chia sẻ → 500. Ứng dụng hiện **không còn bất kỳ cách nào** chia sẻ note hay thêm thành viên.
- Ba method thay thế (`share_note_link`, `delete_share_note`, `share_add_member_link`) chưa có route nào trỏ tới, và tự thân cũng chưa chạy được: dùng lại pattern `$request->user->id` (E-E2), gọi `route('organization.home')` và `route('index')` không tồn tại, `view('organization.invite')` không có file (thư mục tên `organizations`), thông báo copy nhầm ngữ cảnh ("Bạn đang là chủ của tổ chức này" trong hàm share note).
- Route nhận lời mời `/invite/{token}` cũng bị comment out, trong khi `OrganizationInvitation` — vẫn được queue thật ở luồng đổi chủ tổ chức (`PivotChangeHostOrganizationController:72`) — render `route('invitation.show')`. Worker render mail này sẽ ném `RouteNotFoundException`, job rơi vào `failed_jobs`. Đúng lớp lỗi E-D1 của V5, lần này do route biến mất thay vì biến private.

### E-E8 — Hai lần merge tay làm mất code đúng và giữ code sai

Gộp làm một mã lỗi vì cùng nguyên nhân. Đối chiếu `5838f5d` (trước merge) với HEAD: (1) share route mất `->middleware('throttle:5,1')` — dòng comment giải thích vì sao cần throttle vẫn còn, còn throttle thì mất; (2) `cnullable` được chọn thay cho `nullable` (E-E6); (3) cặp route `oauth_*` / method `auth_*` lệch nhau ổn định qua merge (E-E5). Merge xong không chạy test, không chạy thử — cả ba vết đều sẽ hiện ra trong vài phút nếu có một trong hai.

---

## 3. Lỗi chấp nhận được

Không chặn người dùng hôm nay, nhưng nên xử trong tuần.

**E-M1 — `payment_complete_bill` và `history_view` là code chết có nội dung sai.** `$request->user->id` (lần thứ năm), `$order->user_id` sai tên cột (`userID`), `view('payment.bill')` — thư mục `resources/views/payment/` đang **rỗng**. `history_view` đổ dữ liệu bảng `payments` vào view `transactions.user2user.history` vốn kỳ vọng cấu trúc giao dịch user2user (from/to) — render là vỡ. Khi nối route (E-E3) phải viết lại cả hai.

**E-M2 — Flash message dùng key `"Error"` hoa.** Layout chỉ đọc `session('error')` thường (`app.blade.php:31`). Mọi `->with("Error", ...)` trong code mới sẽ không bao giờ hiển thị — người dùng bị chặn mà không thấy lý do. Cùng nhóm: vài chỗ `->with("error","")` với message rỗng.

**E-M3 — Cổng Pint trên CI bị comment tắt** (commit `9df7307` "Fix CI"). "Sửa CI" bằng cách tắt kiểm tra là đảo ngược đúng cái van an toàn V4 đã lắp — và số file lệch style lập tức tăng từ 7 lên 10. Nếu chưa muốn dọn style thì ít nhất giữ gate ở mức cảnh báo, đừng gỡ hẳn.

**E-M4 — `Payment::user()` khai `belongsTo(User::class)` không chỉ định foreign key** — Laravel sẽ đoán `user_id` trong khi cột thật là `userID`; quan hệ luôn trả null. Cùng file: cast `decimal:2` cho cột khai `float` trong migration (tiền nên là `decimal` từ migration), và tên cột tiền `amount` cho VND nhưng `point` cho point — nên thống nhất đơn vị ngay từ schema trước khi có dữ liệu thật.

**E-M5 — Thiếu toàn bộ biến môi trường cho hai tính năng mới.** `.env.example` không có `PAYOS_*` lẫn `GOOGLE_*`, nghĩa là người deploy sau này không có cách nào biết cần khai gì. Đây đúng vết xe E-C6 (ImageKit) của V4, lặp lại lần thứ ba.

---

## 4. Lỗi nhỏ nhặt

- Pint 10 file: nhóm controller mới viết không theo chuẩn format nào của dự án.
- `payment_for_point(PayOS $payos, ...)` inject PayOS qua method trong khi constructor đã inject sẵn `$this->payOS` — hai instance cùng tồn tại, một cái thừa. `payment_verify` dùng lẫn cả hai.
- `if($points > 0)` sau khi đã `validate(['points' => 'required|numeric|min:1'])` — nhánh else không thể xảy ra.
- Import thừa: `App\Http\Requests` trong OauthAuthenticationController (namespace không tồn tại — chỉ chưa nổ vì chưa dùng); Pint cũng gắt loạt import không dùng ở PaymentController.
- Code chết để dạng comment (~120 dòng share/add_member cũ) thay vì xóa — git giữ lịch sử rồi, comment giữ xác chỉ làm khó đọc.
- Thông báo trộn hai ngôn ngữ trong cùng flow ("Payment for Points" / "số tiền ko dc bé hơn 0") và viết tắt kiểu chat trong string hiển thị cho người dùng.
- `resources/view/test2/home.html` (prototype UI): thêm modal payment vào mock là hợp lý, không vấn đề — chỉ lưu ý thư mục `view/` (thiếu `s`) này nằm ngoài mọi pipeline, đừng để logic thật trôi vào đó.

---

## 5. Lỗi chỉ lộ khi scale

- **`orderCode` dùng thẳng `payments.id`.** PayOS yêu cầu orderCode duy nhất trong phạm vi tài khoản merchant. Nếu staging và production (hoặc DB reset lại từ đầu) dùng chung một tài khoản PayOS, id tự tăng sẽ va nhau. Thêm tiền tố môi trường hoặc mã ngẫu nhiên là đủ.
- **Kế thừa V4/V5, chưa đổi:** SQLite không hợp `lockForUpdate()` và queue database khi có tải thật; cần MySQL/Postgres trước khi có người dùng thật.
- **Limiter `smart` 300 req/phút cho user đã đăng nhập** là thoáng — hợp lý cho app note. Riêng nhóm authentication 10 req/phút theo IP: sau NAT văn phòng/trường học, 10 người cùng mạng đăng nhập giờ cao điểm sẽ khóa nhau. Chưa phải sửa ngay, nhưng biết trước.
- Bảng `payments` chưa có index cho `userID`+`status` — lịch sử giao dịch sẽ chậm dần; để ý khi có dữ liệu.

---

## 6. Trạng thái nợ cũ V4/V5

Nhánh queue-reliability trả nợ thật sự. Đối chiếu từng mã:

| Mã cũ | Nội dung | Trạng thái 14-08 |
|---|---|---|
| E-C1 | `route:list` crash vì sai tên class | ✅ Đóng — 87 route liệt kê sạch |
| E-C2..C4, C8 | `Oragnization::find`, `$id` sai, `dump()` | ✅ Đóng (PHPStan 10 → 1 lỗi xác nhận) |
| E-C13 | CI đỏ | 🔴 Đỏ kiểu mới: test chết hàng loạt (E-E1), Pint bị tắt gate (E-M3) |
| E-D1 | `UserEmail` private chết khi worker render | ✅ Đóng — public + test render 12 mailable |
| E-D2 | Worker chỉ có ở máy dev | ✅ Đóng — Supervisor config mẫu + README deploy |
| E-D3 | `--tries=1 --timeout=0` | ✅ Đóng — `--tries=3 --backoff=10 --timeout=60` cả dev lẫn docs |
| E-D4 | Không ai canh `failed_jobs` | ✅ Đóng — listener `JobFailed` + `queue:prune-failed` daily |
| E-D6 | Link mail phụ thuộc `APP_URL` | ✅ Ghi vào README deploy |
| V5 §5.1 | 4 mail thông báo sync | ✅ Đã chuyển queue; OTP có try/catch + dọn giao dịch mồ côi |
| V5 §5.2 | Share fan-out không trần, không throttle | 🟡 Trần 20 email còn; **throttle mất trong merge** (E-E8); mà cả hai route giờ 500 (E-E7) |
| V5 §5.3 | Vòng quét bcrypt sinh OTP | ✅ Đã xóa, còn ghi chú giải thích tại chỗ |
| E-C6 / E-M5 | Thiếu env keys cho service mới | 🔴 Tái phạm lần ba với PAYOS_* và GOOGLE_* |

Bức tranh: nợ cũ được trả 10/12, nhưng nợ mới vay ngày 13–14/08 lớn hơn phần vừa trả.

---

## 7. Đánh giá trình độ qua phần code tự viết

Phần này soi riêng nhóm commit tay 13–14/08 (PayOS, OAuth, share-link, rate limiting, JS, migration) — khoảng 250 dòng code mới không tính phần comment out. Đánh giá công tâm nghĩa là nói cả hai chiều bằng chứng cứ.

### Cái đã khá lên rõ rệt

**Chọn đúng công cụ cho đúng việc — đây là bước tiến thật.** So với code thời V4 (gọi thẳng, không transaction, không validate), code tay đợt này cho thấy người viết đã biết *khái niệm đúng cần dùng*: đăng ký PayOS thành singleton qua `AppServiceProvider::register()` đúng chỗ đúng kiểu (khối này viết chuẩn, named arguments gọn); `DB::transaction` + `lockForUpdate` + kiểm tra `status == Pending` trong webhook — ba lớp chống double-credit mà nhiều người viết web lâu năm còn quên; `RateLimiter::for` phân tầng ba mức đúng tư duy (auth chặt theo IP, user thoáng hơn guest); flow PayOS nắm đúng bản chất orderCode/returnUrl/webhook; JS convert point→VND cập nhật realtime theo input là chi tiết UX tự nghĩ ra. Cấu trúc `payment_for_point` — validate, tạo bản ghi Pending trước, gọi API trong try/catch, redirect theo checkoutUrl — là đúng khung một payment flow.

**Đọc code có sẵn để bắt chước pattern.** `lockForUpdate` trong webhook gần như chắc chắn học từ các transaction controller có sẵn trong dự án. Biết tự học từ codebase là kỹ năng nền quan trọng.

**Định hình sản phẩm dứt khoát.** Quyết định bỏ share-qua-email chuyển sang share-qua-link là quyết định sản phẩm hợp lý (đỡ toàn bộ gánh mail fan-out mà V5 phải phân tích dài). Vấn đề nằm ở thực thi nửa chừng, không nằm ở quyết định.

### Cái đang là điểm nghẽn chính

**Không chạy thứ mình vừa viết.** Đây là khoảng cách lớn nhất, lớn hơn mọi lỗ hổng cú pháp cộng lại. Điểm danh: JS chết ngay lần bấm đầu với lỗi đỏ trong console (E-E3), OAuth chết ngay cú click đầu (E-E5), route thiếu thì 404 hiện ngay (E-E3), migration hỏng thì `php artisan test` báo trong 17 giây (E-E1). Không lỗi nào cần kỹ năng debug để tìm — chúng chỉ cần *một lần chạy*. Bốn commit "Fix PHPStan" từ 12:07 đến 12:27 rồi lại 12:58, 13:01 là bằng chứng quy trình: dùng CI làm compiler, sửa mù đến khi hết đỏ, thay vì chạy `vendor/bin/phpstan` tại chỗ một lần và sửa trong một commit.

**Học API qua phỏng đoán thay vì qua tài liệu/IDE.** Các lỗi `find($id)->first()`, `find('id', $x)`, `$request->user` thiếu ngoặc, `getElementId`, `cnullable`, fillable `point` vs `points`, config key `redirect_url` vs `redirect` — chung một gốc: viết theo trí nhớ mang máng rồi không kiểm lại chữ ký hàm. Mỗi lỗi riêng lẻ là chuyện thường; mật độ 7 lỗi cùng gốc trong 250 dòng là tín hiệu cần đổi cách làm việc, không phải cần học thêm framework.

**Nhất quán đặt tên chưa thành phản xạ.** `userID` / `user_id` / `user` lẫn nhau trong cùng một file; route đặt `oauth_*` nhưng method đặt `auth_*` — cùng một buổi viết. Đây là thứ khiến lỗi lan: khi tên không nhất quán, mỗi chỗ gọi là một lần đoán.

**Merge là kỹ năng chưa có.** Ba vết thương của E-E8 đều từ việc resolve conflict bằng cách chọn phía trông quen mắt rồi commit luôn. Merge đúng cách rẻ hơn nhiều so với hậu quả: chạy test sau merge là đủ bắt cả ba.

### Định vị

Trình độ đang ở đoạn chuyển từ "viết được từng dòng" sang "dựng được một flow" — và phần *thiết kế* flow đã theo kịp (khung payment/webhook/rate-limit đều đúng bài), phần *hoàn thiện* flow chưa theo kịp (không dòng nào được xác nhận chạy). So với mốc V4 (code chạy được nhưng tư duy hệ thống mỏng), hiện tại ngược lại: tư duy hệ thống dày lên trông thấy, kỷ luật xác minh lại tụt. Tin tốt là vế sau rẻ hơn nhiều để sửa: nó là thói quen, không phải kiến thức.

---

## 8. Điểm tốt

**Đợt trả nợ queue là chuẩn mực.** Sáu hạng mục V5 đề xuất được làm đủ, kèm hai file test 191 dòng đúng cách tiếp cận "test ở tầng gần người dùng nhất" — `MailableRenderTest` đi đúng vòng serialize → unserialize → render của worker. README phần deploy giờ có Supervisor config, cron schedule, cảnh báo `APP_URL`, và giải thích *vì sao* chọn từng worker flag. Tài liệu mức này hiếm gặp ở dự án sinh viên.

**PHPStan từ 10 xuống 1.** Và 1 lỗi còn lại (`cnullable`) chính là lỗi thật đang gây hại — công cụ đang làm đúng việc, chỉ cần người nghe nó.

**`route:list` hết crash sau nhiều tuần.** Lỗi treo từ V4 cuối cùng đã đóng.

**Ý tưởng sản phẩm của phần tay đều hợp lý:** nạp point qua cổng thanh toán thật, đăng nhập Google, share bằng link, rate limit phân tầng — bốn hướng đi đều đúng cho một app muốn có người dùng thật. Không có hướng nào phải bỏ; tất cả chỉ cần được làm nốt cho chạy.

---

## 9. Khuyến nghị

### Cho sản phẩm — theo thứ tự, việc trước mở đường việc sau

**Bước 0 — cấp cứu, dưới 1 giờ, đưa `main` về trạng thái xanh:**
1. Trả 25 migration về `$table->id()`; sửa `cnullable()` → `nullable()`. Chạy `php artisan test` — kỳ vọng 85 test có kết quả thật trở lại (nhóm share sẽ fail vì E-E7, đó là fail *đúng*).
2. Sửa limiter: `->by($request->user()?->id ?: $request->ip())`.
3. Bật lại step Pint trong CI.

**Bước 1 — quyết định dứt điểm luồng share (nửa ngày):** hoặc khôi phục `share_note`/`add_member` từ `5838f5d` (kèm throttle đã mất), hoặc làm nốt share-bằng-link: khai route cho ba method mới, sửa `$request->user->id`, tạo view `organizations/invite.blade.php`, sửa route name sai, và **bỏ comment** cặp route `/invite/{token}` vì mail đổi chủ tổ chức đang phụ thuộc nó. Đừng để trạng thái nửa chừng hiện tại sống thêm ngày nào — nó làm 500 hai nút bấm đang hiện trên UI.

**Bước 2 — làm payment thành tính năng thật (1–2 ngày):**
4. Khai 4 route: POST tạo payment (auth), POST webhook (ngoài auth, thêm vào except của CSRF, trả JSON), GET success/cancel (view bill — thư mục `payment/` đang rỗng).
5. Viết lại `payment_verify` theo E-E4: `find($id)` bỏ `->first()`, cột `userID`, cộng `point` chứ không cộng `amount`, return response ngoài transaction.
6. Thống nhất đơn vị `point`/`points` giữa model–controller–JS; sửa 3 lỗi JS; thêm `PAYOS_*` vào `.env.example`.
7. Viết test cho flow: mock PayOS client, assert webhook đúng order + đúng số point + không cộng trùng khi gọi lặp. Flow tiền là chỗ đáng bỏ tiền test nhất toàn dự án.

**Bước 3 — OAuth (nửa ngày):** đổi tên method khớp route, `redirect_url` → `redirect`, whitelist `{provider}` bằng `->whereIn()`, thêm `provider_id`/`provider_name` vào `#[Fillable]`, xử lý trường hợp email đã có tài khoản thường (link account thay vì tạo mới), `return redirect()->intended(route('home'))` + `session()->regenerate()`, thêm `GOOGLE_*` vào `.env.example`, và một feature test với `Socialite::shouldReceive`.

### Cho trình độ — năm thói quen, xếp theo lãi suất

1. **Luật một phút:** trước mỗi commit, chạy tính năng vừa viết đúng một lần theo đường người dùng đi (bấm nút, xem console, xem response). Toàn bộ E-E3, E-E5, E-E7 chết trong phút đó. Đây là thay đổi duy nhất mà nếu chỉ làm một, hãy làm nó.
2. **Chạy bộ ba local trước khi push:** `php artisan test && vendor/bin/pint --test && vendor/bin/phpstan` — dự án đã có sẵn cả ba trong composer. Một lần chạy 30 giây thay cho bốn commit "Fix PHPStan" và một CI đỏ công khai.
3. **Sau merge, test là bắt buộc.** Conflict resolve xong chưa phải là xong; `git diff main...nhánh` trước khi merge để biết mình sắp nhận gì, test sau merge để biết mình vừa làm mất gì.
4. **Tra chữ ký hàm thay vì đoán.** Cụ thể với năm lỗ hổng lộ ra đợt này: `find` vs `where()->first()`, mass assignment và `$fillable`, `$request->user()` vs `$request->user`, `route()` ném exception khi thiếu name, giá trị trả về của `DB::transaction`. Mỗi cái 10 phút đọc docs là khóa vĩnh viễn.
5. **Làm việc trên nhánh, để CI xanh rồi mới vào `main`.** Nhánh queue-reliability chính là hình mẫu: làm trọn vẹn, có test, rồi mới merge. Phần tay đang commit thẳng vào main theo nhịp "viết đến đâu đẩy đến đó" — chính nhịp đó biến main thành nơi 83 test đỏ.

---

## 10. Kết luận

| Tiêu chí | V5 (12-08) | V6 (14-08) | |
|---|---|---|---|
| Hạ tầng queue + độ tin cậy mail | ★☆☆☆☆ | ★★★★☆ | ▲ trả nợ V5 gần trọn; còn lỗ E-E7 (route invite bị comment) |
| Test & khả năng kiểm chứng | ★★★★☆ | ★☆☆☆☆ | ▼ 83/85 error — nền test tốt nhất dự án bị migration đè chết |
| Tính năng mới (payment, OAuth, share-link) | — | ★☆☆☆☆ | 0/3 chạy được đến bước thứ hai |
| Luồng giao dịch hiện có | ★★★☆☆ | ★☆☆☆☆ | ▼ limiter hỏng → 500 toàn bộ từ 13-08 |
| Tư duy thiết kế của phần code tay | ★★☆☆☆ | ★★★☆☆ | ▲ chọn đúng công cụ, khung flow đúng bài |
| Kỷ luật kỹ thuật | ★★☆☆☆ | ★☆☆☆☆ | ▼ tắt Pint gate, push chưa chạy thử, merge không kiểm |
| Sẵn sàng production | Chưa | **Chưa — và lùi so với V5** | |

V5 kết thúc bằng nhận xét "mail đã queue không có nghĩa là mail đã đến". V6 có phiên bản tổng quát hơn: **code đã commit không có nghĩa là code đã chạy.** Mười bảy commit đợt này chia đôi rõ rệt — nửa được kiểm chứng (nhánh queue: có test, có tài liệu, trả nợ đúng hạn) và nửa chưa từng được chạy lần nào (payment, OAuth, share-link, migration). Nửa sau không thiếu ý tưởng, không thiếu kiến trúc — nó thiếu đúng một phút bấm thử trên trình duyệt và một lần `php artisan test` trước khi push.

Việc cấp bách nhất không phải viết thêm gì: là bước 0 mục 9 — ba mũi sửa dưới một giờ để test sống lại. Chừng nào 85 test còn đỏ vì migration, mọi dòng code mới đều đang được viết trong bóng tối; và như V4, V5 đã chứng minh hai lần, thứ đắt nhất ở dự án này chưa bao giờ là lỗi — là khoảng thời gian lỗi được phép vô hình.

---

*Báo cáo lập chiều 14-08-2026 trên commit `c37f13b`, nhánh `main`. Mọi con số lấy từ lệnh chạy thật (`artisan test`, `pint`, `phpstan`, `route:list`, `php -l`) và đối chiếu lịch sử bằng `git show` từng commit; hành vi limiter được tái hiện bằng script mô phỏng đúng error-handler HTTP của Laravel. Không file nào của dự án bị sửa. `.env` được kiểm tra sự tồn tại của khóa, không trích dẫn giá trị.*
