<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitDepartment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'medical_department_id',
        'applied_discount_percentage',
        'applied_max_discount_amount',
        'amount_before_discount',
        'amount_after_discount',
        'added_at',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function medicalDepartment(): BelongsTo
    {
        return $this->belongsTo(MedicalDepartment::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function calculateAmountAfterDiscount(): ?float
    {
        if ($this->amount_before_discount === null) {
            return null;
        }

        $discountAmount = (float) $this->amount_before_discount * ((float) $this->applied_discount_percentage / 100);

        if ($this->applied_max_discount_amount !== null) {
            $discountAmount = min($discountAmount, (float) $this->applied_max_discount_amount);
        }

        return round((float) $this->amount_before_discount - $discountAmount, 2);
    }
}
