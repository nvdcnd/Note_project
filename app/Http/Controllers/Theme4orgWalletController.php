<?php

namespace App\Http\Controllers;

use App\Mail\Theme4org_trans_otp;
use App\Models\Organization;
use App\Models\Theme4org;
use App\Models\Theme4orgTransaction;
use App\Models\Theme4orgWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class Theme4orgWalletController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public function Theme4org_otp_generator()
    {
        do {
            $otp = (string) random_int(100000, 999999);
        } while (Theme4orgTransaction::query()->where('status', '!=', 'finished')->pluck('otp')->contains(
            fn ($hash) => Hash::check($otp, $hash)
        ));

        return $otp;
    }

    public function Organization_buy_theme(Request $request, $id)
    {
        $theme = Theme4org::query()->find($id);

        // The org is passed explicitly to the route.
        $organization = Organization::query()->find($request->integer('organizationID'));
        if (! $organization) {
            return redirect()->back()->with('error', 'Chủ đề hoặc tổ chức không hợp lệ.');
        }

        // Only the host can spend the organization's balance.
        if ($organization->hostID !== Auth::id()) {
            return redirect()->back()->with('error', 'Chỉ chủ sở hữu tổ chức mới có thể mua chủ đề.');
        }

        if (! $theme) {
            return redirect()->back()->with('error', 'Không tìm thấy chủ đề.');
        }

        $alreadyOwned = Theme4orgWallet::query()
            ->where('organizationID', $organization->id)
            ->where('theme4ID', $theme->id)
            ->exists();

        if ($alreadyOwned) {
            return redirect()->back()->with('error', 'Tổ chức đã sở hữu chủ đề này.');
        }

        if ($organization->balance < $theme->price) {
            return redirect()->back()->with('error', 'Số dư của bạn không đủ để mua chủ đề này.');
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, Auth::user()->password)) {
            return redirect()->back()->with('error', 'Mật khẩu không đúng.');
        }

        $otp = $this->Theme4org_otp_generator();

        $transaction = new Theme4orgTransaction;
        $transaction->organizationID = $organization->id;
        $transaction->themeID = $theme->id;
        $transaction->amount = $theme->price;
        $transaction->status = Theme4orgTransaction::STATUS_PENDING;
        $transaction->current_hostID = Auth::id();
        $transaction->otp = Hash::make($otp);
        $transaction->expires_at = now()->addMinutes(10);
        $transaction->attempts = 0;
        $transaction->save();
        Mail::to(Auth::user()->email)->send(new Theme4org_trans_otp($transaction, $otp));

        return redirect()->back()->with('success', 'Yêu cầu giao dịch đã được gửi tới email của bạn.');
    }

    public function Organization_buy_theme_verify_otp(Request $request, $id)
    {
        $request->validate([
            'passkey' => ['required', 'string'],
        ]);

        return DB::transaction(function () use ($request, $id) {
            /** @var Theme4orgTransaction|null $transaction */
            $transaction = Theme4orgTransaction::query()
                ->where('id', $id)
                ->where('status', Theme4orgTransaction::STATUS_PENDING)
                ->first();

            if (! $transaction) {
                return redirect()->back()->with('error', 'Giao dịch không hợp lệ.');
            }

            $org = Organization::query()->lockForUpdate()->find($transaction->organizationID);
            if (! $org) {
                return redirect()->back()->with('error', 'Không tìm thấy tổ chức.');
            }

            if ($org->hostID !== Auth::id()) {
                return redirect()->back()->with('error', 'Chỉ chủ sở hữu tổ chức mới có thể xác nhận giao dịch này.');
            }

            $theme = Theme4org::query()->find($transaction->themeID);
            if (! $theme) {
                return redirect()->back()->with('error', 'Không tìm thấy chủ đề.');
            }

            if ($transaction->attempts >= self::MAX_ATTEMPTS) {
                $transaction->update(['status' => Theme4orgTransaction::STATUS_FAILED]);

                return redirect()->back()->with('error', 'Nhập sai quá nhiều lần. Giao dịch đã bị hủy.');
            }

            if (now()->greaterThan($transaction->expires_at)) {
                $transaction->update(['status' => Theme4orgTransaction::STATUS_EXPIRED]);

                return redirect()->back()->with('error', 'Mã OTP đã hết hạn. Vui lòng thử lại.');
            }

            if (! Hash::check((string) $request->passkey, $transaction->otp)) {
                Theme4orgTransaction::whereKey($transaction->id)->increment('attempts');
                $transaction->refresh();

                return redirect()->back()
                    ->with('error', 'Mã OTP không đúng. '.($transaction->attempts).' lần nhập sai trên tổng số '.self::MAX_ATTEMPTS);
            }

            if ($org->balance < $theme->price) {
                return redirect()->back()->with('error', 'Số dư không đủ.');
            }

            $alreadyOwned = Theme4orgWallet::query()
                ->where('organizationID', $org->id)
                ->where('theme4ID', $theme->id)
                ->exists();

            if (! $alreadyOwned) {
                Theme4orgWallet::create([
                    'organizationID' => $org->id,
                    'theme4ID' => $theme->id,
                ]);
            }

            Organization::whereKey($org->id)->decrement('balance', $theme->price);
            $transaction->update(['status' => Theme4orgTransaction::STATUS_FINISHED]);

            return redirect()->back()->with('success', 'Đã mua chủ đề thành công.');
        });
    }
}
