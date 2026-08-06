# Base element design
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Đây là những quy tắc design cho những phần tử dùng chung trong toàn bộ dự án.

## 1. Mobile
Mobile sẽ là màn hình dành cho những thiết bị có width view dưới 576px.
### 1.1. Navbar
- Bao gồm 4 mục: Home, Organization, Theme, Balance
- Các element bên trong navbar cần được padding một khoảng vừa đủ để trông thuận mắt
- Viền của navbar cần được bo cong vừa phải, và các element bên trong đang có class active cũng cần được bo cong tương ứng.
- Các element active cần có màu background đậm hơn so với màu của navbar.
## 2. Table & Desktop
### 2.1. Sidebar
- Sidebar sẽ padding tầm 15px
- Sidebar sẽ có border-radius và box-shadow vừa phải
- Sidebar sẽ có chiều cao tầm 75% màn hình.
#### 2.1.1. Top
- Ở giữa sẽ là ảnh avatar để bấm vào và hiện ra box menu
- Ảnh avatar hình tròn, 50x50, có viền trắng
#### 2.1.2. Body
- Các mục sẽ trình bày từ trên xuống dưới
- Mỗi mục sẽ có icon ở trên và text ở dưới (tất cả căn giữa)
- Các element active cần có màu background đậm hơn so với màu của navbar.
#### 2.1.3. Footer
- Sẽ có nút thu vào, mở ra (tùy vào trạng thái của bar)
#### 2.1.4. Box menu
- Box menu sẽ hiện ra/biến mất khi người dùng bấm vào avatar tại phần top của bar
- Các mục ở đây sẽ bao gồm: Profile, Setting, Your theme, Log out.
