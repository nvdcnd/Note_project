@extends('layouts.auth')

@section('title', 'Noteket - Quên mật khẩu')

@section('content')
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg" style="text-align: center; margin-bottom: 20px;">
                <h1 class="card-title">Noteket</h1>
                <p class="card-text">Nhập email để nhận mã đặt lại mật khẩu</p>
            </div>
            <div class="col-lg">
                <form action="{{ route('password.forgot.post') }}" method="POST" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Gửi mã đặt lại</button>
                    <p class="text-center mt-2">
                        <a href="{{ route('login') }}" style="text-decoration: underline; color: #000;">← Quay lại đăng nhập</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
@endsection
