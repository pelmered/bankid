<?php


namespace LJSystem\BankID;

use Illuminate\Support\ServiceProvider;

class RiakServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(BankID::class, function () {
            return new BankID();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/bankid.php' => config_path('bankid.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/migrations');

        $this->loadTranslationsFrom(__DIR__.'/lang', 'bankid');

        $this->publishes([
            __DIR__.'/lang' => resource_path('lang/vendor/bankid'),
        ]);
    }
}