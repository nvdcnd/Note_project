# Báo cáo toàn diện dự án Noteket — bản V5, chuyên sâu Queue và xử lý bất đồng bộ

| Mục | Nội dung |
|---|---|
| **Ngày** | 12-08-2026 (chiều, sau báo cáo V4 buổi trưa) |
| **Người thực hiện** | Senior Laravel PHP Full-stack Developer (audit read-only) |
| **Nhánh** | `fix/audit-and-refactor`, HEAD = `b2fa2c6` "Fixing image url error" |
| **Phạm vi** | Toàn bộ dự án, trọng tâm là hàng đợi (queue), mailable, và mọi thứ chạy bất đồng bộ. Các phần khác được kiểm lại và đối chiếu với 16 lỗi V4 đã nêu |
| **Nguyên tắc** | Chỉ đọc code và chạy lệnh kiểm tra. Không sửa một dòng code nào. |
| **Cách kiểm chứng** | `php artisan test`, `pint --test`, `phpstan`, `route:list`, đếm bản ghi bảng `jobs`/`failed_jobs`, và một script tinker mô phỏng đúng đường đi của queue worker: serialize payload → unserialize → render, chạy trong transaction rồi rollback nên không để lại dữ liệu |

## Tóm tắt cho người bận

Kể từ V4 (viết lúc trưa nay tại `643b47d`) có thêm đúng một commit: `b2fa2c6` sửa hai lỗi Blade E-C5 và E-C7. Sửa đúng, tôi đã xem diff và xác nhận. Mười lỗi còn lại của V4 vẫn nguyên trạng, CI vẫn đỏ, `route:list` vẫn crash.

Phần chính của báo cáo này là hàng đợi, và kết quả kiểm chứng nói được bằng một câu: **hạ tầng queue được dựng đúng, nhưng email quan trọng nhất đi qua nó chưa bao giờ gửi thành công được.**

Cụ thể: mail "note đã được chia sẻ với bạn" (`UserEmail`) — một trong ba mail được đưa vào hàng đợi — khai báo dữ liệu bằng thuộc tính `private`. Laravel chỉ truyền thuộc tính `public` của mailable vào view, nên khi worker nhận job và render, view không có biến nào để đọc và ném `ViewException`. Tôi mô phỏng đúng đường đi serialize → unserialize → render của worker: **fail 100%, cả bốn lần chạy thử.** Hai mail queued còn lại (`Mail40account`, `OrganizationInvitation`) render sạch.

Lỗi này vô hình với mọi lớp kiểm tra hiện có: 77 test đều xanh vì `Mail::fake()` không bao giờ render mail; trình duyệt không thấy gì vì lỗi xảy ra ở process worker, sau khi response đã trả về; và với cấu hình `--tries=1` đang dùng, job chết ngay lần đầu, đi thẳng vào `failed_jobs` mà không ai giám sát bảng đó.

Ngoài lỗi trên, mô hình bất đồng bộ của dự án còn lệch ở chỗ: 9 trên 13 điểm gửi mail vẫn là đồng bộ, chặn request bằng một vòng SMTP thật tới Brevo; `add_member` gửi mail sync trong vòng lặp; và bộ sinh OTP quét bcrypt toàn bảng giao dịch — càng dùng lâu app càng chậm, chi tiết ở mục 5.

---

## Kết quả kiểm chứng, chạy thật chiều 12-08-2026

| Hạng mục | Lệnh / cách đo | Kết quả |
|---|---|---|
| Test suite | `php artisan test` | ✅ 77 test / 257 assertion PASS (14,7s) |
| Code style | `pint --test` | ❌ 7 file — không đổi so với V4 |
| Static analysis | `phpstan` | ❌ 10 lỗi — không đổi so với V4 |
| Liệt kê route | `php artisan route:list` | 🔴 vẫn CRASH (`OrgnizationController` không tồn tại) |
| Job đang chờ | `DB::table('jobs')->count()` | 0 |
| Job đã chết | `DB::table('failed_jobs')->count()` | 0 |
| Mailer đang chạy thật | `config('mail.default')` | `smtp` → smtp-relay.brevo.com (SMTP thật, kể cả ở máy dev) |
| Queue driver | `config('queue.default')` | `database` |
| Render mail queued qua đường worker | script tinker, 4 kịch bản | ❌ `UserEmail` fail cả 4 lần; ✅ 2 mailable còn lại OK |

Quy mô phần khảo sát: 12 mailable, 13 điểm gọi `Mail::` trong controller (4 điểm `->queue()`, 9 điểm `->send()`), 0 job class, 0 event/listener, 0 lệnh scheduler (`routes/console.php` chỉ còn stub `inspire`).

---

## Mục lục

1. [Thay đổi kể từ V4](#1-thay-đổi-kể-từ-v4)
2. [Bản đồ hàng đợi và bất đồng bộ của dự án](#2-bản-đồ-hàng-đợi-và-bất-đồng-bộ-của-dự-án)
3. [Lỗi nghiêm trọng nhất: UserEmail chết khi worker render](#3-lỗi-nghiêm-trọng-nhất-useremail-chết-khi-worker-render)
4. [Các rủi ro hàng đợi còn lại, xếp theo mức](#4-các-rủi-ro-hàng-đợi-còn-lại-xếp-theo-mức)
5. [Phần đồng bộ đang chặn request](#5-phần-đồng-bộ-đang-chặn-request)
6. [Kiểm lại các phần khác: trạng thái 16 lỗi V4](#6-kiểm-lại-các-phần-khác-trạng-thái-16-lỗi-v4)
7. [Điểm tốt](#7-điểm-tốt)
8. [Lộ trình đề xuất cho queue](#8-lộ-trình-đề-xuất-cho-queue)
9. [Kết luận](#9-kết-luận)

---

## 1. Thay đổi kể từ V4

Một commit duy nhất: `b2fa2c6` lúc 12:52, sửa hai file view.

`organizations/index.blade.php`: hai chỗ `{{organization->banner_url}}` đã có dấu `$`. Trang danh sách tổ chức không còn trả 500 khi có tổ chức mang banner. Đây là E-C5, đóng.

`layouts/partials/topbar.blade.php`: `$user->avatar_image_url` đổi thành `auth()->user()->avatar_image_url`, khớp với điều kiện `@if` phía trên. Avatar giờ hiện ở mọi trang chứ không riêng trang Cài đặt. Đây là E-C7, đóng.

Cả hai sửa đúng cách tối thiểu, không kèm test. Mười lỗi V4 còn lại chưa ai động vào — bảng chi tiết ở mục 6.

---

## 2. Bản đồ hàng đợi và bất đồng bộ của dự án

### 2.1. Hạ tầng: có, và cấu hình không tệ

Driver mặc định là `database` (`config/queue.php:16`, `.env` và `.env.example` đều đặt `QUEUE_CONNECTION=database`). Ba bảng `jobs`, `job_batches`, `failed_jobs` nằm trong migration skeleton `0001_01_01_000002_create_jobs_table` và đã chạy. Failed jobs lưu bằng driver `database-uuids`.

Script `composer dev` chạy song song ba process: `php artisan serve`, `php artisan queue:listen --tries=1 --timeout=0`, và Vite. Nghĩa là dev nào khởi động dự án bằng lệnh chuẩn thì có sẵn worker, không bị cảnh "mail nằm im trong bảng jobs" ngay trên máy mình.

README có hẳn mục "Queue Worker" (dòng 144–151) nói đúng bản chất vấn đề: dùng `QUEUE_CONNECTION=database` thì phải chạy worker, không thì mail mời "sẽ nằm trong bảng jobs và không bao giờ tới nơi", kèm gợi ý `queue:work --tries=3` và phương án `QUEUE_CONNECTION=sync` cho local. Tài liệu này viết trước cả khi tôi kiểm tra — điểm cộng thật sự.

### 2.2. Cách dùng: mỏng và lệch

Toàn bộ khối lượng bất đồng bộ của dự án là **gửi email, và chỉ gửi email**. Không có job class nào trong `app/` (không có cả thư mục `Jobs`), không event/listener, không notification, không batch, không chain, không scheduled task. Bảng `job_batches` được migrate nhưng chưa từng có gì dùng tới.

Trong 12 mailable, đúng 3 cái implement `ShouldQueue` — và đó là lựa chọn có chủ đích đúng: ba mail này gửi cho *người khác* (người được chia sẻ note, người được mời vào tổ chức), người bấm nút không cần chờ chúng.

| Nhóm | Mailable | Gửi kiểu gì | Điểm gọi |
|---|---|---|---|
| Chia sẻ / mời (3) | `UserEmail`, `Mail40account`, `OrganizationInvitation` | `->queue()`, có `ShouldQueue` | `PivotForNoteController:31,88`, `OrganizationsMemberController:56`, `PivotChangeHostOrganizationController:72` |
| OTP giao dịch (5) | `user2user_trans_otp`, `user2organization_trans_otp`, `organization2user_trans_otp`, `user2theme4_trans_otp`, `Theme4org_trans_otp` | `->send()` đồng bộ | 5 controller giao dịch |
| Thông báo nghiệp vụ (4) | `user_accept_organization`, `change_host_organization`, `user_accept_host_organization`, `Password_change` | `->send()` đồng bộ | `OrganizationsMemberController:78`, `PivotChangeHostOrganizationController:35,57`, `PasswordChangeRequestController:54` |

Ranh giới sync/async hợp lý với nhóm OTP (người dùng đang ngồi chờ mã) nhưng sai với nhóm thông báo: bốn mail đó không ai chờ cả, và chúng đang bắt request gánh một vòng SMTP thật. Chi tiết ở mục 5.

Một điểm đáng nói về phía test: `phpunit.xml` ép `QUEUE_CONNECTION=sync` và `MAIL_MAILER=array`, mọi test dùng `Mail::fake()`. Cấu hình này cho test chạy nhanh và ổn định, nhưng cũng có nghĩa là **không một dòng test nào đi qua con đường serialize → unserialize → render mà job thật phải đi**. Mục 3 cho thấy cái giá của khoảng trống đó.

---

## 3. Lỗi nghiêm trọng nhất: UserEmail chết khi worker render

**Mã lỗi: E-D1. Mức: nghiêm trọng — một tính năng trông như đang chạy nhưng chưa từng giao được kết quả.**

### Hiện tượng

`app/Mail/UserEmail.php:22` khai báo constructor thế này:

```php
public function __construct(private $user, private $notes)
```

Còn view `emails/userEmail.blade.php` đọc `{{ $user->name }}` (dòng 11) và `route('note', ['id' => $notes->id])` (dòng 14).

Cơ chế của Laravel: khi render một mailable, `buildViewData()` chỉ gom các thuộc tính **public** để truyền vào view. `$user` và `$notes` ở đây là `private`, nên view nhận được hai biến không tồn tại. PHP 8 trả `null` kèm warning cho biến undefined, `$notes->id` thành `null`, và `route('note', ['id' => null])` ném `UrlGenerationException` — được Blade bọc lại thành `ViewException`.

### Kiểm chứng

Suy luận trên giấy dễ sai nên tôi dựng script mô phỏng đúng những gì worker làm: bọc mailable vào `SendQueuedMailable`, serialize (như lúc `->queue()` ghi vào bảng `jobs`), unserialize (như lúc worker nhặt job ra), rồi render. Dữ liệu tạo bằng factory trong transaction và rollback sạch. Kết quả:

```
[FAIL] UserEmail (share note cho user co tai khoan)
       ViewException: Missing required parameter for [Route: note] [URI: note/{id}]
       [Missing parameter: id]. (View: resources/views/emails/userEmail.blade.php)
[OK]   Mail40account (share note cho email chua co tai khoan)   - render 2191 bytes
[OK]   OrganizationInvitation (moi vao to chuc)                 - render 2082 bytes
```

Hai mailable kia thoát nạn vì thuộc tính của chúng là `public` (`OrganizationInvitation` dùng promoted public, `Mail40account` khai báo public thường).

### Vì sao không ai thấy

Ba lớp che khuất chồng lên nhau. Một, `Mail::fake()` trong test chỉ ghi nhận "mail đã được queue", không render, nên 77 test xanh không nói gì về chuyện này — `CoreWorkflowsTest:169` còn assert đúng là `UserEmail` đã queued. Hai, ở phía trình duyệt mọi thứ trông thành công: response trả về "Đã chia sẻ ghi chú" trước khi worker kịp chạy. Ba, khi worker chạy thật và job nổ, với `--tries=1` nó đi thẳng vào `failed_jobs`, mà không có gì giám sát bảng đó (mục 4, E-D4).

Nói cách khác: người dùng A chia sẻ note cho người dùng B đã có tài khoản, A thấy thông báo thành công, B **không bao giờ nhận được mail**, và không log nào đỏ trước mặt ai. Bản ghi `PivotForNote` vẫn được tạo nên B vào app vẫn thấy note — thứ mất là toàn bộ giá trị của email thông báo.

### Cách sửa

Nhỏ hơn nhiều so với tầm ảnh hưởng: đổi hai thuộc tính sang `public`, hoặc giữ private và truyền tường minh qua `with:` trong `content()` như `Mail40account` đang làm. Kèm theo đó nên viết một test render cho **cả 12 mailable** — một test duy nhất lặp qua danh sách, mỗi mailable đi đúng vòng serialize → unserialize → `render()`. Chi phí khoảng ba mươi dòng, và nó khóa vĩnh viễn cả lớp lỗi này lẫn lớp lỗi biến-thiếu-trong-view-email mà báo cáo 08-08 từng phải xử lý.

---

## 4. Các rủi ro hàng đợi còn lại, xếp theo mức

**E-D2 — Worker là điều kiện sống của tính năng mời, nhưng chỉ tồn tại trong script dev.**
`composer dev` có `queue:listen`, README có hướng dẫn — phần dev coi như ổn. Nhưng không có Procfile, không có file cấu hình Supervisor/systemd mẫu, không có gì trong quy trình deploy đảm bảo worker chạy ở môi trường thật. Khi deploy chỉ chạy web server, ba luồng mail mời (chia sẻ note, mời vào tổ chức, mời làm chủ) sẽ ghi job vào bảng `jobs` và dừng ở đó. Hiện bảng `jobs` local đang 0 bản ghi nên chưa có gì kẹt, nhưng đó là máy dev có listener. Đề xuất: thêm một file `deploy/supervisor/noteket-worker.conf` mẫu vào repo và một dòng trong README phần deploy; nếu hosting không cho process nền, chuyển hẳn `QUEUE_CONNECTION=sync` để chậm nhưng không mất mail.

**E-D3 — `--tries=1 --timeout=0` là cặp tham số xấu cho mail.**
Với `tries=1`, một lần Brevo chập chờn là mail mời chết hẳn, không thử lại. Với `timeout=0` (không giới hạn) trong khi `retry_after` của connection database là 90 giây (`config/queue.php:43`), một cú SMTP treo quá 90 giây sẽ khiến job bị phát lại trong khi lần chạy đầu vẫn đang gửi — đây đúng là tổ hợp mà tài liệu Laravel cảnh báo phải tránh (timeout luôn phải nhỏ hơn retry_after). Hệ quả thực tế là gửi trùng mail mời. Đề xuất: `queue:work --tries=3 --backoff=10 --timeout=60`, hoặc đặt `$tries`/`$timeout` ngay trên ba mailable để tham số đi theo code thay vì theo lệnh.

**E-D4 — Không ai canh `failed_jobs`.**
Không có notification khi job fail, không có `queue:prune-failed` định kỳ, và `routes/console.php` trống trơn nên chắc chắn chưa có scheduler nào chạy. Kết hợp với E-D1 thì đây là lý do lỗi nghiêm trọng nhất của dự án nằm im được đến giờ. Đề xuất tối thiểu: đăng ký listener cho `JobFailed` ghi `Log::error` kèm tên mailable, và một dòng schedule `queue:prune-failed --hours=168`. Có điều kiện hơn thì bắn thông báo về Slack/mail admin.

**E-D5 — Ba mailable queued giữ nguyên Eloquent model qua `SerializesModels`.**
Model bị serialize thành định danh và được worker query lại lúc chạy. Nếu tổ chức bị xóa sau khi mời nhưng trước khi worker xử lý, `OrganizationInvitation` ném `ModelNotFoundException` và job fail (Organization không dùng SoftDeletes). Note thì dùng SoftDeletes nên job vẫn chạy — nghĩa là người nhận có thể nhận mail mời xem một note đã bị xóa, bấm link vào trang lỗi. Cả hai nhánh đều không phải thảm họa, nhưng email bản chất là snapshot tại thời điểm gửi: đề xuất truyền dữ liệu nguyên thủy (id, tiêu đề, URL đã dựng sẵn) thay vì model sống, vừa hết lớp lỗi này vừa nhẹ payload.

**E-D6 — Link trong mail queued phụ thuộc `APP_URL`, đang là `http://localhost`.**
`route('invitation.show', ...)` trong `content()` chạy ở process worker, không có request nào để suy ra host, nên toàn bộ link lấy từ `APP_URL`. Deploy mà quên đặt biến này thì mọi link mời trỏ về localhost — mà link mời là toàn bộ giá trị của ba email đó. Một dòng nhắc trong README phần deploy là đủ.

**E-D7 — `after_commit` đang `false` và code chưa dùng `afterCommit()`.**
Hiện tại chưa cháy nhà: cả bốn điểm `->queue()` đều nằm ngoài `DB::transaction`. Nhưng `Invitation::issueFor()` tạo bản ghi rồi mail được queue ngay sau đó; ngày nào đó ai bọc `share_note` vào transaction cho "an toàn" thì worker có thể nhặt job trước khi transaction commit và không tìm thấy invitation. Ghi nhận để người sửa sau biết bẫy nằm đâu; sửa ngay thì chỉ cần bật `after_commit => true` cho connection database.

**E-D8 — Queue database trên SQLite không phải cấu hình để lớn.**
Web ghi job, worker poll và xóa job trên cùng một file SQLite; thêm người dùng thật là bắt đầu gặp `SQLITE_BUSY`. Cùng loại ghi chú với `lockForUpdate()` mà V4 đã nêu: lên production cần MySQL/Postgres, và nếu lưu lượng mail tăng thì Redis cho queue là bước tiếp theo tự nhiên. Chưa cần Horizon ở quy mô này.

---

## 5. Phần đồng bộ đang chặn request

Ba vấn đề dưới đây không nằm trong hàng đợi — chúng là những chỗ *lẽ ra* nên bất đồng bộ, hoặc đồng bộ nhưng đang trả giá.

### 5.1. Chín điểm `Mail::send()` giữ request suốt một vòng SMTP

Mailer đang chạy thật là SMTP Brevo (khối `MAIL_*` thứ hai trong `.env` ghi đè khối `log` phía trên — bằng chứng: `config('mail.default')` trả về `smtp`). Mỗi `->send()` là một vòng kết nối + TLS handshake + gửi, thường 0,5–2 giây, thực hiện **trong** request. Người bấm "tạo giao dịch" ngồi chờ đúng khoảng đó trước khi thấy trang nhập OTP.

Với 5 mail OTP, giữ đồng bộ là chấp nhận được — người dùng cần mã ngay, và nếu gửi hỏng thì nên biết ngay. Nhưng hiện **không có try/catch quanh bất kỳ điểm gửi nào**: Brevo từ chối kết nối là request nổ 500 *sau khi* bản ghi giao dịch đã lưu (`User2userTransactionController:100-101` — `save()` rồi mới `send()`). Người dùng thấy trang lỗi, giao dịch pending mồ côi nằm chờ hết hạn 10 phút, và không có nút gửi lại OTP nào để tự cứu. Đề xuất: bọc try/catch, gửi hỏng thì hủy giao dịch vừa tạo và trả thông báo tử tế; hoặc thêm route resend có throttle.

Với 4 mail thông báo (chấp nhận thành viên, đổi chủ, xác nhận đổi mật khẩu), không ai đợi chúng cả — đây là ứng viên chuyển sang `ShouldQueue` rõ nhất, nhất là khi hạ tầng queue đã sẵn.

Một chi tiết môi trường dev đáng biết: `php artisan serve` là PHP built-in server một luồng. Một request đang chờ Brevo nghĩa là **mọi** request khác xếp hàng sau nó. Máy dev cắm SMTP thật còn có nghĩa là mỗi lần thử tính năng là một email thật bay đi — nên trả `MAIL_MAILER=log` cho local như `.env.example` vốn khai.

### 5.2. `add_member`: N thành viên = N vòng SMTP tuần tự

`OrganizationsMemberController:78` gọi `Mail::send(new user_accept_organization(...))` bên trong `foreach` duyệt danh sách email. Mời 15 người có tài khoản là 15 vòng SMTP nối đuôi, dễ dàng đẩy request qua 20–30 giây — quá `max_execution_time` mặc định của PHP-FPM trên production. Trong cùng vòng lặp, nhánh email-chưa-có-tài-khoản lại dùng `->queue()` — cùng một method, hai triết lý. Chuyển mail dòng 78 sang queue là xong cả hai chuyện.

Liên quan trực tiếp: route `share.note` (`web.php:86`) và `share.organization` (`web.php:108`) **không có throttle**, trong khi mọi route tạo giao dịch đều đã `throttle:5,1` từ đợt Đại tu. Hai endpoint này nhận danh sách email tự do, mỗi email sinh một bản ghi `invitations` cộng một job mail. Đây là đường spam rẻ nhất còn mở. Đề xuất: throttle cùng mức, cộng trần số email mỗi lần gửi (ví dụ `'shared_with' => ['array', 'max:20']`).

### 5.3. Bộ sinh OTP quét bcrypt toàn bảng — chậm dần theo tuổi đời hệ thống

Cả 5 controller giao dịch chép nguyên một hàm sinh OTP (10 điểm gọi, xác nhận bằng grep):

```php
do {
    $otp = (string) random_int(100000, 999999);
} while (User2userTransaction::query()->where('status', '!=', 'finished')->pluck('otp')->contains(
    fn ($hash) => Hash::check($otp, $hash)
));
```

Ba tầng vấn đề. Một, `status != 'finished'` khớp cả `failed`, `expired`, `cancelled` — những trạng thái tồn tại vĩnh viễn, nên tập bị quét chỉ tăng không giảm. Hai, mỗi phần tử là một `Hash::check` bcrypt cỡ 50–100ms CPU: khi bảng tích lũy 200 giao dịch không-finished, mỗi lần bấm "tạo giao dịch" đốt 10–20 giây CPU *trước cả khi* gửi mail, trên process đồng bộ. Ba, toàn bộ vòng lặp này vô nghĩa về logic: bước verify chỉ `Hash::check` mã nhập vào với hash **của chính giao dịch đó**, nên hai giao dịch trùng OTP không gây bất kỳ nhầm lẫn nào. Xóa hẳn vòng `do/while`, giữ lại `random_int`, là vừa nhanh vừa đúng — và làm ở 5 chỗ.

---

## 6. Kiểm lại các phần khác: trạng thái 16 lỗi V4

Tôi chạy lại đủ bốn lệnh kiểm chứng và soi từng vị trí V4 đã nêu. Kết quả: 2 đóng, 13 còn nguyên, 1 chuyển trạng thái một nửa.

| Mã V4 | Nội dung | Trạng thái chiều 12-08 |
|---|---|---|
| E-C1 | Sai tên class ở `routes/web.php:74-75` | 🔴 Còn — `route:list` vẫn crash, tôi chạy lại xác nhận |
| E-C2 | `Oragnization::find()` (`OrganizationsController:293,317`) | 🔴 Còn |
| E-C3 | `Auth::user()->$id` (`:294,318`) — lỗi phân quyền | 🔴 Còn |
| E-C4 | `OrgBannerUpload` thiếu tham số `$id` (`:316`) | 🔴 Còn — PHPStan vẫn báo 3 lỗi undefined variable |
| E-C5 | `{{organization->banner_url}}` thiếu `$` | ✅ **Đóng** tại `b2fa2c6` |
| E-C6 | Thiếu biến môi trường ImageKit | 🟡 Nửa vời — 3 khóa đã vào `.env` nhưng **để trống** (`config('services.imagekit.public_key')` trả `''`, SDK vẫn sẽ ném exception), `.env.example` vẫn chưa có |
| E-C7 | `$user` sai ngữ cảnh trong topbar | ✅ **Đóng** tại `b2fa2c6` |
| E-C8 | `dump()` ở `SettingsController:57` | 🔴 Còn |
| E-C9 | Validate upload chỉ có `required` | 🔴 Còn |
| E-C10 | `redirect()->route('organization.settings')` thiếu `{id}`, 4 chỗ | 🔴 Còn — route tại `web.php:104` vẫn yêu cầu `{id}` |
| E-C11 | Banner upload vào thư mục `/org/logo/` | 🔴 Còn (`OrganizationsController:328`) |
| E-C12 | Flash `success` hiện trùng alert + toast | 🔴 Còn (`layouts/app.blade.php:25-35`) |
| E-C13 | CI đỏ: Pint 7 file, PHPStan 10 lỗi | 🔴 Còn, số liệu y nguyên |
| E-C14 | `.env` trùng khóa | 🟡 Giảm nhẹ — còn 7 khóa `MAIL_*` trùng đôi, `FILESYSTEM_DISK` hết trùng |
| E-C15 | Import thừa trong `Controller.php` | 🔴 Còn — Pint vẫn báo `no_unused_imports`; trớ trêu là trong đám import thừa có cả `Queueable` và `ShouldQueue` |
| E-C16 | Không có test cho upload | 🔴 Còn |

Ngoài bảng trên, các module lớn (Note, Transaction, Invitation, Theme, Auth) không có commit nào chạm vào từ V4 nên đánh giá của V4 giữ nguyên giá trị; 77 test pass xác nhận không có hồi quy. Một điểm bảo mật cần nói thẳng dù không phải lỗi code: `.env` local đang chứa SMTP key Brevo thật. File đã được gitignore đúng (tôi kiểm tra bằng `git check-ignore`) nên không nằm trong repo, nhưng key thật trên máy dev cộng `MAIL_MAILER=smtp` nghĩa là mọi phiên thử nghiệm đều gửi mail thật bằng danh tính thật. Nên thu hồi key này khỏi máy dev, local dùng `log`.

---

## 7. Điểm tốt

**README mô tả đúng rủi ro queue trước khi ai kiểm tra.** Mục Queue Worker nói chính xác điều gì xảy ra nếu quên worker, kèm cả lối thoát `QUEUE_CONNECTION=sync` cho local. Người viết hiểu cơ chế, không chép tài liệu.

**`composer dev` gói worker vào quy trình chuẩn.** Dev không phải nhớ mở terminal thứ hai. Đây là lý do bảng `jobs` local sạch.

**Ranh giới sync/queue được chọn có chủ đích.** Ba mail gửi cho người vắng mặt thì queue, năm mail OTP người dùng đang chờ thì sync. Tư duy đúng; phần thực thi (E-D1, nhóm mail thông báo) mới là chỗ hỏng.

**Hai lỗi Blade của V4 được sửa trong vòng một giờ sau báo cáo,** đúng cách, đúng chỗ.

**Test giữ nền ổn định.** 77 test chạy 14,7 giây, ép `sync` + `array` mailer nên không phụ thuộc mạng — nền tảng tốt để cắm thêm test render mailable mà không sợ chậm.

---

## 8. Lộ trình đề xuất cho queue

**Bước 0 — sửa E-D1 và khóa nó lại (dưới 1 giờ).**

1. `UserEmail`: đổi `private` thành `public` ở constructor, hoặc truyền `with:` trong `content()`
2. Viết test render-toàn-bộ-mailable: lặp qua 12 class, mỗi cái serialize → unserialize → `render()`, assert không throw. Test này thay mặt cho mọi mail sau này
3. Chạy lại script mô phỏng worker để xác nhận 3/3 OK

**Bước 1 — làm worker đáng tin (nửa ngày).**

4. Đổi tham số worker: `--tries=3 --backoff=10 --timeout=60` (trong `composer dev` và trong tài liệu deploy)
5. Thêm listener `JobFailed` ghi log lỗi có tên mailable; thêm schedule `queue:prune-failed`
6. Thêm file cấu hình Supervisor mẫu + một đoạn README phần deploy nhắc cả worker lẫn `APP_URL`

**Bước 2 — đưa phần đáng queue vào queue, vá phần sync (1 ngày).**

7. Bốn mail thông báo (`user_accept_organization`, `change_host_organization`, `user_accept_host_organization`, `Password_change`) thêm `ShouldQueue`, đổi `->send()` thành `->queue()`
8. Bọc try/catch quanh 5 điểm gửi OTP; gửi hỏng thì hủy giao dịch vừa tạo và báo lỗi rõ
9. Throttle + trần số email cho `share.note` và `share.organization`
10. Xóa vòng quét bcrypt trong 5 bộ sinh OTP, giữ `random_int`

**Bước 3 — dọn phần còn nợ từ V4 (song song được).**

11. Sáu lỗi chặn CI (E-C1…E-C4, E-C8) — vẫn là 30 phút như V4 ước tính, chưa ai làm
12. Điền giá trị ImageKit thật vào `.env`, thêm khóa rỗng vào `.env.example`
13. Khi upload chạy được: cân nhắc đẩy upload/xóa ảnh ImageKit vào job queue — hạ tầng lúc đó đã đáng tin để nhận thêm việc

Thứ tự này có chủ ý: sửa E-D1 trước khi khuyến khích thêm mail vào queue, làm worker đáng tin trước khi tăng khối lượng cho nó.

---

## 9. Kết luận

| Tiêu chí | V4 (trưa 12-08) | V5 (chiều 12-08) | |
|---|---|---|---|
| Hạ tầng queue (driver, bảng, script dev, tài liệu) | — | ★★★★☆ | mới đánh giá |
| Độ tin cậy mail qua queue | — | ★☆☆☆☆ | 1/3 mailable chết khi render, không giám sát failed |
| Mail đồng bộ / chịu lỗi SMTP | — | ★★☆☆☆ | không try/catch, N+1 SMTP trong vòng lặp |
| Production UI | ★★★☆☆ | ★★★★☆ | ▲ 2 lỗi Blade đã đóng |
| Kỷ luật kỹ thuật | ★★☆☆☆ | ★★☆☆☆ | = CI vẫn đỏ, 13 lỗi V4 chưa động |
| Sẵn sàng production | Chưa | **Chưa** | |

Dự án này có một nghịch lý dễ chịu và một nghịch lý khó chịu. Dễ chịu: phần hạ tầng queue — thứ thường bị bỏ quên ở dự án cỡ này — lại được chuẩn bị tử tế, từ driver, script dev đến tài liệu. Khó chịu: chính email quan trọng nhất chạy trên hạ tầng đó chưa bao giờ đến tay người nhận, và không một công cụ nào đang có (test, CI, log trình duyệt) đủ khả năng phát hiện. Lỗi nằm ở đúng điểm mù giữa "đã queue thành công" và "đã gửi thành công" — nơi chỉ có worker thật, render thật mới nhìn thấy.

Bài học giống hệt V4 nhưng ở tầng khác. V4: test xanh không chứng minh tính năng mới chạy. V5: mail "đã queue" không có nghĩa là mail "đã đến". Cả hai đều chung một thuốc — kiểm chứng ở tầng gần người dùng nhất có thể, và lần này cụ thể là một test render 30 dòng cộng một listener JobFailed. Rẻ hơn nhiều so với chi phí của một tính năng chia sẻ trông-như-chạy suốt từ đầu dự án.

---

*Báo cáo lập chiều 12-08-2026 trên commit `b2fa2c6`, nhánh `fix/audit-and-refactor`. Mọi kết luận về hành vi queue đều lấy từ lệnh chạy thật và script mô phỏng worker (serialize → unserialize → render, rollback sạch), không suy đoán từ tài liệu. Không file nào của dự án bị sửa; secret trong `.env` được nhắc tới nhưng không trích dẫn giá trị.*
