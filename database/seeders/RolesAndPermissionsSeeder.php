<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Roles and permissions are GLOBAL rows (team_id null); the per-team scoping
     * lives in model_has_roles.team_id. guard_name is set explicitly on every
     * row — spatie otherwise defaults to config('auth.defaults.guard') and a
     * mismatch makes hasRole()/can() silently always-false.
     */
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        $registrar->setPermissionsTeamId(null);

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $owner = Role::findOrCreate('owner', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        $member = Role::findOrCreate('member', 'web');

        $owner->syncPermissions(self::PERMISSIONS);
        $admin->syncPermissions(array_values(array_diff(self::PERMISSIONS, ['team.delete'])));
        $member->syncPermissions(self::MEMBER_PERMISSIONS);

        $registrar->forgetCachedPermissions();
    }

    /**
     * @var list<string>
     */
    private const PERMISSIONS = [
        'team.view', 'team.update', 'team.delete',
        'member.invite', 'member.role.update', 'member.remove',
        'project.view', 'project.create', 'project.update', 'project.delete',
        'task.view', 'task.create', 'task.update', 'task.delete', 'task.assign', 'task.transition',
        'comment.view', 'comment.create', 'comment.delete',
    ];

    /**
     * @var list<string>
     */
    private const MEMBER_PERMISSIONS = [
        'team.view',
        'project.view',
        'task.view', 'task.create', 'task.update', 'task.assign', 'task.transition',
        'comment.view', 'comment.create',
    ];
}
