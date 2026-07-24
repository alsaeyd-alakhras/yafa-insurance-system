<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dependent extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'type', 'full_name', 'national_id', 'gender', 'parent_type'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function visitsAsPatient(): HasMany
    {
        return $this->hasMany(Visit::class, 'patient_dependent_id');
    }
}
