# Organization notes list view
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Trang này là trang '/organization/{id}/', sử dụng để hiện tất cả note trong organization có id được chỉ định trong url mà người dùng đã tham gia hoặc host.

## 1. Overview
### 1.1. Top bar
- Gần như tất cả sẽ được thiết kế giống top bar trong file "../base.md"
- Trừ phần nút back, nút back sẽ được thiết kế từ thiết kế trong file "../view_note.md" (nhưng lần này là back về trang org_list_view)
### 1.2. Create note & Note element & Pin element & Edit note
-  Tất cả những phần này sẽ kế thừa từ file "../home.md"
### 1.3. Lưu ý chung
- Không thể scroll ở trang này

## 2. Mobile version
### 2.1. Navbar
- Kế thừa từ file "org_list_view.md"
### 2.2. Các phần còn lại
- Kế thừa từ file "../home.md" và mục overview của file này

## 3. Tablet & Desktop version
### 3.1. Sidebar
- Kế thừa từ file "org_list_view.md"
### 3.2. Các phần còn lại
- Kế thừa từ file "../home.md" và mục overview của file này
