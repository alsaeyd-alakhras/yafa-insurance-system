<?php

namespace App\Rules;

use App\Models\Dependent;
use App\Models\Employee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueNationalId implements ValidationRule
{
    public function __construct(
        protected ?string $excludeTable = null,
        protected ?int $excludeId = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->existsInEmployees($value) || $this->existsInDependents($value)) {
            $fail('رقم الهوية مستخدم مسبقاً');
        }
    }

    protected function existsInEmployees(mixed $value): bool
    {
        $query = Employee::query()->where('national_id', $value);

        if ($this->excludeTable === 'employees' && $this->excludeId !== null) {
            $query->where('id', '!=', $this->excludeId);
        }

        return $query->exists();
    }

    protected function existsInDependents(mixed $value): bool
    {
        $query = Dependent::query()->where('national_id', $value);

        if ($this->excludeTable === 'dependents' && $this->excludeId !== null) {
            $query->where('id', '!=', $this->excludeId);
        }

        return $query->exists();
    }
}
