@extends('layouts.app')

@section('title', $theme->name.' - Noteket')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7 col-12">
            <div class="card">
                <div class="card-header" style="background-color: #FACC15;">
                    <h3 style="margin: 0; font-size: 1.8rem;">{{ $theme->name }}</h3>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <p class="card-text" style="font-size: 1.2rem;">{{ $theme->description }}</p>
                    <div class="d-flex gap-3 flex-wrap mb-3">
                        <span class="badge rounded-pill text-bg-warning" style="font-size: 1.1rem;">{{ number_format($theme->price, 0, ',', '.') }} điểm</span>
                        <span class="badge rounded-pill text-bg-info">Kiểu kéo: {{ $theme->drag_type }}</span>
                    </div>

                    @if (! $organization)
                        <div class="alert alert-warning">Hãy chọn tổ chức để mua chủ đề này.</div>
                    @elseif ($owned)
                        <div class="alert alert-success">✓ Tổ chức đã sở hữu chủ đề này.</div>
                    @else
                        <form action="{{ route('theme.org.buy', $theme->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="organizationID" value="{{ $organization->id }}">
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu xác nhận</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Mua cho {{ $organization->name }} ({{ number_format($theme->price, 0, ',', '.') }} điểm)</button>
                        </form>
                    @endif

                    <a href="{{ route('themes.org.index') }}?organizationID={{ $organization?->id ?? '' }}" class="btn btn-secondary w-100 mt-3">← Quay lại cửa hàng</a>
                </div>
            </div>
        </div>
    </div>
@endsection
