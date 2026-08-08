<?php

namespace App\Http\Controllers;

use App\Mail\user2user_trans_otp;
use App\Models\User;
use App\Models\User2userTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class User2userTransactionController extends Controller
{
    public function user2user_transaction_OTP_generator()
    {
        $otp = rand(100000, 999999);
        $existing = User2userTransaction::query()->where('status', '!=', 'finished')->get();

        foreach ($existing as $transaction) {
            if (Hash::check((string) $otp, $transaction->otp)) {
                return $this->user2user_transaction_OTP_generator();
            }
        }

        return (string) $otp;
    }

    public function user2user_transaction_create(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'to' => 'required',
            'amount' => 'required',
        ]);

        $data = $request->all();
        $User2userTransaction = new User2userTransaction;

        if (Auth::user()->id == $data['to']) {
            return response()->json(['error' => 'You cannot send money to yourself']);
        }

        if (! Hash::check((string) $data['password'], Auth::user()->password)) {
            return response()->json(['error' => 'Invalid password']);
        }

        $User2userTransaction->from = Auth::user()->id;
        $User2userTransaction->to = $data['to'];
        $User2userTransaction->amount = $data['amount'];
        $User2userTransaction->status = 'pending';
        $User2userTransaction->otp = Hash::make($this->user2user_transaction_OTP_generator());
        $User2userTransaction->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $User2userTransaction->save();
        Mail::to(Auth::user()->email)->send(new user2user_trans_otp($User2userTransaction));

        return redirect()->route('user2user_transaction_verify_view', $User2userTransaction->id);
    }

    public function user2user_transaction_verify(Request $request, $id)
    {
        /** @var User2userTransaction|null $transaction */
        $transaction = User2userTransaction::query()->where('id', $id)->where('status', 'pending')->first();
        $request->validate([
            'passkey' => 'required',
        ]);

        $data = $request->all();
        $user = Auth::user();
        $passkey = (string) $data['passkey'];

        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Invalid transaction');
        }

        return DB::transaction(function () use ($transaction, $passkey, $user) {
            $time = Carbon::parse($transaction->expires_at);

            if (! Hash::check($passkey, $transaction->otp)) {
                User2userTransaction::destroy($transaction->id);

                return redirect()->route('home')->with('error', 'Invalid passkey');
            }

            if (now()->greaterThan($time)) {
                User2userTransaction::destroy($transaction->id);

                return redirect()->route('home')->with('error', 'The transaction has expired. Please create a new transaction');
            }

            $recipient = User::query()->find($transaction->to);
            if (! $recipient) {
                User2userTransaction::destroy($transaction->id);

                return redirect()->route('home')->with('error', 'Recipient not found');
            }

            if ($user->balance < $transaction->amount) {
                return redirect()->route('home')->with('error', 'Insufficient balance');
            }

            $recipient->balance += $transaction->amount;
            $recipient->save();

            $user->balance -= $transaction->amount;
            $user->save();

            $transaction->status = 'finished';
            $transaction->save();

            return redirect()->route('user2user_transaction_history_view', $user->id)->with('success', 'Transaction completed successfully');
        });

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
