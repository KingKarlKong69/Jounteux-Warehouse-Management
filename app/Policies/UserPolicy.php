<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only admins can view the user management listing.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    /**
     * Only admins can view a specific user.
     */
    public function view(User $authUser, User $user): bool
    {
        return $authUser->isAdmin();
    }

    /**
     * Only admins can create users.
     */
    public function create(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    /**
     * Only admins can update users.
     */
    public function update(User $authUser, User $user): bool
    {
        return $authUser->isAdmin();
    }

    /**
     * Only admins can delete users.
     * Cannot delete self. Cannot delete last admin.
     */
    public function delete(User $authUser, User $user): bool
    {
        if (!$authUser->isAdmin()) {
            return false;
        }

        // Cannot delete yourself
        if ($authUser->id === $user->id) {
            return false;
        }

        // Cannot delete the last admin
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return false;
        }

        return true;
    }

    /**
     * Only admins can block users.
     * Cannot block self.
     */
    public function block(User $authUser, User $user): bool
    {
        if (!$authUser->isAdmin()) {
            return false;
        }

        return $authUser->id !== $user->id;
    }

    /**
     * Only admins can unblock users.
     */
    public function unblock(User $authUser, User $user): bool
    {
        return $authUser->isAdmin();
    }
}
