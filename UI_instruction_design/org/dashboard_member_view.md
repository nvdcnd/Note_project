# Organization dashboard members view
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Trang này là sẽ là view của các trang có thể giúp cho host organization xem được các member của mình.

## 1. Overview
### 1.1. Top bar
- Kế thừa từ file 'org_list_view.md"
- Nhưng sẽ bổ sung thêm nút thêm thành viên (hình viên thuốc, có dầu cộng và text thêm thành viên, bấm vào hiện pop-up)
### 1.2. Table user
- Sử dụng table của bootstrap
- Table sẽ sử dụng pagination, chỉ hiện 20 user/trang
- Chia thành các cột: ID, tên, email, tình trạng, hành động
- Mục hành động sẽ bao gồm:
    -  Nút Xóa thành viên (thành viên đã join): Xóa thành viên đó đi.
    - Nút hủy lời mời (thành viên pending): Hủy lời mời cho thành viên đó đi.
- Mục hành động - lưu ý:
    - 2 nút đều sẽ gọi ra 1 pop-up để người dùng double check lại quyết định trước khi thực sự làm gì.
### 1.3. Pop-up hành động
- Sử dụng pop-up của bootstrap
#### 1.3.1. Header
- Chỉ có nút đóng (chữ "X") tại góc trên bên phải của card pop-up
#### 1.3.2. Body
- Title "Are you sure?" căn giữa
- Bên dưới là dòng text chỉ ra hành động đó "Hủy lời mời với..." hoặc "Xóa ... khỏi nhóm"
- Bên dưới text sẽ là nút đỏ dài để người dùng bấm xác nhận cho hành động của mình.
### 1.4. Pop-up thêm thành viên
- Sử dụng pop-up của bootstrap
#### 1.3.1. Header
- Chỉ có nút đóng (chữ "X") tại góc trên bên phải của card pop-up
#### 1.3.2. Body
- Title "Thêm thành viên" căn giữa
- Container flex box với input email và nút submit (input width 90%, button 10% với icon dấu cộng)
- Bên dưới là list các email đã được thêm vào phiên thêm thành viên này (sử dụng js để thêm và push lên list hiện thị ở đây)
- Dưới cùng là nút submit để JS gửi tất cả về API
- Pop-up non-scrollable nhưng list các email có thể scroll.

## 2. Mobile version
### 2.1. Navbar
- Kế thừa từ file "org_list_view.md"
### 2.2. View section
- Có table và pop-up như trong phần overview (nhưng sẽ chỉnh kích thước cho phù hợp với màn hình)
- Phần pagination kế thừa từ file "org_list_view.md"
### 2.3. Các element khác
- Kế thừa overview và các file liên quan

## 2. Tablet & Desktop version
### 2.1. Sidebar
- Kế thừa từ file "org_list_view.md"
### 2.2. View section
- Có table và pop-up như trong phần overview (nhưng sẽ chỉnh kích thước cho phù hợp với màn hình)
- Phần pagination kế thừa từ file "org_list_view.md"
### 2.3. Các element khác
- Kế thừa overview và các file liên quan
