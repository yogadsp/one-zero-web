<?php

namespace App\Http\Controllers\User\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\VoucherActivationRequest;
use App\Services\Contracts\Transaction\TransactionInterface as TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VoucherActivationController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
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
        return inertia('User/Transaction/VoucherActivation/Index', [
            'voucherActivations' => $this->transactionService->getAllVoucherActivatedPaginatedWithParamsByUser($request),
        ]);
    }

    public function store(VoucherActivationRequest $request)
    {
        try {
            $this->transactionService->activateVoucherForUser($request->validated('token'), $request->user());
            $request->session()->flash('success', 'Voucher berhasil diaktifkan. Produk belajar sudah masuk ke akun Anda.');

            return $request->header('X-Inertia')
                ? Inertia::location(route('user.voucher-activations.index'))
                : redirect()->back();
        } catch (\DomainException $exception) {
            $request->session()->flash('error', $exception->getMessage());

            return $request->header('X-Inertia')
                ? Inertia::location(route('user.voucher-activations.index'))
                : redirect()->back();
        }
    }
}
