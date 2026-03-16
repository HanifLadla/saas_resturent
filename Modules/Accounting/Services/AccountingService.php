<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function createJournalEntry($data)
    {
        return DB::transaction(function () use ($data) {
            $entryId = DB::table('journal_entries')->insertGetId([
                'restaurant_id' => auth()->user()->restaurant->id,
                'entry_number' => $this->generateEntryNumber(),
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
                    'journal_entry_id' => $entryId,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'],
                    'debit_amount' => $line['debit_amount'] ?? 0,
                    'credit_amount' => $line['credit_amount'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return $entryId;
        });
    }

    public function postJournalEntry($entryId)
    {
        DB::table('journal_entries')
            ->where('id', $entryId)
            ->update(['status' => 'posted']);
    }

    private function generateEntryNumber()
    {
        $date = now()->format('Ymd');
        $sequence = DB::table('journal_entries')->whereDate('created_at', today())->count() + 1;
        return 'JE-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}