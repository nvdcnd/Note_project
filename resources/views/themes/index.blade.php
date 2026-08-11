@extends('layouts.app')

@section('title', 'Noteket - Cửa hàng chủ đề')

@section('topbar-actions')
    <a href="{{ route('create_theme_request_view') }}" class="btn btn-primary rounded-pill" style="padding: 10px 16px;">
        <i class="fas fa-plus"></i> Yêu cầu chủ đề
    </a>
@endsection

@section('content')
    <h2 class="text-center mb-4">Cửa hàng chủ đề</h2>

    @if ($themes->isEmpty())
        <div class="text-center py-5">
            <div style="font-size: 4rem;">🎨</div>
            <h3>Chưa có chủ đề nào</h3>
            <a href="{{ route('create_theme_request_view') }}" class="btn btn-primary rounded-pill mt-3">Yêu cầu chủ đề đầu tiên</a>
        </div>
    @endif

    <div class="row g-4">
        @foreach ($themes as $theme)
            <div class="col-lg-4 col-md-6 col-12">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <a href="{{ route('themes.show', $theme->id) }}" style="text-decoration: none; color: #000;">
                            <h5 class="card-title" style="font-size: 1.6rem;">{{ $theme->name }}</h5>
                        </a>
                        <p class="card-text flex-grow-1">{{ Str::limit($theme->description, 100) }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge rounded-pill text-bg-warning" style="font-size: 1rem;">{{ number_format($theme->price, 0, ',', '.') }} điểm</span>
                            @if (in_array($theme->id, $ownedThemeIds))
                                <span class="badge rounded-pill text-bg-success">✓ Đã sở hữu</span>
                            @else
                                <form action="{{ route('theme.user.buy', $theme->id) }}" method="POST">
                                    @csrf
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buyThemeModal" data-theme-id="{{ $theme->id }}" data-theme-name="{{ $theme->name }}">Mua</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('content-mobile')
    @foreach ($themes as $theme)
        <div class="card mb-3 w-100">
            <div class="card-body">
                <a href="{{ route('themes.show', $theme->id) }}" style="text-decoration: none; color: #000;">
                    <h5 class="card-title">{{ $theme->name }}</h5>
                </a>
                <p class="card-text">{{ Str::limit($theme->description, 80) }}</p>
                <span class="badge rounded-pill text-bg-warning">{{ number_format($theme->price, 0, ',', '.') }} điểm</span>
                @if (! in_array($theme->id, $ownedThemeIds))
                    <form action="{{ route('theme.user.buy', $theme->id) }}" method="POST" class="d-inline ms-2">
                        @csrf
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buyThemeModal" data-theme-id="{{ $theme->id }}" data-theme-name="{{ $theme->name }}">Mua</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
@endsection

@section('modals')
    <div class="modal fade" id="buyThemeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mua chủ đề</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body rounded">
                    <form id="buyThemeForm" method="POST">
                        @csrf
                        <p>Nhập mật khẩu để xác nhận mua <strong id="buyThemeName"></strong>.</p>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Xác nhận mua</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('buyThemeModal');
            const form = document.getElementById('buyThemeForm');
            const nameEl = document.getElementById('buyThemeName');
            modal?.addEventListener('show.bs.modal', (e) => {
                const btn = e.relatedTarget;
                form.action = `/theme/user/buy/${btn.dataset.themeId}`;
                nameEl.textContent = btn.dataset.themeName;
            });
        });
    </script>
@endpush
