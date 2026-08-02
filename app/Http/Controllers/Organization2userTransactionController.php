<?php

namespace App\Http\Controllers;

use App\Models\organization2user_transaction;
use Illuminate\Http\Request;

class Organization2userTransactionController extends Controller
{

    function organization2user_transaction_OTP_generator(){
        $otp =  rand(100000, 999999);
        if(organization2user_transaction::where('otp', $otp)->first()){
            return $this->organization2user_transaction_OTP_generator();
        }else{
            return $otp;
        }
    }

    public function organization2user_transaction_create(Request $request, $id){
        $data = $request->all();
        $organization2user_transaction = new organization2user_transaction();
        $organization = Organization::where('id', $id)->first();
        if(!$organization){
            return response()->json(['error' => 'Organization not found']);
        } else {
            if(Hash::check($data['password'], Auth::user()->password) && Auth::user()->id == $organization->hostID){
                $organization2user_transaction->organizationID = $organization->id;
                $organization2user_transaction->userID = $data['userID'];
                $organization2user_transaction->amount = $data['amount'];
                $organization2user_transaction->current_hostID = Auth::user()->id;
                $organization2user_transaction->status = 'pending';
                $organization2user_transaction->otp = $this->organization2user_transaction_OTP_generator();
                $organization2user_transaction->save();
                Mail::to(Auth::user()->email)->send(new organization2user_trans_otp($organization2user_transaction));
            }else{
                return response()->json(['error' => 'Invalid password']);
            }
        }
        return response()->json($organization2user_transaction);
    }

    public function organization2user_transaction_cancel(Request $request, $id){
        $organization2user_transaction = organization2user_transaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if($organization2user_transaction){
            if(Auth::user()->id == $organization2user_transaction->userID){
                $organization2user_transaction->status = 'declined';
                //$organization2user_transaction->otp = $this->organization2user_transaction_OTP_generator();
                $organization2user_transaction->save();
                return response()->json($organization2user_transaction);
            }else{
                return response()->json(['error' => 'You are not authorized to cancel this transaction']);
            }
        }else{
            return response()->json(['error' => 'Invalid transaction ID']);
        }
    }

    public function organization2user_transaction_verify(Request $request, $id){
        $organization2user_transaction = organization2user_transaction::where('id', $id)->where('status', '!=', 'declined')->where('status', '!=', 'accepted')->first();
        if($organization2user_transaction){
            if(Auth::user()->id == $organization2user_transaction->userID){
                if($organization2user_transaction->otp == $request->otp){
                    $organization2user_transaction->status = 'accepted';
                    $organization2user_transaction->save();
                    return response()->json($organization2user_transaction);
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
