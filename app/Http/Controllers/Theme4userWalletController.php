<?php

namespace App\Http\Controllers;

use App\Mail\user2theme4_trans_otp;
use App\Models\Theme4user;
use App\Models\Theme4userWallet;
use App\Models\User2theme4Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class Theme4userWalletController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public function user2theme4_transaction_OTP_generator()
    {
        do {
            $otp = (string) random_int(100000, 999999);
        } while (User2theme4Transaction::query()->where('status', '!=', 'finished')->pluck('otp')->contains(
            fn ($hash) => Hash::check($otp, $hash)
        ));

        return $otp;
    }

    public function user_buy_theme(Request $request, $themeID)
    {
        $user = Auth::user();
        $theme = Theme4user::query()->find($themeID);

        if (! $user) {
            return redirect()->back()->with('error', 'You are not logged in');
        }

        if (! $theme) {
            return redirect()->back()->with('error', 'Theme not found');
        }

        // Prevent buying a theme you already own (BE-9 / E15).
        $alreadyOwned = Theme4userWallet::query()
            ->where('userID', $user->id)
            ->where('theme4ID', $theme->id)
            ->exists();

        if ($alreadyOwned) {
            return redirect()->back()->with('error', 'You already own this theme');
        }

        if ($user->balance < $theme->price) {
            return redirect()->back()->with('error', 'You do not have enough balance to buy this theme');
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, $user->password)) {
            return redirect()->back()->with('error', 'Incorrect password');
        }

        $otp = $this->user2theme4_transaction_OTP_generator();

        $transaction = new User2theme4Transaction;
        $transaction->userID = $user->id;
        $transaction->theme4ID = $theme->id;
        $transaction->amount = $theme->price;
        $transaction->status = User2theme4Transaction::STATUS_PENDING;
        $transaction->otp = Hash::make($otp);
        $transaction->expires_at = now()->addMinutes(10);
        $transaction->attempts = 0;
        $transaction->save();
        Mail::to($user->email)->send(new user2theme4_trans_otp($transaction, $otp));

        return redirect()->back()->with('success', 'Transaction request sent to your email');
    }

    public function user_buy_theme_verify_otp(Request $request, $id)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->back()->with('error', 'You are not logged in');
        }

        $request->validate([
            'passkey' => ['required', 'string'],
        ]);

        return DB::transaction(function () use ($request, $id, $user) {
            /** @var User2theme4Transaction|null $transaction */
            $transaction = User2theme4Transaction::query()
                ->where('id', $id)
                ->where('status', User2theme4Transaction::STATUS_PENDING)
                ->where('userID', $user->id)
                ->first();

            if (! $transaction) {
                return redirect()->back()->with('error', 'Invalid transaction');
            }

            $theme = Theme4user::query()->find($transaction->theme4ID);
            if (! $theme) {
                return redirect()->back()->with('error', 'Theme not found');
            }

            if ($transaction->attempts >= self::MAX_ATTEMPTS) {
                $transaction->update(['status' => User2theme4Transaction::STATUS_FAILED]);

                return redirect()->back()->with('error', 'Too many failed attempts. Transaction cancelled');
            }

            if (now()->greaterThan($transaction->expires_at)) {
                $transaction->update(['status' => User2theme4Transaction::STATUS_EXPIRED]);

                return redirect()->back()->with('error', 'OTP has expired. Please try again');
            }

            if (! Hash::check((string) $request->passkey, $transaction->otp)) {
                $transaction->increment('attempts');

                return redirect()->back()
                    ->with('error', 'Invalid passkey. '.($transaction->attempts).' failed attempt(s) out of '.self::MAX_ATTEMPTS);
            }

            $lockedUser = \App\Models\User::query()->lockForUpdate()->find($user->id);

            if ($lockedUser->balance < $theme->price) {
                return redirect()->back()->with('error', 'Insufficient balance');
            }

            $alreadyOwned = Theme4userWallet::query()
                ->where('userID', $user->id)
                ->where('theme4ID', $theme->id)
                ->exists();

            if (! $alreadyOwned) {
                Theme4userWallet::create([
                    'userID' => $user->id,
                    'theme4ID' => $theme->id,
                ]);
            }

            $lockedUser->decrement('balance', $theme->price);
            $transaction->update(['status' => User2theme4Transaction::STATUS_FINISHED]);

            return redirect()->back()->with('success', 'Theme purchased successfully');
        });
    }
}
