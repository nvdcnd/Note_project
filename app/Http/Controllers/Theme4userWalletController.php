<?php

namespace App\Http\Controllers;

use App\Mail\user2theme4_trans_otp;
use App\Models\Theme4user;
use App\Models\User2theme4Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

// use App\Mail\user2theme4_trans_verify;
// use App\Models\User2theme4Transaction;

class Theme4userWalletController extends Controller
{
    public function user2theme4_transaction_OTP_generator()
    {
        $otp = rand(100000, 999999);
        $existing = User2theme4Transaction::query()->where('status', '!=', 'finished')->get();

        foreach ($existing as $transaction) {
            if (Hash::check($otp, $transaction->otp)) {
                return $this->user2theme4_transaction_OTP_generator();
            }
        }

        return (string) $otp;
    }

    public function user_buy_theme(Request $request, $themeID)
    {
        $user = Auth::user();
        $theme = Theme4user::query()->find($themeID);
        if (! $user) {
            return redirect()->back()->with('error', 'You are not logged in');
        }
        // $theme = theme4::where('id', $themeID)->first();
        if (! $theme) {
            return redirect()->back()->with('error', 'Theme not found');
        }
        if ($user->balance < $theme->price) {
            return redirect()->back()->with('error', 'You not have enough balance to buy this theme');
        }
        $request->validate([
            'password' => 'required',
        ]);

        if (! Hash::check($request->password, $user->password)) {
            return redirect()->back()->with('error', 'Incorrect password');
        }

        $transaction = new User2theme4Transaction;
        $transaction->userID = $user->id;
        $transaction->themeID = $themeID;
        $transaction->amount = $theme->price;
        $transaction->status = 'pending';
        $transaction->otp = Hash::make($this->user2theme4_transaction_OTP_generator());
        $transaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $transaction->save();
        Mail::to($user->email)->send(new user2theme4_trans_otp($transaction));

        return redirect()->back()->with('success', 'Transaction request sent to your email');
    }

    public function user_buy_theme_verify_otp(Request $request, $id)
    {
        $user = Auth::user();
        /** @var User2theme4Transaction|null $transaction */
        $transaction = User2theme4Transaction::query()->where('id', $id)->first();

        if (! $transaction) {
            return redirect()->back()->with('error', 'Invalid transaction');
        }

        $theme = Theme4user::query()->find($transaction->themeID);
        if (! $theme) {
            return redirect()->back()->with('error', 'Theme not found');
        }

        if ($user->balance < $theme->price) {
            return redirect()->back()->with('error', 'Insufficient balance');
        }

        $time = Carbon::parse($transaction->expires_at);

        if (! Hash::check($request->passkey, $transaction->otp)) {
            return redirect()->back()->with('error', 'Invalid passkey');
        }

        if (now()->greaterThan($time)) {
            User2theme4Transaction::destroy($transaction->id);

            return redirect()->back()->with('error', 'OTP has expired. Please try again');
        }

        $user->balance -= $theme->price;
        $user->save();

        $transaction->status = 'finished';
        $transaction->save();

        return redirect()->back()->with('success', 'Theme purchased successfully');
    }
}
