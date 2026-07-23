<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'patient_employee_id',
        'patient_dependent_id',
        'clinic_id',
        'visit_date',
        'recorded_by',
        'last_updated_by',
        'total_before_discount',
        'total_after_discount',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function patientEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'patient_employee_id');
    }

    public function patientDependent(): BelongsTo
    {
        return $this->belongsTo(Dependent::class, 'patient_dependent_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function visitDepartments(): HasMany
    {
        return $this->hasMany(VisitDepartment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function patient(): Employee|Dependent|null
    {
        return $this->patient_employee_id ? $this->patientEmployee : $this->patientDependent;
    }

    public function recalculateTotals(): void
    {
        $totals = $this->visitDepartments()
            ->whereNotNull('amount_before_discount')
            ->get()
            ->reduce(function (array $carry, VisitDepartment $department) {
                $carry['before'] += (float) $department->amount_before_discount;
                $carry['after'] += (float) $department->calculateAmountAfterDiscount();

                return $carry;
            }, ['before' => 0.0, 'after' => 0.0]);

        $hasAmounts = $this->visitDepartments()->whereNotNull('amount_before_discount')->exists();

        $this->update([
            'total_before_discount' => $hasAmounts ? $totals['before'] : null,
            'total_after_discount' => $hasAmounts ? $totals['after'] : null,
        ]);
    }
}
