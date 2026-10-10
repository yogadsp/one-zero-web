<?php

namespace App\Http\Controllers\User\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Learning\LearningMaterial;
use App\Services\Contracts\Transaction\BankInterface as BankService;
use App\Services\Contracts\Transaction\TransactionInterface as TransactionService;
use App\Services\Learning\LearningCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    protected $transactionService;

    protected $bankService;

    public function __construct(TransactionService $transactionService, BankService $bankService)
    {
        $this->transactionService = $transactionService;
        $this->bankService = $bankService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return inertia('User/Transaction/Transaction/Index', [
            'transactions' => $this->transactionService->getAllPaginatedWithParamsByUser($request),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $transaction = $this->transactionService->findForUser($id, request()->user()->id);
        abort_unless($transaction, 404);

        return inertia('User/Transaction/Transaction/Show', [
            'transaction' => $transaction,
            'banks' => $this->bankService->all(),
        ]);
    }

    public function buy(Request $request, $id)
    {
        return $this->purchase($request, LearningCatalogService::TYPE_TRYOUT, $id);
    }

    public function buyLearningMaterial(Request $request, $id)
    {
        $material = LearningMaterial::query()->find($id);
        abort_unless($material, 404);

        return $this->purchase($request, $material->type, $id);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $this->transactionService->deleteForUser($id, $request->user());
            $request->session()->flash('success', 'Transaksi berhasil dibatalkan. Anda dapat melakukan pembelian ulang.');

            return $request->header('X-Inertia')
                ? Inertia::location(route('user.transactions.index'))
                : redirect()->route('user.transactions.index');
        } catch (\DomainException $exception) {
            $request->session()->flash('error', $exception->getMessage());

            return $request->header('X-Inertia')
                ? Inertia::location(url()->previous())
                : redirect()->back();
        }
    }

    private function purchase(Request $request, string $productType, string $productId)
    {
        try {
            $transaction = $this->transactionService->purchase($request->user(), $productType, $productId);
            $location = route('user.transactions.show', $transaction->id);

            return $request->header('X-Inertia')
                ? Inertia::location($location)
                : redirect($location);

        } catch (\DomainException $exception) {
            $request->session()->flash('error', $exception->getMessage());

            return $request->header('X-Inertia')
                ? Inertia::location(url()->previous())
                : redirect()->back();
        } catch (\Throwable $e) {
            report($e);
            $request->session()->flash('error', 'Transaksi belum dapat dibuat. Silakan coba kembali atau hubungi admin.');

            return $request->header('X-Inertia')
                ? Inertia::location(url()->previous())
                : redirect()->back();
        }
    }
}
