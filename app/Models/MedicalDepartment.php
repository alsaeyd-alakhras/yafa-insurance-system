<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalDepartment extends Model
{
    use HasFactory;

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['name', 'discount_percentage', 'is_active'];
}
