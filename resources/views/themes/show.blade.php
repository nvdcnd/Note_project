@extends('layouts.app')

@section('title', $theme->name.' - Noteket')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7 col-12">
            <div class="card">
                <div class="card-header" style="background-color: var(--nk-yellow);">
                    <h3 style="margin: 0; font-size: 1.8rem;">{{ $theme->name }}</h3>
                </div>
                <div class="card-body" style="background-color: var(--nk-sticky);">
                    <p class="card-text" style="font-size: 1.2rem;">{{ $theme->description }}</p>
                    <div class="d-flex gap-3 flex-wrap mb-3">
                        <span class="badge rounded-pill text-bg-warning" style="font-size: 1.1rem;">{{ number_format($theme->price, 0, ',', '.') }} điểm</span>
                        <span class="badge rounded-pill text-bg-info">Kiểu kéo: {{ $theme->drag_type }}</span>
                    </div>

                    @if ($owned)
                        @if ($isApplied)
                            <div class="alert alert-success">✓ Chủ đề này đang được áp dụng.</div>
                            <form action="{{ route('themes.reset') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">Quay lại giao diện mặc định</button>
                            </form>
                        @else
                            <div class="alert alert-success">✓ Bạn đã sở hữu chủ đề này.</div>
                            <form action="{{ route('themes.apply', $theme->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Áp dụng chủ đề này</button>
                            </form>
                        @endif
                    @else
                        <form action="{{ route('theme.user.buy', $theme->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu xác nhận</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Mua chủ đề ({{ number_format($theme->price, 0, ',', '.') }} điểm)</button>
                        </form>
                    @endif

                    <a href="{{ route('themes.index') }}" class="btn btn-secondary w-100 mt-3">← Quay lại cửa hàng</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content-mobile')
    <div class="card w-100">
        <div class="card-header" style="background-color: var(--nk-yellow);">
            <h3 style="margin: 0; font-size: 1.6rem;">{{ $theme->name }}</h3>
        </div>
        <div class="card-body" style="background-color: var(--nk-sticky);">
            <p class="card-text">{{ $theme->description }}</p>
            <div class="d-flex gap-3 flex-wrap mb-3">
                <span class="badge rounded-pill text-bg-warning">{{ number_format($theme->price, 0, ',', '.') }} điểm</span>
                <span class="badge rounded-pill text-bg-info">Kiểu kéo: {{ $theme->drag_type }}</span>
            </div>

            @if ($owned)
                @if ($isApplied)
                    <div class="alert alert-success">✓ Chủ đề này đang được áp dụng.</div>
                    <form action="{{ route('themes.reset') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">Quay lại giao diện mặc định</button>
                    </form>
                @else
                    <div class="alert alert-success">✓ Bạn đã sở hữu chủ đề này.</div>
                    <form action="{{ route('themes.apply', $theme->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">Áp dụng chủ đề này</button>
                    </form>
                @endif
            @else
                <form action="{{ route('theme.user.buy', $theme->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu xác nhận</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Mua chủ đề ({{ number_format($theme->price, 0, ',', '.') }} điểm)</button>
                </form>
            @endif

            <a href="{{ route('themes.index') }}" class="btn btn-secondary w-100 mt-3">← Quay lại cửa hàng</a>
        </div>
    </div>
@endsection
