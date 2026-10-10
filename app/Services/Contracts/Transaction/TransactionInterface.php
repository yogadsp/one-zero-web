<?php

namespace App\Services\Contracts\Transaction;

use App\Models\Transaction\Transaction;
use App\Models\User;

interface TransactionInterface
{
    public function find($id);

    public function findForUser($id, $userId);

    public function activateVoucherForUser(string $token, User $user);

    public function purchase(User $user, string $productType, string $productId);

    public function create($request);

    public function update(array $data, $id);

    public function deleteForAdmin(string $id, User $actor): Transaction;

    public function deleteForUser(string $id, User $actor): Transaction;
}
