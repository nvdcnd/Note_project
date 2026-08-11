@extends('layouts.auth')

@section('title', 'Noteket - Đặt lại mật khẩu')

@section('content')
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg" style="text-align: center; margin-bottom: 20px;">
                <h1 class="card-title">Noteket</h1>
                <p class="card-text">Đặt lại mật khẩu của bạn</p>
            </div>
            <div class="col-lg">
                <form action="{{ route('password.reset', $change_password_request->id) }}" method="POST" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="passkey" class="form-label">Mã xác thực (OTP)</label>
                        <input type="text" class="form-control @error('passkey') is-invalid @enderror" id="passkey" name="passkey" inputmode="numeric" maxlength="6" required autofocus>
                        @error('passkey')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Đặt lại mật khẩu</button>
                    <p class="text-center mt-2">
                        <a href="{{ route('password.forgot') }}" style="text-decoration: underline; color: #000;">Gửi lại mã mới</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
@endsection
