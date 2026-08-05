<?php

namespace App\Http\Controllers;

use App\Models\User2organizationTransaction;
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
        if(User2organizationTransaction::where('otp', $otp)->first()){
            return $this->user2organization_transaction_OTP_generator();
        }else{
            return $otp;
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
                $User2organizationTransaction->otp = $this->user2organization_transaction_OTP_generator();
                $User2organizationTransaction->save();
                Mail::to(Auth::user()->email)->send(new user2organization_trans_otp($User2organizationTransaction));
            }else{
                return response()->json(['error' => 'Invalid password']);
            }
        }
        return response()->json($User2organizationTransaction);
    }

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
}
