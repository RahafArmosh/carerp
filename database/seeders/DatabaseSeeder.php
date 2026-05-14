<?php

namespace Database\Seeders;

use App\Models\Utility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(NotificationSeeder::class);
        // Artisan::call('migrate LandingPage');
        // Artisan::call('db:seed LandingPage');

        $route = request()->route();
        if($route && $route->getName() !='LaravelUpdater::database')
        {
            $this->call(PlansTableSeeder::class);
            $this->call(CurrencySeeder::class);
            $this->call(UsersTableSeeder::class);
            $this->call(TaskMasterCompanyPermissionsSeeder::class);
            $this->call(DailyTaskLogPermissionsSeeder::class);
            $this->call(AiTemplateSeeder::class);
        }else{
            Utility::languagecreate();
        }

    }
}
