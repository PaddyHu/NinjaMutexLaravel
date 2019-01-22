<?php

namespace PaddyHu\NinjaMutexLaravel;


use Illuminate\Support\ServiceProvider;
use NinjaMutex\Lock\LockInterface;
use NinjaMutex\Lock\MemcachedLock;
use NinjaMutex\MutexFabric;

class NinjaMutexServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/config/config.php' => config_path('ninja-mutex.php')]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/config.php', 'ninjaMutex');

        $this->app->singleton('NinjaMutex\MutexFabric', function($app)
        {
            $config = $app['config']->get('ninjaMutex');

            $lockImplementor = $this->getLockImplementor($config);

            return new MutexFabric($config["driver"], $lockImplementor);
        });
    }

    /**
     * @param array $config
     * @return LockInterface
     * @throws InvalidLockImplementorException
     */
    private function getLockImplementor(array $config)
    {
        switch ($config["driver"]) {
            case "memcached":
                $memcached = new \Memcached();
                $memcached->addServers(config("cache.stores.memcached.servers"));
                $lockImplementor = new MemcachedLock($memcached);
                $lockImplementor->setExpiration(4);
                break;
        }

        if( ! isset($lockImplementor) ) {
            throw new InvalidLockImplementorException("Invalid lock implementor exception: " . $config["driver"]);
        }

        return $lockImplementor;
    }

    public function provides()
    {
        return ['ninjaMutex'];
    }
}