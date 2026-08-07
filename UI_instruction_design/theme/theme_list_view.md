# Theme list view
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Trang này là trang hiện ra ở "/theme/" và "organization/{id}/theme/, cung cấp list các theme của dự án mà bạn có thể mua hoặc áp dụng

## 1. Overview
### 1.1. Topbar
- Kế thừa từ file "../base.md" hoặc "../org/org_list_view.md" (tùy là user hay organization)
- Thêm nút back ở phía trái trên cùng của top bar
- Thay dropbox bằng search bar (nút submit ở bên trong search bar luôn)
### 1.2. Theme card
#### 1.2.1. Header
- Là ảnh bìa của theme được bo cong 2 đầu trên và width 100% và height đến 1 nửa kích thước của thẻ
#### 1.2.2. Body
- Id và tên của theme.
- Vd: "1 - Hello"
- Dạng sử dụng: h3
#### 1.2.3. Footer
- Nút bên trái: Xem thêm
- Nút bên phải:
    - Nếu người dùng đã mua theme này rồi: Nút "Áp dụng" -> bấm vào sẽ áp dụng theme và reload lại cả trang
    - Nếu người dùng chưa mua theme này: Nút "Mua theme" -> bấm vào sẽ gọi pop-up chuyển point lên
### 1.3. Pop-up chuyển point
#### 1.2.1. Header
- Chỉ có nút đóng (chữ "X") tại góc trên bên phải của card pop-up
#### 1.2.2. Body
- Title "Mua theme" căn giữa
- Container flex box với input password (input width 100%)
- Bên dưới là text thể hiện số point bạn hoặc organization còn lại sau khi chuyển
- Dưới cùng là nút submit.
### 1.3. Pagination
#### 1.3.1. Một page
- Trên một page sẽ có tổng 20 organization được hiển thị
#### 1.3.2. Pagination redirect bar
- Căn chính giữa cho cả section 
- Có 2 nút "<" và ">" ở 2 đầu để next và previous
- Ở giữa 2 nút, là list từ 1 -> n là số trang mà chúng ta có thể chọn (nếu quá dài từ từ số 3 ... n)
## 2. Mobile version
### 2.1. Top bar
- Layout như top bar ở overview
- Phần 1 sẽ nằm bên trên và phần 2 nằm bên dưới
### 2.2. Themes card view
- Hiện thị theo dạng dọc 1 hàng từ trên xuống.
- Hết 20 themes thì đến phần pagination để sang trang
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
### 3.3 Themes card view
- Sẽ chia làm 4 cột trên mỗi hàng, mỗi cột sẽ có một thẻ
- Hết 20 themes thì đến phần pagination để sang trang
