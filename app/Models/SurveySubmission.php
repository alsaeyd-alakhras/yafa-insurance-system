<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveySubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_data',
        'national_id',
        'status',
        'reviewed_by',
        'reviewed_at',
        'created_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
        ];
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_employee_id');
    }
}
