<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('keeps the default auth guard as web (spatie roles depend on it)', function () {
    expect(config('auth.defaults.guard'))->toBe('web');
});

it('seeds every role and permission under the web guard', function () {
    seedRolesAndPermissions();

    expect(Role::query()->where('guard_name', '!=', 'web')->count())->toBe(0)
        ->and(Permission::query()->where('guard_name', '!=', 'web')->count())->toBe(0)
        ->and(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'member', 'owner']);
});

it('grants team.delete to the owner but not the admin or member', function () {
    seedRolesAndPermissions();

    $owner = Role::findByName('owner', 'web');
    $admin = Role::findByName('admin', 'web');
    $member = Role::findByName('member', 'web');

    expect($owner->hasPermissionTo('team.delete'))->toBeTrue()
        ->and($admin->hasPermissionTo('team.delete'))->toBeFalse()
        ->and($admin->hasPermissionTo('team.update'))->toBeTrue()
        ->and($member->hasPermissionTo('team.update'))->toBeFalse()
        ->and($member->hasPermissionTo('task.create'))->toBeTrue();
});
