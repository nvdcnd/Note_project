<?php

namespace App\Http\Controllers;

use App\Mail\user2organization_trans_otp;
use App\Models\Organization;
use App\Models\User2organizationTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class User2organizationTransactionController extends Controller
{
    public function user2organization_transaction_OTP_generator()
    {
        $otp = rand(100000, 999999);
        $existing = User2organizationTransaction::query()->where('status', '!=', 'finished')->get();

        foreach ($existing as $transaction) {
            if (Hash::check((string) $otp, $transaction->otp)) {
                return $this->user2organization_transaction_OTP_generator();
            }
        }

        return (string) $otp;
    }

    public function user2organization_transaction_create(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'organizationID' => 'required',
            'amount' => 'required',
        ]);

        $data = $request->all();
        $User2organizationTransaction = new User2organizationTransaction;
        $organization = Organization::query()->find($data['organizationID']);

        if (! $organization) {
            return response()->json(['error' => 'Organization not found']);
        }

        if (! Hash::check((string) $data['password'], Auth::user()->password)) {
            return response()->json(['error' => 'Invalid password']);
        }

        $User2organizationTransaction->from = Auth::user()->id;
        $User2organizationTransaction->organizationID = $data['organizationID'];
        $User2organizationTransaction->amount = $data['amount'];
        $User2organizationTransaction->status = 'pending';
        $User2organizationTransaction->current_hostID = $organization->hostID;
        $User2organizationTransaction->otp = Hash::make($this->user2organization_transaction_OTP_generator());
        $User2organizationTransaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $User2organizationTransaction->save();
        Mail::to(Auth::user()->email)->send(new user2organization_trans_otp($User2organizationTransaction));

        return redirect()->route('user2organization_transaction_verify_view', $User2organizationTransaction->id);
    }

    public function user2organization_transaction_verify(Request $request, $id)
    {
        /** @var User2organizationTransaction|null $transaction */
        $transaction = User2organizationTransaction::query()->where('id', $id)->where('status', 'pending')->first();
        $request->validate([
            'passkey' => 'required',
        ]);

        $data = $request->all();
        $user = Auth::user();
        $passkey = (string) $data['passkey'];

        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Invalid transaction');
        }

        return DB::transaction(function () use ($user, $passkey, $transaction) {
            $organization = Organization::query()->find($transaction->organizationID);
            if (! $organization) {
                User2organizationTransaction::destroy($transaction->id);

                return redirect()->route('home')->with('error', 'Organization not found');
            }

            $time = Carbon::parse($transaction->expires_at);

            if (! Hash::check($passkey, $transaction->otp)) {
                User2organizationTransaction::destroy($transaction->id);

                return redirect()->route('organization', $organization->id)->with('error', 'Invalid passkey');
            }

            if (now()->greaterThan($time)) {
                User2organizationTransaction::destroy($transaction->id);

                return redirect()->route('organization', $organization->id)->with('error', 'The transaction has expired. Please create a new transaction');
            }

            if ($user->balance < $transaction->amount) {
                return redirect()->route('organization', $organization->id)->with('error', 'Insufficient balance');
            }

            $organization->balance += $transaction->amount;
            $organization->save();

            $user->balance -= $transaction->amount;
            $user->save();

            $transaction->status = 'finished';
            $transaction->save();

            return redirect()->route('user2organization_transaction_history_view', $user->id)->with('success', 'Transaction completed successfully');
        });
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
