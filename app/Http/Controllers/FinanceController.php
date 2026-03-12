<?php

namespace App\Http\Controllers;

use App\Services\FinanceService;
use App\Models\FinanceMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __construct(
        protected FinanceService $financeService
    ) {}

    public function index(): Response
    {
        $wallets = $this->financeService->getWallets(auth()->id());
        $categories = $this->financeService->getCategories();
        
        $movements = FinanceMovement::with(['category', 'creator'])
            ->whereIn('wallet_id', $wallets->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'date' => $m->created_at->format('d/m/Y'),
                'type' => $m->type->value,
                'amount' => $m->amount,
                'category' => $m->category?->name ?? 'Sin categoría',
                'description' => $m->description,
                'creator' => $m->creator?->name ?? 'Desconocido',
            ]);

        return Inertia::render('Finance/Index', [
            'wallets' => $wallets,
            'categories' => $categories,
            'movements' => $movements,
        ]);
    }
}
