@extends('layouts.app')

@section('title', 'Cửa hàng chủ đề tổ chức')

@section('content')
    <h2 class="text-center mb-4">Cửa hàng chủ đề tổ chức</h2>

    @if (! $organization)
        <div class="alert alert-warning text-center">
            Bạn chưa chọn tổ chức. Vui lòng mở từ trang tổ chức của bạn.
            <div><a href="{{ route('organizations.index') }}" class="btn btn-primary rounded-pill mt-2">Mở tổ chức của tôi</a></div>
        </div>
    @endif

    <div class="row g-4">
        @foreach ($themes as $theme)
            <div class="col-lg-4 col-md-6 col-12">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <a href="{{ route('themes.org.show', $theme->id) }}?organizationID={{ $organization?->id ?? '' }}" style="text-decoration: none; color: #000;">
                            <h5 class="card-title" style="font-size: 1.6rem;">{{ $theme->name }}</h5>
                        </a>
                        <p class="card-text flex-grow-1">{{ Str::limit($theme->description, 100) }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge rounded-pill text-bg-warning" style="font-size: 1rem;">{{ number_format($theme->price, 0, ',', '.') }} điểm</span>
                            @if ($ownedThemeIds->contains($theme->id))
                                <span class="badge rounded-pill text-bg-success">✓ Đã sở hữu</span>
                            @else
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buyOrgThemeModal" data-theme-id="{{ $theme->id }}" data-theme-name="{{ $theme->name }}">Mua</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('modals')
    <div class="modal fade" id="buyOrgThemeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mua chủ đề cho tổ chức</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body rounded">
                    <form id="buyOrgThemeForm" method="POST">
                        @csrf
                        <input type="hidden" name="organizationID" value="{{ $organization?->id ?? '' }}">
                        <p>Nhập mật khẩu để xác nhận mua <strong id="buyOrgThemeName"></strong> cho tổ chức.</p>
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
            const modal = document.getElementById('buyOrgThemeModal');
            const form = document.getElementById('buyOrgThemeForm');
            const nameEl = document.getElementById('buyOrgThemeName');
            modal?.addEventListener('show.bs.modal', (e) => {
                const btn = e.relatedTarget;
                form.action = `/theme/org/buy/${btn.dataset.themeId}`;
                nameEl.textContent = btn.dataset.themeName;
            });
        });
    </script>
@endpush
