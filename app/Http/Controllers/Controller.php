<?php

namespace App\Http\Controllers;

/**
 * Lớp cha cho toàn bộ controller.
 *
 * Trước đây lớp này chứa 6 method `*_fetch()` trả JSON. Chúng đã được gỡ vì:
 *  - không route nào trỏ tới (không có cách nào gọi được);
 *  - chúng truy vấn cột `note.user_id` KHÔNG tồn tại (bảng `note` dùng
 *    `creater_id`), nên nếu có ai nối route vào thì sẽ lỗi 500 ngay.
 *
 * Việc phân trang note giờ nằm ở NoteController::home() và
 * OrganizationsController::show() bằng paginate().
 */
abstract class Controller
{
    //
}
