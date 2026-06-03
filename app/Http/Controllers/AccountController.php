<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;


class AccountController extends Controller
{

    public function index()
    {
        try {
            $userId = auth()->id();
            $accounts = Account::where('user_id', $userId)->get();

            return response()->json([
                'success' => true,
                'message' => 'Računi su uspešno učitani.',
                'data' => $accounts
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Došlo je do greške prilikom učitavanja računa.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'account_number' => 'required|string|unique:accounts,account_number|max:30',
                'balance'        => 'required|numeric|min:0',
                'currency'    => 'required|in:RSD,EUR,USD',
                'type'    => 'required|in:dinarski, devizni',
                'status'  => 'required|in:active,frozen,closed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validacija nije uspela.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $account = Account::create([
                'user_id'        => auth()->id(),
                'account_number' => $request->account_number,
                'balance'        => $request->balance,
                'currency'    => $request->currency,
                'type'    => $request->type,
                'status'  => $request->status ?? 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Račun je uspešno kreiran.',
                'data'    => $account
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kreiranje računa nije uspelo.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $account = Account::where('user_id', auth()->id())->find($id);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Račun nije pronađen ili nemate pristup.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detalji računa su uspešno učitani.',
                'data'    => $account
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Greška pri dobavljanju računa.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request,string $id)
    {
        try {
            $account = Account::where('user_id', auth()->id())->find($id);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Račun nije pronađen ili nemate pristup.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'type'    => 'sometimes|in:dinarski,devizni',
                'status'  => 'sometimes|in:active,frozen,closed',
                'balance' => 'sometimes|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validacija nije uspela.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $account->update($request->only(['type', 'status', 'balance']));

            return response()->json([
                'success' => true,
                'message' => 'Račun je uspešno ažuriran.',
                'data'    => $account
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ažuriranje računa nije uspelo.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $account = Account::where('user_id', auth()->id())->find($id);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Račun nije pronađen ili nemate pristup.'
                ], 404);
            }

            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Račun je uspešno obrisan.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brisanje računa nije uspelo.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

  
    public function getBalance(string $id)
    {
        try {
            $account = Account::where('user_id', auth()->id())->find($id);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Račun nije pronađen.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'balance' => $account->balance,
                'currency' => $account->currency,
                'account_number' => $account->account_number
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Greška pri proveri stanja.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getTransactions(string $id)
{
    try {
        $account = Account::where('user_id', auth()->id())->find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Račun nije pronađen.'
            ], 404);
        }

        $transactions = $account->sentTransactions()->orWhere('receiver_account_id', $id)->get();

        return response()->json([
            'success' => true,
            'data'    => $transactions
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Greška pri učitavanju transakcija.',
            'error'   => $e->getMessage()
        ], 500);
    }
}
}