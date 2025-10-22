<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }


    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Get all users
     */
    public function getAll(): Collection
    {
        return User::all();
    }


    /**
     * Delete user
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }
}