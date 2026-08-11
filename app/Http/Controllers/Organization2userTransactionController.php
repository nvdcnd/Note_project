<?php

namespace App\Http\Controllers;

use App\Mail\organization2user_trans_otp;
use App\Models\Organization;
use App\Models\Organization2userTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class Organization2userTransactionController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public function create_view($id)
    {
        $organization = Organization::query()->find($id);
        if (! $organization) {
            abort(404);
        }

        if ($organization->hostID !== Auth::id()) {
            abort(403);
        }

        return view('transactions.organization2user.create', compact('organization'));
    }

    public function verify_view($id)
    {
        $transaction = Organization2userTransaction::query()->where('id', $id)->first();
        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Invalid transaction');
        }

        return view('transactions.organization2user.verify', compact('transaction'));
    }

    public function history_view($id)
    {
        $userId = Auth::id();
        $allTransactions = Organization2userTransaction::query()
            ->where(function ($q) use ($userId) {
                $q->where('organizationID', $userId)->orWhere('userID', $userId);
            })
            ->latest()
            ->get();
        $fromTransactions = Organization2userTransaction::query()->where('organizationID', $userId)->latest()->get();
        $toTransactions = Organization2userTransaction::query()->where('userID', $userId)->latest()->get();

        return view('transactions.organization2user.history', compact('allTransactions', 'fromTransactions', 'toTransactions'));
    }

    public function organization2user_transaction_OTP_generator()
    {
        do {
            $otp = (string) random_int(100000, 999999);
        } while (Organization2userTransaction::query()->where('status', '!=', 'finished')->pluck('otp')->contains(
            fn ($hash) => Hash::check($otp, $hash)
        ));

        return $otp;
    }

    public function organization2user_transaction_create(Request $request, $id)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'recipient_email' => ['required_without:userID', 'nullable', 'email'],
            'userID' => ['required_without:recipient_email', 'nullable', 'integer', 'exists:users,id'],
        ]);

        if (! empty($validated['recipient_email'])) {
            $recipient = User::query()->where('email', strtolower(trim($validated['recipient_email'])))->first();
            if (! $recipient) {
                return redirect()->back()->with('error', 'No account found for that email');
            }
            $validated['userID'] = $recipient->id;
        }

        $organization = Organization::query()->find($id);
        if (! $organization) {
            return redirect()->back()->with('error', 'Organization not found');
        }

        if (Auth::id() !== $organization->hostID) {
            return redirect()->back()->with('error', 'Only the organization host can send money');
        }

        if (! Hash::check($validated['password'], Auth::user()->password)) {
            return redirect()->back()->with('error', 'Invalid password');
        }

        $otp = $this->organization2user_transaction_OTP_generator();

        $transaction = new Organization2userTransaction;
        $transaction->organizationID = $organization->id;
        $transaction->userID = $validated['userID'];
        $transaction->amount = $validated['amount'];
        $transaction->current_hostID = Auth::id();
        $transaction->status = Organization2userTransaction::STATUS_PENDING;
        $transaction->otp = Hash::make($otp);
        $transaction->expires_at = now()->addMinutes(10);
        $transaction->attempts = 0;
        $transaction->save();
        Mail::to(Auth::user()->email)->send(new organization2user_trans_otp($transaction, $otp));

        return redirect()->route('organization2user_transaction_verify_view', $transaction->id)->with('success', 'OTP sent to your email');
    }

    public function organization2user_transaction_verify(Request $request, $id)
    {
        $validated = $request->validate([
            'passkey' => ['required', 'string'],
        ]);

        /** @var Organization2userTransaction|null $transaction */
        $transaction = Organization2userTransaction::query()
            ->where('id', $id)
            ->where('status', Organization2userTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('home')->with('error', 'Invalid transaction');
        }

        $user = Auth::user();
        $passkey = $validated['passkey'];

        return DB::transaction(function () use ($passkey, $transaction, $user) {
            $organization = Organization::query()->lockForUpdate()->find($transaction->organizationID);
            $targetUser = User::query()->lockForUpdate()->find($transaction->userID);

            if (! $organization) {
                $transaction->update(['status' => Organization2userTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Organization not found');
            }

            if (! $targetUser) {
                $transaction->update(['status' => Organization2userTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Recipient user not found');
            }

            if ($transaction->attempts >= self::MAX_ATTEMPTS) {
                $transaction->update(['status' => Organization2userTransaction::STATUS_FAILED]);

                return redirect()->route('home')->with('error', 'Too many failed attempts. Transaction cancelled');
            }

            if (now()->greaterThan($transaction->expires_at)) {
                $transaction->update(['status' => Organization2userTransaction::STATUS_EXPIRED]);

                return redirect()->route('organization2user_transaction_history_view', $organization->id)->with('error', 'The transaction has expired. Please create a new transaction');
            }

            if (! Hash::check($passkey, $transaction->otp)) {
                $transaction->increment('attempts');

                return redirect()->route('organization2user_transaction_verify_view', $transaction->id)
                    ->with('error', 'Invalid passkey. '.($transaction->attempts).' failed attempt(s) out of '.self::MAX_ATTEMPTS);
            }

            if ($organization->balance < $transaction->amount) {
                return redirect()->route('organization', $organization->id)->with('error', 'Organization has insufficient balance');
            }

            $organization->decrement('balance', $transaction->amount);
            $targetUser->increment('balance', $transaction->amount);

            $transaction->update(['status' => Organization2userTransaction::STATUS_FINISHED]);

            return redirect()->route('organization2user_transaction_history_view', $organization->id)->with('success', 'Transaction completed successfully');
        });
    }

    public function organization2user_transaction_cancel(Request $request, $id)
    {
        $transaction = Organization2userTransaction::query()
            ->where('id', $id)
            ->where('status', Organization2userTransaction::STATUS_PENDING)
            ->first();

        if (! $transaction) {
            return redirect()->route('organization2user_transaction_history_view', Auth::id())->with('error', 'Transaction not found or already processed');
        }

        $organization = Organization::query()->find($transaction->organizationID);
        if (! $organization || Auth::id() !== $organization->hostID) {
            return redirect()->route('organization2user_transaction_history_view', Auth::id())->with('error', 'You are not authorized to cancel this transaction');
        }

        $transaction->update(['status' => Organization2userTransaction::STATUS_CANCELLED]);

        return redirect()->route('organization2user_transaction_history_view', Auth::id())->with('success', 'Transaction cancelled successfully');
    }
}
