@extends('layouts.app')

@section('title', 'Noteket - Yêu cầu đã gửi')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6 col-12 text-center">
            <div style="font-size: 5rem;">🎉</div>
            <h2>Yêu cầu chủ đề đã được gửi</h2>
            <p class="text-muted">Cảm ơn bạn! Đội ngũ Noteket sẽ xem xét yêu cầu <strong>{{ $theme?->name }}</strong> của bạn.</p>
            <a href="{{ route('themes.index') }}" class="btn btn-primary rounded-pill">← Quay lại cửa hàng</a>
        </div>
    </div>
@endsection

@section('content-mobile')
    <div class="text-center py-5">
        <div style="font-size: 5rem;">🎉</div>
        <h3>Yêu cầu chủ đề đã được gửi</h3>
        <p class="text-muted">Cảm ơn bạn! Đội ngũ Noteket sẽ xem xét yêu cầu <strong>{{ $theme?->name }}</strong> của bạn.</p>
        <a href="{{ route('themes.index') }}" class="btn btn-primary rounded-pill">← Quay lại cửa hàng</a>
    </div>
@endsection
