<?php

namespace FelixFrey\LaravelTheme\Tests;

use FelixFrey\LaravelTheme\ThemeServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ThemeServiceProvider::class,
        ];
    }

    protected function fixturesPath(string $path = ''): string
    {
        return rtrim(__DIR__ . "/fixtures/{$path}", '/');
    }

    /**
     * The generator writes into app, public and base paths, so command tests
     * get a throwaway base path instead of touching the Testbench skeleton.
     */
    protected function useTemporaryBasePath(): string
    {
        $files = new Filesystem();

        $basePath = sys_get_temp_dir() . '/laravel-theme-tests-' . uniqid();

        $files->ensureDirectoryExists($basePath);

        $this->app->setBasePath($basePath);

        $this->app['config']->set('theme.path', "{$basePath}/themes");

        $this->beforeApplicationDestroyed(fn () => $files->deleteDirectory($basePath));

        return $basePath;
    }
}
