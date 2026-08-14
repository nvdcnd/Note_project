# Đánh giá ứng viên — Backend Developer (Django 3 năm, đang chuyển Laravel)

Người đánh giá: Tech Lead kiêm HR IT, 15 năm nghề
Ngày: 12/08/2026
Hồ sơ: 3 repo — `Note_project` (Laravel), `BDTeen` (Django, 2023), `DermAI---Website` (Django, 2026)
Phương pháp: đọc code trực tiếp, chạy thử test suite và linter, đối chiếu lịch sử commit. Không đọc CV, không nghe kể.

Tôi đã đọc hơn hai trăm hồ sơ trong quý này. Phần lớn nộp link GitHub mà tôi mở ra thấy tutorial clone. Hồ sơ này khác: có code thật, có hệ thống thật, có tiền ảo chạy trong đó. Nên tôi đọc kỹ hơn bình thường, và vì đọc kỹ nên tìm được nhiều thứ hơn ứng viên muốn tôi tìm.

Đọc mục 9 trước nếu bạn chỉ có ba phút. Có hai API key đang nằm công khai trên GitHub.

---

## 1. Tổng quan

Ba dự án cho ba lát cắt rất khác nhau, và chính sự khác nhau đó là phần đáng giá nhất của hồ sơ.

`BDTeen` là bản chụp năng lực thật khi không có AI. 4.930 dòng Python, 3.138 dòng nằm trong một file `views.py` duy nhất, 80+ view function, lịch sử từ 18/11/2023 đến 06/08/2026. Ứng viên tự viết 100%. Đây là thứ tôi tin nhất trong hồ sơ, vì nó không thể giả được — và cũng là thứ phơi bày nhiều lỗ hổng nhất.

`Note_project` là Laravel, toàn bộ lịch sử nằm gọn trong 12 ngày: commit đầu 01/08/2026, commit cuối 12/08/2026. Trong 12 ngày đó ra được 60 file PHP, 24 controller, 24 model, 30 migration, 77 test pass sạch, PHPStan level 5 không lỗi. Không ai học Laravel từ số 0 mà làm được vậy trong 12 ngày. Đây là output của AI, có ứng viên ngồi lái. Câu hỏi tuyển dụng không phải "ai gõ" mà là "lái giỏi tới đâu" — tôi sẽ trả lời bằng bằng chứng ở mục 3.

`DermAI` là Django + AI model, ý tưởng và kiến trúc của ứng viên, code do AI sinh. 8 commit, 2.195 dòng, trong đó có 8 file script vứt đi nằm ngay thư mục gốc: `fix_files_v2.py`, `fix_files_final.py`, `fix_result_final.py`, `patch_result_final.py`, `update_po.py`, `update_po_v2.py`, `update_dynamic_po.py`, `debug_strings.py`. Tên file kể đúng câu chuyện: thử, hỏng, thử lại, đặt tên "final", vẫn hỏng, đặt tên "final" lần nữa.

Kết luận sớm, để bạn khỏi phải đoán trong lúc đọc tiếp: **đây là một mid-level developer thật, không phải junior**, nhưng là mid-level có lỗ hổng nền tảng ở đúng những chỗ nguy hiểm nhất — concurrency, security, và kỷ luật giao hàng. Ứng viên đang tiến bộ nhanh nhờ AI, nhưng tiến bộ đó chưa kịp lấp phần móng.

---

## 2. Điểm mạnh

**Chọn bài toán khó và làm tới cùng.** Cả ba dự án đều có tiền tệ nội bộ, ví, giao dịch, phân quyền nhiều cấp. `Note_project` có tới 4 luồng chuyển điểm khác nhau (user→user, user→org, org→user, user→theme), mỗi luồng có OTP gửi mail, hạn 10 phút, giới hạn 5 lần nhập sai. `BDTeen` có marketplace tài liệu, lớp học online, club, quiz, kiểm duyệt nội dung bằng AI. Đa số ứng viên cùng level nộp cho tôi CRUD blog. Người này không.

**Hiểu service container của Laravel sau chưa đầy 2 tuần.** Đoạn này trong [AppServiceProvider.php:23](app/Providers/AppServiceProvider.php:23) là code của ứng viên tự viết:

```php
$this->app->singleton(Imagekit::class, function ($app) {
    return new Imagekit(
        publicKey: config('services.imagekit.public_key'),
        ...
    );
});
```

Rồi inject thẳng vào method: `public function AvatarUpload(Request $request, Imagekit $imagekit)`. Singleton binding, named arguments của PHP 8, config-driven, không hardcode key. Một người mới 12 ngày với Laravel mà nhảy thẳng vào DI container thay vì `new ImageKit(...)` giữa controller là dấu hiệu tốt. Rất nhiều dev Laravel 2 năm vẫn không làm thế.

**Biết `DB::transaction` là gì và dùng đúng chỗ.** Cả `BDTeen` (`@transaction.atomic` trên các view thanh toán) lẫn `Note_project` đều bọc phần trừ/cộng tiền trong transaction. Ứng viên tự nhận đây là phần mình viết, và tôi tin — vì nó xuất hiện nhất quán ở cả dự án không có AI.

**Test suite thật, không phải test cho có.** Tôi chạy `php artisan test`: 77 test, 257 assertion, pass 100% trong 3,1 giây. Quan trọng hơn con số là nội dung — [CoreWorkflowsTest.php:24](tests/Feature/CoreWorkflowsTest.php:24) kiểm tra password có được hash không, có reject email trùng không, có chặn password mismatch không. Đây là test hành vi, không phải test `assertTrue(true)`. Dù test do AI sinh, việc ứng viên giữ nó xanh qua nhiều lần refactor là kỷ luật thật.

**Chịu đọc lại code mình.** Thư mục `report/` có 20 file audit. Ứng viên tự đặt hàng AI soi lỗi dự án của mình rồi sửa theo. Thói quen tự kiểm tra này hiếm ở level mid — phần lớn dev ở tuổi nghề này còn đang bận bảo vệ code của mình.

**Dùng AI có chủ đích, không phải copy mù.** File `AGENTS.md` trong repo là bộ quy tắc ứng viên đặt ra cho AI: bắt tuân theo convention sẵn có, cấm tự đổi dependency, cấm tự đẻ file tài liệu. Người copy-paste mù không viết ra thứ này.

---

## 3. Điểm yếu

### 3.1 Chung cho cả ba dự án

**Không phân biệt được "atomic" và "locked".** Đây là lỗ hổng nghiêm trọng nhất và tôi muốn nói kỹ.

Trong `BDTeen`, [views.py:372](social_learning/views.py:372), code thanh toán tự viết:

```python
@transaction.atomic
def question_payment(request, id):
    ...
    if final == bio.wallet_passcode and bio.balance >= question.price:
        answer.user.balance += question.price
        bio.balance -= question.price
```

`@transaction.atomic` bảo đảm "được ăn cả ngã về không", nhưng không khóa dòng. Hai request đồng thời cùng đọc `balance = 100`, cả hai cùng thấy đủ tiền, cả hai cùng ghi. Tiền được nhân đôi. Đây là lost update kinh điển, và nó nằm trong code xử lý tiền.

Bây giờ so với `Note_project`, [User2userTransactionController.php:129](app/Http/Controllers/User2userTransactionController.php:129):

```php
// Lock the sender + recipient rows to prevent double-spend (BE-6 / E11).
$lockedSender = User::query()->lockForUpdate()->find($transaction->from);
```

Đúng bài. Nhưng để ý cái comment: `(BE-6 / E11)` — đó là mã số issue trong một report audit do AI sinh. Nghĩa là AI phát hiện lỗi, AI vá, ứng viên merge. Kiến thức này chưa vào người. Bằng chứng: `BDTeen` được ứng viên chỉnh sửa gần đây (commit tháng 8/2026, ứng viên nói là để "thêm transaction") mà race condition vẫn nguyên đó. Học được ở dự án A, không mang sang dự án B.

Và ngay cả bản Laravel đã vá vẫn còn hai lỗ mà AI không thấy:

- Khóa theo thứ tự `from` rồi `to`. Hai giao dịch ngược chiều A→B và B→A chạy đồng thời sẽ khóa ngược thứ tự nhau — **deadlock**. Phải khóa theo thứ tự ID tăng dần.
- Dòng `transactions` không được khóa, chỉ dòng `users`. Hai request cùng submit đúng OTP một lúc, cả hai đều thấy status `pending`, và nếu số dư đủ cho cả hai lần thì giao dịch chạy hai lần. Thiếu tính idempotent.

**Không validate input là tiền.** [views.py:449](social_learning/views.py:449) trong `BDTeen`:

```python
value = float(request.POST.get("value"))
...
if final == bio.wallet_passcode and bio.balance >= value:
    document.user.balance += value
    bio.balance -= value
```

Gửi `value = -1000000`. Điều kiện `balance >= -1000000` luôn đúng. Người nhận bị trừ một triệu, người gửi được cộng một triệu. Đây không phải lỗi tinh vi — đây là thứ phải chặn ở phản xạ.

**Tiền để kiểu `float`.** `balance = models.FloatField()` ở [models.py:24](social_learning/models.py:24). Số dấu phẩy động không biểu diễn chính xác được tiền; cộng trừ nhiều lần sẽ lệch. Trong `Note_project` chỗ này đã được sửa sang decimal, nhưng lại là do AI: migration `2026_08_11_000001_change_balances_to_decimal.php`, tức là AI audit chỉ ra rồi mới sửa.

**Tự chế mã hóa.** File [hashed.py](social_learning/hashed.py) là một hàm băm tự nghĩ ra: ánh xạ từng chữ số qua bảng thay thế cố định, nối chuỗi, rồi SHA-256 không salt. Passcode chỉ gồm chữ số. Với PIN 6 số, không gian tìm kiếm là một triệu — máy tính xách tay vét cạn trong vài giây, và vì không có salt nên vét một lần dùng được cho toàn bộ user. Django có sẵn `make_password`/`check_password`. Tự viết crypto là điều tuyệt đối không làm, và tôi sẽ hỏi câu này trong phỏng vấn.

Hàm đó còn một bug thường: `if int(i) not in hs_num` — nếu người dùng nhập chữ cái, `int(i)` ném `ValueError` trước khi câu lệnh guard kịp chạy. Và khi guard chạy được, nó `return` một chuỗi thông báo lỗi thay vì raise, nên caller đem chuỗi đó đi so sánh như thể là hash.

**Docker và CI viết cho đẹp hồ sơ, chưa từng chạy được.** `Dockerfile` của `BDTeen`:

```dockerfile
ENV PYTHONUNBUFFERED1=1
COPY requirements.txt
RUN pip install --no-cache-dir -r requirements.txt
COPY ..
CMD ["sh", "-c", "... gunicorn config.wsgi:application ..."]
```

Bốn lỗi trong tám dòng. `COPY requirements.txt` thiếu đích, `COPY ..` thiếu đích, `PYTHONUNBUFFERED1` thừa số 1 nên biến vô nghĩa, và `config.wsgi` không tồn tại — module của dự án tên là `stempetion`. File này không build được. Ứng viên có 5 commit liên tiếp tên "Requirement.txt fixed", "Fixed health check api" ×3 — dấu hiệu sửa mò trên CI thay vì test cục bộ.

CI thì cả hai dự án Django dùng chung một file `django.yml` copy y hệt nhau, kết thúc bằng `python manage.py test`. Mà `tests.py` của cả hai dự án đều là:

```python
from django.test import TestCase
# Create your tests here.
```

CI xanh vĩnh viễn vì nó chạy 0 test. Badge xanh, tín hiệu bằng không. Nếu ứng viên ghi "CI/CD" vào CV thì đây là chỗ tôi sẽ đào.

**`requirements.txt` mã hóa UTF-16.** Đọc bằng công cụ thường ra chuỗi có khoảng trắng giữa mỗi ký tự. File này còn kéo theo `web3`, `eth-account`, `eth-keys` và 12 gói blockchain khác cho phần code đã bị comment từ 2023.

### 3.2 Riêng Django

Ba năm Django mà tôi không thấy dấu vết của những thứ Django cho không:

- **Không dùng `forms.py`.** Mọi input đều `request.POST.get(...)` thẳng vào model. Không validation layer, không error message chuẩn, không CSRF-aware form rendering. Cả `BDTeen` lẫn `DermAI` đều không có file `forms.py`.
- **Không dùng Class-Based View.** Có `import generic` ở đầu `views.py` nhưng không dùng dòng nào. 80 function view viết tay, mỗi cái tự lặp lại `if request.user.is_authenticated: ... else: return redirect("index")` — trong khi `@login_required` đã import sẵn ngay dòng trên.
- **Không có `Meta`, không `__str__`, không `choices`, không index.** [models.py](social_learning/models.py) dùng `status = models.CharField(max_length=1000)` cho trạng thái thay vì `choices`; dùng `IntegerField` làm boolean (`deleted`, `answered`, `choosen`); `comment_counter` đếm tay thay vì `annotate(Count(...))`. Trang admin của dự án này chắc chắn hiện toàn `object (1)`, `object (2)`.
- **Naming loạn.** `Education_rank`, `have_buy_document`, `club`, `hornorable` (sai chính tả, và sai vĩnh viễn vì đã vào migration), `online_class` nằm cạnh `Quiz_questions` và `Bio`. Không theo PEP 8, không theo Django convention, không theo chính nó.
- **N+1 khắp nơi.** Không có `select_related`/`prefetch_related` ở bất kỳ đâu, trong khi template lặp qua danh sách rồi truy cập `.user.user.username` — mỗi item hai query phụ.
- **Hàm trùng tên.** `delete_question` được định nghĩa hai lần trong cùng file. Python lấy cái sau, cái trước thành code chết mà không ai báo.

**Lỗ hổng đăng nhập ở `Signup`**, [views.py:80](social_learning/views.py:80):

```python
if not user and not user2:
    ...tạo tài khoản...
else:
    login(request, user, backend='django.contrib.auth.backends.ModelBackend')
    return redirect("check")
```

Nhánh `else` chạy khi username đã tồn tại. Nó gọi `login()` mà **không kiểm tra mật khẩu**. Nghĩa là: vào trang đăng ký, gõ username của người khác, mật khẩu gõ bừa, bấm gửi — và bạn đăng nhập vào tài khoản người ta. Đây là chiếm quyền tài khoản, mức critical, nằm trong code tự viết, tồn tại từ 2023.

Còn `def Login(request)` thì thiếu nhánh `return` khi method là GET, nên Django ném `ValueError: didn't return an HttpResponse`.

**`DEBUG` bật ở production.** `BDTeen`: `DEBUG = os.getenv("DEBUG")` — hàm này trả về chuỗi, mà chuỗi `"False"` là truthy trong Python. `DermAI`: `DEBUG = True` hardcode thẳng trong [settings.py:37](dermai/settings.py:37), kèm `render.yaml` và `Procfile` để deploy lên Render. Cả hai đều `ALLOWED_HOSTS = ['*']`. Bật DEBUG trên production nghĩa là mọi lỗi 500 sẽ in ra toàn bộ traceback, biến môi trường và cấu hình cho bất kỳ ai gõ đúng URL.

**In secret ra log.** [views.py:211](Dermal/views.py:211) trong `DermAI`: `print(api_key)`. Dòng dưới nữa: `print(img_bytes)` — đổ nguyên ảnh nhị phân vào log.

**`@csrf_exempt` trên endpoint upload đã `@login_required`.** Tắt CSRF vì gọi bằng JavaScript rồi ngại xử lý token. Cách đúng là gửi header `X-CSRFToken`.

### 3.3 Riêng Laravel

Laravel mới 12 ngày nên tôi không chấm nặng phần thiếu kiến thức. Tôi chấm phần **phân biệt được đâu là code của ứng viên, đâu là code của AI** — vì nó đo đúng cái tôi cần biết: khi AI không đỡ nữa thì chất lượng rơi xuống đâu.

Tôi chạy `./vendor/bin/pint --test`. Kết quả fail, và danh sách file fail đúng bằng danh sách file ứng viên tự gõ gần đây:

| File fail lint | Ai viết |
| --- | --- |
| `SettingsController.php` | phần `AvatarUpload` — ứng viên |
| `OrganizationsController.php` | phần banner ImageKit — ứng viên |
| `AppServiceProvider.php` | singleton ImageKit — ứng viên |
| `config/services.php` | config ImageKit — ứng viên |
| `routes/web.php` | ứng viên thêm route |

22 controller còn lại do AI viết: pass sạch. Dấu vân tay rõ đến mức tôi không cần hỏi ai viết phần nào.

Xem kỹ [SettingsController.php:35](app/Http/Controllers/SettingsController.php:35), code tự viết, đặt ngay cạnh code AI viết trong cùng một file:

```php
public function AvatarUpload(Request $request, Imagekit $imagekit){
    $user = User::find(Auth::user()->id);
    $request->validate([
        "avatar"=>'required'
    ]);

    $image = $request->avatar;
    $save_image = $imagekit->uploadFile([
        'file' => fopen($image->getRealPath(),'r'),
        ...
    ]);
    //dd($save_image);
    if(isset($save_image->error)){
        ...
    } else {
        $user->avatar_image_url = $save_image->result->url;
        $user->save();
        dump($user->avatar_image_url);
        return redirect()->route('settings')->with('success','Your avatar has changed');
    }
}
```

Đếm được bảy vấn đề:

1. `dump()` còn nguyên trong code đã commit. Mỗi lần user đổi avatar, response sẽ có một khối debug HTML chèn vào. Đây là thứ lọt lên production rồi mới có người báo.
2. Validation chỉ có `required`. Không `image`, không `mimes`, không `max`. Upload file `.php` 500MB vẫn qua. Với endpoint nhận file từ internet thì đây là lỗi bảo mật, không phải lỗi cẩu thả.
3. `$request->avatar` thay vì `$request->file('avatar')`. Nếu client gửi field text thay vì file, `->getRealPath()` gọi trên string → fatal error 500.
4. `User::find(Auth::user()->id)` — `Auth::user()` đã trả về model rồi, câu này query thừa một lần.
5. `fopen()` không có `fclose()`.
6. `AvatarUpload` viết PascalCase, trong khi `updateProfile` và `changePassword` ngay trên dưới viết camelCase.
7. `//dd(...)` bỏ lại hai chỗ.

So sánh với `updateProfile` phía trên nó — AI viết — có docblock, validation đầy đủ, `unique:users,email,{id}`, message tiếng Việt, format chuẩn PSR-12. Cùng một file, hai chất lượng.

**Những thứ chưa thấy trong toàn bộ dự án Laravel:** không có Form Request, không có Policy/Gate (phân quyền viết tay bằng `if (Auth::id() !== ...)` lặp lại ở mọi action), không có Service/Action layer (logic tiền nằm trong closure của controller), không có Event/Listener, không có Job/Queue (mail gửi đồng bộ, user phải chờ SMTP), không có API Resource, không có Eloquent scope, không dùng route model binding (`$id` rồi tự `->first()`).

**Dependency thừa trong `composer.json`:** `encore/laravel-admin` và `hernol/uploadthing-php` được require nhưng không có một dòng code nào dùng. `uploadthing` là tàn tích từ trước khi chuyển sang ImageKit. Cài package rồi bỏ đó là thói quen làm phình vendor và mở rộng bề mặt bảo mật.

**PHPStan level 5 "0 lỗi" có mua bằng ignore.** File `phpstan.neon` có 4 rule `ignoreErrors`, và chúng che đúng nhóm lỗi Eloquent magic property. Cách đúng là thêm `@property` docblock vào model. Con số 0 lỗi này tôi trừ điểm chứ không cộng.

**Hàm sinh OTP là quả bom hiệu năng.** [User2userTransactionController.php:52](app/Http/Controllers/User2userTransactionController.php:52):

```php
do {
    $otp = (string) random_int(100000, 999999);
} while (User2userTransaction::query()->where('status', '!=', 'finished')->pluck('otp')->contains(
    fn ($hash) => Hash::check($otp, $hash)
));
```

Mỗi lần tạo OTP, code load toàn bộ giao dịch chưa hoàn tất rồi chạy bcrypt so sánh từng cái. Bcrypt cố tình chậm (~100ms). Có 1.000 giao dịch pending thì một lần tạo OTP mất 100 giây CPU. Đây là self-DoS. Mà logic này còn sai về mặt thiết kế: OTP trùng nhau giữa hai giao dịch khác nhau không phải vấn đề, vì OTP được kiểm tra theo từng transaction ID.

---

## 4. Cơ hội nghề nghiệp

### 4.1 Đường Django

Đây là đường có thật và mở ngay. Ba năm kinh nghiệm là con số đủ để qua vòng lọc CV của phần lớn tin tuyển Django ở Việt Nam. Ứng viên có `BDTeen` chạy được, có `DermAI` tích hợp model AI thật (`gradio_client`, Gemini API, heatmap, đa ngôn ngữ với `.po`) — mảng AI/ML integration đang thiếu người và trả cao hơn CRUD thuần.

Rào cản: những lỗ hổng ở mục 3.2 là thứ mà bất kỳ interviewer Django có nghề nào cũng sẽ chạm phải trong 20 phút đầu. "Bạn dùng ModelForm thế nào?" — không trả lời được. "Xử lý N+1 ra sao?" — không có kinh nghiệm. Ba năm mà không dùng forms và CBV thì tôi sẽ đọc là "ba năm lặp lại năm thứ nhất".

Thị trường Django Việt Nam nhỏ hơn PHP và Java rõ rệt, tập trung ở startup, công ty AI/data, và outsourcing cho khách Âu Mỹ. Ít vị trí nhưng cạnh tranh cũng ít hơn, và mức lương mảng AI tốt.

### 4.2 Đường Laravel

Đường này rộng hơn nhiều về số lượng job — PHP/Laravel vẫn là stack backend phổ biến nhất ở thị trường Việt Nam, đặc biệt mảng agency, e-commerce, và sản phẩm nội địa.

Nhưng 12 ngày kinh nghiệm không phải kinh nghiệm. Nếu nộp CV ghi "Laravel" bây giờ, ứng viên sẽ được xếp vào nhóm fresher Laravel, tức là cạnh tranh với sinh viên mới ra trường về mức lương, trong khi mang theo kỳ vọng của người 3 năm nghề. Đây là vùng tệ nhất để đứng.

Điều đáng nói: `Note_project` là một portfolio Laravel tốt hơn 90% portfolio Laravel tôi nhận. Nếu ứng viên hiểu được toàn bộ nó — chứ không chỉ merge nó — thì sau 3-4 tháng nữa đây là tấm vé vào thẳng vị trí Laravel middle.

### 4.3 Hướng khác

**Backend AI integration** là nơi hồ sơ này mạnh nhất mà ứng viên có vẻ chưa nhận ra. `DermAI` gọi model qua Gradio, xử lý ảnh, sinh heatmap, dịch kết quả đa ngôn ngữ. `BDTeen` dùng LLM để kiểm duyệt nội dung. `Note_project` được xây bằng quy trình AI-assisted có `AGENTS.md`, có audit report. Rất nhiều công ty đang tuyển đúng người biết ghép model vào sản phẩm web, và ứng viên đã làm ba lần. Đây là điểm khác biệt thật, không phải điểm đánh bóng.

**AI-assisted development / prompt engineering cho team dev** cũng là hướng thực tế. Thư mục `report/` với 20 file audit và `AGENTS.md` cho thấy ứng viên đã xây được quy trình làm việc với AI có kỷ luật. Kỹ năng này đang được trả tiền.

Ngược lại, tôi khuyên **không** đi hướng DevOps. Dockerfile không build được, CI chạy 0 test, `DEBUG=True` lên production — ba bằng chứng liên tiếp cho thấy mảng vận hành chưa phải sở trường.

---

## 5. Trình độ theo từng title

Thang: Fresher → Junior → Middle → Senior.

**Django Backend Developer — Middle yếu (Junior+ ở công ty có chuẩn cao).**
Làm được sản phẩm hoàn chỉnh, hiểu ORM, hiểu request lifecycle, tích hợp được bên thứ ba. Nhưng thiếu forms, CBV, query optimization, và có lỗ hổng bảo mật mức critical trong code tự viết. Ở công ty product nghiêm túc, mức này chưa qua vòng technical. Ở outsourcing hoặc startup nhỏ thì nhận được.

**Laravel Backend Developer — Fresher+.**
Đã hiểu service container, routing, Eloquent cơ bản, biết Pest. Chưa chạm Form Request, Policy, Job, Event, Service layer. Code tự viết chưa đạt PSR-12. Đây là đánh giá của 12 ngày, hoàn toàn hợp lý — vấn đề chỉ nảy sinh nếu ứng viên tự nhận cao hơn.

**Fullstack Developer — Junior.**
Có tự viết `noteket.css` 18KB và `noteket.js` 592 dòng, dựng demo UI 15 file HTML. Là vanilla thuần, không framework, không build pipeline thật. Đủ để tự làm sản phẩm một mình, không đủ để vào team frontend.

**AI/LLM Integration Engineer — Middle.**
Đây là title cao nhất tôi có thể ký. Ba dự án đều tích hợp AI ở mức khác nhau, có xử lý ảnh, có streaming, có fallback khi API lỗi. Điểm trừ: quản lý API key kém (mục 9).

**Software Engineer (không gắn stack) — Junior+ hướng Middle.**
Điểm mạnh là product sense và khả năng hoàn thành. Điểm yếu là engineering discipline: không đọc lại code trước khi commit, không xóa debug statement, không verify thứ mình vừa thêm có chạy không. Đây mới là thứ chặn ứng viên lên Middle thật, không phải kiến thức framework.

**Technical Lead — chưa.**
Chưa có bằng chứng review code người khác, chưa có commit message đủ để người khác lần theo (`"Hello"`, `"Hello world"`, `"Next one"`, `"Add files via upload"` ×8). Nhắc lại lần nữa: một team lead không thể để `git log` như vậy.

---

## 6. Cách cải thiện cho từng title

### Django Backend Developer

Việc phải làm, theo thứ tự:

1. Sửa lỗ hổng `Signup` ở [views.py:80](social_learning/views.py:80) ngay hôm nay. Đây là lỗi chiếm quyền tài khoản.
2. Xóa `hashed.py`, thay bằng `django.contrib.auth.hashers.make_password`/`check_password`. Viết một đoạn 3 câu trong README giải thích vì sao đã bỏ — interviewer sẽ thích câu chuyện này hơn là không thấy gì.
3. Thêm `select_for_update()` vào cả 4 hàm payment. Đổi `FloatField` sang `DecimalField(max_digits=12, decimal_places=2)`.
4. Viết `forms.py` cho ít nhất 3 luồng (signup, question_create, payment). Chuyển 5 view sang `ListView`/`DetailView`/`CreateView` để có cái mà kể trong phỏng vấn.
5. Viết 10 test thật vào `tests.py`, trong đó phải có 1 test chứng minh không thể chuyển tiền âm và 1 test chứng minh không đăng nhập được bằng username người khác. Lúc đó CI mới có nghĩa.
6. Tách `views.py` 3.138 dòng thành `views/` package theo domain: `auth.py`, `payment.py`, `club.py`, `staff.py`.

Xong 6 việc trên, ứng viên đi phỏng vấn Django Middle với hồ sơ đứng vững.

### Laravel Backend Developer

1. Viết lại `AvatarUpload` bằng tay, không dùng AI: đổi tên thành `updateAvatar`, dùng Form Request `UpdateAvatarRequest` với rule `['required','image','mimes:jpg,png,webp','max:2048']`, xóa `dump()`, xóa `//dd()`, `fclose()` cho tử tế. Chạy `./vendor/bin/pint` cho đến khi sạch.
2. Chọn 3 controller do AI viết, xóa đi, viết lại từ đầu không mở AI. Đối chiếu bản mình với bản cũ. Đây là bài tập hiệu quả nhất trong toàn bộ danh sách này.
3. Chuyển logic chuyển tiền ra `App\Actions\TransferPoints`. Controller chỉ được validate, gọi action, trả redirect.
4. Thay 40+ dòng `if (Auth::id() !== $x)` bằng Policy. `php artisan make:policy NotePolicy --model=Note`.
5. Chuyển mail OTP sang Queue. `ShouldQueue` trên Mailable, chạy `queue:work`.
6. Sửa deadlock: khóa user theo `min(from,to)` trước rồi `max(from,to)`. Thêm `lockForUpdate()` cho chính dòng transaction và kiểm tra lại status bên trong transaction.
7. Viết lại hàm sinh OTP thành 3 dòng: `random_int` rồi lưu, bỏ vòng lặp bcrypt.
8. Gỡ `encore/laravel-admin` và `hernol/uploadthing-php` khỏi `composer.json`.
9. Xóa 4 dòng `ignoreErrors` trong `phpstan.neon`, thêm `@property` vào model cho đến khi level 5 sạch thật.

### AI/LLM Integration Engineer

Đây là hướng gần nhất tới mức lương tốt, và cũng là hướng ít việc phải làm nhất.

1. Rút toàn bộ key ra biến môi trường, xóa mọi `print(api_key)`.
2. Thêm retry với exponential backoff và timeout cho mọi lời gọi model. Hiện tại `DermAI` gọi Gradio với `timeout=300` bị comment, không có retry — model sleep là user thấy lỗi.
3. Thêm caching kết quả theo hash ảnh. Cùng một ảnh gọi hai lần không nên tốn hai lượt inference.
4. Viết một trang README nói rõ kiến trúc: ảnh đi đâu, model nào, heatmap sinh thế nào, chi phí mỗi request. Đây là thứ tôi sẽ đọc trước khi gọi phỏng vấn.

### Software Engineer nói chung

Ba thói quen, không liên quan framework:

- **`git diff` trước mọi commit.** Tất cả `dump()`, `print()`, `//dd()` lọt vào repo đều chết ở bước này nếu có làm.
- **Commit message viết bằng câu.** `"Hello"` và `"Add files via upload"` là thứ khiến tôi ngờ ứng viên chưa từng làm việc trong team.
- **Chạy thử thứ mình vừa viết.** Dockerfile không build được nằm trong repo 2 năm. Nếu ứng viên gõ `docker build .` một lần thôi thì đã không có mục này trong báo cáo.

---

## 7. Nên chuyển hẳn sang Laravel hay ở lại Django?

Câu trả lời của tôi: **ở lại Django làm nghề chính trong 6-9 tháng tới, giữ Laravel làm stack thứ hai, và đừng chuyển hẳn.** Lý do rất cụ thể.

Ba năm Django là tài sản duy nhất ứng viên có để thương lượng lương. Chuyển hẳn sang Laravel nghĩa là tự nguyện vứt nó đi để bắt đầu lại ở vạch fresher, trong khi vấn đề của ứng viên **không phải là chọn sai framework**. Nhìn lại toàn bộ mục 3: race condition, không validate số âm, tự chế crypto, `DEBUG=True`, `dump()` sót lại, Dockerfile không build. Không có lỗi nào trong số đó do Django gây ra, và không lỗi nào trong số đó được Laravel chữa. Chúng theo người, không theo stack. Chuyển sang Laravel với nguyên bộ thói quen này chỉ tạo ra `Note_project` thứ hai — đẹp bên ngoài nhờ AI, rỗng ở phần móng.

Có một chi tiết tôi muốn ứng viên tự nhìn thẳng. `Note_project` sinh ra trong 12 ngày với 77 test và PHPStan sạch. `BDTeen` mất gần ba năm và có lỗi chiếm quyền tài khoản. Nếu đọc lướt, kết luận sẽ là "Laravel hợp mình hơn". Kết luận đúng là: AI làm việc đó, không phải Laravel. Chọn stack dựa trên cảm giác đó là chọn dựa trên nhầm lẫn về nguyên nhân.

Ngoài ra, một dev Django biết Laravel là hồ sơ hiếm và có giá. Một dev Laravel 12 ngày thì không hiếm chút nào. Giữ cả hai, đừng đổi một lấy một.

Điều kiện tôi sẽ đổi ý: nếu sau 6 tháng ứng viên tự viết được một feature Laravel hoàn chỉnh không mở AI, code pass Pint ngay lần đầu, và có việc Laravel trả cao hơn Django ít nhất 30% — lúc đó chuyển hẳn là quyết định đúng. Trước mốc đó thì không.

Lộ trình 90 ngày tôi đề xuất: tháng 1 sửa toàn bộ lỗ hổng bảo mật ở cả ba repo (mục 6), tháng 2 làm 6 việc Django, tháng 3 làm 9 việc Laravel. Sau 90 ngày, đi phỏng vấn Django Middle và Laravel Junior song song, xem thị trường trả lời.

---

## 8. Vài điều khác, nói thẳng

**Về việc dùng AI.** Tôi không trừ điểm chuyện dùng AI. Cả team tôi đều dùng. Tôi trừ điểm chuyện **không kiểm tra output của AI** — và ở đây có bằng chứng cụ thể: race condition được AI vá trong `Note_project` nhưng vẫn nguyên trong `BDTeen` dù ứng viên vừa sửa file đó tháng trước. Nghĩa là bản vá được merge chứ không được hiểu. Đây là rủi ro lớn nhất khi tuyển người này: khi AI sai, ứng viên chưa chắc phát hiện được.

Cách chứng minh điều ngược lại rất đơn giản — làm bài tập số 2 ở mục 6.2. Xóa 3 controller, viết lại tay, so sánh. Nếu bản tự viết gần bằng bản AI thì ứng viên thật sự hiểu. Nếu cách xa thì ứng viên vừa biết điều quan trọng nhất về chính mình.

**Về câu "tôi vẫn hiểu rõ những gì đang chạy" ở DermAI.** Tôi tin một phần. Kiến trúc thì hiểu — luồng ảnh, model, heatmap, i18n rành mạch. Nhưng người hiểu rõ code đang chạy sẽ không để `DEBUG = True` hardcode trong file settings của một dự án đã deploy lên Render, và sẽ không để 8 file `fix_*_final.py` nằm ở thư mục gốc. Hiểu kiến trúc và kiểm soát chi tiết là hai việc khác nhau; ứng viên đang có cái đầu, thiếu cái sau.

**Về portfolio khi đi ứng tuyển.** Đừng đưa cả ba repo. Ở trạng thái hiện tại, `BDTeen` là con dao hai lưỡi: nó chứng minh ứng viên tự làm được sản phẩm lớn, và nó cũng chứa lỗ hổng auth mà bất kỳ reviewer nào cũng thấy trong 10 phút. Sửa xong mục 6.1 rồi hãy đưa. Trước mắt, `Note_project` + `DermAI` là bộ đôi mạnh hơn.

Và trong CV, hãy ghi rõ phần nào có AI hỗ trợ. Nghe ngược đời, nhưng tôi đã loại nhiều ứng viên vì phát hiện họ giấu, chưa loại ai vì họ thành thật. Người trung thực về quy trình làm việc của mình là người tôi dám giao production.

**Về commit message.** Sửa từ hôm nay, miễn phí, mất 5 giây mỗi lần. `git log` là thứ nhà tuyển dụng đọc mà ứng viên quên mất là có người đọc.

**Điểm tôi thật sự đánh giá cao và muốn nói rõ để khỏi bị chìm giữa các mục chê:** ứng viên tự đặt hàng người khác soi lỗi code của mình, 20 lần, rồi sửa theo. Trong 15 năm tôi gặp rất ít người làm việc đó. Kiến thức thiếu thì học được trong vài tháng; thái độ này thì không dạy được. Đây là lý do tôi viết báo cáo dài thế này thay vì gạch một dòng "chưa đạt" rồi chuyển hồ sơ tiếp theo.

---

## 9. Việc phải làm ngay, trước khi làm bất cứ điều gì khác

Trong `BDTeen` có hai API key hardcode và đã được commit lên GitHub:

- [GPTsecurity.py:5](social_learning/GPTsecurity.py:5) — Coze personal access token, chuỗi bắt đầu `pat_XSNlrVHn...`
- [gpt_search.py:4](social_learning/gpt_search.py:4) — key dạng OpenAI, chuỗi bắt đầu `sk-J3vEsFqZ...`

Bot quét GitHub tìm secret chạy liên tục và tự động; key public từ 2023 nên phải giả định đã bị lấy.

Ba việc, theo đúng thứ tự:

1. Vào dashboard của Coze và OpenAI, **revoke cả hai key ngay**. Làm việc này trước, không đợi làm xong việc 2 và 3.
2. Kiểm tra lịch sử sử dụng và hóa đơn của hai tài khoản đó xem có lượt gọi lạ không.
3. Đổi code sang `os.getenv(...)`, thêm `.env` vào `.gitignore`. Lưu ý: xóa key khỏi file rồi commit **không** làm nó biến mất — nó vẫn nằm trong lịch sử git. Muốn sạch thật thì phải dùng `git filter-repo` hoặc BFG rồi force push, nhưng bước đó chỉ có ý nghĩa sau khi đã revoke.

Nếu ứng viên nộp hồ sơ mà tôi tìm thấy hai key này trước khi ứng viên kịp xóa, hồ sơ dừng ở đó. Không phải vì lỗi nặng, mà vì nó nằm trong repo được giới thiệu là "100% tự code" — tức là không đổ cho AI được.

---

## Kết luận tuyển dụng

**Django Middle:** mời phỏng vấn, có điều kiện. Sẽ hỏi kỹ về concurrency và về `Signup`. Nếu ứng viên tự nhận ra lỗi trước khi tôi chỉ, tôi ký.

**Laravel Middle:** chưa. Hẹn lại sau 6 tháng.

**Laravel Junior / Fresher có mentor:** nhận, và tôi tin người này lên Middle nhanh hơn mặt bằng.

**AI Integration Engineer:** mời phỏng vấn.

Điều kiện chung cho mọi vị trí: hai API key ở mục 9 phải được revoke trước buổi phỏng vấn, và tôi sẽ hỏi ứng viên đã xử lý thế nào.

---

# Phụ lục — Chấm lại sau khi biết ứng viên là ai

Bổ sung ngày 12/08/2026, sau khi đọc kỹ metadata của cả ba repo.

## P.1 Thông tin tôi đã bỏ sót

Toàn bộ báo cáo phía trên viết trên giả định "ứng viên có 3 năm kinh nghiệm nghề". Giả định đó sai, và tôi đáng ra phải kiểm tra trước khi chấm. Bằng chứng nằm ngay trong repo:

- Git author email: `30067814@st.buv.edu.vn` — sinh viên British University Vietnam, mã 30067814.
- README của DermAI: bản quyền thuộc Hoàng Hùng Anh, Phạm Đức Mạnh và Trường Tiểu học, THCS & THPT FPT Bắc Ninh.
- DermAI đạt Giải Ba ViSEF 2026 — cuộc thi dành cho học sinh THCS, THPT. Đầu năm 2026 ứng viên còn là học sinh lớp 12. Trước đó có Giải Nhất Tin học Trẻ tỉnh Bắc Ninh 2025 và Giải Nhì Khoa học Kỹ thuật tỉnh Bắc Ninh 2026.
- BDTeen khởi tạo 18/11/2023, thư mục dự án tên `stempetion` — dự án đi thi khoa học kỹ thuật, viết khi ứng viên khoảng lớp 10.

Ứng viên là **sinh viên năm nhất, khoảng 18 tuổi**. "Ba năm Django" là ba năm tự học từ lớp 10, xen giữa việc học phổ thông và các kỳ thi, không phải ba năm đi làm.

Nhịp commit khớp: BDTeen rải rác 2023–2024, im lặng suốt 2025 (lớp 12), rồi Note_project dồn 60 commit trong 12 ngày đầu tháng 8/2026, ngày nào cũng có, cao điểm 12 commit một ngày. Đó là nhịp nghỉ hè.

## P.2 Phần nào của báo cáo vẫn đúng, phần nào phải bỏ

**Vẫn đúng nguyên vẹn — toàn bộ mục 3 và mục 9.** Race condition vẫn là race condition. Lỗ hổng `Signup` cho phép đăng nhập vào tài khoản người khác vẫn là lỗi critical. Hai API key public trên GitHub vẫn phải revoke hôm nay. Tuổi tác không sửa được lỗi nào trong số đó, và một hệ thống có tiền chạy trong nó thì người dùng bị mất tiền không quan tâm tác giả bao nhiêu tuổi.

Điều thay đổi là **cách đọc** những lỗi ấy. Trước: "lỗ hổng nền tảng đáng lo ở một dev mid-level, sau ba năm mà chưa vá là dấu hỏi về khả năng học". Giờ: "những thứ chưa ai dạy, và chưa có môi trường nào bắt phải học". Không ai review code cho ứng viên trong ba năm qua. Không có production thật sập lúc 2 giờ sáng để dạy bài học về `DEBUG=True`. Phần lớn danh sách ở mục 3 là thứ người ta học trong hai tháng đầu đi làm, chứ không phải thứ tự nghĩ ra được khi ngồi một mình.

**Phải bỏ — thang điểm ở mục 5.** Tôi chấm "Middle yếu", "Fresher+", "Junior+" theo thước của thị trường lao động. Với sinh viên năm nhất thì thước đúng là so với sinh viên cùng khóa, và so với chính hồ sơ thực tập sinh tôi nhận hàng năm.

Chấm lại:

| Hạng mục | Xếp hạng so với sinh viên năm nhất | Ghi chú |
| --- | --- | --- |
| Năng lực kỹ thuật tổng thể | Trên hẳn mặt bằng | Vượt phần lớn hồ sơ thực tập sinh năm 3 tôi nhận |
| Khả năng hoàn thành sản phẩm | Hiếm | Ba sản phẩm chạy được, một cái có người dùng thật |
| Nền tảng CS (concurrency, security, SQL) | Yếu, đúng như tuổi nghề | Chưa học chính quy phần này |
| Kỷ luật kỹ thuật | Yếu | Thiếu môi trường team, không phải thiếu ý thức |
| Product sense | Rất tốt | Chọn bài toán khó, làm tới sản phẩm giao được |
| Thái độ học | Tốt nhất trong hồ sơ | 20 file audit tự đặt hàng soi lỗi mình |

Về DermAI, tôi tách riêng vì nó không cùng hạng mục với hai dự án kia: một công cụ được bác sĩ da liễu ở 4 bệnh viện và phòng khám thẩm định, độ chính xác lâm sàng 86,1%, giải quốc gia. Đưa được sản phẩm ra khỏi máy mình đến tay người dùng thật là việc phần lớn kỹ sư đi làm 5 năm chưa từng làm.

**Phải viết lại — mục 7.**

## P.3 Django hay Laravel, trả lời lại

Lập luận cũ của tôi dựa trên một tiền đề: "ba năm Django là tài sản duy nhất để thương lượng lương, chuyển hẳn là vứt nó đi". Tiền đề đó không còn đứng được. Ở tuổi 18, chưa đi làm ngày nào, không có lịch sử lương để bảo vệ, cũng không có chi phí chìm. Rủi ro chuyển stack gần như bằng không.

Nên câu hỏi "chuyển hẳn hay ở lại" tự nó là câu hỏi sai ở giai đoạn này. Cưới một framework năm 18 tuổi là cách chắc chắn nhất để 25 tuổi trở thành người chỉ biết một thứ. Trong bốn năm đại học phía trước, Laravel 13 sẽ thành Laravel 15, Django 4 thành Django 6, và có thể cả hai đều không phải thứ ứng viên dùng để kiếm sống.

Thứ không đổi trong bốn năm đó, và cũng đúng là thứ danh sách lỗi ở mục 3 đang chỉ vào: khóa dòng và cô lập giao dịch trong CSDL, cách lưu và xác minh mật khẩu, validate dữ liệu ở biên, chỉ mục và N+1, viết test có ý nghĩa, đọc lại diff trước khi commit. Không có mục nào trong đó thuộc về Django hay Laravel. Học xong một lần thì mang đi đâu cũng dùng, và học sớm thì rẻ.

Nên khuyến nghị đổi thành: **đừng chọn stack, chọn nền.** Còn về mặt chiến thuật cho 6 tháng tới thì phần cũ vẫn giữ — đi thực tập bằng Django, vì đó là thứ có sản phẩm để chứng minh; Laravel để trong CV như stack thứ hai đang học, đừng khai là thành thạo.

Điều kiện đổi ý ở bản cũ (chờ 6 tháng, chờ chênh lệch lương 30%) bỏ luôn. Nó được viết cho một người có sự nghiệp cần bảo vệ.

## P.4 Đã nên đi xin việc chưa

**Thực tập hoặc part-time: nên, trong 2–3 tháng tới.**

Lý do không phải vì kỹ năng đã đủ. Mà vì nhìn lại mục 3, phần lớn thứ còn thiếu chỉ có môi trường làm việc mới cho được. `dump()` sót trong commit, Dockerfile chưa từng chạy thử, commit message `"Hello"` — đây đều là những thứ chết ngay trong hai tuần đầu ở một team có code review, và tự học ở nhà thì ba năm nữa vẫn còn, vì không ai nói cho mà biết. Ứng viên đang ở đúng điểm mà mỗi tháng trong môi trường có mentor bằng sáu tháng tự học.

**Full-time: không.** Năm nhất, học phí BUV, bỏ học đi làm là đánh đổi tệ. Part-time 15–20 giờ một tuần là đủ.

**Cái bẫy phải tránh trong CV.** Đừng viết "3 năm kinh nghiệm Django". Người tuyển đọc câu đó là hiểu ba năm đi làm, có hợp đồng, có chịu trách nhiệm production. Khi họ phát hiện ra thực tế, thứ mất đi không phải là ấn tượng về kỹ năng mà là niềm tin — và mất niềm tin ở vòng lọc CV thì phần còn lại của hồ sơ không được đọc nữa. Với hồ sơ này thì đó là thiệt hại lớn, vì phần còn lại rất mạnh.

Cách viết đúng lại mạnh hơn:

> Tự học Django từ năm lớp 10 (2023). Tác giả DermAI — công cụ chẩn đoán da liễu qua ảnh, Giải Ba ViSEF Quốc gia 2026, thẩm định bởi bác sĩ da liễu tại 4 bệnh viện và phòng khám, độ chính xác lâm sàng 86,1%. Giải Nhất Tin học Trẻ Bắc Ninh 2025. Sinh viên năm nhất British University Vietnam.

Không con số nào bị thổi, và đọc lên nặng ký hơn "3 năm kinh nghiệm" nhiều. Giải ViSEF mở được cửa mà code không mở được, đặc biệt ở nhóm công ty health-tech và AI.

**Ba việc trước khi nộp đơn**, gói gọn trong một cuối tuần:

1. Revoke hai API key ở mục 9. Việc này chặn mọi thứ phía sau — nhà tuyển dụng mở GitHub thấy `sk-` hardcode thì dừng đọc ở đó.
2. Vá lỗ hổng `Signup` trong `BDTeen` (mục 3.2).
3. Viết README cho `Note_project`, ghi rõ phần nào AI hỗ trợ.

**Nộp vào đâu:** startup AI hoặc health-tech, công ty sản phẩm có mảng ML, agency Laravel nhận part-time. Tránh DevOps và Infra, lý do đã nêu ở mục 4.3.

## P.5 Kết luận tuyển dụng, bản sửa

Bỏ toàn bộ phần "Kết luận tuyển dụng" phía trên. Thay bằng:

**Thực tập sinh Backend (Django) — nhận, ưu tiên cao.** Trong nhóm hồ sơ thực tập tôi nhận năm nay, hồ sơ này nằm ở nhóm trên cùng, và khoảng cách chủ yếu đến từ DermAI chứ không phải từ code.

**Part-time Backend Django — nhận, có mentor kèm.**

**Part-time Laravel — nhận ở mức fresher.** Với tốc độ hiện tại, ba tháng nữa là làm được việc thật.

**Full-time bất kỳ vị trí nào — không xét, vì đang học năm nhất.** Nói lại sau khi tốt nghiệp, và tôi muốn được liên lạc lại lúc đó.

Câu hỏi tôi sẽ hỏi trong buổi phỏng vấn, để ứng viên chuẩn bị trước: đưa đoạn code thanh toán trong `BDTeen` ở [views.py:372](social_learning/views.py:372) và hỏi "hai người cùng bấm mua một lúc thì chuyện gì xảy ra". Không trả lời được cũng không sao ở tuổi này. Nhưng nếu trả lời được, hoặc tự nhận ra trước khi tôi hỏi, thì tôi ký ngay.

Ghi chú cuối, ngoài vai trò người tuyển dụng: phần lớn báo cáo phía trên là một danh sách lỗi dài. Đọc liền một mạch thì nản. Nên nói rõ một lần cho khỏi hiểu nhầm — tôi viết dài như vậy vì hồ sơ này đáng để viết dài. Hồ sơ không đáng thì tôi gạch một dòng rồi chuyển sang cái tiếp theo, và tôi làm thế với đa số hồ sơ trong tuần này.
