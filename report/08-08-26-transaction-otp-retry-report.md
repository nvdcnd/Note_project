# Báo cáo: cải thiện luồng xác thực giao dịch để giảm đau đầu cho người dùng

## 1. Vấn đề
Luồng xác thực OTP của giao dịch user-to-user trước đây có thể xóa giao dịch ngay khi passkey sai hoặc không hợp lệ. Hậu quả là người dùng phải tạo lại toàn bộ giao dịch từ đầu, gây cảm giác bị "đứt flow" và làm trải nghiệm rất khó chịu.

## 2. Root cause
Controller [app/Http/Controllers/User2userTransactionController.php](app/Http/Controllers/User2userTransactionController.php) gọi `destroy()` khi passkey sai, khiến transaction bị xóa khỏi hệ thống. Đây là một pattern "fail-fast" không thân thiện với người dùng.

## 3. Concept fix
Đổi sang flow "retry-friendly":
- passkey sai không làm transaction mất đi,
- transaction vẫn giữ trạng thái `pending` để người dùng thử lại,
- giao dịch hết hạn thì cập nhật trạng thái `expired` thay vì xóa bản ghi,
- nếu recipient không tồn tại hoặc balance không đủ thì giữ trạng thái `failed` hoặc redirect về trang verify với message phù hợp.

## 4. Cách fix
- Bỏ việc destroy transaction khi passkey sai.
- Redirect người dùng quay lại trang verify với lỗi rõ ràng.
- Cập nhật trạng thái giao dịch thành `expired`/`failed` khi hợp lệ theo điều kiện.
- Giữ nguyên dữ liệu để hỗ trợ audit và retry sau này.

## 5. Kết quả sau khi fix
- Người dùng có thể nhập lại passkey mà không phải tạo giao dịch mới.
- Hệ thống có thể theo dõi trạng thái giao dịch tốt hơn.
- Flow trở nên bền vững và dễ mở rộng cho các loại giao dịch khác.

## 6. Verification
Đã thêm regression test trong [tests/Feature/UserExperienceImprovementsTest.php](tests/Feature/UserExperienceImprovementsTest.php) và chạy:
- `php artisan test --compact --filter=UserExperienceImprovementsTest`
- Kết quả: 3 tests passed.
