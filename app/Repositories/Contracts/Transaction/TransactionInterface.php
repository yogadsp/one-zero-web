<?php

namespace App\Repositories\Contracts\Transaction;

interface TransactionInterface
{
    public function all();

    public function find($id);

    public function findForUser($id, $userId);

    public function create(array $data);

    public function update(array $data, $id);

    public function delete($id);
}
