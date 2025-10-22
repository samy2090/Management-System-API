<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User;


    /**
     * Find user by ID
     */
    public function findById(int $id): ?User;

    /**
     * Get all users
     */
    public function getAll(): Collection;


    /**
     * Delete user
     */
    public function delete(User $user): bool;
}