<?php

namespace App\Http\Controllers\Admin\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\TransactionUpdateRequest;
use App\Services\Contracts\Transaction\BankInterface as BankService;
use App\Services\Contracts\Transaction\TransactionInterface as TransactionService;
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
        return inertia('Admin/Transaction/Transaction/Index', [
            'transactions' => $this->transactionService->getAllPaginatedWithParams($request),
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
        if (! $this->transactionService->find($id)) {
            return abort('404', 'uppss....');
        }

        return inertia('Admin/Transaction/Transaction/Show', [
            'transaction' => $this->transactionService->find($id),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TransactionUpdateRequest $request, $id)
    {
        try {
            $transaction = $this->transactionService->update($request->validated(), $id);
            $request->session()->flash('success', 'Status transaksi berhasil diperbarui.');
            $location = route('admin.transactions.show', $transaction->id);

            return $request->header('X-Inertia')
                ? Inertia::location($location)
                : redirect($location);
        } catch (\DomainException $exception) {
            $request->session()->flash('failed', $exception->getMessage());

            return $request->header('X-Inertia')
                ? Inertia::location(url()->previous())
                : redirect()->back()->withInput();
        } catch (\Throwable $exception) {
            report($exception);
            $request->session()->flash('failed', 'Status transaksi belum dapat diperbarui. Silakan coba kembali.');

            return $request->header('X-Inertia')
                ? Inertia::location(url()->previous())
                : redirect()->back()->withInput();
        }
    }

    public function invoice($id)
    {
        if (! $this->transactionService->find($id)) {
            return abort('404', 'uppss....');
        }

        return inertia('Admin/Transaction/Transaction/Invoice', [
            'transaction' => $this->transactionService->find($id),
            'banks' => $this->bankService->all(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $this->transactionService->deleteForAdmin($id, $request->user());
            $request->session()->flash('success', 'Transaksi berhasil dihapus. User dapat melakukan pembelian ulang.');

            return $request->header('X-Inertia')
                ? Inertia::location(route('admin.transactions.index'))
                : redirect()->route('admin.transactions.index');
        } catch (\DomainException $exception) {
            $request->session()->flash('failed', $exception->getMessage());

            return $request->header('X-Inertia')
                ? Inertia::location(url()->previous())
                : redirect()->back();
        }
    }
}
