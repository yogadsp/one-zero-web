<?php

namespace App\Services\Learning;

use App\Models\Exam\Exam;
use App\Models\Learning\LearningMaterial;
use App\Models\Transaction\Transaction;
use App\Models\User;

class LearningAccessService
{
    public function canAccess(User $user, Exam $exam): bool
    {
        return Transaction::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('transaction_status', 'done')
            ->where('voucher_activated', true)
            ->exists();
    }

    public function canAccessMaterial(User $user, LearningMaterial $material): bool
    {
        $directAccess = Transaction::query()
            ->where('user_id', $user->id)
            ->where('learning_material_id', $material->id)
            ->where('transaction_status', 'done')
            ->where('voucher_activated', true)
            ->exists();

        if ($directAccess || ! $material->exam_id || ! $material->standalone_since) {
            return $directAccess;
        }

        return Transaction::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $material->exam_id)
            ->where('transaction_status', 'done')
            ->where('voucher_activated', true)
            ->where('created_at', '<=', $material->standalone_since)
            ->exists();
    }
}
