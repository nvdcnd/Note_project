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
//use App\Mail\Theme4org_trans_verify;
//use App\Models\Theme4orgTransaction;

class Theme4orgWalletController extends Controller
{
    function Theme4org_otp_generator(){
        $otp =  rand(100000, 999999);
        if (Theme4orgTransaction::where('otp', $otp)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->exists()) {
            return Theme4org_otp_generator();
        }else{
            return $otp;
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
            $transaction->otp = $this->Theme4org_otp_generator();
            $transaction->save();
            Mail::to(Auth::user()->email)->send(new Theme4org_trans_otp($transaction));
        }else{
            return redirect()->back()->with('error', 'Incorrect password');
        }
        return redirect()->back()->with('success', 'Transaction request sent to your email');
   }

   public function Organization_buy_theme_otp_verify(Request $request, $id){
        $transaction = Theme4orgTransaction::where('id', $id)->first();
        if($transaction->otp == $request->otp){
            $transaction->status = 'accepted';
            $transaction->save();
            $org = Organization::where('id', $transaction->organizationID)->first();
            $org->themeID = $transaction->themeID;
            $org->save();
            $org->balance -= $transaction->amount;
            $org->save();
            return redirect()->back()->with('success', 'Theme bought successfully');
        }else{
            return redirect()->back()->with('error', 'Incorrect otp');
        }
   }
}
