<?php

namespace App\Services\Transaction;

use App\Models\Exam\Exam;
use App\Models\Learning\LearningMaterial;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Notifications\SendTransactionNotification;
use App\Repositories\Contracts\Transaction\TransactionInterface as TransactionRepo;
use App\Services\Contracts\Transaction\TransactionInterface;
use App\Services\Learning\LearningCatalogService;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService implements TransactionInterface
{
    protected $transactionRepo;

    public function __construct(
        TransactionRepo $transactionRepo,
        protected TransactionStatusNotificationService $statusNotificationService,
    ) {
        $this->transactionRepo = $transactionRepo;
    }

    public function all()
    {
        return $this->transactionRepo->all();
    }

    public function getAllSimplePaginatedWithParams($params, $limit = 10)
    {
        return $this->transactionRepo->getAllSimplePaginatedWithParams($params, $limit);
    }

    public function getAllPaginatedWithParams($params, $limit = 10)
    {
        return $this->transactionRepo->getAllPaginatedWithParams($params, $limit);
    }

    /**
     * Find data by id
     *
     * @param  array  $columns
     * @return mixed
     */
    public function find($id)
    {
        return $this->transactionRepo->find($id);
    }

    public function findForUser($id, $userId)
    {
        return $this->transactionRepo->findForUser($id, $userId);
    }

    public function activateVoucherForUser(string $token, User $user)
    {
        return DB::transaction(function () use ($token, $user) {
            $transaction = Transaction::query()
                ->with(['exam', 'learningMaterial.category'])
                ->where('user_id', $user->id)
                ->where('voucher_token', trim($token))
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                throw new \DomainException('Voucher tidak ditemukan untuk akun Anda.');
            }

            if ($transaction->transaction_status !== 'done') {
                throw new \DomainException('Voucher belum dapat diaktifkan karena transaksi belum selesai.');
            }

            if ($transaction->voucher_activated) {
                throw new \DomainException('Voucher ini sudah pernah diaktifkan.');
            }

            $transaction->update([
                'voucher_used' => 1,
                'voucher_activated' => 1,
            ]);

            if ($transaction->exam_id) {
                $student = $user->student()->lockForUpdate()->first();
                if (! $student) {
                    throw new \DomainException('Profil siswa belum tersedia. Hubungi admin.');
                }

                $memberAccess = collect($student->member_access ?? [])
                    ->filter(fn ($access) => isset($access['category_id']))
                    ->keyBy('category_id');
                $memberAccess->put($transaction->exam->category_id, [
                    'category_id' => $transaction->exam->category_id,
                ]);

                $student->update([
                    'member_access' => $memberAccess->values()->all(),
                    'is_member' => 1,
                ]);
            }

            return $transaction->refresh();
        });
    }

    /**
     * @return mixed
     *
     * @throws \Throwable
     */
    public function create($request)
    {
        $permissions = DB::transaction(function () use ($request) {
            $input = $request->all();

            return $this->transactionRepo->create($input);
        });

        return $permissions;
    }

    /**
     * @param $request
     * @return mixed
     *
     * @throws \Throwable
     */
    public function update(array $data, $id)
    {
        [$transaction, $statusChanged] = DB::transaction(function () use ($data, $id) {
            $transaction = Transaction::query()->lockForUpdate()->find($id);

            if (! $transaction) {
                throw new \DomainException('Transaksi tidak ditemukan.');
            }

            $currentStatus = $transaction->transaction_status;
            $nextStatus = $data['transaction_status'];
            $allowedTransitions = [
                'pending' => ['pending', 'paid', 'done', 'failed'],
                'paid' => ['paid', 'done', 'failed'],
                'done' => ['done'],
                'failed' => ['failed'],
            ];

            if (! in_array($nextStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
                throw new \DomainException("Status transaksi tidak dapat diubah dari {$currentStatus} menjadi {$nextStatus}.");
            }

            $statusChanged = $currentStatus !== $nextStatus;
            $attributes = ['transaction_status' => $nextStatus];

            if ($nextStatus === 'done' && ! $transaction->voucher_token) {
                $attributes['voucher_token'] = $this->generateUniqueVoucherToken();
            }

            $transaction->update($attributes);

            return [$transaction->fresh(['exam', 'user.student']), $statusChanged];
        });

        if ($statusChanged) {
            $this->statusNotificationService->send($transaction);
        }

        return $transaction;
    }

    private function generateUniqueVoucherToken(): string
    {
        do {
            $token = strtoupper(Str::random(15));
        } while (Transaction::query()->where('voucher_token', $token)->exists());

        return $token;
    }

    public function deleteForAdmin(string $id, User $actor): Transaction
    {
        return $this->deleteTransaction($id, $actor, 'admin');
    }

    public function deleteForUser(string $id, User $actor): Transaction
    {
        return $this->deleteTransaction($id, $actor, 'user', $actor->id);
    }

    public function createTransacationWithExamId($id)
    {
        return $this->purchase(Auth::user(), LearningCatalogService::TYPE_TRYOUT, $id);
    }

    public function purchase(User $user, string $productType, string $productId)
    {
        $product = $this->resolvePurchasable($productType, $productId);

        $transaction = DB::transaction(function () use ($user, $product) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (Transaction::query()->where('user_id', $user->id)->where('transaction_status', 'pending')->exists()) {
                throw new \DomainException('Anda memiliki transaksi yang masih menunggu pembayaran. Selesaikan transaksi tersebut atau hubungi admin.');
            }

            $targetColumn = $product instanceof Exam ? 'exam_id' : 'learning_material_id';
            $ownedTransaction = Transaction::query()
                ->where('user_id', $user->id)
                ->where($targetColumn, $product->id)
                ->where('transaction_status', 'done')
                ->latest('created_at')
                ->first();

            if ($ownedTransaction?->voucher_activated) {
                throw new \DomainException('Produk ini sudah aktif di akun Anda.');
            }

            if ($ownedTransaction) {
                throw new \DomainException('Pembelian ini sudah selesai. Aktifkan voucher yang telah diberikan.');
            }

            $price = (int) $product->price;
            $attributes = [
                'user_id' => $user->id,
                'exam_id' => $product instanceof Exam ? $product->id : null,
                'learning_material_id' => $product instanceof LearningMaterial ? $product->id : null,
                'code' => Transaction::generateCode(),
                'date' => Carbon::now(),
                'voucher_token' => $price === 0 ? strtoupper(Str::random(15)) : null,
                'total_purchases' => $price,
                'maximum_payment_time' => Carbon::now()->addDays(2),
                'transaction_status' => $price === 0 ? 'done' : 'pending',
            ];

            return $this->transactionRepo->create($attributes);
        });

        $transaction->load(['exam.category', 'learningMaterial.category', 'user.student']);
        $this->sendPurchaseNotifications($transaction);

        return $transaction;
    }

    private function resolvePurchasable(string $productType, string $productId): Exam|LearningMaterial
    {
        if ($productType === LearningCatalogService::TYPE_TRYOUT) {
            $exam = Exam::query()->find($productId);
            if (! $exam) {
                throw new \DomainException('Tryout tidak ditemukan.');
            }

            return $exam;
        }

        if (! in_array($productType, LearningMaterial::TYPES, true)) {
            throw new \DomainException('Jenis produk belajar tidak valid.');
        }

        $material = LearningMaterial::query()
            ->where('type', $productType)
            ->where('is_published', true)
            ->whereNotNull('price')
            ->find($productId);

        if (! $material) {
            throw new \DomainException('Produk belajar belum tersedia untuk dibeli.');
        }

        return $material;
    }

    private function deleteTransaction(
        string $id,
        User $actor,
        string $source,
        ?string $ownerId = null,
    ): Transaction {
        return DB::transaction(function () use ($id, $actor, $source, $ownerId) {
            $query = Transaction::query()->whereKey($id);

            if ($ownerId !== null) {
                $query->where('user_id', $ownerId);
            }

            $transaction = $query->lockForUpdate()->firstOrFail();

            if (! $transaction->can_be_deleted) {
                throw new \DomainException('Hanya transaksi pending atau gagal yang belum menghasilkan hak akses yang dapat dihapus.');
            }

            $transaction->forceFill([
                'deleted_by' => $actor->id,
                'deletion_source' => $source,
            ])->save();
            $transaction->delete();

            return $transaction;
        });
    }

    private function sendPurchaseNotifications(Transaction $transaction): void
    {
        $user = $transaction->user;
        $isFree = (int) $transaction->total_purchases === 0;
        $paymentInformation = $isFree
            ? "\n\n*TOKEN ANDA*\n*{$transaction->voucher_token}*\n\n_Salin token lalu aktifkan melalui menu *AKTIVASI VOUCHER*._"
            : "\nMaksimal Pembayaran: {$transaction->maximum_payment_time}\n\n*_Token voucher dikirim setelah pembayaran dikonfirmasi. Silakan kirim nomor transaksi dan bukti pembayaran ke WhatsApp admin._*";
        $message = "*[TRANSAKSI ONE ZERO LEARNING]*\n\n_Halo, berikut detail pesanan Anda._\n\nNama: {$user->name}\nEmail: {$user->email}\n- - - - - - - - - - - - - - - - - - - - - - - - - - - -\nNomor Transaksi: {$transaction->code}\nProduk: {$transaction->product_title}\nTotal Pembayaran: *{$this->formatPrice($transaction->total_purchases)}*{$paymentInformation}\n\n_Terima kasih telah belajar bersama One Zero Learning._";

        try {
            if ($user->student?->phone_number) {
                sendWhatsappNotification($user->student->phone_number, $message);
            }

            $admin = User::find('20b2a4122c614bb68e41b1a6f3f37780')
                ?? User::query()->where('level', 1)->first();
            $admin?->notify(new SendTransactionNotification($transaction));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function getAllPaginatedWithParamsByUser($params, $limit = 10)
    {
        return $this->transactionRepo->getAllPaginatedWithParamsByUser($params, $limit);
    }

    public function getAllVoucherActivatedPaginatedWithParamsByUser($params, $limit = 10)
    {
        return $this->transactionRepo->getAllVoucherActivatedPaginatedWithParamsByUser($params, $limit);
    }

    public function getSummaryTransactionByUser($limit = 5)
    {
        return $this->transactionRepo->getSummaryTransactionByUser($limit);
    }

    public function getTotalTransactionToday()
    {
        return $this->transactionRepo->getTotalTransactionToday();
    }

    public function getTotalTransactionMonthly()
    {
        return $this->transactionRepo->getTotalTransactionMonthly();
    }

    public function getTotalTransactionYearly()
    {
        return $this->transactionRepo->getTotalTransactionYearly();
    }

    public function getTotalTransactionPending()
    {
        return $this->transactionRepo->getTotalTransactionPending();
    }

    public function getTotalTransactionPaid()
    {
        return $this->transactionRepo->getTotalTransactionPaid();
    }

    public function getTotalTransactionDone()
    {
        return $this->transactionRepo->getTotalTransactionDone();
    }

    public function getTotalTransactionFailed()
    {
        return $this->transactionRepo->getTotalTransactionFailed();
    }

    public function getTransactionMonthly($limit = 10)
    {
        return $this->transactionRepo->getTransactionMonthly($limit);
    }

    public function checkTransactionPendingByUser()
    {
        return $this->transactionRepo->checkTransactionPendingByUser();
    }

    public function formatPrice($price)
    {
        return 'Rp. '.number_format($price, 0, ',', '.');
    }
}
