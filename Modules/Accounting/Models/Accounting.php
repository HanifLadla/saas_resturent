<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Accounting extends Model
{
    public static function createJournalEntry($restaurantId, $data)
    {
        return DB::transaction(function () use ($restaurantId, $data) {
            $entry = DB::table('journal_entries')->insertGetId([
                'restaurant_id' => $restaurantId,
                'entry_number' => self::generateEntryNumber(),
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'total_debit' => collect($data['lines'])->sum('debit_amount'),
                'total_credit' => collect($data['lines'])->sum('credit_amount'),
                'status' => 'draft',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($data['lines'] as $line) {
                DB::table('journal_entry_lines')->insert([
                    'journal_entry_id' => $entry,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'],
                    'debit_amount' => $line['debit_amount'] ?? 0,
                    'credit_amount' => $line['credit_amount'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return $entry;
        });
    }

    public static function getFinancialStatements($restaurantId, $startDate, $endDate)
    {
        $accounts = DB::table('chart_of_accounts as coa')
            ->leftJoin('journal_entry_lines as jel', 'coa.id', '=', 'jel.account_id')
            ->leftJoin('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->where('coa.restaurant_id', $restaurantId)
            ->whereBetween('je.entry_date', [$startDate, $endDate])
            ->where('je.status', 'posted')
            ->groupBy('coa.account_type', 'coa.account_name')
            ->selectRaw('
                coa.account_type,
                coa.account_name,
                SUM(jel.debit_amount - jel.credit_amount) as balance
            ')
            ->get()
            ->groupBy('account_type');

        return [
            'assets' => $accounts['asset'] ?? collect(),
            'liabilities' => $accounts['liability'] ?? collect(),
            'equity' => $accounts['equity'] ?? collect(),
            'revenue' => $accounts['revenue'] ?? collect(),
            'expenses' => $accounts['expense'] ?? collect()
        ];
    }

    private static function generateEntryNumber()
    {
        $date = now()->format('Ymd');
        $sequence = DB::table('journal_entries')->whereDate('created_at', today())->count() + 1;
        return 'JE-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}