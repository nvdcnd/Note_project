# Báo cáo tổng quan sản phẩm — phiên bản hiệu chỉnh

**Ngày audit:** 04-08-2026  
**Định vị do chủ dự án xác nhận:** ứng dụng note-to-do lấy cảm hứng từ Locket; người dùng có balance để mua theme; organization mở rộng trải nghiệm đó sang làm việc đội nhóm.  
**Phạm vi đánh giá:** working tree hiện tại, database SQLite cục bộ, 12 commit Git, cấu hình runtime và các kiểm tra chỉ-đọc. Không có mã nguồn nào bị sửa trong đợt audit.

## 1. Tóm tắt đúng về ý tưởng sản phẩm

Đây không phải một ứng dụng ghi chú chung chung, cũng không phải ví điện tử. Nó là một **note-to-do app giàu tính cá nhân và tương tác**, lấy cảm hứng từ cảm giác gần gũi, nhanh và trực quan của Locket:

- note là đơn vị chính để ghi lại việc cần làm/ý tưởng;
- thao tác với note ưu tiên card, gesture và trải nghiệm mobile thay vì màn hình quản trị nặng;
- người dùng hoàn thành, bỏ qua, trả lời hoặc chia sẻ note;
- balance là credit nội bộ dành cho việc mua theme và cá nhân hoá trải nghiệm;
- organization biến note-to-do cá nhân thành không gian phối hợp theo đội nhóm.

Điểm khác biệt có thể xây dựng được là: **một to-do app có cảm giác sống động và riêng tư như một luồng note gửi cho nhau, nhưng vẫn có cấu trúc đủ để dùng theo cá nhân lẫn team.**

Các prototype xác nhận trực tiếp hướng này: thẻ giấy có tạo note, đọc note, swipe done/skip, desktop/mobile layout, menu Organization/Theme/Balance và bottom navigation. Xem [prototype đầu](/C:/Users/Admin/Desktop/project1/resources/testing_view/index.html:518) và [prototype hiện hành](/C:/Users/Admin/Desktop/project1/resources/testing_view/testing2.html:240).

## 2. Product thesis và vòng lặp giá trị

| Tầng giá trị | Người dùng nhận được | Chức năng tương ứng |
|---|---|---|
| 1. Capture | Ghi việc/ý tưởng cực nhanh, không bị ngợp bởi form. | Create note, title, content. |
| 2. Momentum | Duyệt note theo trạng thái và cử chỉ; cảm giác hoàn thành rõ ràng. | Done/undone, skip, card gesture. |
| 3. Connection | Gửi một note cho người khác, nhận reply, cùng theo dõi công việc nhỏ. | Share, invite, reply. |
| 4. Team | Tập hợp note và con người quanh một organization. | Membership, owner, invitation, team dashboard. |
| 5. Expression | Tạo cảm giác “đây là app của mình” bằng theme. | Theme catalogue, theme request, apply theme. |
| 6. Balance loop | Balance đổi lấy theme; theme làm trải nghiệm thú vị hơn và khuyến khích quay lại app. | Balance, purchase record, owned theme. |

Điểm quan trọng: **balance ở scope chuẩn chỉ phục vụ theme**. Source hiện có các controller giao dịch user-to-user và organization-to-user, nhưng chúng nên được coi là nhánh thử nghiệm chưa được đưa vào product contract, không phải core cần hoàn thiện trước. Nếu giữ balance là internal credit không quy đổi tiền, rủi ro và độ phức tạp thấp hơn nhiều; vẫn phải dùng số nguyên hoặc decimal, lịch sử credit và database transaction để không phát sinh số dư sai.

## 3. Những gì source code hiện đã thể hiện

### Có nền ý tưởng và cấu trúc phù hợp

| Mảng | Bằng chứng | Nhận định |
|---|---|---|
| Authentication | Login/signup, hash password và session regenerate. [AuthenticationController.php](/C:/Users/Admin/Desktop/project1/app/Http/Controllers/AuthenticationController.php:19) | Có điểm khởi đầu cho tài khoản cá nhân. |
| Note-to-do | Note, reply, pivot share và mark-as-done tồn tại ở schema/controller. [Note.php](/C:/Users/Admin/Desktop/project1/app/Models/Note.php:7), [MarkAsDoneController.php](/C:/Users/Admin/Desktop/project1/app/Http/Controllers/MarkAsDoneController.php:13) | Đây là domain core đúng với product thesis. |
| Organization | Tạo/sửa/xoá org, membership, transfer host đã được phác thảo. [OrganizationsController.php](/C:/Users/Admin/Desktop/project1/app/Http/Controllers/OrganizationsController.php:12) | Ý tưởng teamwork không chỉ dừng ở bảng database. |
| Theme/balance | Có bảng theme user/org, style JSON, wallet/transaction và theme request. [create_theme4users_table.php](/C:/Users/Admin/Desktop/project1/database/migrations/2026_08_02_123344_create_theme4users_table.php:14) | Hướng cá nhân hoá/balance đã được nghĩ đến từ data layer. |
| Responsive interaction | Hai static prototype có layout mobile/tablet/desktop và gesture. [index.html](/C:/Users/Admin/Desktop/project1/resources/testing_view/index.html:649) | Đây là điểm mạnh khác biệt của dự án. |

### Công nghệ

| Lớp | Hiện trạng |
|---|---|
| Backend | PHP 8.5.0, Laravel Framework 13.23.0, Eloquent, migration, session auth, mail, queue/cache database. [composer.json](/C:/Users/Admin/Desktop/project1/composer.json:11) |
| Frontend | Vite 8, Tailwind CSS 4.3, plugin Tailwind/Vite; CSS đã khởi tạo theo cú pháp v4. [package.json](/C:/Users/Admin/Desktop/project1/package.json:5), [app.css](/C:/Users/Admin/Desktop/project1/resources/css/app.css:1) |
| Data | SQLite trong môi trường hiện tại; 22 migration, 19 model, 21 controller, 10 mailable. |
| Quality tools | Pest 5, Pint, Laravel Boost, Pail đã có trong dependency. |

## 4. Mức hoàn thiện thực tế

### Theo góc độ sản phẩm

| Hạng mục | Mức hoàn thiện | Vì sao |
|---|---:|---|
| Product direction | 65% | Sau phần làm rõ từ chủ dự án, định vị note-to-do + theme credit + organization khá mạch lạc. |
| UX exploration | 45% | Có prototype cụ thể, responsive và gesture; nhưng chưa vào UI Laravel thật. |
| Core note-to-do implementation | 15% | Có entity/controller nhưng chưa có một luồng end-to-end chạy được. |
| Social & organization | 10% | Có nhiều code phác thảo, chưa có view, invitation và authorization hoàn chỉnh. |
| Balance + theme | 10% | Schema/ý tưởng đã có, nhưng contract balance, catalog, ownership, purchase/apply chưa nối hoàn chỉnh. |
| Quality, security, release | 5% | Test nghiệp vụ, policy, migration ổn định, CI và release process đều chưa có. |
| **Release readiness toàn dự án** | **khoảng 20% (±5%)** | Đã đi xa về khám phá phạm vi; chưa đủ ổn định để gọi là MVP dùng được. |

Con số 20% không phủ nhận ý tưởng. Nó phản ánh phần việc còn lại để biến những khối đã phác thảo thành trải nghiệm người dùng chạy đúng, an toàn và duy trì được.

## 5. Các điểm chặn phải xử lý trước khi demo MVP

### 5.1. Schema và migration chưa là nguồn chân lý

- Môi trường local ghi nhận 10 migration đã chạy và 12 migration pending. Bảng note đã tồn tại nhưng migration note hiện tại lại pending; đây là dấu hiệu migration đã bị đổi tên/nội dung sau khi chạy.
- SQLite hiện có không có các cột balance/theme4_id trong users, dù source migration/controller có dùng chúng. Như vậy code transaction/theme sẽ lỗi nếu chạy theo database hiện tại.
- Migration có các lỗi cần sửa trong một schema thống nhất: users tham chiếu theme4s chưa tồn tại [create_users_table.php](/C:/Users/Admin/Desktop/project1/database/migrations/0001_01_01_000000_create_users_table.php:23); theme4orgs khai báo id hai lần [create_theme4orgs_table.php](/C:/Users/Admin/Desktop/project1/database/migrations/2026_08_02_135247_create_theme4orgs_table.php:14); wallet trỏ foreign key đến tên bảng không tồn tại [create_theme4user_wallets_table.php](/C:/Users/Admin/Desktop/project1/database/migrations/2026_08_02_123447_create_theme4user_wallets_table.php:14).
- Một số model trỏ sai table, ví dụ Organization trỏ Organization thay vì organizations [Organization.php](/C:/Users/Admin/Desktop/project1/app/Models/Organization.php:7) và PivotForNote trỏ PivotForNote thay vì pivot_for_note [PivotForNote.php](/C:/Users/Admin/Desktop/project1/app/Models/PivotForNote.php:7).

**Việc cần làm:** chốt ERD trước, backup database cũ, rồi tạo database development sạch để chạy migration từ đầu. Không dùng migrate fresh lên dữ liệu quan trọng. Khi có người dùng thật thì migration đã chạy phải bất biến, chỉ bổ sung migration mới để sửa.

### 5.2. Chưa có màn hình sản phẩm nối với backend

- Có 44 routes, nhưng static check thấy 17 Blade view bị tham chiếu mà không tồn tại: note, organization, dashboard, transaction và theme request. Route note minh hoạ rõ điều này. [web.php](/C:/Users/Admin/Desktop/project1/routes/web.php:50)
- Route edit note trỏ một method không tồn tại. [web.php](/C:/Users/Admin/Desktop/project1/routes/web.php:66)
- Home hiện vẫn là welcome scaffold Laravel; login/signup cũng mới là form HTML tối giản. [welcome.blade.php](/C:/Users/Admin/Desktop/project1/resources/views/welcome.blade.php:20), [login.blade.php](/C:/Users/Admin/Desktop/project1/resources/views/login.blade.php:9)

**Việc cần làm:** lấy prototype hiện hành làm design reference, sau đó dựng một Blade layout thật với Vite/Tailwind. Không chuyển nguyên file HTML vào app; tách card note, bottom navigation, sidebar và toast thành component để chúng nhận dữ liệu thật.

### 5.3. An toàn tài khoản và quyền truy cập chưa đủ

- Không có auth middleware ở routes, dù nhiều action gọi Auth user trực tiếp. [web.php](/C:/Users/Admin/Desktop/project1/routes/web.php:25)
- Chỉ ThemeRequestController có validation; note, signup, share, organization và balance đều nhận input trực tiếp. [ThemeRequestController.php](/C:/Users/Admin/Desktop/project1/app/Http/Controllers/ThemeRequestController.php:11)
- Luồng reset password tạo token nhưng không kiểm token, expiry hay used trước khi đổi password. [PasswordChangeRequestController.php](/C:/Users/Admin/Desktop/project1/app/Http/Controllers/PasswordChangeRequestController.php:29)

**Việc cần làm:** đưa những route cần đăng nhập vào middleware auth; tạo Form Request cho mỗi form; dùng Policy cho note, organization, membership và theme purchase. Đây là điều kiện trước khi mở share/team cho người dùng khác.

### 5.4. Invitation/email và balance/theme chưa được nối thành flow

- Nhiều mailable đang dùng placeholder view.name hoặc sai constructor/view nên invite không thể gửi ổn định. [user2user_trans_otp.php](/C:/Users/Admin/Desktop/project1/app/Mail/user2user_trans_otp.php:38), [Mail40account.php](/C:/Users/Admin/Desktop/project1/app/Mail/Mail40account.php:23)
- User theme model và vài mailable được import nhưng không tồn tại; theme wallet controller cũng dùng class/table không khớp. [Theme4userWalletController.php](/C:/Users/Admin/Desktop/project1/app/Http/Controllers/Theme4userWalletController.php:28)

**Việc cần làm:** giản lược flow trước: catalog theme → user thấy balance → buy một theme → balance giảm đúng một lần → theme trở thành owned và được apply. Không làm chuyển balance giữa người dùng/tổ chức trong phiên bản đầu nếu đó không thuộc idea chuẩn.

## 6. Thiết kế MVP được khuyến nghị

### Scope không nên vượt quá

| Persona | Job cần làm | Tiêu chí hoàn thành |
|---|---|---|
| Người dùng cá nhân | Ghi nhanh note-to-do và xử lý lần lượt. | Tạo, xem, sửa, done/undone, lọc; hoạt động trên mobile. |
| Người được chia sẻ | Nhận một note và phản hồi/đánh dấu trạng thái của mình. | Link/in-app invitation, quyền chỉ giới hạn note được chia sẻ. |
| Chủ organization | Tạo nhóm nhỏ và mời thành viên. | Tạo org, mời/nhận lời, tạo team note, quản lý role owner/member. |
| Người thích cá nhân hoá | Xem balance và mua/apply theme. | Balance hiển thị chính xác; theme đã mua chỉ áp dụng cho chủ sở hữu. |

### Data model tối thiểu nên chốt

| Entity | Trách nhiệm |
|---|---|
| User | Profile, authentication, balance credit hiện tại. |
| Note | Nội dung, owner, organization nullable, trạng thái/metadata. |
| NoteShare hoặc NoteParticipant | Ai có quyền xem/đáp/review một note, invitation state. |
| NoteCompletion | Trạng thái per user nếu một note được chia sẻ cho nhiều người. |
| Organization | Tên, owner, branding cơ bản. |
| OrganizationMember | organization_id, user_id, role, accepted_at; unique theo cặp. |
| Theme | Catalog theme, price credits, preview/configuration. |
| UserTheme | Theme đã sở hữu/đang apply. |
| BalanceLedger | Tăng/giảm credit có lý do và idempotency key; balance có thể cache ở User. |

BalanceLedger là quan trọng dù credit không phải tiền: nó giải thích được balance đến từ đâu và giúp một lần mua không bị trừ hai lần. Amount nên là integer credit hoặc decimal fixed scale, không dùng float.

## 7. Roadmap phát triển đúng với idea chuẩn

### Phase 0 — Product contract và nền database

1. Viết một trang product brief: Locket là cảm hứng về tốc độ/cảm giác thân mật và card interaction, không tự suy diễn thêm chức năng ngoài brief.
2. Chốt cách balance được cấp hoặc nạp; nếu chưa có quyết định, cho một giá trị seed/demo và ghi rõ đó là demo credit.
3. Vẽ ERD theo bảng ở phần 6; chuẩn hoá tên bảng/cột theo Laravel.
4. Rebuild schema development, model relationships, factory và migration test.

### Phase 1 — Personal note-to-do hoàn chỉnh

1. Auth, logout, dashboard note, create/edit/delete, done/undone, filter.
2. Dựng UI từ prototype: một card tạo note, một card duyệt note, mobile bottom navigation; tất cả dùng data thật.
3. Viết feature test cho guest, owner, validation và status.

**Definition of done:** user mới đăng ký, tạo một note, đánh dấu done, refresh vẫn thấy đúng trạng thái, chạy qua test và dùng tốt trên màn hình mobile.

### Phase 2 — Share và social interaction

1. Invitation an toàn có token, expiry, trạng thái nhận/từ chối.
2. Share/reply với policy rõ ràng và mailable có view thật.
3. Activity tối thiểu: created, shared, replied, completed.

### Phase 3 — Organization teamwork

1. Owner/member roles, create org, invite/accept/remove, leave và transfer ownership.
2. Team note list và dashboard thật, không cần dashboard nhiều biểu đồ ở bản đầu.
3. Test toàn bộ quyền đọc/sửa/xoá theo note cá nhân và note của organization.

### Phase 4 — Balance và theme

1. Catalog vài theme seed sẵn; preview bằng biến Tailwind/CSS hoặc JSON config được validate.
2. Hiển thị balance và giá; transaction database + lock khi mua; ghi BalanceLedger và UserTheme.
3. Apply one active theme; test không mua lại, không âm balance, không dùng theme chưa sở hữu.
4. Theme request chỉ mở lại sau khi catalogue và apply flow đã chạy tốt.

## 8. Tình trạng kiểm chứng

| Kiểm tra đã chạy | Kết quả | Cách hiểu đúng |
|---|---|---|
| Route list | 44 routes đăng ký được. | Không chứng minh view/method/policy đúng. |
| Migration status | 10 ran, 12 pending. | Baseline database chưa ổn định. |
| Pest | 2 tests, 2 assertions pass. | Đây là test scaffold, chưa kiểm core domain. |
| PHP syntax | 160 file parse được. | Không phát hiện lỗi route, table, class hoặc business logic runtime. |
| SQLite read-only | Các bảng nghiệp vụ chưa có data; schema có chênh lệch với source. | Chưa thể kiểm thử flow thật hoặc concurrency. |

## 9. Đánh giá triển vọng

Dự án có một hạt nhân sản phẩm tốt: note-to-do không chỉ là danh sách checkbox mà có nhịp điệu tương tác, kết nối cá nhân và khả năng đi vào teamwork. Sự kết hợp organization với theme credit không phải phần thừa nếu thứ tự làm hợp lý:

1. note-to-do phải hữu ích khi không có theme;
2. organization phải giải quyết nhu cầu hợp tác thật;
3. balance/theme làm tăng sự gắn bó và cá nhân hoá, không thay thế giá trị core.

Với scope được làm rõ hiện tại, khả năng hoàn thành MVP cá nhân + team cơ bản + theme credit demo là **65–75% trong 2–3 tháng part-time tập trung**, nếu dừng mở rộng transaction ngoài scope, làm từng vertical slice và có test cho mỗi flow. Production beta chỉ nên tính sau khi migration, policy, invitation/email, purchase flow và test suite đã ổn định.

## 10. Ghi chú

- Báo cáo này đã thay thế cách diễn giải cũ: balance được hiểu là cơ chế mua theme theo idea chủ dự án, không mặc định là tiền hay ví chuyển khoản.
- Các lỗi kỹ thuật nêu ra là quan sát từ source và runtime local tại thời điểm audit; chúng không phủ nhận chất lượng của ý tưởng.
- Báo cáo chuyên biệt về năng lực và cách cải thiện của người viết nằm tại [developer_growth_report.md](</C:/Users/Admin/Desktop/project1/report/04-08-26 developer_growth_report.md>).
