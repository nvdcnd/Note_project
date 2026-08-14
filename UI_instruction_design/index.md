# Index view
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Trang này là trang hiện ra ở "/" khi bạn chưa đăng nhập vào trong hệ thống
## 1. Overview
- Trang này chỉ có một card ở chính giữa để có thể đăng nhập/ đăng ký
### 1.1. Card
- Có border-radius và box-shadow vừa phải
- Trên top của class có dropbox chọn ngôn ngữ.
## 2. Mobile version
### 2.1. Card
- Bên trong card chỉ có title h1 tên của dự án, bên dưới là description ngắn
- Gạch ngang trắng (căn giữa, 75% width)
- Dưới gạch ngang lần lượt là 2 nút đăng nhập/đăng ký (khi bấm vào sẽ hiện pop-up)
- Bên trong cần phải có padding phù hợp
### 2.2 Pop up sign in/sign up
#### 2.2.1. Overview
- Sử dụng pop-up cho sẵn của bootstrap
- Hai loại pop-up cần có ID khác nhau
#### 2.2.2. Sign in
- Có 3 input: email, password, remeber me (check-box)
- Nút submit ở dưới cùng chính giữa
- form method = post và action là '/login'
- bên góc trên cùng bên phải sẽ có nút 'X' để thoát
#### 2.2.3. Sign up
- Có 4 input: name, email, password, remember me (check box)
- Nút submit ở dưới cùng chính giữa
- form method = post và action là '/signup'
- bên góc trên cùng bên phải sẽ có nút 'X' để thoát
## 3. Tablet & Desktop version
### 3.1. Card
- Card sẽ được chia ra làm 2 nửa
- Card sẽ được làm to hơn về chiều ngang
#### 3.1.1. Bên trái
- Title tên của dự án, description ngắn
- Tất cả được căn giữa
#### 3.1.2. Bên phải
- Title "Log-in"
- Bên dưới title, lần lượt là 3 input từ trên xuống dưới: email, password, remember me (check box)
- Bên dưới là nút submit, bên dưới nút submit là một dòng kẻ ngang bị tách ở giữa với chữ "or" và bên dưới dòng đó là nút Sign-up để mở pop-up sign up
### 3.2. Pop-up sign up
- Chỉ có pop-up cho sign up
- Thiết kế giống hệt pop-up sign up của mobile
## 4. Lưu ý chung
- Tuân thủ các quy tắc thiết kế
- Toàn trang không thể scroll
