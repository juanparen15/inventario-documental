<?php

namespace App\Policies;

use App\Models\User;
use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;

class WhatsappAgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, WhatsappAgent $agent): bool
    {
        return $user->hasRole('super_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, WhatsappAgent $agent): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, WhatsappAgent $agent): bool
    {
        return $user->hasRole('super_admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}
