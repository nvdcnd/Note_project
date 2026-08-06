# Báo cáo thiết kế UI/UX — Note swipe workspace

**Ngày:** 06-08-2026  
**Phạm vi:** prototype HTML/CSS/JS độc lập; không thay đổi backend hoặc Blade đang vận hành.

## 1. Kết luận thiết kế

Sản phẩm nên có cảm giác **"paper workspace hiện đại"**: nhanh như Tinder khi xử lý việc, nhưng đủ yên tĩnh và đáng tin cậy để dùng làm ghi chú cá nhân hay không gian nhóm. Theme mặc định là **Paper Pop**: nền giấy ngà rất nhạt, thẻ note vàng bơ, mực than, xanh indigo cho hành động chính và xanh lục cho trạng thái hoàn tất. Đây là nền trung tính để các theme trả phí có cá tính mà không làm luồng cơ bản khó dùng.

Không dùng thao tác vuốt như một hành động bí mật hay không thể hoàn tác. Swipe là lối tắt; mọi kết quả đều có nút tương đương, nhãn hướng dẫn ngắn, phản hồi trước khi kích hoạt và toast có Undo.

## 2. Nền tảng thiết kế

### Design tokens mặc định

| Nhóm | Giá trị | Vai trò |
| --- | --- | --- |
| Canvas | `#F8F7F2` | Nền dịu, tạo cảm giác giấy |
| Surface | `#FFFFFF` | thanh điều hướng, modal, bảng |
| Note | `#FFF4A8` | thẻ note cần chú ý |
| Ink | `#20212A` | nội dung chính, tương phản cao |
| Primary | `#4F46E5` | CTA, focus, điều hướng đang chọn |
| Success | `#15803D` | completed/accepted |
| Warning | `#B45309` | pending/cảnh báo |
| Danger | `#B91C1C` | destructive action |

- Font hệ thống `Inter, system-ui`, cỡ body tối thiểu 16px, line-height 1.5.
- Spacing theo nhịp 4/8/12/16/24/32px; bán kính 12px cho control, 20px cho card.
- Bóng note ngắn và có texture điểm chấm nhẹ; không giả lập giấy đến mức ảnh hưởng readability.
- Focus ring indigo 3px, luôn hỗ trợ `prefers-reduced-motion`.

### Nguyên tắc thao tác

1. Card note là đối tượng chính, một card nổi bật tại một thời điểm trên mobile.
2. Kéo ngang chỉ kích hoạt sau ngưỡng 92px: trái = chuyển/lưu để sau; phải = hoàn thành. Trước ngưỡng hiển thị màu và nhãn hành động.
3. Nếu bắt đầu trên input, textarea, button, link hoặc select thì không bắt swipe. Desktop dùng Pointer Events, chuột và touch dùng cùng một engine.
4. Sau swipe: card bay ra, note tiếp theo vào, toast có Undo trong 5 giây. Phím mũi tên trái/phải và nút "Để sau"/"Hoàn thành" là phương án thay thế.
5. Mọi trạng thái quan trọng đều có text và icon; không truyền thông tin chỉ bằng màu.

## 3. Kiến trúc điều hướng và từng trang

| Trang prototype | Mục tiêu | Bố cục và hành động |
| --- | --- | --- |
| `index.html` | Inbox cá nhân | sidebar desktop/bottom nav mobile, filter scope/status, một swipe deck và danh sách queue |
| `note.html` | Đọc/sửa/trao đổi note | card nội dung, complete/skip/share rõ ràng, reply timeline ở cột phụ |
| `organization.html` | Không gian tổ chức | hero thông tin org, tab Notes/Members, board note dùng cùng ngôn ngữ swipe |
| `create-organization.html` | Tạo workspace | form ngắn có live preview, mô tả quyền owner |
| `organization-dashboard.html` | Điều hành org | số liệu, tiến độ, member pending và note gần đây; không swipe trong màn dashboard |
| `theme-store.html` | Khám phá/mua/áp theme | tabs Personal/Organization, card xem trước, trạng thái Owned/Applied/Buy, confirm modal mô phỏng OTP |
| `wallet.html` | Số dư và lịch sử | balance lớn, CTA nạp/chuyển tiền, bảng giao dịch dễ quét |
| `transactions.html` | Chuyển tiền + OTP | flow 2 bước, tổng tiền và trạng thái không mơ hồ |
| `login.html`, `signup.html` | Xác thực | khung đơn cột, không ép thị giác paper-style lên form đăng nhập |
| `theme-request.html` | Yêu cầu theme mới | brief có scope, drag type, ngân sách và trạng thái gửi |

Các route/view khác (pending member, verify transaction, history) dùng shell và component tương ứng của các trang trên: empty state, data table, status badge, confirmation dialog. Khi backend đã có contract ổn định, cần map chính xác route names/field names; không suy diễn endpoint mới từ prototype.

## 4. Responsive

- **Mobile < 640px:** thanh trên gọn, bottom navigation 5 mục, một note toàn chiều rộng, FAB tạo note. Swipe là primary; actions có nhãn dưới card; target chạm tối thiểu 44px.
- **Tablet 640–1023px:** rail icon có label tooltip, deck và queue đặt cạnh nhau khi đủ rộng; dialog rộng tối đa 560px.
- **Desktop ≥ 1024px:** sidebar 248px, header cố định trong app shell, content max 1280px; inbox dùng 2 cột (deck 1fr, queue 320px). Dashboard/table có thể dùng 3–4 cột.
- Không khóa zoom, không cấm scroll toàn trang và không chỉ hỗ trợ hover. Content dài scroll trong flow bình thường.

## 5. Theme system

Theme là một lớp token, không phải tập CSS tùy tiện:

- Theme định nghĩa `--canvas`, `--surface`, `--ink`, `--accent`, `--note`, `--radius`, `--shadow` và `drag_type`.
- User theme chỉ thay đổi personal surfaces; organization theme áp vào shell org sau khi owner xác nhận. Luôn giữ màu semantic success/warning/danger và contrast WCAG AA.
- Store phải cho preview trước, nêu rõ phạm vi (Personal/Organization), giá, trạng thái sở hữu và action xác nhận. Không trừ balance chỉ vì click preview.
- Prototype dùng nút Apply để đổi CSS variables tức thì; đây là mô phỏng client-side, không phải cơ chế persist/mua thật.

## 6. Accessibility, states và chất lượng

- Semantic landmarks, heading theo cấp, label form thực, `aria-live` cho toast, `aria-pressed` cho tab/filter.
- Cung cấp empty/loading/error/success/pending states trên mọi màn chứa dữ liệu.
- Modal trap focus khi tích hợp thật; prototype đóng bằng Escape/click overlay.
- Không render note content bằng `innerHTML` từ dữ liệu người dùng. Khi tích hợp Laravel phải escape bằng Blade mặc định.
- Test UI: mobile 360px, 768px, 1280px; keyboard-only; reduced motion; swipe mouse/touch; undo completion; các trạng thái theme owned/applied/pending.

## 7. Phạm vi prototype đã tạo

Toàn bộ demo nằm trong `resources/view/testing/`, dùng CSS và JavaScript thuần, dữ liệu giả lập trong trình duyệt. Nó không gửi form, không gọi API, không sửa PHP, migration, route hay view Laravel hiện hữu. Các HTML dùng chung `ui.css` và `ui.js` để kiểm tra thống nhất component và responsive trước giai đoạn tích hợp.

