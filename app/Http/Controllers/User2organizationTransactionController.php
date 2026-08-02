<?php

namespace App\Http\Controllers;

use App\Models\user2organization_transaction;
use Illuminate\Http\Request;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\user2organization_trans_otp;

class User2organizationTransactionController extends Controller
{

    public function user2organization_transaction_OTP_generator(){
        $otp =  rand(100000, 999999);
        if(user2organization_transaction::where('otp', $otp)->first()){
            return $this->user2organization_transaction_OTP_generator();
        }else{
            return $otp;
        }
    }

    public function user2organization_transaction_create(Request $request){
        $data = $request->all();
        $user2organization_transaction = new user2organization_transaction();
        $organization = Organization::where('id', $data['organizationID'])->first();
        if(!$organization){
            return response()->json(['error' => 'Organization not found']);
        } else {
            if(Hash::check($data['password'], Auth::user()->password)){
                $user2organization_transaction->from = Auth::user()->id;
                $user2organization_transaction->organizationID = $data['organizationID'];
                $user2organization_transaction->amount = $data['amount'];
                $user2organization_transaction->status = 'pending';
                $user2organization_transaction->current_hostID = $organization->hostID;
                $user2organization_transaction->otp = $this->user2organization_transaction_OTP_generator();
                $user2organization_transaction->save();
                Mail::to(Auth::user()->email)->send(new user2organization_trans_otp($user2organization_transaction));
            }else{
                return response()->json(['error' => 'Invalid password']);
            }
        }
        return response()->json($user2organization_transaction);
    }

    public function user2organization_transaction_cancel(Request $request, $id){
        $user2organization_transaction = user2organization_transaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if($user2organization_transaction){
            if(Auth::user()->id == $user2organization_transaction->from){
                    $user2organization_transaction->status = 'declined';
                    //$user2organization_transaction->otp = $this->user2organization_transaction_OTP_generator();
                    $user2organization_transaction->save();
                    return response()->json($user2organization_transaction);
                }else{
                    return response()->json(['error' => 'You are not authorized to cancel this transaction']);
                }
        }else{
            return response()->json(['error' => 'Invalid transaction ID']);
        }
    }

    public function user2organization_transaction_verify(Request $request, $id){
        $user2organization_transaction = user2organization_transaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if($user2organization_transaction){
            if(Auth::user()->id == $user2organization_transaction->from || Auth::user()->id == $user2organization_transaction->to){
                if($user2organization_transaction->otp == $request->otp){
                    $user2organization_transaction->status = 'accepted';
                    $user2organization_transaction->save();
                    return response()->json($user2organization_transaction);
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
}
