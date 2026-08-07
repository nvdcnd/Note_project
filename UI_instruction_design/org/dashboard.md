# Organization note view
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
