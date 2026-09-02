<?php

namespace App\Policies;

use App\Models\MedicalDepartment;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDepartment;

class VisitPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ?Visit $visit = null): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Visit $visit): bool
    {
        return true;
    }

    public function addDepartment(User $user, Visit $visit, MedicalDepartment $department): bool
    {
        if ($user->role !== 'department_user') {
            return true;
        }

        return $department->id === $user->medical_department_id;
    }

    public function manageDepartmentRow(User $user, Visit $visit, VisitDepartment $visitDepartment): bool
    {
        if ($user->role !== 'department_user') {
            return true;
        }

        return $visitDepartment->medical_department_id === $user->medical_department_id;
    }

    public function delete(User $user, Visit $visit): bool
    {
        if ($user->roles->where('role_name', 'visits.delete')->isNotEmpty()) {
            return true;
        }

        if ($user->roles->where('role_name', 'visits.delete-own')->isNotEmpty()) {
            return $visit->recorded_by === $user->id;
        }

        return false;
    }
}
