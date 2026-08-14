@extends('layouts.app')

@section('title', $note->title.' - Noteket')

@section('content')
    <div class="row justify-content-center note-page-center">
        <div class="col-lg-7 col-12">
            <div class="card note-card note-card-static" data-note-id="{{ $note->id }}">
                <div class="card-header note-header" style="background-color: var(--nk-yellow);">
                    <p>Tạo lúc: {{ $note->created_at?->format('Y-m-d H:i') }}</p>
                </div>
                <div class="card-body rounded" style="background-color: var(--nk-sticky); padding: 24px;">
                    <h3 class="card-title">{{ $note->title }}</h3>
                    <p class="card-text" style="white-space: pre-wrap;">{{ $note->description }}</p>
                    <p class="card-text text-secondary">Bởi: {{ $note->creater?->name ?? 'Không rõ' }}</p>

                    <div class="d-flex gap-2 flex-wrap mt-3">
                        @if ($isDone)
                            <form action="{{ route('undo.done', $note->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary rounded-pill">↺ Hoàn tác</button>
                            </form>
                        @else
                            <form action="{{ route('mark.done', $note->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary rounded-pill">✓ Đánh dấu hoàn thành</button>
                            </form>
                        @endif

                        @if ($isCreator)
                            <form action="{{ route('delete.note', $note->id) }}" method="POST" onsubmit="return confirm('Xóa ghi chú này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger rounded-pill">🗑 Xóa</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Replies --}}
            <div class="card mt-4">
                <div class="card-header" style="background-color: var(--nk-yellow);">
                    <h4 style="margin: 0; font-size: 1.4rem;">💬 Trả lời ({{ $replies->count() }})</h4>
                </div>
                <div class="card-body" style="background-color: var(--nk-sticky);">
                    @forelse ($replies as $reply)
                        <div class="mb-3 p-3 rounded" style="background: rgba(255,255,255,0.6);">
                            <p style="margin: 0; white-space: pre-wrap;">{{ $reply->description }}</p>
                            <small class="text-muted">{{ $reply->user?->name }} — {{ $reply->created_at?->format('d/m/Y H:i') }}</small>
                            @if ($reply->userID === auth()->id())
                                <form action="{{ route('delete.reply', $reply->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0 ms-2">Xóa</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">Chưa có trả lời nào.</p>
                    @endforelse

                    <form action="{{ route('reply.note', $note->id) }}" method="POST" class="mt-3">
                        @csrf
                        <div class="mb-2">
                            <label for="replyContent" class="form-label">Nội dung trả lời</label>
                            <textarea class="form-control bigform" id="replyContent" name="description" rows="2" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Gửi trả lời</button>
                    </form>
                </div>
            </div>

            {{-- Share list --}}
            @if ($isCreator)
                <div class="card mt-4">
                    <div class="card-header" style="background-color: var(--nk-yellow);">
                        <h4 style="margin: 0; font-size: 1.4rem;">🔗 Đã chia sẻ với</h4>
                    </div>
                    <div class="card-body" style="background-color: var(--nk-sticky);">
                        @forelse ($shares as $share)
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: rgba(255,255,255,0.6);">
                                <span>{{ $share->user?->name ?? 'Người dùng #'.$share->shared_with }} ({{ $share->user?->email }})</span>
                                <form action="{{ route('unshare.note', $share->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Thu hồi</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-muted text-center mb-0">Chưa chia sẻ ghi chú này.</p>
                        @endforelse

                        <form action="{{ route('share.note', $note->id) }}" method="POST" class="mt-3">
                            @csrf
                            <label for="shareEmail" class="form-label">Chia sẻ qua email</label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="shareEmail" name="shared_with[]" placeholder="email@example.com">
                                <button type="submit" class="btn btn-primary">Chia sẻ</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('content-mobile')
    <div class="card note-card note-card-static" data-note-id="{{ $note->id }}">
        <div class="card-header note-header" style="background-color: var(--nk-yellow);">
            <p>Tạo lúc: {{ $note->created_at?->format('Y-m-d H:i') }}</p>
        </div>
        <div class="card-body rounded" style="background-color: var(--nk-sticky); padding: 24px;">
            <h3 class="card-title">{{ $note->title }}</h3>
            <p class="card-text" style="white-space: pre-wrap;">{{ $note->description }}</p>
            <p class="card-text text-secondary">Bởi: {{ $note->creater?->name ?? 'Không rõ' }}</p>
            <div class="d-flex gap-2 flex-wrap mt-3">
                @if ($isDone)
                    <form action="{{ route('undo.done', $note->id) }}" method="POST">@csrf<button type="submit" class="btn btn-secondary rounded-pill">↺ Hoàn tác</button></form>
                @else
                    <form action="{{ route('mark.done', $note->id) }}" method="POST">@csrf<button type="submit" class="btn btn-primary rounded-pill">✓ Hoàn thành</button></form>
                @endif
                @if ($isCreator)
                    <form action="{{ route('delete.note', $note->id) }}" method="POST" onsubmit="return confirm('Xóa ghi chú này?');">@csrf @method('DELETE')<button type="submit" class="btn btn-outline-danger rounded-pill">🗑 Xóa</button></form>
                @endif
            </div>
        </div>
    </div>
@endsection
