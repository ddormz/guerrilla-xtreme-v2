<?php

namespace App\Services;

use App\Models\FinanceWallet;
use App\Models\FinanceMovement;
use App\Models\FinanceCategory;
use App\Models\FinanceSplit;
use App\Enums\FinanceType;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    /**
     * Get wallets accessible by the user.
     */
    public function getWallets(?int $userId = null)
    {
        return FinanceWallet::where('user_id', $userId)
            ->orWhereNull('user_id') // Common community wallets
            ->get();
    }

    /**
     * Record a financial movement (income/expense).
     * SOLID: SRP - Handles ledger logic.
     */
    public function addMovement(array $data, int $createdBy): FinanceMovement
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $wallet = FinanceWallet::findOrFail($data['wallet_id']);
            $type = FinanceType::from($data['type']);
            
            // 1. Create movement
            $movement = FinanceMovement::create([
                'wallet_id' => $wallet->id,
                'category_id' => $data['category_id'],
                'type' => $type,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? '',
                'created_by' => $createdBy,
            ]);

            // 2. Update wallet balance
            $delta = $type->sign() * $data['amount'];
            $wallet->increment('balance', $delta);

            // 3. Handle splits (if provided)
            if (!empty($data['splits'])) {
                foreach ($data['splits'] as $split) {
                    FinanceSplit::create([
                        'movement_id' => $movement->id,
                        'user_id' => $split['user_id'],
                        'share' => $split['share'],
                    ]);
                }
            }

            return $movement;
        });
    }

    public function getCategories()
    {
        return FinanceCategory::all();
    }
}
