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
## 3. Theme mặc định
- Phong cách: Skeumorphism, neumorphism, mô phỏng chính xác với chuyển động vật lý của việc sử dụng note giấy thông thường.
- Font chữ: Ưu tiên font viết tay dễ nhìn, hiện thị được nhiều loại ngôn ngữ mà không lỗi
- Màu note: 
    - Note element: Vàng đất nhạt
    - Nếp gấp (Vùng tối): Vàng đậm (gần đen)
    - Nếp gấp (Vùng sáng): Vàng đất nhạt (sáng hơn màu note)
- Nền: 
    - TH1: Nền trắng với chấm bi xám
    - TH2: Nền đen với chấm bi trắng xám
    - TH3: Nền ảnh giả lập board ghim
- Navbar & Sidebar & Slidebar:
    - Màu nền: Tùy chọn sao cho phù hợp với theme
    - Màu chữ: Tùy chọn sao cho có tương phản với nền
    - Màu hover & active: 
        - TH1: Màu nền đậm hơn nền gốc, chữ giữ nguyên màu
        - TH2: Màu nền thành màu chữ gốc và ngược lại
- Các element khác & các trang khác:
    -   Tự phối màu sao cho hợp và thuận mắt người dùng phổ thông nhưng vẫn phải đúng chủ đề của theme
- Icon:
    - Bộ icon phải đồng bộ với nhau (trừ khi active ở các bar)

## 4. Element chung
### 4.1. Topbar
#### 4.1.1. Dropbox
- Chính giữa topbar là một dropbox để người dùng chọn giữa các loại note như: đã xong, chưa xong, tất cả
- Dropbox cần được bo tròn 2 cạnh để trở thành dạng viên thuốc
- Có box shadow hướng xuống dưới với phạm vi và mức độ vừa đủ.

#### 4.1.2. Setting & avatar button
- Ở góc bên phải màn hình sẽ là nút để mở ra slidebar setting
- Nút này sẽ có dạng vòng tròn và bên trong là ảnh đại diện của người dùng (giữa class bên ngoài và avatar có padding vừa phải)
- Có box shadow hướng xuống dưới với phạm vi và mức độ vừa đủ.


#### 4.1.3. Slidebar setting
- Bình thường slidebar này sẽ ẩn đi và chỉ khi click vào button đã nói ở mục 4.1.2 thì mới trượt vào từ bên phải
- slidebar sẽ chiếm 1/2 màn hình khi được hiện và một nửa không có slidebar sẽ là làm mờ màn hình hiện tại.
##### 4.1.3.1. Top
- Phần top sẽ có avatar của người dùng ở dạng 50x50 và hình tròn
- bên cạnh sẽ là username và bên dưới username là email của người dùng
##### 4.1.3.2. Middle
- Phần middle sẽ là nơi mà các liên kết được thể hiện
- Các liên kết sẽ được thể hiện với tên rõ ràng và không có gạch chân, không có màu default của link.
- Các mục ở đây sẽ bao gồm: Profile, Setting, Nạp point (gọi pop-up nạp point), Log out.
- Các element sẽ cách nhau một khoảng vừa đủ
##### 4.1.3.3 End
- Tại chính giữa của phần end sẽ là nút "X" để có thể tắt slidebar
- Nút này sẽ là hình tròn và có viền trắng

#### 4.1.4. Balance view (chỉ hiện ở giao diện desktop & tablet)
- Một nút hình viên thuốc (bấm vào gọi pop-up nạp thêm), bên trong thể hiện số dư điểm của người dùng
- Vị trí đặt bên cạnh (về phía bên trái) của "Setting & Avatar Button"

#### 4.1.5. Pop-up nạp tiền
- Sử dụng pop-up của bootstrap
#### 1.3.1. Header
- Chỉ có nút đóng (chữ "X") tại góc trên bên phải của card pop-up
#### 1.3.2. Body
- Title "Nạp point" căn giữa
- Container flex box với input số point muốn nạp (input width 100%)
- Bên dưới là text thể hiển convert point ra VND (tỉ lệ 1 pts = 1000 VND)
- Dưới cùng là nút submit để JS tập hợp thông tin để mở link mailto (có nội dung) để gửi đến email "hoanghunganh9544@gmail.com" để xin nạp point.
