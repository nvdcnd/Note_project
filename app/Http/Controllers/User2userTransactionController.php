<?php

namespace App\Http\Controllers;

use App\Mail\user2user_trans_otp;
use App\Models\User;
use App\Models\User2userTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class User2userTransactionController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public function create_view()
    {
        return view('transactions.user2user.create');
    }

    public function verify_view($id)
    {
        $transaction = User2userTransaction::query()->where('id', $id)->first();
        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Giao dịch không hợp lệ.');
        }

        if (Auth::id() !== $transaction->from) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền xác nhận giao dịch này.');
        }

        return view('transactions.user2user.verify', compact('transaction'));
    }

    public function history_view($id)
    {
        $userId = Auth::id();
        $allTransactions = User2userTransaction::query()
            ->where(function ($q) use ($userId) {
                $q->where('from', $userId)->orWhere('to', $userId);
            })
            ->latest()
            ->get();
        $fromTransactions = User2userTransaction::query()->where('from', $userId)->latest()->get();
        $toTransactions = User2userTransaction::query()->where('to', $userId)->latest()->get();

        return view('transactions.user2user.history', compact('allTransactions', 'fromTransactions', 'toTransactions'));
    }

    public function user2user_transaction_OTP_generator()
    {
        // OTP chỉ được so với hash của chính giao dịch đó lúc xác nhận, nên
        // không cần unique toàn cục. Vòng quét Hash::check mọi giao dịch chưa
        // finished trước đây chậm dần theo dữ liệu tích lũy (~100ms mỗi hash).
        return (string) random_int(100000, 999999);
    }

    public function user2user_transaction_create(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'recipient_email' => ['required_without:to', 'nullable', 'email'],
            'to' => ['required_without:recipient_email', 'nullable', 'integer', 'exists:users,id'],
        ]);

        if (! empty($validated['recipient_email'])) {
            $recipient = User::query()->where('email', strtolower(trim($validated['recipient_email'])))->first();
            if (! $recipient) {
                return redirect()->back()->with('error', 'Không tìm thấy tài khoản với email đó.');
            }
            $validated['to'] = $recipient->id;
        }

        $data = $validated;

        if (Auth::id() === (int) $data['to']) {
            return redirect()->back()->with('error', 'Bạn không thể chuyển điểm cho chính mình.');
        }

        if (! Hash::check($data['password'], Auth::user()->password)) {
            return redirect()->back()->with('error', 'Mật khẩu không đúng.');
        }

        $otp = $this->user2user_transaction_OTP_generator();

        $transaction = new User2userTransaction;
        $transaction->from = Auth::id();
        $transaction->to = $data['to'];
        $transaction->amount = $data['amount'];
        $transaction->status = User2userTransaction::STATUS_PENDING;
        $transaction->otp = Hash::make($otp);
        $transaction->expires_at = now()->addMinutes(10);
        $transaction->attempts = 0;
        $transaction->save();

        // Gửi OTP hỏng thì giao dịch thành mồ côi (không có mã để xác nhận,
        // không có nút gửi lại) — xóa giao dịch và báo người dùng thử lại.
        try {
            Mail::to(Auth::user()->email)->send(new user2user_trans_otp($transaction, $otp));
        } catch (Throwable $e) {
            $transaction->delete();
            Log::error('OTP mail failed (user2user): '.$e->getMessage());

            return redirect()->back()->with('error', 'Không gửi được email chứa mã OTP. Vui lòng thử lại.');
        }

        return redirect()->route('user2user_transaction_verify_view', $transaction->id)->with('success', 'Mã OTP đã được gửi tới email của bạn.');
    }

    public function user2user_transaction_verify(Request $request, $id)
    {
        $validated = $request->validate([
            'passkey' => ['required', 'string'],
        ]);

        /** @var User2userTransaction|null $transaction */
        $transaction = User2userTransaction::query()
            ->where('id', $id)
            ->where('status', User2userTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Giao dịch không hợp lệ.');
        }

        if (Auth::id() !== $transaction->from) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền xác nhận giao dịch này.');
        }

        $user = Auth::user();
        $passkey = $validated['passkey'];

        return DB::transaction(function () use ($transaction, $passkey, $user) {
            // Lock the sender + recipient rows to prevent double-spend (BE-6 / E11).
            $lockedSender = User::query()->lockForUpdate()->find($transaction->from);
            $lockedRecipient = User::query()->lockForUpdate()->find($transaction->to);

            if (! $lockedRecipient) {
                $transaction->update(['status' => User2userTransaction::STATUS_FAILED]);

                return redirect()->route('user2user_transaction_verify_view', $transaction->id)->with('error', 'Không tìm thấy người nhận.');
            }

            if ($transaction->attempts >= self::MAX_ATTEMPTS) {
                $transaction->update(['status' => User2userTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Nhập sai quá nhiều lần. Giao dịch đã bị hủy.');
            }

            if (now()->greaterThan($transaction->expires_at)) {
                $transaction->update(['status' => User2userTransaction::STATUS_EXPIRED]);

                return redirect()->route('user2user_transaction_history_view', $user->id)->with('error', 'Giao dịch đã hết hạn. Vui lòng tạo giao dịch mới.');
            }

            if (! Hash::check($passkey, $transaction->otp)) {
                User2userTransaction::whereKey($transaction->id)->increment('attempts');
                $transaction->refresh();

                return redirect()->route('user2user_transaction_verify_view', $transaction->id)
                    ->with('error', 'Mã OTP không đúng. '.($transaction->attempts).' lần nhập sai trên tổng số '.self::MAX_ATTEMPTS);
            }

            if ($lockedSender->balance < $transaction->amount) {
                return redirect()->route('user2user_transaction_verify_view', $transaction->id)->with('error', 'Số dư không đủ.');
            }

            User::whereKey($lockedRecipient->id)->increment('balance', $transaction->amount);
            User::whereKey($lockedSender->id)->decrement('balance', $transaction->amount);

            $transaction->update(['status' => User2userTransaction::STATUS_FINISHED]);

            return redirect()->route('user2user_transaction_history_view', $user->id)->with('success', 'Giao dịch thành công.');
        });
    }

    public function user2user_transaction_cancel(Request $request, $id)
    {
        $transaction = User2userTransaction::query()
            ->where('id', $id)
            ->where('status', User2userTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('user2user_transaction_history_view', Auth::id())->with('error', 'Không tìm thấy giao dịch, hoặc giao dịch đã được xử lý.');
        }

        if (Auth::id() !== $transaction->from) {
            return redirect()->route('user2user_transaction_history_view', Auth::id())->with('error', 'Bạn không có quyền hủy giao dịch này.');
        }

        $transaction->update(['status' => User2userTransaction::STATUS_CANCELLED]);

        return redirect()->route('user2user_transaction_history_view', Auth::id())->with('success', 'Đã hủy giao dịch.');
    }
}
