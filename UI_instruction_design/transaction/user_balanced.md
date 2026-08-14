# User balance & transaction view
Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này.

Trang này là trang hiện những lịch sử giao dịch và hỗ trợ chuyển point của user
## 1. Overview
### 1.1. Top bar
- Kế thừa từ file "../base.md"
### 1.2. Balance Hero
- Hero này chứa thông tin về số point của user
#### 1.2.1. Content
- "Xin chào <tên user>, hiện bạn đang có" (p)
- Xuống dòng và ghi số point bằng h1
### 1.3. Button group
#### 1.3.1. Nút nạp tiền
- Nằm bên trái của màn hình (Desktop & Tablet) hoặc xếp trên (Mobile)
- Bấm vào sẽ gọi pop-up nạp point riêng của user
#### 1.3.2. Nút chuyển tiền
- Nằm bên phải của màn hình (Desktop & Tablet) hoặc xếp dưới (Mobile)
- Bấm vào sẽ gọi pop-up chuyển point riêng của user
#### 1.3.4. Pop-up nạp point của user
- Sử dụng pop-up của bootstrap
#### 1.3.4.1. Header
- Chỉ có nút đóng (chữ "X") tại góc trên bên phải của card pop-up
#### 1.3.4.2. Body
- Title "Nạp point" căn giữa
- Container flex box với input số point muốn nạp (input width 100%)
- Bên dưới là text thể hiển convert point ra VND (tỉ lệ 1 pts = 1000 VND)
- Dưới cùng là nút submit để JS tập hợp thông tin để mở link mailto (có nội dung) để gửi đến email "hoanghunganh9544@gmail.com" để xin nạp point.
#### 1.3.5. Pop-up chuyển point của user
- Sử dụng pop-up của bootstrap
#### 1.3.5.1. Header
- Chỉ có nút đóng (chữ "X") tại góc trên bên phải của card pop-up
#### 1.3.5.2. Body
- Title "user chuyển point" căn giữa
- Bar chọn giữa chuyển cho user hay organization (dạng slide on/off button nhưng width 100%)
- Container flex box với input To (đến) và input password (input width 100%)
- Input To (đến):
    - Nếu chuyển cho user khác: Nhận email
    - Nếu chuyển cho organization: Nhận ID
- Bên dưới là text thể hiện số point bạn hoặc user còn lại sau khi chuyển
- Dưới cùng là nút submit và để JS xử lý và gọi API phù hợp.
### 1.4. History card
- Sử dụng card của bootstrap
#### 1.4.1. Header
- Chứa "From. User #<ID của user này> - to <email user/ID organization>"
#### 1.4.2. Body
- Số point của giao dịch (nếu giao dịch ra thì thêm dấu "-" đằng trước số point còn nhận thì thêm dấu "+" trước số point)
#### 1.4.1. Footer
- Chứa loại giao dịch - timestamp
- VD1: "Org transaction - 07/08/2026 12:00:00"
- VD2: "Buy Theme - 07/08/2026 12:00:00"
- VD3: "User transaction - 07/08/2026 12:00:00"
### 1.5. Pagination
#### 1.5.1. Một page
- Trên một page sẽ có tổng 20 history được hiển thị
#### 1.5.2. Pagination redirect bar
- Căn chính giữa cho cả section 
- Có 2 nút "<" và ">" ở 2 đầu để next và previous
- Ở giữa 2 nút, là list từ 1 -> n là số trang mà chúng ta có thể chọn (nếu quá dài từ từ số 3 ... n)
### 1.6. Search section
- Container flexbox bao gồm 1 form search: input From, input To, dropbox Type
- Nút submit width 100% ở bên dưới input form
## 2. Mobile version
### 2.1. Top bar
- Kế thừa từ mục overview
### 2.2. Navbar
- Mục Balance sẽ được active
- Kế thừa từ file "../base.md"
### 2.3. View section
- Lần lượt từ trên xuống dưới theo thứ tự: Hero balance -> Button group -> break line -> title "Transaction history" ->  search section -> History list view (mục 2.3.1) -> Pagination
#### 2.3.1. History list view
- Các history card sẽ sắp xếp tuần tự trên xuống dưới (thứ tự mới -> cũ)
- Max 20 history/page
## 3 Mobile version
### 3.1. Top bar
- Kế thừa từ mục overview
### 3.2. Sidebar
- Mục Balance sẽ được active
- Kế thừa từ file "../base.md"
### 2.3. View section
- Lần lượt từ trên xuống dưới theo thứ tự: Hero balance -> Button group -> break line -> title "Transaction history" ->  search section -> History list view (mục 2.3.1) -> Pagination
#### 2.3.1. History list view
- Các history card sẽ được chia ra thành 4 cột, mỗi cột/card (thứ tự sắp xếp trái -> phải, mới -> cũ)
- Max 20 history/page
