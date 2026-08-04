# Báo cáo năng lực và lộ trình phát triển cho người viết code

**Ngày đánh giá:** 04-08-2026  
**Nguồn đánh giá:** mã nguồn, 12 commit Git, prototype UI, kiểm tra runtime chỉ-đọc và product brief do chủ dự án bổ sung.  
**Mục đích:** giúp người viết phát triển nhanh hơn và hoàn thành dự án; không phải xếp hạng con người bằng số dòng code hay suy đoán lượng AI đã dùng.

## 1. Kết luận ngắn

Người viết có dấu hiệu rõ của một **junior developer có tư duy sản phẩm tốt và sức xây dựng lớn**, đặc biệt ở khả năng tự nghĩ ra domain rộng hơn một CRUD app thông thường:

- nhìn note-to-do như một trải nghiệm tương tác, không chỉ là checklist;
- có trực giác UI/mobile tốt qua prototype card/gesture;
- nghĩ được tới share, reply, membership, ownership, theme và balance;
- tự dựng một project Laravel nhiều lớp trong thời gian ngắn.

Khoảng cách lớn nhất để lên trình độ middle không phải là học thêm nhiều framework. Đó là khả năng **đưa một feature từ ý tưởng đến trạng thái hoàn chỉnh, an toàn và kiểm chứng được**: schema đúng, route đúng, UI thật, validation, policy, test, migration và commit rõ ràng.

Đánh giá hiện tại phù hợp nhất: **Junior vững về product exploration; đang xây nền để trở thành backend/full-stack developer có thể ship MVP.**

## 2. Những năng lực đã thể hiện

| Năng lực | Mức hiện tại | Dẫn chứng từ dự án | Giá trị thật |
|---|---:|---|---|
| Product sense | 3.5/5 | Note-to-do lấy cảm hứng Locket, organization, theme/balance và prototype gesture. | Không chỉ lập trình theo bảng dữ liệu; biết nghĩ về cảm xúc và retention. |
| UX prototyping | 3.0/5 | Hai phiên bản HTML responsive với desktop/mobile, drawer, swipe và card. [testing2.html](/C:/Users/Admin/Desktop/project1/resources/testing_view/testing2.html:336) | Có thể biến ý tưởng mơ hồ thành thứ nhìn/chạm được. |
| Laravel fundamentals | 2.0/5 | Đã dùng controller, migration, model, auth, hashing, mail, route, factory. [AuthenticationController.php](/C:/Users/Admin/Desktop/project1/app/Http/Controllers/AuthenticationController.php:19) | Đủ nền tảng để học bằng cách hoàn thiện feature thật. |
| Domain exploration | 2.5/5 | Note share/reply/done, membership, host transfer, theme và wallet đã được mô hình hoá. | Nhìn thấy các trạng thái và vai trò khác nhau trong sản phẩm. |
| Git & tiến độ | 2.0/5 | 12 commit liên tiếp trong vài ngày, có sửa bug và thử UI. | Có nhịp làm việc và thử nghiệm thực tế. |
| Database design | 1.5/5 | Có nhiều entity/foreign key nhưng migration/model/schema không đồng bộ. | Đây là điểm học có đòn bẩy lớn nhất. |
| Security & authorization | 1.0/5 | Thiếu auth middleware, validation/policy, reset-token check. | Chưa nên mở tính năng social/team cho user thật. |
| Testing & reliability | 1.0/5 | Chỉ 2 test scaffold; không có test core domain. [Pest.php](/C:/Users/Admin/Desktop/project1/tests/Pest.php:17) | Đây là nguyên nhân khiến lỗi integration tích luỹ nhanh. |

Các con số chỉ là bản đồ ưu tiên học, không phải năng lực cố định. Product sense và khả năng tự làm project từ đầu là lợi thế hiếm hơn nhiều so với việc học syntax.

## 3. Những pattern đang cản tốc độ

### 3.1. Mở rộng ngang trước khi hoàn tất dọc

Project có nhiều controller, migration và flow: note, organization, mail, transfer host, wallet, transaction, theme. Nhưng chưa có một luồng note cá nhân hoàn chỉnh từ view đến database và test.

Ví dụ route gọi view note không tồn tại [web.php](/C:/Users/Admin/Desktop/project1/routes/web.php:50); edit note được route nhưng method không tồn tại [web.php](/C:/Users/Admin/Desktop/project1/routes/web.php:66). Đây không phải thiếu khả năng viết code; nó là dấu hiệu thiếu “definition of done”.

**Cách sửa thói quen:** cấm bản thân tạo file cho feature mới nếu feature đang làm chưa qua 7 ô: migration, model, route, controller, view, authorization/validation, test.

### 3.2. Thay đổi migration đã chạy và không kiểm schema thực

Migration status hiện có 10 ran/12 pending, trong khi database có table note. Database actual cũng thiếu cột mà source đang dùng. Điều này thường xảy ra khi sửa file migration cũ thay vì tạo migration mới, hoặc không test database rỗng.

**Cách sửa thói quen:** sau mỗi thay đổi database, chạy một database development rỗng; nhìn lại migration status; dùng factory/test để xác nhận bảng và relationship hoạt động. Một migration đã dùng chung không được sửa nội dung.

### 3.3. Dựa vào “parse được” thay vì “flow chạy đúng”

160 file PHP lint không lỗi và 2 test pass, nhưng có missing view, missing action, class import không tồn tại, model/table sai và email không khởi tạo đúng. PHP không thể bắt các lỗi integration này.

**Cách sửa thói quen:** khi hoàn thành feature, tự đóng vai user và kiểm qua các trạng thái:

1. guest thử vào;
2. owner làm hành động hợp lệ;
3. người khác thử làm hành động không hợp lệ;
4. input sai;
5. refresh/truy cập lại;
6. test tự động bảo vệ năm trường hợp trên.

### 3.4. Nhầm business intent với implementation chi tiết

Code hiện có user-to-user/organization transaction, trong khi product brief chuẩn là balance để mua theme. Đây là ví dụ tốt cho việc code có thể chạy xa hơn problem cần giải quyết.

**Cách sửa thói quen:** trước khi code, viết ba dòng:

- Ai gặp vấn đề?
- Người đó nhận giá trị gì sau khi bấm nút?
- Tính năng này có bắt buộc cho bản demo một phút không?

Nếu câu cuối là không, đưa vào backlog thay vì controller/migration.

## 4. Lộ trình cải thiện 12 tuần

### Tuần 1–2: Làm chủ một vertical slice Laravel

Mục tiêu duy nhất: personal note-to-do.

- Học và áp dụng chuẩn route group + auth middleware, route model binding, Form Request, Eloquent relationship, migration mới, Policy.
- Làm dashboard note, create, edit, delete, done/undone trên UI Blade thật.
- Viết 8–12 Pest feature test: guest redirect, validation, owner CRUD, user khác forbidden, done state.
- Chạy format và test trước mỗi commit.

**Mốc đạt:** một người mới clone project có thể migrate database rỗng, đăng ký tài khoản, dùng note-to-do trên điện thoại và test xanh.

### Tuần 3–4: Chuyển prototype thành UI sản phẩm

- Tách layout, note card, create form, status filter, mobile navigation thành Blade component.
- Dùng Vite/Tailwind trong project thay vì HTML standalone nạp CSS/JS riêng.
- Chỉ giữ gesture nào có lợi cho accessibility; luôn có button/keyboard fallback cho done/skip.
- Dùng browser/smoke test để kiểm console error và responsive basics.

**Mốc đạt:** prototype không còn là demo tách rời; cùng một card nhận note thật từ database.

### Tuần 5–6: Share và invitation

- Thiết kế NoteShare/Invitation với token, expiry, accepted state.
- Viết policy trước controller: ai share được, ai xem được, ai reply được.
- Tạo mailable thật, Mail fake trong test, queue sau khi flow chạy đúng.
- Ghi activity đơn giản thay vì thêm nhiều dashboard.

**Mốc đạt:** user A chia sẻ một note; user B chấp nhận, đọc/reply/done; user C bị chặn.

### Tuần 7–8: Organization

- Thống nhất OrganizationMember với role owner/member và unique index.
- Thực hiện create/invite/accept/remove/leave/transfer owner theo transaction database.
- Viết một policy hoặc một service cho quyền organization, tránh copy if condition khắp controller.

**Mốc đạt:** team nhỏ dùng chung team note mà không thể đọc/sửa nhầm dữ liệu của team khác.

### Tuần 9–10: Balance và theme đúng scope

- Làm catalog theme read-only, UserTheme, active theme và balance credit.
- Ghi ledger cho mỗi thay đổi balance; dùng integer credit hoặc decimal cố định, tuyệt đối không dùng float.
- Một purchase phải atomic: check balance, trừ balance, create ledger, grant theme trong cùng database transaction.
- Test thiếu balance, mua lặp, simultaneous purchase và apply theme chưa sở hữu.

**Mốc đạt:** balance chỉ dùng để mua theme nhưng luôn giải thích được số dư và không thể bị trừ hai lần.

### Tuần 11–12: Delivery discipline

- Thêm CI chạy test và Pint; ghi README sản phẩm/cài đặt ngắn gọn.
- Dùng commit message mang ý định: feat, fix, test, refactor.
- Tạo checklist pre-demo và beta feedback form.
- Chỉ sau đó quay lại theme request, animation mới hay tính năng social phụ.

## 5. Hệ điều hành phát triển cá nhân

### Checklist trước khi tạo feature mới

| Câu hỏi | Cần có câu trả lời rõ |
|---|---|
| User story | Ai làm gì, vì sao, kết quả nào là thành công? |
| Data | Bảng/cột/relationship/index nào thực sự cần? |
| Permission | Ai được xem, tạo, sửa, xoá? |
| Failure | Input sai, data không tồn tại, token hết hạn, balance không đủ thì sao? |
| UI | Màn hình mobile/desktop nào hiển thị nó? |
| Test | Ít nhất một happy path, validation và forbidden path là gì? |
| Done | Có test xanh, format, manual smoke check và commit rõ ý định chưa? |

Nếu một ô còn trống, feature chưa sẵn sàng để code.

### Vòng lặp mỗi ngày

1. Chọn một user story có thể demo trong 5 phút.
2. Viết test hoặc checklist hành vi trước.
3. Chỉ sửa những file cần cho story.
4. Chạy test; tự thử bằng browser; đọc lỗi thật thay vì đoán.
5. Commit với một ý định.
6. Ghi một dòng: điều gì đã học được, điều gì cần làm sau.

Nhịp này sẽ giảm đáng kể việc phải “sửa nhiều file cùng lúc” và làm Git history trở thành công cụ tư duy.

## 6. Dùng AI/vibe coding có lợi mà không mất quyền kiểm soát

Việc dùng AI không phải vấn đề. Rủi ro nằm ở chỗ nhận một block code khi chưa có contract, test hay khả năng giải thích nó. Quy tắc có ích:

- Nhờ AI giải thích trade-off, viết test case, review diff và đề xuất schema trước khi nhờ viết code.
- Chỉ nhận một feature nhỏ mỗi lần; yêu cầu AI liệt kê file sẽ đổi và lý do.
- Tự đọc từng dòng ở controller, migration và policy trước khi chạy.
- Không để AI sửa migration đã chạy hoặc đưa transaction/bảo mật vào production mà không có test.
- Khi gặp lỗi, yêu cầu giải thích nguyên nhân và cách chứng minh, thay vì chỉ yêu cầu “fix”.

Đây là cách biến AI thành người pair-programming, thay vì biến project thành tập hợp code không ai sở hữu.

## 7. Tiêu chí để tự đánh giá đã lên một bậc

Người viết có thể coi mình đã tiến từ junior product-oriented lên middle-ready khi làm được đồng thời:

- tự thiết kế một schema nhỏ chạy sạch từ database rỗng;
- hoàn thành một feature có route, UI, validation, policy và test;
- giải thích được vì sao từng user có hoặc không có quyền;
- tự tìm lỗi bằng test/log/schema thay vì sửa theo cảm giác;
- chia scope thành các commit nhỏ, có thể review và revert;
- giữ project chạy được ở cuối mỗi ngày.

Khi sáu điều này trở thành thói quen, ý tưởng tốt đang có trong dự án sẽ biến thành lợi thế cạnh tranh thực sự.

## 8. Lời kết

Điểm cần bảo vệ nhất là **gu sản phẩm**: note-to-do gần gũi, card interaction, theme như phần thưởng và organization cho teamwork là một tổ hợp có cá tính. Điểm cần xây thêm là **kỷ luật hoàn thiện**: ít feature hơn trong một thời điểm, nhưng mỗi feature phải chạy đúng từ đầu đến cuối.

Tác giả không cần bỏ ý tưởng lớn. Cần đặt nó lên một con đường nhỏ hơn, được test kỹ hơn và hoàn thành từng nấc. Đó là cách khả năng làm sản phẩm solo phát triển nhanh nhất.
