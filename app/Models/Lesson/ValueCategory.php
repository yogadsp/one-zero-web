<?php

namespace App\Models\Lesson;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ValueCategory extends Model
{
    public const ASSESSMENT_PERCENTAGE = 1;

    public const ASSESSMENT_CORRECT_COUNT = 2;

    public const ASSESSMENT_OPTION_POINTS = 3;

    use HasFactory,SoftDeletes,Uuid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'category_id',
        'name',
        'assessment_type',
        'section',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function category()
    {
        return $this->belongsTo(\App\Models\MasterData\Category::class)->withTrashed();
    }
}
