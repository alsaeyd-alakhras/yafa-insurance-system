<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationUnit extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'level'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Resolve this unit's ancestor chain (center, department, section) regardless of its own level.
     *
     * @return array{center: ?self, department: ?self, section: ?self}
     */
    public function ancestryChain(): array
    {
        $chain = [$this];
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            $chain[] = $current;
        }

        // $chain is ordered from this unit up to the root; reverse so index 0 = level 1 (center).
        $chain = array_reverse($chain);

        return [
            'center' => $chain[0] ?? null,
            'department' => $chain[1] ?? null,
            'section' => $chain[2] ?? null,
        ];
    }
}
