<?php

namespace App\Models\Learning;

use App\Models\Exam\Exam;
use App\Models\MasterData\Category;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes, Uuid;

    public const TYPE_VIDEO = 'video';

    public const TYPE_LIVE_CLASS = 'live_class';

    public const TYPES = [
        self::TYPE_VIDEO,
        self::TYPE_LIVE_CLASS,
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'exam_id',
        'category_id',
        'type',
        'title',
        'price',
        'standalone_since',
        'description',
        'url',
        'starts_at',
        'ends_at',
        'is_published',
        'position',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_published' => 'boolean',
        'position' => 'integer',
        'price' => 'integer',
        'standalone_since' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction\Transaction::class);
    }
}
