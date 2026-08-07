# Organization dashboard view
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Trang này là trang '/organization/dashboard/{id}', sử dụng để hiện note {note_id} trong organization có id được chỉ định trong url mà người dùng đã tham gia hoặc host.
## 1. Overview
### 1.1. Top bar
- kế thừa từ file 'org_list_view.md"
### 1.2. Card
- Sử dụng mẫu thẻ có sẵn của bootstrap
#### 1.2.1. Header
- Có phần tên của mục mà card đó chịu trách nghiệm hiện thị
- Các mục bao gồm: Undone note, All note, Current member, Pending member, Done note 
#### 1.2.2. Body
- Hiện số lượng với title h3
- Bên cạnh số lượng sẽ là đơn vị (notes cho các card liên quan đến note và peple cho những card liên quan đến người)
- Bến dưới sẽ là link để đến với trang view detail to từ card đó
### 1.3. Hero section
- Phần Hero section sẽ là một card chứa tên của organization để người dùng có thể nhận biết đây là organization nào.

## 2. Mobile version
### 2.1. Navbar
- Kế thừa từ file 'org_list_view.md"
### 2.2. View section
- Bố trí hero ở trên đầu và các card từ trên xuống theo chiều dọc.
- Tất cả đều có thể scroll được
### Các phần còn lại
- kế thừa từ mục overview của file này

## 3. Tablet version
### 2.1. Sidebar
- Kế thừa từ file 'org_list_view.md"
### 2.2. View section
- Hero sẽ ở trên cùng với width 90% và được căn giữa.
- Các card sẽ được chia thành các hàng tương ứng với độ rộng của viewport.
- Tất cả đều có thể scroll được
### Các phần còn lại
- kế thừa từ mục overview của file này
