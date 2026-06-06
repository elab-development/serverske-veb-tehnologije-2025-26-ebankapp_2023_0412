<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index()
    {
        $transaction = Transaction::with(['senderAccount', 'receiverAccount'])->get();
        return response()->json($transaction);
    }

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
        
        if ($sender->id === $receiver->id){
            return response()->json(['message'=>'Ne moze slati sredstva na isti racun.'], 400);
        }

        if($sender->balance < $request->amount){
            return response()->json(['message'=>'Nedovoljno sredstava na racunu.'], 400);
        }

        $amountReceived = $request->amount;
        $senderCurrency = is_string($sender->currency) ? $sender->currency : $sender->currency->value;
        $receiverCurrency = is_string($receiver->currency) ? $receiver->currency : $receiver->currency->value;

        if ($senderCurrency !== $receiverCurrency) {
            $rates = [
                'EUR_RSD' => 117.20,
                'RSD_EUR' => 0.0085,
                'USD_RSD' => 108.50,
                'RSD_USD' => 0.0092,
                'EUR_USD' => 1.08,
                'USD_EUR' => 0.93,
            ];

            $par = $senderCurrency . '_' . $receiverCurrency;

            if (!isset($rates[$par])) {
                return response()->json([
                    'message' => "Kursna lista za par {$par} nije definisana."
                ], 400);
            }

            $amountReceived = round($request->amount * $rates[$par], 2);
        }

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

    $transactions = $query->get();

    if ($transactions->isEmpty()) {
        return response()->json(['message' => 'Ne postoje transakcije.'], 404);
    }

        return response()->json($transactions);
    }

    public function byAccount(string $id)
{
    $account = Account::findOrFail($id);

    $transactions = Transaction::with(['senderAccount', 'receiverAccount'])
        ->where('sender_account_id', $id)
        ->orWhere('receiver_account_id', $id)
        ->get();

    return response()->json($transactions);
}
}


