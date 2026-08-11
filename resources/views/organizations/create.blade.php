@extends('layouts.app')

@section('title', 'Noteket - Tạo tổ chức')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6 col-12">
            <div class="card">
                <div class="card-header" style="background-color: #FACC15;">
                    <h3 style="margin: 0; font-size: 1.6rem;">Tạo tổ chức mới</h3>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <form action="{{ route('organizations.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Tên tổ chức</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control bigform @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Tạo tổ chức</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
