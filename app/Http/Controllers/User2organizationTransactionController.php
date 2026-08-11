<?php

namespace App\Http\Controllers;

use App\Mail\user2organization_trans_otp;
use App\Models\Organization;
use App\Models\User2organizationTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class User2organizationTransactionController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public function create_view()
    {
        return view('transactions.user2organization.create');
    }

    public function verify_view($id)
    {
        $transaction = User2organizationTransaction::query()->where('id', $id)->first();
        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Invalid transaction');
        }

        if (Auth::id() !== $transaction->from) {
            return redirect()->route('home')->with('error', 'You are not authorized to verify this transaction');
        }

        return view('transactions.user2organization.verify', compact('transaction'));
    }

    public function history_view($id)
    {
        $userId = Auth::id();
        $allTransactions = User2organizationTransaction::query()
            ->where(function ($q) use ($userId) {
                $q->where('from', $userId)->orWhere('organizationID', $userId);
            })
            ->latest()
            ->get();
        $fromTransactions = User2organizationTransaction::query()->where('from', $userId)->latest()->get();
        $toTransactions = User2organizationTransaction::query()->where('organizationID', $userId)->latest()->get();

        return view('transactions.user2organization.history', compact('allTransactions', 'fromTransactions', 'toTransactions'));
    }

    public function user2organization_transaction_OTP_generator()
    {
        do {
            $otp = (string) random_int(100000, 999999);
        } while (User2organizationTransaction::query()->where('status', '!=', 'finished')->pluck('otp')->contains(
            fn ($hash) => Hash::check($otp, $hash)
        ));

        return $otp;
    }

    public function user2organization_transaction_create(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'organizationID' => ['required', 'integer', 'exists:organizations,id'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $organization = Organization::query()->find($validated['organizationID']);
        if (! $organization) {
            return redirect()->back()->with('error', 'Organization not found');
        }

        if (! Hash::check($validated['password'], Auth::user()->password)) {
            return redirect()->back()->with('error', 'Invalid password');
        }

        $otp = $this->user2organization_transaction_OTP_generator();

        $transaction = new User2organizationTransaction;
        $transaction->from = Auth::id();
        $transaction->organizationID = $organization->id;
        $transaction->amount = $validated['amount'];
        $transaction->status = User2organizationTransaction::STATUS_PENDING;
        $transaction->current_hostID = $organization->hostID;
        $transaction->otp = Hash::make($otp);
        $transaction->expires_at = now()->addMinutes(10);
        $transaction->attempts = 0;
        $transaction->save();
        Mail::to(Auth::user()->email)->send(new user2organization_trans_otp($transaction, $otp));

        return redirect()->route('user2organization_transaction_verify_view', $transaction->id)->with('success', 'OTP sent to your email');
    }

    public function user2organization_transaction_verify(Request $request, $id)
    {
        $validated = $request->validate([
            'passkey' => ['required', 'string'],
        ]);

        /** @var User2organizationTransaction|null $transaction */
        $transaction = User2organizationTransaction::query()
            ->where('id', $id)
            ->where('status', User2organizationTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Invalid transaction');
        }

        if (Auth::id() !== $transaction->from) {
            return redirect()->route('home')->with('error', 'You are not authorized to verify this transaction');
        }

        $user = Auth::user();
        $passkey = $validated['passkey'];

        return DB::transaction(function () use ($user, $passkey, $transaction) {
            $organization = Organization::query()->lockForUpdate()->find($transaction->organizationID);
            $lockedSender = \App\Models\User::query()->lockForUpdate()->find($transaction->from);

            if (! $organization) {
                $transaction->update(['status' => User2organizationTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Organization not found');
            }

            if ($transaction->attempts >= self::MAX_ATTEMPTS) {
                $transaction->update(['status' => User2organizationTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Too many failed attempts. Transaction cancelled');
            }

            if (now()->greaterThan($transaction->expires_at)) {
                $transaction->update(['status' => User2organizationTransaction::STATUS_EXPIRED]);

                return redirect()->route('user2organization_transaction_history_view', $user->id)->with('error', 'The transaction has expired. Please create a new transaction');
            }

            if (! Hash::check($passkey, $transaction->otp)) {
                $transaction->increment('attempts');

                return redirect()->route('user2organization_transaction_verify_view', $transaction->id)
                    ->with('error', 'Invalid passkey. '.($transaction->attempts).' failed attempt(s) out of '.self::MAX_ATTEMPTS);
            }

            if ($lockedSender->balance < $transaction->amount) {
                return redirect()->route('organization', $organization->id)->with('error', 'Insufficient balance');
            }

            $organization->increment('balance', $transaction->amount);
            $lockedSender->decrement('balance', $transaction->amount);

            $transaction->update(['status' => User2organizationTransaction::STATUS_FINISHED]);

            return redirect()->route('user2organization_transaction_history_view', $user->id)->with('success', 'Transaction completed successfully');
        });
    }

    public function user2organization_transaction_cancel(Request $request, $id)
    {
        $transaction = User2organizationTransaction::query()
            ->where('id', $id)
            ->where('status', User2organizationTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('user2organization_transaction_history_view', Auth::id())->with('error', 'Transaction not found or already processed');
        }

        if (Auth::id() !== $transaction->from) {
            return redirect()->route('user2organization_transaction_history_view', Auth::id())->with('error', 'You are not authorized to cancel this transaction');
        }

        $transaction->update(['status' => User2organizationTransaction::STATUS_CANCELLED]);

        return redirect()->route('user2organization_transaction_history_view', Auth::id())->with('success', 'Transaction cancelled successfully');
    }
}
