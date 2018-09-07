<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (php_sapi_name() == 'cli') {
            //开启DB监听, 输出sql语句
            DB::listen(function ($query) {
                echo '['.date('Y-m-d H:i:s').']'.json_encode([
                        'SQL:'.$query->sql,
                        'Params:'.json_encode($query->bindings, JSON_UNESCAPED_UNICODE),
                        'Time:'.$query->time.'ms'
                    ], JSON_UNESCAPED_UNICODE)."\n";
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
