<?php

namespace App\Http\Controllers;

use App\Models\User2organizationTransaction;
use Illuminate\Http\Request;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\user2organization_trans_otp;
use Illuminate\Support\Carbon;

class User2organizationTransactionController extends Controller
{

    public function user2organization_transaction_OTP_generator(){
        $otp =  rand(100000, 999999);
        $check = User2organizationTransaction::where('otp', $otp)->first();
        if($check->status != 'finished' || now()->greaterThan(Carbon::prase($check->expires_at)) || !$check){
            return $this->user2organization_transaction_OTP_generator();
        }else{
            return (string)$otp;
        }
    }

    public function user2organization_transaction_create(Request $request){
        $request->validate([
            'password'=>'required',
            'organizationID'=>'required',
            'amount'=>'required'
        ]);
        $data = $request->all();
        $User2organizationTransaction = new User2organizationTransaction();
        $organization = Organization::where('id', $data['organizationID'])->first();
        if(!$organization){
            return response()->json(['error' => 'Organization not found']);
        } else {
            if(Hash::check($data['password'], Auth::user()->password)){
                $User2organizationTransaction->from = Auth::user()->id;
                $User2organizationTransaction->organizationID = $data['organizationID'];
                $User2organizationTransaction->amount = $data['amount'];
                $User2organizationTransaction->status = 'pending';
                $User2organizationTransaction->current_hostID = $organization->hostID;
                $User2organizationTransaction->otp = Hash::make($this->user2organization_transaction_OTP_generator());
                $User2organizationTransaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $User2organizationTransaction->save();
                Mail::to(Auth::user()->email)->send(new user2organization_trans_otp($User2organizationTransaction));

                return redirect("user2org_transaction_verify_view",$User2organizationTransaction->id);
            }else{
                return response()->json(['error' => 'Invalid password']);
            }
        }
        return response()->json($User2organizationTransaction);
    }

    public function user2organization_transaction_verify(Request $request, $id){
        $transaction = User2organizationTransaction::where('id',$id)->where('status','pending')->first();
        $request->validate([
            'passkey'=>'required'
        ]);
        $data = $request->all();
        $user = Auth::user();
        $organization = Organization::find($transaction->organizationID);
        $passkey = $data['passkey'];
        $time = Carbon::prase($transaction->expires_at);

        if ($transaction && Hash::check($passkey,$transaction)) {
            if (now()->greaterThan($time)) {
                $transaction->delete();
                return redirect()->route('user2org.view',$organization->id)->with('error','the time was expire. Please create the new transaction');
            } else {
                $organization->balance += $transaction->amount;
                $user->balance -= $transaction->amount;
                $transaction->status = 'finished';
                return redirect()->route('user2org_bill.view',$id)->with('success');
            }
        } else {
            $transaction->delete();
            return redirect()->route('user2org.view',$organization->id)->with('error','something have wrong. please try again');
        }
    }

    /*
    public function user2organization_transaction_cancel(Request $request, $id){
        $User2organizationTransaction = User2organizationTransaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if($User2organizationTransaction){
            if(Auth::user()->id == $User2organizationTransaction->from){
                    $User2organizationTransaction->status = 'declined';
                    //$User2organizationTransaction->otp = $this->user2organization_transaction_OTP_generator();
                    $User2organizationTransaction->save();
                    return response()->json($User2organizationTransaction);
                }else{
                    return response()->json(['error' => 'You are not authorized to cancel this transaction']);
                }
        }else{
            return response()->json(['error' => 'Invalid transaction ID']);
        }
    }

    public function user2organization_transaction_verify(Request $request, $id){
        $User2organizationTransaction = User2organizationTransaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if($User2organizationTransaction){
            if(Auth::user()->id == $User2organizationTransaction->from || Auth::user()->id == $User2organizationTransaction->to){
                if($User2organizationTransaction->otp == $request->otp){
                    $User2organizationTransaction->status = 'accepted';
                    $User2organizationTransaction->save();
                    return response()->json($User2organizationTransaction);
                }else{
                    return response()->json(['error' => 'Invalid OTP']);
                }
            }else{
                return response()->json(['error' => 'You are not authorized to verify this transaction']);
            }
        }else{
            return response()->json(['error' => 'Invalid transaction ID']);
        }
    }
        */
}
