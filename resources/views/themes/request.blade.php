@extends('layouts.app')

@section('title', 'Noteket - Yêu cầu chủ đề')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6 col-12">
            <div class="card">
                <div class="card-header" style="background-color: #FACC15;">
                    <h3 style="margin: 0; font-size: 1.6rem;">Yêu cầu chủ đề mới</h3>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <form action="{{ route('create_theme_request') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Tên chủ đề</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control bigform" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="style" class="form-label">Phong cách</label>
                            <input type="text" class="form-control" id="style" name="style" value="{{ old('style') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="drag_type" class="form-label">Kiểu kéo</label>
                            <select class="form-select" id="drag_type" name="drag_type" required>
                                <option value="1">Cổ điển</option>
                                <option value="2">Xoay nhẹ</option>
                                <option value="3">Bay</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Giá đề xuất</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" value="{{ old('price') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="catalog_link" class="form-label">Liên kết mẫu (tùy chọn)</label>
                            <input type="url" class="form-control" id="catalog_link" name="catalog_link" value="{{ old('catalog_link') }}">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Gửi yêu cầu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content-mobile')
    <div class="card w-100">
        <div class="card-header" style="background-color: #FACC15;">
            <h3 style="margin: 0; font-size: 1.5rem;">Yêu cầu chủ đề mới</h3>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('create_theme_request') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="nameThemeReqM" class="form-label">Tên chủ đề</label>
                    <input type="text" class="form-control" id="nameThemeReqM" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="mb-3">
                    <label for="descriptionThemeReqM" class="form-label">Mô tả</label>
                    <textarea class="form-control bigform" id="descriptionThemeReqM" name="description" rows="3" required>{{ old('description') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="styleThemeReqM" class="form-label">Phong cách</label>
                    <input type="text" class="form-control" id="styleThemeReqM" name="style" value="{{ old('style') }}" required>
                </div>
                <div class="mb-3">
                    <label for="drag_typeThemeReqM" class="form-label">Kiểu kéo</label>
                    <select class="form-select" id="drag_typeThemeReqM" name="drag_type" required>
                        <option value="1">Cổ điển</option>
                        <option value="2">Xoay nhẹ</option>
                        <option value="3">Bay</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="priceThemeReqM" class="form-label">Giá đề xuất</label>
                    <input type="number" class="form-control" id="priceThemeReqM" name="price" min="0" step="0.01" value="{{ old('price') }}" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Gửi yêu cầu</button>
            </form>
        </div>
    </div>
@endsection
