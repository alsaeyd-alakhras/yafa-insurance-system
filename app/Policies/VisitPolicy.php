<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Visit $visit): bool
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
