<?php

namespace App\Http\Controllers;

use App\Mail\user2user_trans_otp;
use App\Models\User;
use App\Models\User2userTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class User2userTransactionController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public function create_view()
    {
        return view('transactions.user2user.create');
    }

    public function verify_view($id)
    {
        $transaction = User2userTransaction::query()->where('id', $id)->first();
        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Invalid transaction');
        }

        if (Auth::id() !== $transaction->from) {
            return redirect()->route('home')->with('error', 'You are not authorized to verify this transaction');
        }

        return view('transactions.user2user.verify', compact('transaction'));
    }

    public function history_view($id)
    {
        $userId = Auth::id();
        $allTransactions = User2userTransaction::query()
            ->where(function ($q) use ($userId) {
                $q->where('from', $userId)->orWhere('to', $userId);
            })
            ->latest()
            ->get();
        $fromTransactions = User2userTransaction::query()->where('from', $userId)->latest()->get();
        $toTransactions = User2userTransaction::query()->where('to', $userId)->latest()->get();

        return view('transactions.user2user.history', compact('allTransactions', 'fromTransactions', 'toTransactions'));
    }

    public function user2user_transaction_OTP_generator()
    {
        do {
            $otp = (string) random_int(100000, 999999);
        } while (User2userTransaction::query()->where('status', '!=', 'finished')->pluck('otp')->contains(
            fn ($hash) => Hash::check($otp, $hash)
        ));

        return $otp;
    }

    public function user2user_transaction_create(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'recipient_email' => ['required_without:to', 'nullable', 'email'],
            'to' => ['required_without:recipient_email', 'nullable', 'integer', 'exists:users,id'],
        ]);

        if (! empty($validated['recipient_email'])) {
            $recipient = User::query()->where('email', strtolower(trim($validated['recipient_email'])))->first();
            if (! $recipient) {
                return redirect()->back()->with('error', 'No account found for that email');
            }
            $validated['to'] = $recipient->id;
        }

        $data = $validated;

        if (Auth::id() === (int) $data['to']) {
            return redirect()->back()->with('error', 'You cannot send money to yourself');
        }

        if (! Hash::check($data['password'], Auth::user()->password)) {
            return redirect()->back()->with('error', 'Invalid password');
        }

        $otp = $this->user2user_transaction_OTP_generator();

        $transaction = new User2userTransaction;
        $transaction->from = Auth::id();
        $transaction->to = $data['to'];
        $transaction->amount = $data['amount'];
        $transaction->status = User2userTransaction::STATUS_PENDING;
        $transaction->otp = Hash::make($otp);
        $transaction->expires_at = now()->addMinutes(10);
        $transaction->attempts = 0;
        $transaction->save();
        Mail::to(Auth::user()->email)->send(new user2user_trans_otp($transaction, $otp));

        return redirect()->route('user2user_transaction_verify_view', $transaction->id)->with('success', 'OTP sent to your email');
    }

    public function user2user_transaction_verify(Request $request, $id)
    {
        $validated = $request->validate([
            'passkey' => ['required', 'string'],
        ]);

        /** @var User2userTransaction|null $transaction */
        $transaction = User2userTransaction::query()
            ->where('id', $id)
            ->where('status', User2userTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Invalid transaction');
        }

        if (Auth::id() !== $transaction->from) {
            return redirect()->route('home')->with('error', 'You are not authorized to verify this transaction');
        }

        $user = Auth::user();
        $passkey = $validated['passkey'];

        return DB::transaction(function () use ($transaction, $passkey, $user) {
            // Lock the sender + recipient rows to prevent double-spend (BE-6 / E11).
            $lockedSender = User::query()->lockForUpdate()->find($transaction->from);
            $lockedRecipient = User::query()->lockForUpdate()->find($transaction->to);

            if (! $lockedRecipient) {
                $transaction->update(['status' => User2userTransaction::STATUS_FAILED]);

                return redirect()->route('user2user_transaction_verify_view', $transaction->id)->with('error', 'Recipient not found');
            }

            if ($transaction->attempts >= self::MAX_ATTEMPTS) {
                $transaction->update(['status' => User2userTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Too many failed attempts. Transaction cancelled');
            }

            if (now()->greaterThan($transaction->expires_at)) {
                $transaction->update(['status' => User2userTransaction::STATUS_EXPIRED]);

                return redirect()->route('user2user_transaction_history_view', $user->id)->with('error', 'The transaction has expired. Please create a new transaction');
            }

            if (! Hash::check($passkey, $transaction->otp)) {
                $transaction->increment('attempts');

                return redirect()->route('user2user_transaction_verify_view', $transaction->id)
                    ->with('error', 'Invalid passkey. '.($transaction->attempts).' failed attempt(s) out of '.self::MAX_ATTEMPTS);
            }

            if ($lockedSender->balance < $transaction->amount) {
                return redirect()->route('user2user_transaction_verify_view', $transaction->id)->with('error', 'Insufficient balance');
            }

            $lockedRecipient->increment('balance', $transaction->amount);
            $lockedSender->decrement('balance', $transaction->amount);

            $transaction->update(['status' => User2userTransaction::STATUS_FINISHED]);

            return redirect()->route('user2user_transaction_history_view', $user->id)->with('success', 'Transaction completed successfully');
        });
    }

    public function user2user_transaction_cancel(Request $request, $id)
    {
        $transaction = User2userTransaction::query()
            ->where('id', $id)
            ->where('status', User2userTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('user2user_transaction_history_view', Auth::id())->with('error', 'Transaction not found or already processed');
        }

        if (Auth::id() !== $transaction->from) {
            return redirect()->route('user2user_transaction_history_view', Auth::id())->with('error', 'You are not authorized to cancel this transaction');
        }

        $transaction->update(['status' => User2userTransaction::STATUS_CANCELLED]);

        return redirect()->route('user2user_transaction_history_view', Auth::id())->with('success', 'Transaction cancelled successfully');
    }
}
