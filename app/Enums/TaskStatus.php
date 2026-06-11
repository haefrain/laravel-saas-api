<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Done = 'done';
    case Cancelled = 'cancelled';

    /**
     * The full lifecycle graph. Work cannot skip review (no direct path to
     * done from todo/in_progress); done and cancelled are terminal except for
     * the explicit reopen/revive edges.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Todo => [self::InProgress, self::Cancelled],
            self::InProgress => [self::InReview, self::Todo, self::Cancelled],
            self::InReview => [self::InProgress, self::Done, self::Cancelled],
            self::Done => [self::InProgress, self::Cancelled],
            self::Cancelled => [self::Todo],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Done || $this === self::Cancelled;
    }
}
