<?php

namespace App\Http\Controllers;

use App\Mail\organization2user_trans_otp;
use App\Models\Organization;
use App\Models\Organization2userTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class Organization2userTransactionController extends Controller
{
    public function organization2user_transaction_OTP_generator()
    {
        $otp = rand(100000, 999999);
        $existing = Organization2userTransaction::query()->where('status', '!=', 'finished')->get();

        foreach ($existing as $transaction) {
            if (Hash::check((string) $otp, $transaction->otp)) {
                return $this->organization2user_transaction_OTP_generator();
            }
        }

        return (string) $otp;
    }

    public function organization2user_transaction_create(Request $request, $id)
    {
        $request->validate([
            'userID' => 'required',
            'amount' => 'required',
        ]);

        $data = $request->all();
        $Organization2userTransaction = new Organization2userTransaction;
        $organization = Organization::query()->find($id);

        if (! $organization) {
            return response()->json(['error' => 'Organization not found']);
        }

        if (! Hash::check((string) $data['password'], Auth::user()->password) || Auth::user()->id != $organization->hostID) {
            return response()->json(['error' => 'Invalid password']);
        }

        $Organization2userTransaction->organizationID = $organization->id;
        $Organization2userTransaction->userID = $data['userID'];
        $Organization2userTransaction->amount = $data['amount'];
        $Organization2userTransaction->current_hostID = Auth::user()->id;
        $Organization2userTransaction->status = 'pending';
        $Organization2userTransaction->otp = Hash::make($this->organization2user_transaction_OTP_generator());
        $Organization2userTransaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $Organization2userTransaction->save();
        Mail::to(Auth::user()->email)->send(new organization2user_trans_otp($Organization2userTransaction));

        return redirect()->route('organization2user_transaction_verify_view', $Organization2userTransaction->id);
    }

    public function organization2user_transaction_verify(Request $request, $id)
    {
        /** @var Organization2userTransaction|null $transaction */
        $transaction = Organization2userTransaction::query()->where('id', $id)->where('status', 'pending')->first();
        $request->validate([
            'passkey' => 'required',
        ]);

        $data = $request->all();
        $user = Auth::user();
        $passkey = (string) $data['passkey'];

        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Invalid transaction');
        }

        return DB::transaction(function () use ($passkey, $transaction) {
           $organization = Organization::query()->find($transaction->organizationID);
            if (! $organization) {
                Organization2userTransaction::destroy($transaction->id);

                return redirect()->route('home')->with('error', 'Organization not found');
            }

            $time = Carbon::parse($transaction->expires_at);

            if (! Hash::check($passkey, $transaction->otp)) {
                Organization2userTransaction::destroy($transaction->id);

                return redirect()->route('home')->with('error', 'Invalid passkey');
            }

            if (now()->greaterThan($time)) {
                Organization2userTransaction::destroy($transaction->id);

                return redirect()->route('home')->with('error', 'The transaction has expired. Please create a new transaction');
            }

            if ($organization->balance < $transaction->amount) {
                return redirect()->route('home')->with('error', 'Organization has insufficient balance');
            }

            $targetUser = User::query()->find($transaction->userID);
            if (! $targetUser) {
                return redirect()->route('home')->with('error', 'Recipient user not found');
            }

            $organization->balance -= $transaction->amount;
            $organization->save();

            $targetUser->balance += $transaction->amount;
            $targetUser->save();

            $transaction->status = 'finished';
            $transaction->save();

            return redirect()->route('organization2user_transaction_history_view', $organization->id)->with('success', 'Transaction completed successfully');
        });
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
