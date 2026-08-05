<?php

namespace App\Http\Controllers;

use App\Mail\user2user_trans_otp;
use App\Models\User;
use App\Models\User2userTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class User2userTransactionController extends Controller
{
    public function user2user_transaction_OTP_generator()
    {
        $otp =  rand(100000, 999999);
        $check = User2userTransaction::where('otp', $otp)->first();
        if($check->status != 'finished' || now()->greaterThan(Carbon::prase($check->expires_at)) || !$check){
            return $this->user2organization_transaction_OTP_generator();
        }else{
            return (string)$otp;
        }
    }

    public function user2user_transaction_create(Request $request)
    {
        $request->validate([
            'password'=>'required',
            'organizationID'=>'required',
            'amount'=>'required'
        ]);
        $data = $request->all();
        $User2userTransaction = new User2userTransaction;
        if (Auth::user()->id == $data['to']) {
            return response()->json(['error' => 'You cannot send money to yourself']);
        } else {
            if (Hash::check($data['password'], Auth::user()->password)) {
                $User2userTransaction->from = Auth::user()->id;
                $User2userTransaction->to = $data['to'];
                $User2userTransaction->amount = $data['amount'];
                $User2userTransaction->status = 'pending';
                $User2userTransaction->otp = Hash::make($this->user2organization_transaction_OTP_generator());
                $User2userTransaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $User2userTransaction->save();
                Mail::to(Auth::user()->email)->send(new user2user_trans_otp($User2userTransaction));

                return redirect("user2user_transaction_verify_view",$User2userTransaction->id);
            } else {
                return response()->json(['error' => 'Invalid password']);
            }
        }

        return response()->json($User2userTransaction);
    }

    public function user2user_transaction_verify(Request $request, $id){
        $transaction = User2userTransaction::where('id',$id)->where('status','pending')->first();
        $request->validate([
            'passkey'=>'required'
        ]);
        $data = $request->all();
        $user = Auth::user();
        //$organization = Organization::find($transaction->organizationID);
        $passkey = $data['passkey'];
        $time = Carbon::prase($transaction->expires_at);

        if ($transaction && Hash::check($passkey,$transaction)) {
            if (now()->greaterThan($time)) {
                $transaction->delete();
                return redirect()->route('home')->with('error','the time was expire. Please create the new transaction');
            } else {
                $organization->balance += $transaction->amount;
                $user->balance -= $transaction->amount;
                $transaction->status = 'finished';
                return redirect()->route('user2user_bill.view',$id)->with('success');
            }
        } else {
            $transaction->delete();
            return redirect()->route('home')->with('error','something have wrong. please try again');
        }
    }

    /*

    public function user2user_transaction_cancel(Request $request, $id)
    {
        $User2userTransaction = User2userTransaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if ($User2userTransaction) {
            if (Auth::user()->id == $User2userTransaction->from) {
                $User2userTransaction->status = 'declined';
                $User2userTransaction->save();
            } else {
                return response()->json(['error' => 'You are not authorized to cancel this transaction']);
            }

            return response()->json($User2userTransaction);
        } else {
            return response()->json(['error' => 'You are not authorized to cancel this transaction']);
        }
    }

    public function user2user_transaction_verify(Request $request, $id)
    {
        $data = $request->all();
        $User2userTransaction = User2userTransaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if ($User2userTransaction && ($User2userTransaction->otp == $data['otp'])) {
            $User2userTransaction->status = 'accepted';
            $User2userTransaction->save();
            $from = User::where('id', $User2userTransaction->from)->first();
            $to = User::where('id', $User2userTransaction->to)->first();
            if ($from && $to) {
                $from->balance -= $User2userTransaction->amount;
                $to->balance += $User2userTransaction->amount;
                $from->save();
                $to->save();
            }

            return response()->json($User2userTransaction);
        } else {
            return response()->json(['error' => 'Invalid OTP']);
        }
    }
        */
}
