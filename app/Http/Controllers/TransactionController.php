<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Http\Controllers\CurrencyController;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'sender_account_id' => 'required|exists:accounts,id',
            'receiver_account_id' => 'nullable|exists:accounts,id',
            'external_account_number' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'transaction_type' => 'required|in:internal,external',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $transaction = Transaction::create($request->all());
        return response()->json($transaction, 201);
    }

    public function show(string $id)
    {
        $transaction = Transaction::with(['senderAccount', 'receiverAccount'])->findOrFail($id);
        return response()->json($transaction);    
    }

    public function update(Request $request, string $id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->update($request->only(['description', 'category']));
        return response()->json($transaction);
    }

    public function destroy(string $id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();
        return response()->json(['message'=>'Transakcija je obrisana.']);
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'sender_account_id' => 'required|exists:accounts,id',
            'receiver_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $sender = Account::findOrFail($request->sender_account_id);
        $receiver = Account::findOrFail($request->receiver_account_id);
        
        if ((string) $sender->user_id !== (string) auth()->id()) {
            return response()->json([
                'message' => 'Nemate dozvolu da šaljete sredstva sa ovog računa.',
            ], 403);
        }

        if ($sender->id === $receiver->id){
            return response()->json(['message'=>'Ne moze slati sredstva na isti racun.'], 400);
        }

        if($sender->balance < $request->amount){
            return response()->json(['message'=>'Nedovoljno sredstava na racunu.'], 400);
        }

        $senderCurrency = is_string($sender->currency) ? $sender->currency : $sender->currency->value;
        $receiverCurrency = is_string($receiver->currency) ? $receiver->currency : $receiver->currency->value;

        $rate = CurrencyController::exchangeRate($senderCurrency, $receiverCurrency);

        if ($rate === null) {
            return response()->json([
                'message' => "Kursna lista za par {$senderCurrency}_{$receiverCurrency} nije dostupna.",
            ], 400);
        }

        $amountReceived = round($request->amount * $rate, 2);

        DB::transaction(function () use ($sender, $receiver, $request, $amountReceived, $senderCurrency) {
            $sender->decrement('balance', $request->amount);
            $receiver->increment('balance', $amountReceived);

            Transaction::create([
                'sender_account_id'   => $sender->id,
                'receiver_account_id' => $receiver->id,
                'amount'              => $request->amount,
                'currency'            => $senderCurrency,
                'transaction_type'    => 'internal',
                'description'         => $request->description,
                'category'            => $request->category,
            ]);
        });

        return response()->json([
            'message'         => 'Prenos sredstava je uspješno izvršen.',
            'money_sent'      => $request->amount,
            'currency_sent'   => $senderCurrency,
            'exchange_rate' => $rate,
            'amount_received' => $amountReceived,
            'currency_received' => $receiverCurrency,

        ], 201);
    }

    public function search(Request $request)
    {
        $query = Transaction::with(['senderAccount', 'receiverAccount']);

        if($request->filled('account_id')){
            $accountId = $request->account_id;
            $query->where(function($q) use ($accountId){
                $q->where('sender_account_id', $accountId)
                      ->orWhere('receiver_account_id', $accountId);
            });
        }
         if ($request->filled('category')) {
        $query->where('category', $request->category);
    }

    if ($request->filled('search')) {
        $query->where('description', 'like', '%' . $request->search . '%');
    }

     $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = strtolower($request->get('sort_dir', 'desc'));
        $allowedSortColumns = ['created_at', 'amount', 'category'];
        $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc';

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDir);
        }


    $transactions = $query->get();

    if ($transactions->isEmpty()) {
        return response()->json(['message' => 'Ne postoje transakcije.'], 404);
    }

        return response()->json($transactions);
    }

    public function byAccount(string $id)
{
    $account = Account::where('user_id', auth()->id())->find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Račun nije pronađen ili nemate pristup.',
            ], 404);
        }

    $transactions = Transaction::with(['senderAccount', 'receiverAccount'])
        ->where('sender_account_id', $id)
        ->orWhere('receiver_account_id', $id)
        ->get();

    return response()->json($transactions);
}

public function index(Request $request)
{
    try {
        $query = Transaction::with(['senderAccount', 'receiverAccount']);

        // Filtriranje
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = strtolower($request->get('sort_dir', 'desc'));

        $allowedSortColumns = ['created_at', 'amount', 'category', 'currency'];
        $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc';

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }


        $perPage = $request->get('per_page', 10);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $transactions->items(),
            'meta'    => [
                'current_page' => $transactions->currentPage(),
                'per_page'     => $transactions->perPage(),
                'total'        => $transactions->total(),
                'last_page'    => $transactions->lastPage(),
            ]
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Greska pri ucitavanju transakcija.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}



}


