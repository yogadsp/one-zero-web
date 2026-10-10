<?php

namespace App\Models\Transaction;

use App\Models\Learning\LearningMaterial;
use App\Traits\Uuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, Uuid;

    public $incrementing = false;

    protected $keyType = 'string';

    // protected $dates = ['created_at', 'maximum_payment_time', 'date'];

    protected $fillable = [
        'id',
        'user_id',
        'exam_id',
        'learning_material_id',
        'code',
        'voucher_activated',
        'voucher_used',
        'total_purchases',
        'maximum_payment_time',
        'transaction_status',
        'voucher_token',
    ];

    protected $casts = [
        'voucher_activated' => 'boolean',
        'voucher_used' => 'boolean',
    ];

    public static function generateCode()
    {
        $code = 'INV';
        $sequence = 1;
        $format = formatCode($code, $sequence);
        $result = null;

        while (true) {
            $query = static::withTrashed()->where('code', $format)->first();
            if (empty($query)) {
                $result = $format;
                break;
            }
            $format = formatCode($code, ++$sequence);
        }

        return $result;
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function exam()
    {
        return $this->belongsTo(\App\Models\Exam\Exam::class);
    }

    public function learningMaterial()
    {
        return $this->belongsTo(LearningMaterial::class)->withTrashed();
    }

    public function getProductTitleAttribute(): string
    {
        return (string) ($this->learningMaterial?->title ?? $this->exam?->title ?? 'Produk belajar');
    }

    public function getProductTypeAttribute(): string
    {
        return (string) ($this->learningMaterial?->type ?? 'tryout');
    }

    public function getProductCategoryNameAttribute(): string
    {
        return (string) ($this->learningMaterial?->category?->name ?? $this->exam?->category?->name ?? '-');
    }

    public function getCanBeDeletedAttribute(): bool
    {
        return in_array($this->transaction_status, ['pending', 'failed'], true)
            && ! $this->voucher_activated
            && ! $this->voucher_used;
    }

    protected $appends = [
        'product_title',
        'product_type',
        'product_category_name',
        'can_be_deleted',
    ];

    public function getDateAttribute($value)
    {
        return dateFormat($value, 'd F Y');
    }

    public function getVoucherExpiredDateAttribute($value)
    {
        return empty($value) ? '-' : dateFormat($value, 'd F Y');
    }

    public function getMaximumPaymentTimeAttribute($value)
    {
        return dateFormat($value, 'd F Y H:i');
    }

    public function getCreatedAtAttribute($value)
    {
        return dateFormat($value, 'd F Y H:i');
    }

    public function getIsExpiredAttribute()
    {
        return $this->attributes['voucher_expired_date'] < Carbon::now() ? true : false;
    }
}
