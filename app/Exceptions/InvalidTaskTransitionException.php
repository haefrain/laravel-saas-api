<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\TaskStatus;

class InvalidTaskTransitionException extends DomainException
{
    public function __construct(
        private readonly TaskStatus $from,
        private readonly TaskStatus $to,
    ) {
        parent::__construct(sprintf('Cannot transition from %s to %s.', $from->value, $to->value));
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'invalid_transition';
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return [
            'from' => $this->from->value,
            'to' => $this->to->value,
            'allowed_transitions' => array_map(
                static fn (TaskStatus $status): string => $status->value,
                $this->from->allowedTransitions(),
            ),
        ];
    }
}
