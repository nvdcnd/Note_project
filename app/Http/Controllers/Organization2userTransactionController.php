<?php

namespace App\Http\Controllers;

use App\Mail\organization2user_trans_otp;
use App\Models\Organization;
use App\Models\Organization2userTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class Organization2userTransactionController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public function create_view($id)
    {
        $organization = Organization::query()->find($id);
        if (! $organization) {
            abort(404);
        }

        if ($organization->hostID !== Auth::id()) {
            abort(403);
        }

        return view('transactions.organization2user.create', compact('organization'));
    }

    public function verify_view($id)
    {
        $transaction = Organization2userTransaction::query()->where('id', $id)->first();
        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Giao dịch không hợp lệ.');
        }

        if (Auth::id() !== $transaction->current_hostID) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền xác nhận giao dịch này.');
        }

        return view('transactions.organization2user.verify', compact('transaction'));
    }

    public function history_view($id)
    {
        $userId = Auth::id();
        $organization = Organization::query()->find($id);

        // The route parameter is the organization id; fall back to the user id
        // when the caller only has their own (user-scoped) history view.
        $organizationId = $organization ? $organization->id : $userId;

        $allTransactions = Organization2userTransaction::query()
            ->where(function ($q) use ($userId, $organizationId) {
                $q->where('organizationID', $organizationId)->orWhere('userID', $userId);
            })
            ->latest()
            ->get();
        $fromTransactions = Organization2userTransaction::query()->where('organizationID', $organizationId)->latest()->get();
        $toTransactions = Organization2userTransaction::query()->where('userID', $userId)->latest()->get();

        return view('transactions.organization2user.history', compact('allTransactions', 'fromTransactions', 'toTransactions', 'organization'));
    }

    public function organization2user_transaction_OTP_generator()
    {
        // OTP chỉ được so với hash của chính giao dịch đó lúc xác nhận, nên
        // không cần unique toàn cục. Vòng quét Hash::check mọi giao dịch chưa
        // finished trước đây chậm dần theo dữ liệu tích lũy (~100ms mỗi hash).
        return (string) random_int(100000, 999999);
    }

    public function organization2user_transaction_create(Request $request, $id)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'recipient_email' => ['required_without:userID', 'nullable', 'email'],
            'userID' => ['required_without:recipient_email', 'nullable', 'integer', 'exists:users,id'],
        ]);

        if (! empty($validated['recipient_email'])) {
            $recipient = User::query()->where('email', strtolower(trim($validated['recipient_email'])))->first();
            if (! $recipient) {
                return redirect()->back()->with('error', 'Không tìm thấy tài khoản với email đó.');
            }
            $validated['userID'] = $recipient->id;
        }

        $organization = Organization::query()->find($id);
        if (! $organization) {
            return redirect()->back()->with('error', 'Không tìm thấy tổ chức.');
        }

        if (Auth::id() !== $organization->hostID) {
            return redirect()->back()->with('error', 'Chỉ chủ sở hữu tổ chức mới có thể chuyển điểm.');
        }

        if (! Hash::check($validated['password'], Auth::user()->password)) {
            return redirect()->back()->with('error', 'Mật khẩu không đúng.');
        }

        $otp = $this->organization2user_transaction_OTP_generator();

        $transaction = new Organization2userTransaction;
        $transaction->organizationID = $organization->id;
        $transaction->userID = $validated['userID'];
        $transaction->amount = $validated['amount'];
        $transaction->current_hostID = Auth::id();
        $transaction->status = Organization2userTransaction::STATUS_PENDING;
        $transaction->otp = Hash::make($otp);
        $transaction->expires_at = now()->addMinutes(10);
        $transaction->attempts = 0;
        $transaction->save();

        // Gửi OTP hỏng thì giao dịch thành mồ côi (không có mã để xác nhận,
        // không có nút gửi lại) — xóa giao dịch và báo người dùng thử lại.
        try {
            Mail::to(Auth::user()->email)->send(new organization2user_trans_otp($transaction, $otp));
        } catch (Throwable $e) {
            $transaction->delete();
            Log::error('OTP mail failed (organization2user): '.$e->getMessage());

            return redirect()->back()->with('error', 'Không gửi được email chứa mã OTP. Vui lòng thử lại.');
        }

        return redirect()->route('organization2user_transaction_verify_view', $transaction->id)->with('success', 'Mã OTP đã được gửi tới email của bạn.');
    }

    public function organization2user_transaction_verify(Request $request, $id)
    {
        $validated = $request->validate([
            'passkey' => ['required', 'string'],
        ]);

        /** @var Organization2userTransaction|null $transaction */
        $transaction = Organization2userTransaction::query()
            ->where('id', $id)
            ->where('status', Organization2userTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Giao dịch không hợp lệ.');
        }

        if (Auth::id() !== $transaction->current_hostID) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền xác nhận giao dịch này.');
        }

        $passkey = $validated['passkey'];

        return DB::transaction(function () use ($passkey, $transaction) {
            $organization = Organization::query()->lockForUpdate()->find($transaction->organizationID);
            $targetUser = User::query()->lockForUpdate()->find($transaction->userID);

            if (! $organization) {
                $transaction->update(['status' => Organization2userTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Không tìm thấy tổ chức.');
            }

            if (! $targetUser) {
                $transaction->update(['status' => Organization2userTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Không tìm thấy người nhận.');
            }

            if ($transaction->attempts >= self::MAX_ATTEMPTS) {
                $transaction->update(['status' => Organization2userTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Nhập sai quá nhiều lần. Giao dịch đã bị hủy.');
            }

            if (now()->greaterThan($transaction->expires_at)) {
                $transaction->update(['status' => Organization2userTransaction::STATUS_EXPIRED]);

                return redirect()->route('organization2user_transaction_history_view', $organization->id)->with('error', 'Giao dịch đã hết hạn. Vui lòng tạo giao dịch mới.');
            }

            if (! Hash::check($passkey, $transaction->otp)) {
                Organization2userTransaction::whereKey($transaction->id)->increment('attempts');
                $transaction->refresh();

                return redirect()->route('organization2user_transaction_verify_view', $transaction->id)
                    ->with('error', 'Mã OTP không đúng. '.($transaction->attempts).' lần nhập sai trên tổng số '.self::MAX_ATTEMPTS);
            }

            if ($organization->balance < $transaction->amount) {
                return redirect()->route('organization', $organization->id)->with('error', 'Số dư của tổ chức không đủ.');
            }

            Organization::whereKey($organization->id)->decrement('balance', $transaction->amount);
            User::whereKey($targetUser->id)->increment('balance', $transaction->amount);

            $transaction->update(['status' => Organization2userTransaction::STATUS_FINISHED]);

            return redirect()->route('organization2user_transaction_history_view', $organization->id)->with('success', 'Giao dịch thành công.');
        });
    }

    public function organization2user_transaction_cancel(Request $request, $id)
    {
        $transaction = Organization2userTransaction::query()
            ->where('id', $id)
            ->where('status', Organization2userTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('organization2user_transaction_history_view', Auth::id())->with('error', 'Không tìm thấy giao dịch, hoặc giao dịch đã được xử lý.');
        }

        $organization = Organization::query()->find($transaction->organizationID);
        if (! $organization || Auth::id() !== $organization->hostID) {
            return redirect()->route('organization2user_transaction_history_view', Auth::id())->with('error', 'Bạn không có quyền hủy giao dịch này.');
        }

        $transaction->update(['status' => Organization2userTransaction::STATUS_CANCELLED]);

        return redirect()->route('organization2user_transaction_history_view', Auth::id())->with('success', 'Đã hủy giao dịch.');
    }
}
