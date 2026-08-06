# Authentication — Login, Signup, Forgot password, Reset password

**Dùng chung:** [base.md](base.md) B, C, D2, F. Không render app sidebar/bottom nav trước xác thực.

## 1. Cây element chung

```text
main.auth-page
├─ a.brand → Home
├─ section.auth-card[aria-labelledby]
│  ├─ header: h1 + mô tả + flash alert
│  ├─ form
│  │  ├─ .field × n (label, input, hint/error)
│  │  ├─ remember checkbox (login/signup theo controller)
│  │  └─ button primary (text + spinner)
│  └─ footer: link sang state auth liên quan
└─ #toast-region
```

Card rộng 440–520px, padding 24px mobile/32px desktop; không có paper texture hoặc note fold để form tài khoản giữ cảm giác tin cậy. Logo link `/`; `main` có một h1 và alert `role=alert` khi server trả flash error.

## 2. Bốn state và thao tác

| State/route | Fields/element con | Trigger → kết quả | Lỗi/continuity |
| --- | --- | --- | --- |
| Login `/login` | email, password + show/hide, remember, Login | Enter/click POST; spinner → redirect Home/intended khi success | giữ email/remember; focus password khi credential lỗi |
| Signup `/signup` | name, email, password + show/hide, remember, Create account | POST → login session → Home | không tự clear form nếu server validation fail |
| Forgot `/forgot-password` | email, Send reset code | POST → cùng card state “Đã gửi nếu email hợp lệ” | message trung tính, che email; Back Login; không tiết lộ account có tồn tại |
| Reset `/reset-password/{id}` | passkey 6 digit, new password, confirm password client-side | POST khi code/confirm hợp lệ → server login → Home | OTP sai/hết hạn focus OTP; CTA quay Forgot; server là nguồn expiry/used |

- Controller hiện validate `remember` ở Login/Signup; checkbox cần name/value gửi được. Nếu điều khoản hiển thị, không làm required khi server không kiểm tra.
- Password toggle là button 44px, `aria-label` đổi “Hiện/Ẩn mật khẩu”, không submit form. OTP cho phép dán sáu số, tự focus ô/field, `autocomplete=one-time-code`.
- Đang submit: disabled toàn bộ submit (không disabled link Back), text `Đang đăng nhập…`/`Đang gửi…`; response error reset enabled và focus lỗi đầu. Chỉ sau response success mới chuyển trang.

## 3. Animation và responsive

| Element | Default motion | Reduced motion | Mobile 320–639 | Tablet/Desktop ≥640 |
| --- | --- | --- | --- | --- |
| auth-card | opacity 0→1 + translateY 8px, 200ms | opacity tức thì | full width, lề 16px, top spacing 32px | width 440–520px, canh giữa ngang |
| field focus/error | focus ring 120ms; error fade 160ms | tức thì | stack 1 cột | giống mobile |
| password reveal | không animate text/value | giống | button cuối input | giống |
| success/reset state | crossfade card body 180ms, giữ card height khi có thể | swap tức thì | không căn dọc cứng khi keyboard | centered trong viewport |

Không dùng auto-redirect countdown. Tab order: brand → fields → remember → submit → footer links. Target ≥44px, contrast và focus theo base.

