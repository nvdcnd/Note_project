<?php

namespace App\Http\Controllers;

use App\Mail\Theme4org_trans_otp;
use App\Models\Organization;
use App\Models\Theme4org;
use App\Models\Theme4orgTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class Theme4orgWalletController extends Controller
{
    public function Theme4org_otp_generator()
    {
        $otp = rand(100000, 999999);
        $existing = Theme4orgTransaction::query()->where('status', '!=', 'finished')->get();

        foreach ($existing as $transaction) {
            if (Hash::check($otp, $transaction->otp)) {
                return $this->Theme4org_otp_generator();
            }
        }

        return (string) $otp;
    }

    public function Organization_buy_theme(Request $request, $id)
    {
        $theme = Theme4org::query()->find($id);
        $org = Organization::query()->find(Auth::user()->organizationID);

        if (! $theme || ! $org) {
            return redirect()->back()->with('error', 'Invalid theme or organization');
        }

        if ($org->balance < $theme->price) {
            return redirect()->back()->with('error', 'You not have enough balance to buy this theme');
        }

        $request->validate([
            'password' => 'required',
        ]);

        if (Hash::check($request->password, Auth::user()->password) && $org->hostID == Auth::id()) {
            $transaction = new Theme4orgTransaction;
            $transaction->organizationID = $org->id;
            $transaction->themeID = $theme->id;
            $transaction->amount = $theme->price;
            $transaction->status = 'pending';
            $transaction->otp = Hash::make($this->Theme4org_otp_generator());
            $transaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $transaction->save();
            Mail::to(Auth::user()->email)->send(new Theme4org_trans_otp($transaction));
        } else {
            return redirect()->back()->with('error', 'Incorrect password');
        }

        return redirect()->back()->with('success', 'Transaction request sent to your email');
    }

    public function Organization_buy_theme_verify_otp(Request $request, $id)
    {
        $transaction = Theme4orgTransaction::query()->find($id);

        if (! $transaction) {
            return redirect()->back()->with('error', 'Invalid transaction');
        }

        $org = Organization::query()->find($transaction->organizationID);
        if (! $org) {
            return redirect()->back()->with('error', 'Organization not found');
        }

        $theme = Theme4org::query()->find($transaction->themeID);
        if (! $theme) {
            return redirect()->back()->with('error', 'Theme not found');
        }

        if ($org->balance < $theme->price) {
            return redirect()->back()->with('error', 'Insufficient balance');
        }

        $time = Carbon::parse($transaction->expires_at);

        if (! Hash::check($request->passkey, $transaction->otp)) {
            return redirect()->back()->with('error', 'Invalid passkey');
        }

        if (now()->greaterThan($time)) {
            $transaction->delete(null);

            return redirect()->back()->with('error', 'OTP has expired. Please try again');
        }

        $org->balance -= $theme->price;
        $org->save();

        $transaction->status = 'finished';
        $transaction->save();

        return redirect()->back()->with('success', 'Theme purchased successfully');
    }
}
