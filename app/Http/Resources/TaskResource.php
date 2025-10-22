<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'assignee' => $this->when(
                $this->assignee,
                function () {
                    return [
                        'id' => $this->assignee->id,
                        'name' => $this->assignee->name,
                        'email' => $this->assignee->email,
                        'role' => [
                            'value' => $this->assignee->role->value,
                            'label' => $this->assignee->role->label(),
                        ]
                    ];
                }
            ),
            'assignee_user_id' => $this->assignee_user,
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'due_date_human' => $this->due_date?->diffForHumans(),
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at_human' => $this->updated_at->diffForHumans(),
        ];
    }
}