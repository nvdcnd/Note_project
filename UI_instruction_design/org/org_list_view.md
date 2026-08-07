# Organization list view
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Trang này là trang '/organization/all/', sử dụng để hiện tất cả các organization mà người dùng đã tham gia hoặc host
## 1. Overview
### 1.1. Top bar
- Chia ra làm 2 phần ở chính giữa:
    - Phần 1: Dropbox chọn giữa các organization (Các organization đã join hoặc host bởi user hiện tại) -> khi người dùng bấm vào organization đó -> chuyển đến trang note riêng của organization đó.
    - Phần 2: Thanh search để tìm các organization hiện user đã tham gia hoặc host.
- Phần sát bên tay phải vẫn là nút avatar để mở slidebar như trong file "../home.md" đã nói
### 1.2 Card for org
#### 1.2.1. Header
- Là ảnh của tổ chức được bo cong 2 đầu trên và dài đến 1 nửa kích thước của thẻ
#### 1.2.2. Body
- Id và tên của organization.
- Vd: "1 - Hello"
- Dạng sử dụng: h3
#### 1.2.3. Footer
- Nếu user là host:
    - Nút bên trái: Note view (redirect đến trang note của tổ chức đó)
    - Nút bên phải: Dashboard view (redirect đến dashboard view của tổ chức đó)
- Nếu user ko phải là host:
    -  Chỉ có một nút Note view
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
### 2.2. Organizations card view
- Hiện thị theo dạng dọc 1 hàng từ trên xuống.
- Hết 20 organization thì đến phần pagination để sang trang
### 2.3. Navbar
- Phần organization sẽ active
- Navbar đã được thiết kế tại mục Mobile -> navbar tại file'../base.md"

## 3. Tablet & Desktop version
### 3.1. Top bar
- Layout như top bar ở overview
- Phần 1 sẽ ở bên trái và phần 2 sẽ ở bên phải
### 3.2. Sidebar
- Phần organization sẽ active
- Sidebar đã được thiết kế tại mục Tablet & Desktop -> sidebar tại file "../base.md"
### 3.3 Organizations card view
- Sẽ chia làm 4 cột trên mỗi hàng, mỗi cột sẽ có một thẻ
- Hết 20 organization thì đến phần pagination để sang trang