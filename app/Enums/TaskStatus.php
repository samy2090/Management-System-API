<?php

namespace App\Enums;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    /**
     * Get all status values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status labels for display
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELED => 'Canceled',
        };
    }

    /**
     * Check if the task is pending
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if the task is in progress
     */
    public function isInProgress(): bool
    {
        return $this === self::IN_PROGRESS;
    }

    /**
     * Check if the task is completed
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if the task is canceled
     */
    public function isCanceled(): bool
    {
        return $this === self::CANCELED;
    }

    /**
     * Get statuses that represent active tasks
     */
    public static function activeStatuses(): array
    {
        return [
            self::PENDING->value,
            self::IN_PROGRESS->value,
        ];
    }

    /**
     * Get statuses that represent finished tasks
     */
    public static function finishedStatuses(): array
    {
        return [
            self::COMPLETED->value,
            self::CANCELED->value,
        ];
    }
}