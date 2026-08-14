<?php

namespace App\Http\Controllers;

use App\Models\Organization2userTransaction;
use App\Models\User2organizationTransaction;
use App\Models\User2userTransaction;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $user2userAll = User2userTransaction::query()
            ->where(function ($q) use ($userId) {
                $q->where('from', $userId)->orWhere('to', $userId);
            })
            ->latest()
            ->take(50)
            ->get();

        $user2organizationAll = User2organizationTransaction::query()
            ->where('from', $userId)
            ->latest()
            ->take(50)
            ->get();

        $organization2userAll = Organization2userTransaction::query()
            ->where('userID', $userId)
            ->latest()
            ->take(50)
            ->get();

        $allTransactions = collect()
            ->merge($user2userAll->map(fn ($t) => (object) [
                'id' => $t->id,
                'type' => 'User → User',
                'from' => $t->from,
                'to' => $t->to,
                'amount' => $t->amount,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]))
            ->merge($user2organizationAll->map(fn ($t) => (object) [
                'id' => $t->id,
                'type' => 'User → Organization',
                'from' => $t->from,
                'to' => $t->organizationID,
                'amount' => $t->amount,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]))
            ->merge($organization2userAll->map(fn ($t) => (object) [
                'id' => $t->id,
                'type' => 'Organization → User',
                'from' => $t->organizationID,
                'to' => $t->userID,
                'amount' => $t->amount,
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]))
            ->sortByDesc('created_at')
            ->values();

        return view('balance', [
            'user' => Auth::user(),
            'allTransactions' => $allTransactions,
        ]);
    }
}
