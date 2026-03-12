<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceWallet;
use App\Models\FinanceCategory;
use App\Models\FinanceMovement;
use App\Services\AuditLogger;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminFinanceController extends Controller
{
    public function __construct(
        protected FinanceService $financeService
    ) {}

    /**
     * Display a listing of global finances and categories.
     */
    public function index(Request $request): Response
    {
        $wallets = FinanceWallet::all();
        $categories = FinanceCategory::all();

        $query = FinanceMovement::with(['category', 'creator', 'splits.user']);

        // Filters
        if ($request->filled('wallet')) {
            $query->where('wallet_id', $request->wallet);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString()->through(fn ($m) => [
            'id' => $m->id,
            'date' => $m->created_at->format('d/m/Y H:i'),
            'type' => $m->type->value,
            'amount' => $m->amount,
            'wallet_id' => $m->wallet_id,
            'category_id' => $m->category_id,
            'category' => $m->category?->name ?? 'Sin categoría',
            'description' => $m->description,
            'creator' => $m->creator?->name ?? 'Sistema',
            'splits' => $m->splits->map(fn($s) => [
                'user' => $s->user?->name,
                'share' => $s->share
            ])
        ]);

        return Inertia::render('Admin/Finance/Index', [
            'wallets' => $wallets,
            'categories' => $categories,
            'movements' => $movements,
            'users' => \App\Models\User::where('role', 'miembro_gx')->orderBy('name')->get(['id', 'name', 'blader_name']),
            'filters' => $request->only(['wallet', 'type', 'category'])
        ]);
    }

    /**
     * Store a new finance category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:finance_categories,name',
            'description' => 'nullable|string|max:255',
        ]);

        $category = FinanceCategory::create($validated);

        AuditLogger::log('create_category', 'FinanceCategory', $category->id, $validated);

        return back()->with('success', 'Categoría creada correctamente.');
    }

    public function updateCategory(Request $request, FinanceCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:finance_categories,name,'.$category->id,
            'description' => 'nullable|string|max:255',
        ]);

        $category->update($validated);

        AuditLogger::log('update_category', 'FinanceCategory', $category->id, $validated);

        return back()->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroyCategory(FinanceCategory $category)
    {
        if ($category->movements()->exists()) {
            return back()->with('error', 'No se puede eliminar una categoría que tiene movimientos asociados.');
        }
        $categoryId = $category->id;
        $categoryName = $category->name;
        $category->delete();

        AuditLogger::log('delete_category', 'FinanceCategory', $categoryId, ['name' => $categoryName]);

        return back()->with('success', 'Categoría eliminada correctamente.');
    }

    /**
     * Store a new wallet.
     */
    public function storeWallet(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:finance_wallets,name',
            'user_id' => 'nullable|exists:users,id|unique:finance_wallets,user_id',
            'balance' => 'required|numeric',
        ]);

        $wallet = FinanceWallet::create($validated);

        AuditLogger::log('create_wallet', 'FinanceWallet', $wallet->id, $validated);

        return back()->with('success', 'Fondo creado correctamente.');
    }

    public function updateWallet(Request $request, FinanceWallet $wallet)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:finance_wallets,name,'.$wallet->id,
            'user_id' => 'nullable|exists:users,id|unique:finance_wallets,user_id,'.$wallet->id,
            'balance' => 'required|numeric',
        ]);

        $wallet->update($validated);

        AuditLogger::log('update_wallet', 'FinanceWallet', $wallet->id, $validated);

        return back()->with('success', 'Fondo actualizado correctamente.');
    }

    public function destroyWallet(FinanceWallet $wallet)
    {
        if ($wallet->movements()->exists()) {
            return back()->with('error', 'No se puede eliminar una billetera con movimientos existentes.');
        }
        $walletId = $wallet->id;
        $walletName = $wallet->name;
        $wallet->delete();

        AuditLogger::log('delete_wallet', 'FinanceWallet', $walletId, ['name' => $walletName]);

        return back()->with('success', 'Fondo eliminado correctamente.');
    }

    /**
     * Store a new movement as Admin.
     */
    public function storeMovement(Request $request)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:finance_wallets,id',
            'category_id' => 'required|exists:finance_categories,id',
            'type' => 'required|in:ingreso,gasto',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'splits' => 'nullable|array',
            'splits.*.user_id' => 'required|exists:users,id',
            'splits.*.share' => 'required|numeric|min:0.01',
        ]);

        $movement = $this->financeService->addMovement($validated, auth()->id());

        AuditLogger::log('create_movement', 'FinanceMovement', $movement->id, $validated);

        return back()->with('success', 'Movimiento registrado correctamente.');
    }

    /**
     * Update an existing movement as Admin (Adjust balance).
     */
    public function updateMovement(Request $request, FinanceMovement $movement)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:finance_categories,id',
            'type' => 'required|in:ingreso,gasto',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $wallet = $movement->wallet;

        // Rollback old movement effect
        if ($movement->type->value === 'ingreso') {
            $wallet->decrement('balance', $movement->amount);
        } else {
            $wallet->increment('balance', $movement->amount);
        }

        // Apply new movement effect
        if ($validated['type'] === 'ingreso') {
            $wallet->increment('balance', $validated['amount']);
        } else {
            $wallet->decrement('balance', $validated['amount']);
        }

        $movement->update($validated);

        AuditLogger::log('update_movement', 'FinanceMovement', $movement->id, $validated);

        return back()->with('success', 'Movimiento actualizado correctamente.');
    }

    /**
     * Destroy a movement as Admin (Rollback balance).
     */
    public function destroyMovement(FinanceMovement $movement)
    {
        // Simple rollback logic
        $wallet = $movement->wallet;
        
        if ($movement->type->value === 'ingreso') {
            $wallet->decrement('balance', $movement->amount);
        } else {
            $wallet->increment('balance', $movement->amount);
        }

        // Delete associated splits and the movement itself
        $movement->splits()->delete();
        $movement->delete();

        return back()->with('success', 'Movimiento eliminado y balance restaurado.');
    }
}
