<?php

namespace App\Http\Controllers;

use App\Models\Theme4orgWallet;
use Illuminate\Http\Request;
use App\Models\Theme4orgTransaction;
use App\Models\Theme4org;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\Theme4org_trans_otp;
use Illuminate\Support\Carbon;
//use App\Mail\Theme4org_trans_verify;
//use App\Models\Theme4orgTransaction;

class Theme4orgWalletController extends Controller
{
    function Theme4org_otp_generator(){
        $otp = rand(100000, 999999);
        $check = Theme4orgTransaction::where('otp', $otp)->exists();
        if($check->status != 'finished' || now()->greaterThan(Carbon::prase($check->expires_at)) || !$check){
            return $this->Theme4org_transaction_OTP_generator();
        } else {
            return (string)$otp;
        }
    }

    public function Organization_buy_theme(Request $request, $id){
        $theme = Theme4org::where('id', $id)->first();
        $org = Organization::where('id', Auth::user()->organizationID)->first();
        if($org->balance < $theme->price){
            return redirect()->back()->with('error', 'You not have enough balance to buy this theme');
        }
        $request->validate([
            'password' => 'required',
        ]);
    
        if(Hash::check($request->password, Auth::user()->password) && $org->hostID == Auth::user()->id){
           $transaction = new Theme4orgTransaction();
            $transaction->organizationID = $org->id;
            $transaction->themeID = $theme->id;
            $transaction->amount = $theme->price;
            $transaction->status = 'pending';
            $transaction->otp = Hash::make($this->Theme4org_otp_generator());
            $transaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $transaction->save();
            Mail::to(Auth::user()->email)->send(new Theme4org_trans_otp($transaction));
        }else{
            return redirect()->back()->with('error', 'Incorrect password');
        }
        return redirect()->back()->with('success', 'Transaction request sent to your email');
   }

   public function Organization_buy_theme_verify_otp(Request $request, $id){
     $transaction = Theme4org_transactions::where('id',$id)->first();
     $org = Organization::find($transaction->organizationID);
     if(!$transaction){
         return redirect()->back()->with('error', 'Invalid OTP');
     }
     $theme = theme4orgs::where('id', $transaction->themeID)->first();
     $user_wallet = Theme4org_wallets::where('organizationID', $user->id)->first();
     if($org->balance < $theme->price){
         return redirect()->back()->with('error', 'Insufficient balance');
     } else {
        $time = Carbon::parse($transaction->expires_at);
        if (Hash::check($request->passkey,$otp)){
            if (now()->greaterThan($time)){
                $transaction->delete();
                return redirect("user_buy_theme_view")->with('error','otp has expired. Please buying it agian');
            } else {
                $org->balance -= $theme->price;
                $org->save();
                $transaction->status = 'finished';
                $transaction->save();
                return redirect()->back()->with('success', 'Theme purchased successfully');
            }
        }
     }
   }
}
