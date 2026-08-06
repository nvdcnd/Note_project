# Skill: `note-swipe-uiux`

## Mục đích

Thiết kế, prototype, review hoặc tích hợp giao diện cho Note swipe workspace bằng HTML/CSS/JS thuần, trong khi giữ nguyên backend Laravel nếu nhiệm vụ không yêu cầu thay đổi backend.

## Khi kích hoạt

Dùng skill này khi nhiệm vụ liên quan tới note deck/swipe, responsive UI, organization workspace, theme store, wallet/transaction screen, hoặc chuyển prototype thành Blade. Khi sửa Blade/CSS Tailwind, phối hợp thêm skill Tailwind của dự án.

## Quy trình bắt buộc

1. Đọc `AGENTS.md`, report UI mới nhất, hai prototype trong `resources/testing_view/` và route/model liên quan. Xác nhận branch trước khi ghi file.
2. Nếu Laravel Boost/search-docs khả dụng, search docs trước khi thay đổi code Laravel. Với prototype thuần, không cần thêm dependency.
3. Xác định role, data state, action và permission cho từng page từ source hiện hữu. Không tự phát minh endpoint/field/model.
4. Dùng token Paper Pop hoặc token của theme hiện hành; reuse app shell, button, badge, modal, toast và form pattern.
5. Dựng mobile trước, sau đó tablet/desktop. Kiểm bằng 360px, 768px, 1280px.
6. Với swipe, dùng Pointer Events; chỉ start khi target không phải interactive element; lock một pointer; có threshold; `pointercancel` trả card về vị trí cũ; respect reduced motion; có buttons/keyboard/Undo thay thế.
7. Kiểm accessibility và states. Chỉ sau đó mới bàn giao và nêu rõ file đã đổi.

## Contract UI

### Tokens

```css
:root {
  --canvas: #F8F7F2; --surface: #FFFFFF; --ink: #20212A;
  --muted: #676875; --accent: #4F46E5; --note: #FFF4A8;
  --success: #15803D; --warning: #B45309; --danger: #B91C1C;
  --radius: 20px; --shadow: 0 14px 34px rgb(32 33 42 / 12%);
}
```

- Font body tối thiểu 16px; tương phản text/body đạt WCAG AA.
- Button/interactive area tối thiểu 44×44px; `:focus-visible` không bị loại bỏ.
- Theme chỉ đổi token visual. Semantic status colors và hành vi không đổi.

### Swipe contract

- Right: completed; left: deferred/next. Chỉ commit khi độ dịch ngang >= 92px.
- Trong lúc kéo hiển thị nhãn hành động và màu preview; card trở lại khi chưa đạt ngưỡng.
- Nút `[data-swipe-action]` có `data-action="complete|defer"` làm phương án phụ.
- Card phải có `aria-label`, action cập nhật `aria-live`, và toast Undo.
- Không dùng `touch-action: none` trên cả trang/card nội dung có scroll; chỉ áp dụng vùng gesture chuyên biệt nếu thực sự cần.

### Data security

- Tạo node bằng `textContent`, không render title/description/reply người dùng qua `innerHTML`.
- Trong Blade, dùng `{{ }}` mặc định; chỉ `{!! !!}` cho HTML đã được sanitize rõ ràng.
- Prototype không submit thật, không chứa key/token/password mẫu nhạy cảm.

## Checklist page

- Có title, landmark (`header`, `nav`, `main`) và heading một cấp h1.
- Có loading/empty/error/success/pending khi page có dữ liệu/network action.
- Role host/member/guest hiển thị rõ; action không có quyền bị disabled với giải thích hoặc không render theo contract backend.
- Modal có label, Escape và overlay close trong prototype; tích hợp thật cần focus trap/restore focus.
- Không bị overflow tại 360px; bảng biến thành list/card khi cần.
- Không trộn Bulma/Bootstrap/Tailwind trong một page. Ưu tiên CSS shared hiện có.

## Definition of done

1. Chỉ ghi đúng scope được giao và không chạm PHP/backend nếu không được phép.
2. Tất cả page HTML liên kết shared assets đúng relative path.
3. Xác minh cú pháp với browser hoặc checker phù hợp; test swipe bằng chuột và touch emulation.
4. Báo các giả định integration (route, authorization, data field) tách riêng, không biến chúng thành implementation tự phát.

