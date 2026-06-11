<?php

declare(strict_types=1);

use App\Enums\TaskStatus;

/**
 * Full transition matrix, table-driven. Key rules: no todo→done skip (work
 * must pass review), done/cancelled are terminal except explicit reopen/revive.
 */
dataset('transitions', [
    // from, to, allowed
    'todo → in_progress' => [TaskStatus::Todo, TaskStatus::InProgress, true],
    'todo → in_review' => [TaskStatus::Todo, TaskStatus::InReview, false],
    'todo → done (no skip)' => [TaskStatus::Todo, TaskStatus::Done, false],
    'todo → cancelled' => [TaskStatus::Todo, TaskStatus::Cancelled, true],

    'in_progress → todo' => [TaskStatus::InProgress, TaskStatus::Todo, true],
    'in_progress → in_review' => [TaskStatus::InProgress, TaskStatus::InReview, true],
    'in_progress → done (no skip)' => [TaskStatus::InProgress, TaskStatus::Done, false],
    'in_progress → cancelled' => [TaskStatus::InProgress, TaskStatus::Cancelled, true],

    'in_review → todo' => [TaskStatus::InReview, TaskStatus::Todo, false],
    'in_review → in_progress' => [TaskStatus::InReview, TaskStatus::InProgress, true],
    'in_review → done' => [TaskStatus::InReview, TaskStatus::Done, true],
    'in_review → cancelled' => [TaskStatus::InReview, TaskStatus::Cancelled, true],

    'done → todo' => [TaskStatus::Done, TaskStatus::Todo, false],
    'done → in_progress (reopen)' => [TaskStatus::Done, TaskStatus::InProgress, true],
    'done → in_review' => [TaskStatus::Done, TaskStatus::InReview, false],
    'done → cancelled' => [TaskStatus::Done, TaskStatus::Cancelled, true],

    'cancelled → todo (revive)' => [TaskStatus::Cancelled, TaskStatus::Todo, true],
    'cancelled → in_progress' => [TaskStatus::Cancelled, TaskStatus::InProgress, false],
    'cancelled → in_review' => [TaskStatus::Cancelled, TaskStatus::InReview, false],
    'cancelled → done' => [TaskStatus::Cancelled, TaskStatus::Done, false],
]);

it('enforces the transition matrix', function (TaskStatus $from, TaskStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with('transitions');

it('never allows a self-transition', function () {
    foreach (TaskStatus::cases() as $status) {
        expect($status->canTransitionTo($status))->toBeFalse()
            ->and($status->allowedTransitions())->not->toContain($status);
    }
});

it('marks done and cancelled as terminal', function () {
    expect(TaskStatus::Done->isTerminal())->toBeTrue()
        ->and(TaskStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(TaskStatus::Todo->isTerminal())->toBeFalse()
        ->and(TaskStatus::InProgress->isTerminal())->toBeFalse()
        ->and(TaskStatus::InReview->isTerminal())->toBeFalse();
});
