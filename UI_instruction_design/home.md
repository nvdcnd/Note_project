# Home page design

Tất cả các AI agent được phân công để thiết kế và code front-end cho dự án này đều phải tuân thủ các hướng dẫn trong các file trong thư mục này

Trang này là trang "/" sau khi người dùng đã đăng nhập.

## 1. Overview
### 1.1. Note
- Note element sẽ có nền vàng, header khác màu và có element hình cái ghim (pin)
#### 1.1.1. Note element
##### 1.1.1.1. Header
- Phần header sẽ khác màu, có một khoảng chiều cao nhất định
- Bên trong sẽ padding vừa phải, và chứa tên người dùng (dạng link) và - ngày tháng năm tạo (dạng dd-mm-yyy HH:MM:SS)
##### 1.1.1.2. Body
- Body sẽ có màu nền là vàng
- Title và description của note sẽ được hiện nhưng nếu dài quá thì thành dạng ... ở đoạn bị cắt
##### 1.1.1.3. Footer
- Bên góc phải sẽ có mép gấp hình tam giác lên và chúng ta sẽ có 2 phân vùng tam giác là vùng tối và vùng sáng
- Vùng tối sẽ là nút view more với hình con mắt giấy được padding giữa và sizing vừa phải

### 1.2. Pop-up shared note
- Sử dụng pop-up của bootstrap
#### 1.3.1. Header
- Chỉ có nút đóng (chữ "X") tại góc trên bên phải của card pop-up
#### 1.3.2. Body
- Title "Shared note" căn giữa
- Container flex box với input email và nút submit (input width 90%, button 10% với icon dấu cộng)
- Bên dưới là list các email đã được thêm vào phiên thêm thành viên này (sử dụng js để thêm và push lên list hiện thị ở đây)
- Dưới cùng là nút submit để JS gửi tất cả về API
- Pop-up non-scrollable nhưng list các email có thể scroll.

## 2. Mobile version
Mobile sẽ là màn hình dành cho những thiết bị có width view dưới 576px.
### 2.1. Navbar
- Bao gồm 4 mục: Home, Organization, Theme, Balance
- Các element bên trong navbar cần được padding một khoảng vừa đủ để trông thuận mắt
- Viền của navbar cần được bo cong vừa phải, và các element bên trong đang có class active cũng cần được bo cong tương ứng.
- Các element active cần có màu background đậm hơn so với màu của navbar.

### 2.2. Topbar
#### 2.2.1. Dropbox
- Chính giữa topbar là một dropbox để người dùng chọn giữa các loại note như: đã xong, chưa xong, tất cả
- Dropbox cần được bo tròn 2 cạnh để trở thành dạng viên thuốc
- Có box shadow hướng xuống dưới với phạm vi và mức độ vừa đủ.

#### 2.2.2. Setting & avatar button
- Ở góc bên phải màn hình sẽ là nút để mở ra slidebar setting
- Nút này sẽ có dạng vòng tròn và bên trong là ảnh đại diện của người dùng (giữa class bên ngoài và avatar có padding vừa phải)
- Có box shadow hướng xuống dưới với phạm vi và mức độ vừa đủ.

#### 2.2.3. Slidebar setting
- Kế thừa từ file "base.md"

### 2.3. Create note
- Lúc đầu pop-up này sẽ ẩn đi. Nhưng khi float button được bấm thì nó sẽ làm cho note element hiện tại (sẽ nói ở mục sau) chạy animation bay đi để lộ ra pop-up create note trông giống một tờ note

#### 2.3.1. Float button
- Float button này sẽ ở góc phải màn hình và cách navbar và góc phải màn hình tầm 20px
- Bên trong sẽ là hình dấu "+" khi mà chưa kích hoạt pop-up. Sau khi kích hoạt pop-up, nút sẽ biến thành chữ "X" và nền màu đỏ
- Nút có dạng hình tròn, 50x50, icon căn chính giữa và viền trắng
- Box shadow cần làm cho object nổi lên nhưng chỉ ở mức vừa phải để tránh ăn vào navbar.

#### 2.3.2. Pop-up
- Pop-up sẽ có dạng giống một tờ note (và đè lên note element được)
##### 2.3.2.1. Header
- Phần header của pop-up sẽ có màu nền khác so với màu nền của note
- padding vừa phải và bên trong sẽ ghi "create note"
##### 2.3.2.2. Body
- Các mục title, description form input sẽ không có viền, không có nền.
- Các mục input sẽ có sẵn dummy value và có thể chỉnh sửa được
- Textarea sẽ không thể kéo thả để resize được
##### 2.3.2.3. Footer
- Bên góc phải sẽ có mép gấp hình tam giác lên và chúng ta sẽ có 2 phân vùng tam giác là vùng tối và vùng sáng
- Vùng tối sẽ là nút submit với hình máy bay giấy được padding giữa và sizing vừa phải
#### 2.3.3. Animation & experience
##### 2.3.3.1. Thao tác kéo thả
- Element chính là là element có thể kéo thả
- Khi kéo thả element đi lên hoặc xuống đều sẽ chạy animation skip đi để quay về note element.
##### 2.3.3.2. Animation
- Animation skip sẽ thuận theo chiều kéo mà chạy đến một điểm trên tục tọa độ giả định để thoát ra khỏi màn hình và sau đó sẽ được set display: none
- Nếu do người dùng bấm nút hủy (nút float màu đỏ chữ "X") thì animation skip sẽ chạy thẳng dọc xuống phía dưới đáy màn hình.
- Khi cho pop-up chạy vào, note đang hiện trên màn hình sẽ dùng animation skip để chạy đi và pop-up này sẽ hiện ra (tức ta phải chèn giữa 2 note)
### 2.4. Note
- Element note sẽ ở chính giữa màn hình của người dùng
#### 2.4.2. Animation & Experience
##### 2.4.2.1. Animation
- Animation skip sẽ thuận theo chiều kéo mà chạy đến một điểm trên tục tọa độ giả định để thoát ra khỏi màn hình và sau đó sẽ được set display: none
- Nếu do người dùng bấm nút create note thì animation skip sẽ chạy thẳng dọc xuống phía chéo trái góc trên màn hình.
##### 2.4.2.2. Experience
- Element note sẽ là element chính và sẽ có thể drag đến một điểm trên tọa độ giả định thì sẽ chạy skip animation.
- Nếu chưa kéo đến thì lại quay về vị trí ban đầu.
- Với mark as done, người dùng double tap vào element sẽ có thể đánh mark as done và undo nó nếu nó đã done.
#### 2.4.3. Pin element
- Element pin (ghim) là một element có hình cái ghim và có thể click vào
- Khi click vào sẽ hiện ra menu dạng vòng tròn xung quanh cái pin đó với các mục khác nhau để người dùng chọn
- Các mục sẽ bao gồm: Edit, Mark as done, Delete, Reply, Share note
## 3. Tablet & Desktop version
### 3.1. Sidebar
- Sidebar là sidebar thiết kế ở base.md
### 3.2. Note & Create note element
- Hai element này cần được tự động thay đổi kích thước để có thể tự fit với nhiều kích thước màn hình khác nhau.
- Hai element này sẽ nằm một cái bên trái và một cái bên phải, cách nhau tầm 20px và cả đều nằm ở giữa màn hình (tính từ sidebar đổ vào)
#### 3.2.1. Create note
##### 3.2.1.1. Floating button
- Không còn floating button ở size màn này nữa
##### 3.2.1.2. Pop-up
- Thiết kế sẽ giống y hệt dạng ở mobile
- Nhưng element này sẽ nằm bên trái của màn hình người dùng
##### 3.2.1.3. Animation & Experience
###### 3.2.1.3.1. Animation
- Các animation sẽ y hệt như dạng mobile
- Khi element này chạy skip animation để skip đi thì 0,5s-1s để cho animation khác chạy vào từ góc chéo trên bên trái màn hình.
###### 3.2.1.3.2. Experience
- Không còn chức năng bấm floating button nữa
#### 3.2.2. Note element
- Note element sẽ nằm bên phải màn hình của người dùng 
- Mọi chức năng, thiết kế, animation giữ nguyên như overview trừ auto skip cho create note
