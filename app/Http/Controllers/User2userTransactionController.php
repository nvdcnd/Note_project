<?php

namespace App\Http\Controllers;

use App\Mail\user2user_trans_otp;
use App\Models\User;
use App\Models\User2userTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class User2userTransactionController extends Controller
{
    public function user2user_transaction_OTP_generator()
    {
        $otp = rand(100000, 999999);
        if (User2userTransaction::where('otp', $otp)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->exists()) {
            return $this->user2user_transaction_OTP_generator();
        } else {
            return $otp;
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
                $User2userTransaction->otp = $this->user2user_transaction_OTP_generator();
                $User2userTransaction->save();
                Mail::to(Auth::user()->email)->send(new user2user_trans_otp($User2userTransaction));
            } else {
                return response()->json(['error' => 'Invalid password']);
            }
        }

        return response()->json($User2userTransaction);
    }

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
}
