<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Medicament;
use App\Models\Vente;
use App\Models\Lot;
use App\Models\User;
use App\Models\ClotureCaisse;
use App\Models\Setting;
use App\Observers\MedicamentObserver;
use App\Observers\VenteObserver;
use App\Observers\LotObserver;
use App\Observers\UserObserver;
use App\Observers\ClotureCaisseObserver;
use App\Observers\SettingObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Medicament::observe(MedicamentObserver::class);
        Vente::observe(VenteObserver::class);
        Lot::observe(LotObserver::class);
        User::observe(UserObserver::class);
        ClotureCaisse::observe(ClotureCaisseObserver::class);
        Setting::observe(SettingObserver::class);
    }
}
