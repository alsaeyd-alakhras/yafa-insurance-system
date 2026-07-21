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
}
