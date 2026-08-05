<?php

namespace App\Http\Controllers;

use App\Mail\organization2user_trans_otp;
use App\Models\Organization2userTransaction;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class Organization2userTransactionController extends Controller
{
    public function organization2user_transaction_OTP_generator()
    {
        $otp =  rand(100000, 999999);
        $check = Organization2userTransaction::where('otp', $otp)->first();
        if($check->status != 'finished' || now()->greaterThan(Carbon::prase($check->expires_at)) || $check){
            return $this->user2organization_transaction_OTP_generator();
        }else{
            return (string)$otp;
        }
    }

    public function organization2user_transaction_create(Request $request, $id)
    {
        $request->validate([
            'userID'=>'required',
            'amount'=>'required'
        ]);
        $data = $request->all();
        $Organization2userTransaction = new Organization2userTransaction;
        $organization = Organization::where('id', $id)->first();
        if (! $organization) {
            return response()->json(['error' => 'Organization not found']);
        } else {
            if (Hash::check($data['password'], Auth::user()->password) && Auth::user()->id == $organization->hostID) {
                $Organization2userTransaction->organizationID = $organization->id;
                $Organization2userTransaction->userID = $data['userID'];
                $Organization2userTransaction->amount = $data['amount'];
                $Organization2userTransaction->current_hostID = Auth::user()->id;
                $Organization2userTransaction->status = 'pending';
                $Organization2userTransaction->otp = Hash::make($this->organization2user_transaction_OTP_generator());
                $Organization2userTransaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $Organization2userTransaction->save();
                Mail::to(Auth::user()->email)->send(new organization2user_trans_otp($Organization2userTransaction));

                return redirect("org2user_transaction_view",$Organization2userTransaction->id);
            } else {
                return response()->json(['error' => 'Invalid password']);
            }
        }

        return response()->json($Organization2userTransaction);
    }

    public function organization2user_transaction_verify(Request $request, $id){
        $transaction = Organization2userTransaction::where('id',$id)->where('status','pending')->first();
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

    public function organization2user_transaction_cancel(Request $request, $id)
    {
        $Organization2userTransaction = Organization2userTransaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if ($Organization2userTransaction) {
            if (Auth::user()->id == $Organization2userTransaction->userID) {
                $Organization2userTransaction->status = 'declined';
                // $Organization2userTransaction->otp = $this->organization2user_transaction_OTP_generator();
                $Organization2userTransaction->save();

                return response()->json($Organization2userTransaction);
            } else {
                return response()->json(['error' => 'You are not authorized to cancel this transaction']);
            }
        } else {
            return response()->json(['error' => 'Invalid transaction ID']);
        }
    }

    public function organization2user_transaction_verify(Request $request, $id)
    {
        $Organization2userTransaction = Organization2userTransaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if ($Organization2userTransaction) {
            if (Auth::user()->id == $Organization2userTransaction->userID) {
                if ($Organization2userTransaction->otp == $request->otp) {
                    $Organization2userTransaction->status = 'accepted';
                    $Organization2userTransaction->save();

                    return response()->json($Organization2userTransaction);
                } else {
                    return response()->json(['error' => 'Invalid OTP']);
                }
            } else {
                return response()->json(['error' => 'You are not authorized to verify this transaction']);
            }
        } else {
            return response()->json(['error' => 'Invalid transaction ID']);
        }
    } */
}
