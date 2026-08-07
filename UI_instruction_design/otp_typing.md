# OTP/Passkey verify view
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Trang này là trang hiện ra tất cả các function cần xác nhận OTP/Passkey
## 1. Overview
- Trang này chỉ có một main card để nhập OTP/Passkey ở  chính giữa màn hình
### 1.1. Main card
- Mọi thứ trong card này sẽ đều được căn giữa
- Nếu là khung của user (trừ forgot password) thì sẽ theo theme của user đó, còn của organization thì theo theme organization đó.
#### 1.1.1. Body
- Title "Nhập OTP cho <tên của function>"
- Form input OTP (có cho hiện số, width 100%)
- Nút submit (width 100%)
### 1.2. Responsive
- Chỉ cần sửa lại kích thước cho hợp từng loại thiết bị, không cần có những config riêng như những trang khác
### 1.3. Lưu ý
- Trang này không thể scroll
