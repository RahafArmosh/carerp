<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            $isCrmAdminByPermission = method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('manage crm admin');
            $isCrmAdminByRole = method_exists($user, 'hasRole') && $user->hasRole('crm admin');

            if (!$isCrmAdminByPermission && !$isCrmAdminByRole) {
                return null;
            }

            $crmAbilities = [
                'manage crm admin',
                'manage lead',
                'create lead',
                'edit lead',
                'delete lead',
                'manage lead role',
                'create lead role',
                'edit lead role',
                'delete lead role',
                'delete lead role condition',
                'manage deal',
                'create deal',
                'edit deal',
                'delete deal',
                'manage pipeline',
                'create pipeline',
                'edit pipeline',
                'delete pipeline',
                'manage stage',
                'create stage',
                'edit stage',
                'delete stage',
                'manage lead stage',
                'create lead stage',
                'edit lead stage',
                'delete lead stage',
                'manage source',
                'create source',
                'edit source',
                'delete source',
                'manage label',
                'create label',
                'edit label',
                'delete label',
            ];

            return in_array($ability, $crmAbilities, true) ? true : null;
        });
    }
}
