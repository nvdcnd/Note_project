<?php

namespace App\Http\Controllers;

use App\Models\user2user_transaction;
use Illuminate\Http\Request;
use App\Models\user;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\user2user_trans_otp;
//use App\Mail\user2user_trans_verify;
//use App\Models\user2user_transaction;

class User2userTransactionController extends Controller
{

    function user2user_transaction_OTP_generator(){
        $otp = rand(100000, 999999);
        if (user2user_transaction::where('otp', $otp)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->exists()) {
            return user2user_transaction_OTP_generator();
        }else{
            return $otp;
        }
    }

    public function user2user_transaction_create(Request $request){
        $data = $request->all();
        $user2user_transaction = new user2user_transaction();
        if(Auth::user()->id == $data['to']){
            return response()->json(['error' => 'You cannot send money to yourself']);
        } else {
            if(Hash::check($data['password'], Auth::user()->password)){
                $user2user_transaction->from = Auth::user()->id;
                $user2user_transaction->to = $data['to'];
                $user2user_transaction->amount = $data['amount'];
                $user2user_transaction->status = 'pending';
                $user2user_transaction->otp = $this->user2user_transaction_OTP_generator();
                $user2user_transaction->save();
                Mail::to(Auth::user()->email)->send(new user2user_trans_otp($user2user_transaction));
            }else{
                return response()->json(['error' => 'Invalid password']);
            }
        }
        return response()->json($user2user_transaction);
    }

    public function user2user_transaction_cancel(Request $request, $id){
        $user2user_transaction = user2user_transaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if($user2user_transaction){
                if(Auth::user()->id == $user2user_transaction->from){
                    $user2user_transaction->status = 'declined';
                    //$user2user_transaction->otp = $this->user2user_transaction_OTP_generator();
                    $user2user_transaction->save();
                }else{
                    return response()->json(['error' => 'You are not authorized to cancel this transaction']);
                }
                return response()->json($user2user_transaction);
            }else{
                return response()->json(['error' => 'You are not authorized to cancel this transaction']);
            }
    }

    public function user2user_transaction_verify(Request $request, $id){
        $data = $request->all();
        $user2user_transaction = user2user_transaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if($user2user_transaction && ($user2user_transaction->otp == $data['otp'])){
            $user2user_transaction->status = 'accepted';
            $user2user_transaction->save();
            $from = user::where('id', $user2user_transaction->from)->first();
            $to = user::where('id', $user2user_transaction->to)->first();
            $from->balance -= $user2user_transaction->amount;
            $to->balance += $user2user_transaction->amount;
            $from->save();
            $to->save();
            return response()->json($user2user_transaction);
        }else{
            return response()->json(['error' => 'Invalid OTP']);
        }
    }
}
