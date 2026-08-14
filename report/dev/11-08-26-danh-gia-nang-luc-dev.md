# Đánh giá năng lực developer qua branch `fix/audit-and-refactor`

Người đánh giá: tech lead Laravel (giả định), review theo tiêu chuẩn tuyển dụng thị trường IT Việt Nam 2026
Ngày: 11/08/2026
Phạm vi: toàn bộ branch `fix/audit-and-refactor`, đối chiếu với `main` và lịch sử commit từ `1b4660f` đến `1edacee`
Bối cảnh người viết code: 3 năm Django (giai đoạn chưa có AI hỗ trợ), 5 project trước đó, Laravel là stack mới. UI được tự dựng dạng demo HTML ở `resources/view/test2`, sau đó dùng AI để chuyển sang Blade, sửa lỗi và refactor.

---

## 1. Số liệu thô trước khi bàn chất lượng

| Hạng mục | Con số |
| --- | --- |
| Diff so với `main` | 346 file, +55.153 dòng |
| Controller | 24 file, ~2.900 dòng |
| Model | 24 file |
| Migration | 30 |
| Test | 77 test, 257 assertion, **pass 100%** trong 1,9 giây |
| PHPStan level 5 | **0 lỗi** |
| Pint `--test` | **fail** — `config/filesystems.php` (`no_extra_blank_lines`) |
| Demo UI gốc | 15 file HTML, mỗi file 41–50 KB, tổng ~640 KB |
| Frontend hiện tại | `public/css/noteket.css` 18 KB + `public/js/noteket.js` 592 dòng, viết tay |

Test xanh và PHPStan sạch là con số đẹp. Phần dưới sẽ cho thấy vì sao hai con số đó không đủ để kết luận về chất lượng.

---

## 2. Điểm mạnh — nêu kèm bằng chứng

### 2.1 Chọn bài toán đủ khó và làm tới nơi

Đây không phải app to-do. Trong repo có ví tiền với số dư decimal, năm luồng giao dịch riêng biệt (user↔user, user↔org, org↔user, mua theme cá nhân, mua theme tổ chức), mỗi luồng đều có OTP qua email, hạn 10 phút, giới hạn 5 lần nhập sai, trạng thái pending/finished/failed/expired/cancelled, và luồng hủy. Thêm quản lý tổ chức, chuyển quyền host có xác nhận hai chiều, marketplace theme, và hệ thống mời người chưa có tài khoản.

Một fresher chọn CRUD. Người này chọn thứ có tiền và có trạng thái. Riêng lựa chọn đó đã tách khỏi nhóm fresher.

### 2.2 Hiểu vấn đề đồng thời khi động tới tiền

`app/Http/Controllers/User2userTransactionController.php:129-165`:

```php
return DB::transaction(function () use ($transaction, $passkey, $user) {
    $lockedSender = User::query()->lockForUpdate()->find($transaction->from);
    $lockedRecipient = User::query()->lockForUpdate()->find($transaction->to);
    ...
    User::whereKey($lockedRecipient->id)->increment('balance', $transaction->amount);
    User::whereKey($lockedSender->id)->decrement('balance', $transaction->amount);
```

Khóa cả hai hàng trước khi kiểm tra số dư, dùng `increment`/`decrement` ở tầng SQL thay vì đọc-sửa-ghi trong PHP. Đây là kiến thức mà phần lớn ứng viên 1–2 năm kinh nghiệm ở Việt Nam không viết ra được trong bài test. Nó đến từ 3 năm backend, không phải từ AI.

### 2.3 Tư duy bảo mật có thật, không phải học vẹt

Ba chi tiết cho thấy người viết thực sự nghĩ về kẻ tấn công:

OTP không bao giờ được lưu dạng rõ: `$transaction->otp = Hash::make($otp)` (`User2userTransactionController.php:97`), khi verify thì `Hash::check`.

Token lời mời sinh bằng `Str::random(64)`, lưu bằng `sha256`, bản gốc không nằm ở đâu trong DB (`app/Models/Invitation.php:53-72`). Và quan trọng hơn — email khi đăng ký qua lời mời lấy từ bản ghi invitation, không lấy từ form:

```php
// Email lấy từ lời mời, KHÔNG lấy từ form — người dùng không thể
// đổi sang email khác để chiếm quyền truy cập nội dung.
'email' => $invitation->email,
```

`app/Support/ThemeStyle.php:46-63` chặn CSS injection bằng whitelist khóa cộng regex hex, vì giá trị từ DB đi thẳng vào thẻ `<style>`. Người không nghĩ tới attack surface sẽ `{!! $theme->color !!}` rồi đi tiếp.

### 2.4 Kỷ luật kỹ thuật vượt mức thường thấy ở dev tự học

CI ở `.github/workflows` chạy trên mọi nhánh, và đặt Pint + PHPStan **trước** test, kèm comment giải thích lý do. `composer check` gom cả ba cổng. Có `phpstan.neon` cấu hình level 5 với `ignoreErrors` được viết có chủ đích chứ không tắt bừa. Có Dockerfile, `.editorconfig`, factory, seeder.

### 2.5 Comment ghi lại lý do, không ghi lại cú pháp

`routes/web.php:162-164`:

```php
// LƯU Ý THỨ TỰ: '/themes/org' phải đứng TRƯỚC '/themes/{id}', nếu không Laravel
// sẽ khớp '/themes/org' vào route show với $id = 'org' và trả 404.
// Ràng buộc ->where('id', '[0-9]+') là lớp bảo vệ thứ hai cho lỗi này.
```

`AuthenticationController.php:59-65` giải thích vì sao ba method `signup40acc_*` bị gỡ, kèm chẩn đoán gốc rễ (đọc email từ một cột vốn là khóa ngoại tới `users.id`). Đây là thói quen của người sẽ trở thành senior, không phải của người vừa vá xong là quên.

### 2.6 Test có kiểm thử phủ định

`tests/Feature/CoreWorkflowsTest.php:97-105` test rằng người ngoài **không** xem được note, `assertForbidden`. Phần lớn test do người mới viết chỉ có happy path. Ở đây có test từ chối, test trùng email, test sai mật khẩu.

### 2.7 Chuyển demo sang Blade là một bước nhảy thật

15 file HTML rời, mỗi file 41–50 KB, nghĩa là toàn bộ sidebar/topbar/CSS bị nhân bản 15 lần. Sau khi chuyển: một `layouts/app.blade.php`, một `layouts/auth.blade.php`, partial `topbar`, `@yield`/`@stack`, view composer bơm biến theme cho layout (`app/Providers/AppServiceProvider.php:36-48`). Từ 640 KB HTML lặp còn 95 dòng layout. Người này hiểu được vì sao phải làm vậy — đó mới là điều đáng tính điểm, chứ không phải việc AI gõ hộ.

---

## 3. Điểm yếu — phần quyết định mức lương

### 3.1 Lỗi phân quyền còn sót sau chính đợt "đại tu"

Đây là mục nghiêm trọng nhất, và tôi đặt nó lên đầu vì nó phá vỡ câu chuyện "77 test xanh, PHPStan 0 lỗi".

**Lỗi 1 — IDOR ở lịch sử giao dịch tổ chức.** `app/Http/Controllers/Organization2userTransactionController.php:47-65`:

```php
public function history_view($id)
{
    $userId = Auth::id();
    $organization = Organization::query()->find($id);
    $organizationId = $organization ? $organization->id : $userId;

    $allTransactions = Organization2userTransaction::query()
        ->where(function ($q) use ($userId, $organizationId) {
            $q->where('organizationID', $organizationId)->orWhere('userID', $userId);
        })
```

Không có một dòng nào kiểm tra người gọi có phải host hay thành viên của tổ chức `$id`. Bất kỳ tài khoản nào cũng đổi số trên URL để đọc toàn bộ lịch sử chi tiền của mọi tổ chức trong hệ thống: số tiền, người nhận, thời điểm. So sánh với `create_view` ngay phía trên (dòng 26-28) có `abort(403)` đàng hoàng — chứng tỏ người viết biết phải chặn, chỉ là quên chặn ở đây.

Dòng `$organizationId = $organization ? $organization->id : $userId;` còn tệ hơn về mặt tư duy: nó nhét id tổ chức và id người dùng vào cùng một biến. Hai không gian định danh khác nhau, không bao giờ được trộn.

**Lỗi 2 — so sánh nhầm cột, vừa sai logic vừa rò dữ liệu.** `app/Http/Controllers/User2organizationTransactionController.php:38-50`:

```php
$q->where('from', $userId)->orWhere('organizationID', $userId);
...
$toTransactions = User2organizationTransaction::query()->where('organizationID', $userId)->latest()->get();
```

`organizationID` được đem so với `Auth::id()`. Người dùng có id 7 sẽ thấy mọi giao dịch nạp vào tổ chức có id 7. Tham số `$id` của route bị bỏ qua hoàn toàn — hàm nhận tham số rồi không dùng.

Hai lỗi này nằm ở hai file khác nhau nhưng cùng một nguyên nhân: copy-paste giữa các controller giao dịch rồi sửa tên model mà không đọc lại ngữ nghĩa cột. PHPStan không bắt được vì cả hai đều là `int` so `int` hợp lệ. Test không bắt được vì không có test nào cho hai route đó.

Đây chính xác là loại lỗi mà thị trường 2026 dùng để phân biệt "biết dùng AI" với "review được AI".

### 3.2 Không có tầng kiến trúc nào của Laravel

Thư mục `app/` chỉ gồm `Http/Controllers`, `Models`, `Mail`, `Providers`, `Support`. Không tồn tại:

- `app/Policies` — 0 file. Toàn bộ phân quyền là `if` viết tay trong controller.
- `app/Http/Requests` — 0 file. `$request->validate()` nội tuyến ở 20+ chỗ.
- `app/Services`, `app/Actions` — 0 file. Nghiệp vụ chuyển tiền nằm trong closure của controller.
- `app/Jobs`, `app/Observers`, `app/Http/Middleware` — 0 file.

Hệ quả đo được: đoạn kiểm tra thành viên tổ chức

```php
OrganizationsMember::query()
    ->where('organizationID', $organization->id)
    ->where('userID', Auth::id())
    ->where('status', true)
    ->exists();
```

lặp lại nguyên văn ở `OrganizationsController` (3 lần), `NoteController`, và vài chỗ khác. Một `OrganizationPolicy` với `view`, `manage`, `createNote` sẽ gom hết vào một file, và quan trọng hơn — sẽ khiến lỗi IDOR ở mục 3.1 không thể xảy ra, vì người viết sẽ phải trả lời câu hỏi "route này gọi authorize gì" cho từng action.

Đây là khác biệt lớn nhất giữa code này và code của một Laravel middle thật.

### 3.3 Năm controller giao dịch là năm bản sao

Hàm sinh OTP xuất hiện gần như nguyên văn ở `User2userTransactionController:52`, `User2organizationTransactionController:52`, `Organization2userTransactionController:68`, `Theme4userWalletController:20`, `Theme4orgWalletController`. Chỉ khác tên model. Toàn bộ khung verify — kiểm attempts, kiểm hết hạn, `Hash::check`, `increment('attempts')`, khóa hàng, cộng trừ số dư, đổi status — cũng lặp năm lần với sai khác nhỏ.

Đây là dấu hiệu rõ nhất của người **viết code tốt nhưng chưa thiết kế code**. Một `TransactionOtp` trait hoặc `OtpVerifier` service với interface chung sẽ rút năm bản còn một, và khi đó lỗi ở mục 3.1 sửa một lần là xong thay vì phải nhớ sửa năm chỗ.

### 3.4 Bản thân hàm sinh OTP là một lỗi thiết kế

```php
do {
    $otp = (string) random_int(100000, 999999);
} while (User2userTransaction::query()->where('status', '!=', 'finished')->pluck('otp')->contains(
    fn ($hash) => Hash::check($otp, $hash)
));
```

Hai vấn đề chồng lên nhau. Thứ nhất, `Hash::check` là bcrypt — có 500 giao dịch chưa finished thì mỗi lần tạo OTP phải chạy 500 lần bcrypt, mỗi lần khoảng 50–100ms. Request treo vài chục giây, và bất kỳ ai cũng có thể bơm giao dịch pending để làm chậm cả hệ thống. Thứ hai, yêu cầu "OTP duy nhất toàn hệ thống" vốn không tồn tại. OTP chỉ cần duy nhất trong phạm vi **một** giao dịch, vì khi verify đã có `$id` rồi. Vòng lặp này giải một bài toán không có thật, bằng cách đắt nhất có thể.

Người viết đã hiểu bcrypt đủ để dùng nó đúng chỗ (lưu OTP), nhưng chưa hiểu chi phí của nó.

### 3.5 Ràng buộc dữ liệu bị đẩy hết lên PHP

Rà 30 migration: **không có một `unique([...])` composite nào**. Trong khi đó các bảng sau bắt buộc phải có:

| Bảng | Cặp cột cần unique | Hiện đang chống trùng bằng |
| --- | --- | --- |
| `organizations_members` | `organizationID` + `userID` | `exists()` trong PHP |
| `mark_as_dones` | `noteID` + `userID` | không chống |
| `pivot_for_note` | `note_id` + `shared_with` | `exists()` trong PHP |
| `theme4user_wallets` | `userID` + `theme4ID` | `exists()` trong PHP |

Kiểm tra `exists()` rồi `create()` là race condition kinh điển: hai request song song cùng thấy "chưa có", cùng tạo. Điểm chua nhất là người viết **đã biết** cách xử lý đúng — `lockForUpdate` trong luồng tiền — nhưng không mang tư duy đó sang phần còn lại. Kiến thức có, chưa thành phản xạ.

Vài chi tiết migration khác cho thấy code được sao chép hơn là được đọc: `$table->id()->autoIncrement()->unique()` thừa hai lần gọi (id() đã là khóa chính auto-increment); `$table->foreignID(...)` viết hoa sai chuẩn ở ba file, chạy được chỉ vì PHP không phân biệt hoa thường tên method.

### 3.6 Soft delete nửa vời

`Note` dùng `SoftDeletes`, nhưng `NoteController::delete_note` (dòng 182-187) xóa cứng shares, mark-as-done và replies trước khi soft delete note. Khôi phục note sẽ ra một note trắng trơn, mất hết chia sẻ và bình luận. Hoặc chọn xóa cứng toàn bộ, hoặc soft delete đồng bộ cả cụm — hiện tại là kết hợp tệ nhất của cả hai.

### 3.7 Hiệu năng: N+1 và mail chặn request

`resources/views/home.blade.php:69` gọi `$note->creater?->name` trong vòng lặp 20 note, còn `NoteController::home` không `with('creater')`. 21 query cho một trang. Lặp lại ở `organizations/show.blade.php`.

Về mail: 13 chỗ `Mail::to()->send()`. Ba mailable có `implements ShouldQueue` (`Mail40account`, `OrganizationInvitation`, `UserEmail`), còn toàn bộ mailable OTP thì không. Nghĩa là người dùng bấm "Chuyển tiền" rồi ngồi chờ SMTP bắt tay xong mới thấy trang verify. Repo đã cấu hình `QUEUE_CONNECTION=database`, `composer dev` đã chạy sẵn `queue:listen` — hạ tầng có đủ, chỉ thiếu một dòng `implements ShouldQueue`. Sự không nhất quán này cho thấy khái niệm queue được áp dụng theo từng lần sửa lẻ, chưa thành quyết định kiến trúc.

### 3.8 Tài liệu lệch thực tế

README ghi: "Blade templates + Tailwind CSS 4 bundled with Vite". Thực tế: không có `@vite` ở bất kỳ view nào, CSS và JS viết tay đặt thẳng trong `public/`, Bootstrap 5.3 và Font Awesome nạp qua CDN. Tailwind và Vite nằm trong `package.json` nhưng không tham gia vào bất cứ thứ gì được render.

Ba dependency production không được import ở đâu trong `app/`, `routes/`, `resources/`, `config/`:

- `encore/laravel-admin`
- `imagekit/imagekit`
- `hernol/uploadthing-php`

Người phỏng vấn sẽ mở `composer.json` và hỏi ba gói này dùng ở đâu. Không trả lời được thì toàn bộ README mất độ tin cậy — kể cả những phần viết đúng.

### 3.9 Quy ước đặt tên không có quy ước

Trong cùng một codebase:

- Method controller snake_case: `user2user_transaction_create`, `create_note_in_organization`
- Method controller camelCase: `updateProfile`, `changePassword`
- Method controller viết hoa chữ đầu: `Organization_buy_theme`, `Organization_buy_theme_verify_otp`
- Class Mailable snake_case: `user2user_trans_otp`, `change_host_organization`
- Model: `Replynote` (đúng phải là `ReplyNote`)
- Bảng `note` số ít trong khi 29 bảng còn lại số nhiều
- Cột `creater_id` — sai chính tả `creator`, và cột này đã đi vào 15+ file
- Cột `organizationID`, `userID`, `noteID` camelCase trong DB, cạnh `note_id`, `shared_with`, `user_id` snake_case

Mỗi cái riêng lẻ là chuyện nhỏ. Gộp lại, người review sẽ kết luận: chưa từng làm trong team có code review. Đó là kết luận đúng, và cũng là thứ sửa được nhanh nhất trong toàn bộ báo cáo này.

### 3.10 Code chết

`Note::scopeVisibleTo` (`app/Models/Note.php:65`) không được gọi ở bất kỳ đâu, trong khi chính logic đó lại được viết lại bằng tay trong `NoteController::home`. Tương tự `Note::shared_notes`, `User::sharedNotes`. Scope đã được viết đúng nhưng không được dùng — dấu hiệu của refactor dở dang, có lẽ do sửa theo từng report thay vì theo một kế hoạch.

Ngoài ra `vendor/bin/pint --test` đang fail ở `config/filesystems.php`. CI cấu hình chạy Pint trước tiên, nên branch này đang đỏ.

---

## 4. Kết luận về trình độ, đối chiếu thị trường 2026

### 4.1 Xếp loại

**Laravel backend: junior khá, đang ở ranh giới lên middle.** Không phải fresher — fresher không viết được `lockForUpdate` đúng chỗ, không nghĩ tới CSS injection, không tự dựng CI ba cổng. Nhưng chưa phải middle — middle Laravel không để thiếu Policy trên một app có ví tiền, và không để lọt IDOR ngay trong nhánh mang tên `fix/audit-and-refactor`.

**Tư duy hệ thống (không phụ thuộc framework): middle thật.** Mô hình hóa 24 bảng có quan hệ, thiết kế state machine cho giao dịch, xử lý double-spend, thiết kế luồng mời người chưa có tài khoản bằng token dùng một lần — đây là 3 năm Django nói chuyện, không phải AI.

**Frontend: junior.** 592 dòng vanilla JS thao tác `innerHTML` để đổi chế độ card. Có escape HTML nên không thủng XSS, nhưng đây là ngõ cụt về khả năng bảo trì. Không dùng build tool dù đã cài.

**Kỹ năng dùng AI: trên trung bình về sản lượng, dưới trung bình về kiểm soát.** Cấu trúc `.agents/skills/laravel-best-practices` với 20 file rule cho thấy đầu tư nghiêm túc vào việc dạy AI làm đúng. Nhưng kết quả cuối vẫn còn IDOR, còn `organizationID` so với user id, còn README sai thực tế. AI đã tăng tốc độ, chưa tăng phán đoán.

### 4.2 Điều thẳng thắn nhất tôi muốn nói

55.000 dòng thêm vào trong một nhánh. 77 test xanh. PHPStan 0 lỗi. Và vẫn còn hai lỗi phân quyền đọc được dữ liệu tiền của người khác.

Đó là bức chân dung chính xác của developer 2026 dùng AI mà chưa đổi cách làm việc. AI làm cho việc **viết** rẻ đi, nên khối lượng phình ra. Nhưng việc **kiểm** thì không rẻ đi chút nào, mà lại phình theo cùng tốc độ. Người này đang chạy với sản lượng của một team ba người và quy trình kiểm tra của một người.

Thị trường 2026 không còn trả tiền cho tốc độ gõ code. Nó trả tiền cho người dám nói "đoạn AI vừa sinh ra này sai ở dòng 54, vì nó trộn hai không gian id". Đây chính là kỹ năng cần tập, và tin tốt là code trong repo cho thấy người này có đủ nền để tập được — các comment ở `routes/web.php:162` và `AuthenticationController.php:59` là bằng chứng của một người đã biết suy nghĩ theo lối đó, chỉ là chưa làm đều.

### 4.3 Có tiềm năng với Laravel không

Có, và tôi không nói cho dễ nghe.

Laravel về bản chất là Django với cú pháp khác: cả hai đều MVC, đều ORM active-record-ish (Django ORM gần data-mapper hơn nhưng cách dùng tương tự), đều có migration, middleware, template engine, queue. 3 năm Django nghĩa là toàn bộ khái niệm đã có sẵn, chỉ thiếu ánh xạ:

| Django | Laravel |
| --- | --- |
| `PermissionRequiredMixin`, `user.has_perm` | Policy / Gate |
| `forms.Form` / DRF Serializer | FormRequest |
| `select_related`, `prefetch_related` | `with()` |
| Celery task | Queue Job |
| `get_object_or_404` | Route model binding |
| signals | Model Observer |

Sáu dòng trong bảng trên chính là sáu điểm yếu lớn nhất ở mục 3. Nghĩa là khoảng cách hiện tại **không phải khoảng cách năng lực, mà là khoảng cách từ vựng**. Loại khoảng cách đó lấp trong vài tuần, không phải vài năm.

Bằng chứng ủng hộ thêm: `ThemeStyle::sanitize`, `Invitation::issueFor`, và comment giải thích thứ tự route đều là thứ người ta chỉ viết ra sau khi đã tự hiểu vì sao. Người không có tiềm năng sẽ dán code AI rồi đi tiếp.

### 4.4 Có xin được việc không

Được, nhưng vị trí và mức lương phụ thuộc vào việc branch này được dọn hay không.

Nộp nguyên trạng: qua vòng CV (project có ví tiền, OTP, CI, test — hồ sơ đẹp hơn 80% ứng viên junior), rớt hoặc bị ép lương ở vòng review code, vì reviewer nào mở `Organization2userTransactionController` cũng thấy lỗi IDOR trong ba phút. Kết quả nhiều khả năng: offer fresher/junior thấp, hoặc bị đánh giá là "code AI".

Nộp sau khi dọn xong mục 5 dưới đây: ứng viên junior mạnh, có khả năng đàm phán vị trí middle ở công ty vừa và nhỏ. Câu chuyện "3 năm Django, tự chuyển sang Laravel, tự audit ra lỗi phân quyền của chính mình rồi viết test hồi quy" là câu chuyện phỏng vấn tốt hơn hẳn "em làm project có 55k dòng".

Một cảnh báo về thị trường: 2026, vị trí Laravel ở Việt Nam gần như luôn kèm Livewire hoặc Inertia + Vue trong JD. Vanilla JS thao tác DOM không còn được tính là kỹ năng frontend.

---

## 5. Việc cần làm, theo thứ tự ưu tiên

### Tuần 1 — chặn máu (bắt buộc trước khi đưa repo cho bất kỳ nhà tuyển dụng nào)

1. Sửa IDOR ở `Organization2userTransactionController::history_view`: thêm kiểm tra host/thành viên, bỏ dòng fallback `$organizationId = ... : $userId`.
2. Sửa `User2organizationTransactionController::history_view`: `organizationID` phải so với id tổ chức, không so với `Auth::id()`; dùng đúng tham số `$id`.
3. Viết test hồi quy cho hai lỗi trên. Test phải **đỏ** trước khi sửa, xanh sau khi sửa. Commit hai bước riêng để lịch sử git kể được câu chuyện đó.
4. Chạy `vendor/bin/pint` cho CI xanh trở lại.
5. Gỡ ba dependency không dùng khỏi `composer.json`, sửa phần Tech Stack trong README cho khớp thực tế.

### Tuần 2–3 — lấp khoảng cách từ vựng Laravel

6. Viết `OrganizationPolicy` và `NotePolicy`. Thay toàn bộ `if` phân quyền trong controller bằng `$this->authorize(...)`. Đây là việc có giá trị cao nhất trong toàn bộ danh sách.
7. Chuyển sang route model binding: `public function show(Organization $organization)` thay cho `find($id)` + null check. Xóa được vài trăm dòng.
8. Tạo FormRequest cho ba luồng phức tạp nhất (tạo giao dịch, tạo tổ chức, đổi mật khẩu).
9. Thêm `implements ShouldQueue` cho toàn bộ mailable OTP.
10. Thêm `with('creater')` vào `NoteController::home` và `OrganizationsController::show`.

### Tuần 4–6 — làm một mẫu chuẩn để đi phỏng vấn

11. Viết migration thêm unique composite cho bốn bảng ở mục 3.5, kèm script dọn dữ liệu trùng.
12. Rút một `TransactionService` (hoặc trait) dùng chung cho năm luồng giao dịch. Sửa hàm sinh OTP: bỏ vòng lặp kiểm tra trùng toàn hệ thống.
13. Viết lại **một** luồng giao dịch cho thật chuẩn — FormRequest + Policy + Service + Job + test đầy đủ — và dùng chính nó làm bài mẫu khi phỏng vấn. Một luồng làm đúng thuyết phục hơn năm luồng làm vội.
14. Thống nhất đặt tên: toàn bộ method controller về camelCase, class Mailable về PascalCase. Cột `creater_id` thì để lại và ghi vào README như một quyết định có ý thức — sửa nó bây giờ tốn hơn giá trị thu được, và biết dừng lại cũng là một kỹ năng.

### Sau đó — kỹ năng cần bổ sung cho thị trường

Xếp theo mức độ ảnh hưởng tới khả năng có việc:

**Livewire 3/4 hoặc Inertia + Vue 3.** Ưu tiên số một. Không có nó thì loại khỏi phần lớn JD Laravel 2026.

**Authorization và validation sâu của Laravel.** Policy, Gate, `authorize`, form request authorization, rate limiting theo user. Đây là điểm yếu lớn nhất hiện tại.

**Queue và job thật sự.** Job có retry, backoff, failed job table, `php artisan queue:work` với supervisor. Tương đương Celery đã dùng ở Django, chỉ là học lại API.

**Tối ưu database.** `EXPLAIN`, index composite, phát hiện N+1 bằng Telescope hoặc Laravel Debugbar, mức isolation của transaction.

**Kiểm thử phân quyền có hệ thống.** Quy tắc tự đặt: mọi route nhận `{id}` phải có ít nhất một test "người lạ nhận 403". Nếu quy tắc này có từ đầu thì hai lỗi ở mục 3.1 đã không tồn tại.

**Observability.** Structured logging, Sentry, health check. Phần này hoàn toàn vắng mặt trong repo.

**Đọc diff của AI.** Không phải học prompt tốt hơn — sản lượng đã đủ. Đề xuất cụ thể: mỗi lần AI sinh ra một khối code, tự trả lời ba câu trước khi commit. Ai được phép gọi hàm này? Truy vấn này chạy bao nhiêu query? Cột đang so sánh có cùng kiểu định danh không? Ba câu đó, nếu áp dụng, đã bắt được cả hai lỗi phân quyền và cả hàm sinh OTP.

---

## 6. Bảng điểm

| Tiêu chí | Điểm /10 | Ghi chú |
| --- | --- | --- |
| Mô hình hóa nghiệp vụ | 7,5 | Nhiều thực thể, quan hệ hợp lý, state machine giao dịch rõ ràng |
| Kiến trúc Laravel | 3,5 | Thiếu toàn bộ Policy, FormRequest, Service, Job |
| Bảo mật | 5,0 | Nền tảng tốt (hash OTP, hash token, sanitize CSS) nhưng còn IDOR đang mở |
| Chất lượng dữ liệu | 4,5 | Có FK và cascade, thiếu hoàn toàn unique composite |
| Hiệu năng | 4,0 | N+1, mail đồng bộ, hàm sinh OTP O(n) bcrypt |
| Kiểm thử | 6,5 | 77 test có test phủ định, nhưng bỏ trống đúng chỗ có lỗi |
| Công cụ và CI | 8,0 | CI ba cổng chạy mọi nhánh, Docker, phpstan cấu hình chủ đích |
| Sạch sẽ và quy ước | 3,5 | Bốn kiểu đặt tên cùng tồn tại, code chết, README sai |
| Frontend | 4,0 | Blade có cấu trúc tốt; JS thì đi vào ngõ cụt |
| Tài liệu | 5,5 | Comment giải thích lý do rất tốt; README lại mô tả sai stack |
| **Trung bình** | **5,2** | Junior khá, cách middle khoảng 3–6 tháng làm việc có kỷ luật |

---

## 7. Lời cuối gửi người viết code

Ba năm Django của bạn nhìn thấy rõ trong repo này. `lockForUpdate` đặt đúng chỗ, token hash bằng sha256 chứ không lưu thô, regex chặn CSS injection, cái comment ở `routes/web.php:162` giải thích vì sao route này phải đứng trước route kia. Không AI nào tự nghĩ ra việc phải giải thích điều đó cho người đọc sau. Đấy là bạn.

Nhưng cũng chính repo này có một hàm nhận tham số `$id` rồi không dùng, và một truy vấn đem id tổ chức so với id người dùng. Cả hai nằm trong nhánh tên là `fix/audit-and-refactor`.

Khoảng cách giữa hai điều trên là toàn bộ nội dung của báo cáo này. Nó không phải khoảng cách về trí tuệ hay kinh nghiệm — nó là khoảng cách giữa lượng code bạn tạo ra được và lượng code bạn thực sự đọc. AI đã kéo giãn khoảng cách đó ra, và nhiệm vụ của 6 tháng tới là kéo nó lại.

Bắt đầu bằng năm việc của tuần 1. Chúng chiếm chưa tới một ngày.
