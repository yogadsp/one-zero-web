<?php

namespace App\Services\Transaction;

use App\Models\Transaction\Transaction;

class TransactionStatusNotificationService
{
    public function send(Transaction $transaction): void
    {
        $transaction->loadMissing(['exam.category', 'learningMaterial.category', 'user.student']);
        $user = $transaction->user;

        if (! $user?->student?->phone_number) {
            return;
        }

        $formattedPrice = 'Rp. '.number_format($transaction->total_purchases, 0, ',', '.');
        $message = match ($transaction->transaction_status) {
            'paid' => "*[TRANSAKSI ONE ZERO LEARNING]*\n\n_Halo, berikut detail pesanan Anda._\n\nNama: {$user->name}\nEmail: {$user->email}\nNomor Transaksi: {$transaction->code}\nProduk: {$transaction->product_title}\nTotal Pembayaran: *{$formattedPrice}*\n\n*STATUS: PEMBAYARAN TELAH DITERIMA*\n\n*_SILAKAN TUNGGU HINGGA TOKEN DIKIRIMKAN KEPADA ANDA_*\n\n_Terima kasih telah belajar bersama One Zero Learning._",
            'failed' => "*[TRANSAKSI ONE ZERO LEARNING]*\n\n_Halo, berikut detail pesanan Anda._\n\nNama: {$user->name}\nEmail: {$user->email}\nNomor Transaksi: {$transaction->code}\nProduk: {$transaction->product_title}\nTotal Pembayaran: *{$formattedPrice}*\nMaksimal Pembayaran: {$transaction->maximum_payment_time}\n\n*STATUS: TRANSAKSI DIBATALKAN*\n\n*_SILAKAN LAKUKAN TRANSAKSI ULANG_*\n\n_Terima kasih telah belajar bersama One Zero Learning._",
            'done' => "*[TRANSAKSI ONE ZERO LEARNING]*\n\n_Halo, berikut detail pesanan Anda._\n\nNama: {$user->name}\nEmail: {$user->email}\nNomor Transaksi: {$transaction->code}\nProduk: {$transaction->product_title}\nTotal Pembayaran: *{$formattedPrice}*\n\n*TOKEN ANDA*\n*{$transaction->voucher_token}*\n\n_Salin token lalu aktifkan melalui menu *AKTIVASI VOUCHER*._\n\n_Terima kasih telah belajar bersama One Zero Learning._",
            default => null,
        };

        if (! $message) {
            return;
        }

        try {
            sendWhatsappNotification($user->student->phone_number, $message);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
