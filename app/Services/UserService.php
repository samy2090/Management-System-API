<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Get all users
     */
    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAll();
    }

    /**
     * Update user profile
     */
    public function updateUser(User $user, array $data): User
    {
        // Only allow certain fields to be updated
        $allowedFields = ['name', 'email'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        return $this->userRepository->update($user, $updateData);
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user): bool
    {
        return $this->userRepository->delete($user);
    }

    /**
     * Check if user exists by email
     */
    public function userExistsByEmail(string $email): bool
    {
        return $this->userRepository->findByEmail($email) !== null;
    }
}