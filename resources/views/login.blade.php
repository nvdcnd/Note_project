@extends('layouts.auth')

@section('title', 'Noteket - Đăng nhập')

@section('content')
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg" style="display: flex; flex-direction: column; justify-content: center; align-items: center; margin-bottom: 20px; text-align: center;">
                <h1 class="card-title">Noteket</h1>
                <p class="card-text">Phiên bản ghi chú dán của Locket Widget</p>
            </div>
            <div class="col-lg-1 d-none d-lg-block">
                <div style="border-radius: 20px; height: 90%; width: 3px; background-color: #000; margin: 0 auto;"></div>
            </div>
            <div class="col-lg">
                <form action="{{ route('login.post') }}" method="POST" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                        <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" style="margin-bottom: 10px;">Đăng nhập</button>
                    <p style="margin: 10px 0 0;">Chưa có tài khoản, <a href="{{ route('signup') }}" style="text-decoration: underline;">Đăng ký</a></p>
                    <a href="{{ route('password.forgot') }}" style="text-decoration: underline; color: #000;">Quên mật khẩu?</a>
                </form>
            </div>
        </div>
    </div>
@endsection
