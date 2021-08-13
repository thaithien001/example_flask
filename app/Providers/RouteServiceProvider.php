<?php

namespace App\Providers;

use App\Http\Middleware\TrimStrings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = '';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $middleWares = env('APP_ENV') != 'production' && env('FAKE_CURRENT_TIME') ? ['set-test-now'] : [];

        $this->configureRateLimiting();

        $this->mapPublicRoutes($middleWares);

        $this->mapApiRoutes($middleWares);

        $this->mapAdminRoutes($middleWares);

        Route::post('/oauth/token', [
            'uses' => '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken',
            'as' => 'passport.token',
            'middleware' => 'throttle:6000,1',
        ]);
    }

    /**
     * Configures rate limiters for the application
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function () {
            return Limit::perMinute(100);
        });
        RateLimiter::for('web', function () {
            return Limit::perMinute(600);
        });
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param $middleWares
     * @return void
     */
    protected function mapAdminRoutes($middleWares)
    {
        Route::prefix('admin')
            ->middleware($middleWares)
            ->namespace($this->namespace)
            ->group(base_path('Bachelor/Port/Primary/WebApi/routes/admin.php'));
    }

    /**
     * Define the "guest" routes for the application.
     *
     * These routes all receive session state
     *
     * @param $middleWares
     * @return void
     */
    protected function mapPublicRoutes($middleWares)
    {
        $middleWaresPublic = array_merge($middleWares, ['public']);
        Route::namespace($this->namespace)
            ->middleware($middleWaresPublic)
            ->group(base_path('Bachelor/Port/Primary/WebApi/routes/guest.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @param $middleWares
     * @return void
     */
    protected function mapApiRoutes($middleWares)
    {
        $middleWaresApi = array_merge($middleWares, ['api']);
        Route::prefix('api')
            ->middleware($middleWaresApi)
            ->namespace($this->namespace)
            ->group(base_path('Bachelor/Port/Primary/WebApi/routes/api.php'));
    }
}
