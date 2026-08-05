<?php

namespace App\Http\Controllers;

use App\Models\Theme4userWallet;
use Illuminate\Http\Request;
use App\Models\user;
use App\Models\Theme4user;
use App\Models\User2theme4Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\user2theme4_trans_otp;
use Illuminate\Support\Carbon;
//use App\Mail\user2theme4_trans_verify;
//use App\Models\User2theme4Transaction;

class Theme4userWalletController extends Controller
{
   function user2theme4_transaction_OTP_generator()
    {
        $otp = rand(100000, 999999);
        $check = User2theme4Transaction::where('otp', $otp)->exists();
        if($check->status != 'finished' || now()->greaterThan(Carbon::prase($check->expires_at)) || !$check){
            return $this->user2theme4_transaction_OTP_generator();
        } else {
            return (string)$otp;
        }
        //return $otp;
    }

    public function user_buy_theme(Request $request, $themeID){
        $user = Auth::user();
        $theme = Theme4user::where('id', $themeID)->first();
        if(!$user){
            return redirect()->back()->with('error', 'You are not logged in');
        }
        //$theme = theme4::where('id', $themeID)->first();
        if(!$theme){
            return redirect()->back()->with('error', 'Theme not found');
        }
        if($user->balance < $theme->price){
            return redirect()->back()->with('error', 'You not have enough balance to buy this theme');
        }
        $request->validate([
            'password' => 'required',
        ]);
        if($user->password == Hash::make($request->password)){
           $transaction = new User2theme4Transaction();
            $transaction->userID = $user->id;
            $transaction->themeID = $themeID;
            $transaction->amount = $theme->price;
            $transaction->status = 'pending';
            $transaction->otp = Hash::make($this->user2theme4_transaction_OTP_generator());
            $transaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $transaction->save();
            Mail::to($user->email)->send(new user2theme4_trans_otp($transaction));
        }else{
            return redirect()->back()->with('error', 'Incorrect password');
        }
        return redirect()->back()->with('success', 'Transaction request sent to your email');
   }

   public function user_buy_theme_verify_otp(Request $request, $id){
     $user = Auth::user();
     $transaction = User2theme4_transactions::where('id',$id)->first();
     if(!$transaction){
         return redirect()->back()->with('error', 'Invalid OTP');
     }
     $theme = theme4user::where('id', $transaction->themeID)->first();
     $user_wallet = Theme4user_wallets::where('userID', $user->id)->first();
     if($user->balance < $theme->price){
         return redirect()->back()->with('error', 'Insufficient balance');
     } else {
        $time = Carbon::parse($transaction->expires_at);
        if (Hash::check($request->passkey,$otp)){
            if (now()->greaterThan($time)){
                $transaction->delete();
                return redirect("user_buy_theme_view")->with('error','otp has expired. Please buying it agian');
            } else {
                $user->balance -= $theme->price;
                $user->save();
                $transaction->status = 'finished';
                $transaction->save();
                return redirect()->back()->with('success', 'Theme purchased successfully');
            }
        }
     }
   }
}
