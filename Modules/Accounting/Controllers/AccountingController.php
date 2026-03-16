<?php

namespace Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function index()
    {
        $restaurant = auth()->user()->restaurant;
        
        $stats = [
            'total_accounts' => DB::table('chart_of_accounts')->where('restaurant_id', $restaurant->id)->count(),
            'journal_entries' => DB::table('journal_entries')->where('restaurant_id', $restaurant->id)->count(),
            'current_assets' => $this->getAccountBalance($restaurant->id, 'asset'),
            'current_liabilities' => $this->getAccountBalance($restaurant->id, 'liability')
        ];

        return response()->json($stats);
    }

    public function chartOfAccounts()
    {
        $restaurant = auth()->user()->restaurant;
        
        $accounts = DB::table('chart_of_accounts')
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return response()->json($accounts);
    }

    public function createAccount(Request $request)
    {
        $validated = $request->validate([
            'account_code' => 'required|string|unique:chart_of_accounts,account_code',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'account_subtype' => 'required|string',
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id',
            'opening_balance' => 'nullable|numeric'
        ]);

        DB::table('chart_of_accounts')->insert([
            'restaurant_id' => auth()->user()->restaurant->id,
            ...$validated,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    public function trialBalance()
    {
        $restaurant = auth()->user()->restaurant;
        
        $trialBalance = DB::table('chart_of_accounts as coa')
            ->leftJoin('journal_entry_lines as jel', 'coa.id', '=', 'jel.account_id')
            ->leftJoin('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->where('coa.restaurant_id', $restaurant->id)
            ->where('je.status', 'posted')
            ->groupBy('coa.id', 'coa.account_name', 'coa.account_code')
            ->selectRaw('
                coa.account_code,
                coa.account_name,
                SUM(jel.debit_amount) as total_debit,
                SUM(jel.credit_amount) as total_credit,
                (SUM(jel.debit_amount) - SUM(jel.credit_amount)) as balance
            ')
            ->get();

        return response()->json($trialBalance);
    }

    private function getAccountBalance($restaurantId, $accountType)
    {
        return DB::table('chart_of_accounts as coa')
            ->leftJoin('journal_entry_lines as jel', 'coa.id', '=', 'jel.account_id')
            ->leftJoin('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->where('coa.restaurant_id', $restaurantId)
            ->where('coa.account_type', $accountType)
            ->where('je.status', 'posted')
            ->sum(DB::raw('jel.debit_amount - jel.credit_amount'));
    }
}