@extends('layouts.app')

@section('title', 'Lịch sử giao dịch - Noteket')

@section('content')
    <h2 class="text-center mb-4">Lịch sử giao dịch Người dùng ↔ Người dùng</h2>

    <table class="table bg-white rounded shadow-sm">
        <thead>
            <tr><th>STT</th><th>Từ</th><th>Đến</th><th>Số tiền</th><th>Trạng thái</th><th>Thời gian</th></tr>
        </thead>
        <tbody>
            @forelse ($allTransactions as $index => $tx)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $tx->from == auth()->id() ? 'Bạn' : '#' . $tx->from }}</td>
                    <td>{{ $tx->to == auth()->id() ? 'Bạn' : '#' . $tx->to }}</td>
                    <td>{{ number_format($tx->amount, 0, ',', '.') }}</td>
                    <td><span class="badge rounded-pill text-bg-{{ $tx->status === 'finished' ? 'success' : ($tx->status === 'pending' ? 'warning' : 'danger') }}">{{ $tx->status }}</span></td>
                    <td>{{ $tx->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có giao dịch nào</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
