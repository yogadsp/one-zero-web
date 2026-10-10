<?php

namespace App\Http\Controllers\Admin;

use App\Charts\VisitorCounterChart;
use App\Http\Controllers\Controller;
use App\Http\Requests\EditorImageUploadRequest;
use App\Services\Content\EditorImageStorageService;
use App\Services\Contracts\Transaction\TransactionInterface as TransactionService;
use App\Services\Contracts\User\UserInterface as UserService;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $transactionService;

    protected $userService;

    public function __construct(TransactionService $transactionService, UserService $userService)
    {
        $this->transactionService = $transactionService;
        $this->userService = $userService;
    }

    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $chart = new VisitorCounterChart(new LarapexChart);

        return inertia('Admin/Dashboard/Index', [
            'totalStudent' => number_format($this->userService->getTotalStudent()),
            'totalStudentActive' => number_format($this->userService->getTotalStudentActive()),
            'totalStudentNonActive' => number_format($this->userService->getTotalStudentNonActive()),
            'totalStudentMember' => number_format($this->userService->getTotalStudentMember()),

            'totalTransactionToday' => number_format($this->transactionService->getTotalTransactionToday()),
            'totalTransactionMonthly' => number_format($this->transactionService->getTotalTransactionMonthly()),
            'totalTransactionYearly' => number_format($this->transactionService->getTotalTransactionYearly()),

            'totalTransactionPending' => number_format($this->transactionService->getTotalTransactionPending()),
            'totalTransactionPaid' => number_format($this->transactionService->getTotalTransactionPaid()),
            'totalTransactionDone' => number_format($this->transactionService->getTotalTransactionDone()),
            'totalTransactionFailed' => number_format($this->transactionService->getTotalTransactionFailed()),

            'transactions' => $this->transactionService->getTransactionMonthly(),
            // 'chart' => $chart->build()
        ]);
    }

    public function upload(EditorImageUploadRequest $request, EditorImageStorageService $imageStorage)
    {
        return response()->json([
            'location' => $imageStorage->storeTemporarily($request->file('file'), $request->user()->getAuthIdentifier()),
        ]);
    }

    public function uploadAnnouncement(Request $request)
    {
        $fileName = Carbon::now()->format('Ymdhis').$request->file('file')->getClientOriginalName();
        $path = $request->file('file')->storeAs('/upload_files/announcements', $fileName, 'public');

        return response()->json(['location' => "/storage/$path"]);
    }
}
