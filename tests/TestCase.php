<?php

namespace FelixFrey\LaravelTheme\Tests;

use ErrorException;
use FelixFrey\LaravelTheme\ThemeServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->failOnPackageDeprecations();
    }

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

    /**
     * Laravel's error handler only logs deprecations, so PHPUnit never sees
     * them. Deprecations raised by the package itself become test errors,
     * everything else still goes to Laravel's handler.
     */
    private function failOnPackageDeprecations(): void
    {
        $packagePath = realpath(__DIR__ . '/../src');

        $previousHandler = set_error_handler(
            function (int $level, string $message, string $file = '', int $line = 0) use (&$previousHandler, $packagePath) {
                if (in_array($level, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
                    if (str_starts_with($file, $packagePath)) {
                        throw new ErrorException($message, 0, $level, $file, $line);
                    }
                }

                return $previousHandler ? $previousHandler($level, $message, $file, $line) : false;
            },
        );

        $this->beforeApplicationDestroyed(fn () => restore_error_handler());
    }
}
