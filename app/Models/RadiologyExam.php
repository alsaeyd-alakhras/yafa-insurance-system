<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiologyExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'name',
        'price',
        'discount_amount',
        'is_active',
    ];
}
