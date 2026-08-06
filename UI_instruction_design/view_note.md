# View note
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

View note là trang hiển thị chi tiết một note. Route sẽ là "/note/{id}", nên nó sẽ hiện chi tiết nội dung và những reply cho note có id đã cho.

## 1. Mobile
### 1.1. Navbar
- Giống như đã khai báo trong file "base.md" tại cùng thư mục này.
### 1.2. Top bar
- Giữ nguyên Avatar button, slidebar như file "home.md" tại cùng thư mục này.
- Loại bỏ dropbox ở chính giữa
- Góc bên trái sẽ là nút back lại home (đảm bảo khi back lại ra đúng note như trước khi click vào, nếu truy cập link từ ngoài thì trả về home)
### 1.3. Note content view
#### 1.3.1. Thiết kế
- Sẽ giữ nguyên kiểu giấy note nhưng sẽ có thể kéo dài và mở rộng ra
- Mục description và text có thể scroll
- Các mục về pin element box menu giữ nguyên.
#### 1.3.2. Animation & UX
- Khi khéo note đi đến điểm trên tọa độ giả định sẽ giúp người dùng về lại trang 'home'
### 1.4. Reply note view
#### 1.4.1. Thiết kế
- Giống section comment của Facebook và các trang mạng nổi tiếng khác
- Cho phép người dùng edit nếu sai ngay trên dòng comment (Giống facebook, bấm nút reply nhỏ bên dưới -> sửa luôn trên dòng chữ đang 
hiện)
- Form create_reply ở trên đầu như comment section của facebook.
#### 1.4.2. Vị trí
- Bên dưới tờ note đã nói bên trên
- Có thể scroll đuọc
#### 1.4.3. UX & Animation
- Không có animation quá đặc biệt nhưng phải giữ tính liên tục trong trải nghiệm và update được reply và nội dung real-time.

## 2. Tablet & Desktop
