<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lời mời vào tổ chức</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background-color: #fdf8e3; margin: 0; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #f1e4a8;">
        <div style="background-color: #FACC15; padding: 16px 24px;">
            <h2 style="margin: 0; color: #111827;">Noteket</h2>
        </div>

        <div style="padding: 24px;">
            <h3 style="margin-top: 0;">Bạn được mời vào tổ chức {{ $organization->name }}</h3>
            <p>Xin chào,</p>
            <p>
                Bạn vừa được mời tham gia tổ chức <strong>{{ $organization->name }}</strong> trên Noteket.
                Email này chưa có tài khoản, hãy tạo tài khoản để tham gia:
            </p>

            <p style="text-align: center; margin: 28px 0;">
                <a href="{{ $inviteUrl }}" style="background-color: #FACC15; color: #111827; padding: 12px 28px; border-radius: 999px; text-decoration: none; font-weight: bold; display: inline-block;">
                    Tạo tài khoản và tham gia
                </a>
            </p>

            <p style="font-size: 13px; color: #6b7280;">
                Nếu nút không bấm được, hãy sao chép đường dẫn sau vào trình duyệt:<br>
                <span style="word-break: break-all;">{{ $inviteUrl }}</span>
            </p>
            <p style="font-size: 13px; color: #6b7280;">Lời mời có hiệu lực trong {{ \App\Models\Invitation::TTL_DAYS }} ngày.</p>
            <p style="font-size: 13px; color: #6b7280;">Lời mời này được gửi tới: {{ $recipientEmail }}</p>

            <p style="margin-bottom: 0;">Trân trọng,<br>Đội ngũ Noteket</p>
        </div>
    </div>
</body>
</html>
