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
//use App\Mail\user2theme4_trans_verify;
//use App\Models\User2theme4Transaction;

class Theme4userWalletController extends Controller
{
   function user2theme4_transaction_OTP_generator()
    {
        $otp = rand(100000, 999999);
        if(User2theme4Transaction::where('otp', $otp)->exists()){
            return $this->user2theme4_transaction_OTP_generator();
        }
        return $otp;
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
    
        if($user->password == Hash::make($request->password)){
           $transaction = new User2theme4Transaction();
            $transaction->userID = $user->id;
            $transaction->themeID = $themeID;
            $transaction->amount = $theme->price;
            $transaction->status = 'pending';
            $transaction->otp = $this->user2theme4_transaction_OTP_generator();
            $transaction->save();
            Mail::to($user->email)->send(new user2theme4_trans_otp($transaction));
        }else{
            return redirect()->back()->with('error', 'Incorrect password');
        }
        return redirect()->back()->with('success', 'Transaction request sent to your email');
   }

   public function user_buy_theme_verify_otp(Request $request,$otp){
     $user = Auth::user();
     $transaction = User2theme4Transaction::where('otp', $otp)->where('userID', $user->id)->first();
     if(!$transaction){
         return redirect()->back()->with('error', 'Invalid OTP');
     }
     $theme = theme4::where('id', $transaction->themeID)->first();
     $user_wallet = Theme4userWallet::where('userID', $user->id)->first();
     if($user_wallet->balance < $theme->price){
         return redirect()->back()->with('error', 'Insufficient balance');
     }
     $user_wallet->balance -= $theme->price;
     $user_wallet->save();
     $transaction->status = 'completed';
     $transaction->save();
     return redirect()->back()->with('success', 'Theme purchased successfully');
   }
}
