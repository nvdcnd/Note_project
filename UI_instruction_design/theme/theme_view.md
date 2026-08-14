# Theme list view
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Trang này là trang hiện ra ở "/theme/{id}" và "organization/theme/{id}, cung cấp list các theme của dự án mà bạn có thể mua hoặc áp dụng

## 1. Overview
### 1.1. Topbar
- Kế thừa từ file "../base.md" hoặc "../org/org_list_view.md" (tùy là user hay organization)
- Thêm nút back ở phía trên cùng bên trái
- Thay dropbox bằng search bar (nút submit ở bên trong search bar luôn)
### 1.2. Pop-up chuyển point
#### 1.2.1. Header
- Chỉ có nút đóng (chữ "X") tại góc trên bên phải của card pop-up
#### 1.2.2. Body
- Title "Mua theme" căn giữa
- Container flex box với input password (input width 100%)
- Bên dưới là text thể hiện số point bạn hoặc organization còn lại sau khi chuyển
- Dưới cùng là nút submit.
### 1.3. Theme detail view
- Trình bày tuần tự theo hướng từ trên xuống dưới
#### 1.3.1. Title
- Tên theme (h1)
#### 1.3.2. Carousel ảnh demo
- Ảnh đầu tiên là ảnh bìa
- Các ảnh sau đó sẽ được tự sắp xếp
- Tất cả sẽ tự chạy sang ảnh khác sau 5s
#### 1.3.3. Mô tả
- Mô tả của theme (p)
#### 1.3.4. Nút hành động
- Nếu người dùng đã mua theme này rồi: Nút "Áp dụng" -> bấm vào sẽ áp dụng theme và reload lại cả trang
- Nếu người dùng chưa mua theme này: Nút "Mua theme" -> bấm vào sẽ gọi pop-up chuyển point lên
## 2. Mobile version
### 2.1. Top bar
- Layout như top bar ở overview
- Phần 1 sẽ nằm bên trên và phần 2 nằm bên dưới
### 2.2. Themes detail view
- Kế thừa từ mục overview
### 2.3. Navbar
- Phần Themes sẽ active
- Navbar đã được thiết kế tại mục Mobile -> navbar tại file'../base.md"

## 3. Tablet & Desktop version
### 3.1. Top bar
- Layout như top bar ở overview
- Phần 1 sẽ ở bên trái và phần 2 sẽ ở bên phải
### 3.2. Sidebar
- Phần Themes sẽ active
- Sidebar đã được thiết kế tại mục Tablet & Desktop -> sidebar tại file "../base.md"
### 3.3 Themes detail view
- Kế thừa từ mục overview
