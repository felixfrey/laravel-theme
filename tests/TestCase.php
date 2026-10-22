<?php

namespace FelixFrey\LaravelTheme\Tests;

use ErrorException;
use FelixFrey\LaravelTheme\ThemeServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    private const UNSILENCEABLE_LEVELS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;

    protected function setUp(): void
    {
        $this->loadServiceProviderWithPackageDeprecationsAsErrors();

        parent::setUp();

        $this->pushPackageDeprecationHandler();

        $this->beforeApplicationDestroyed(fn () => restore_error_handler());
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
     * Laravel loads the service provider while the application boots, under
     * its own error handler, so it is loaded here first.
     */
    private function loadServiceProviderWithPackageDeprecationsAsErrors(): void
    {
        $this->pushPackageDeprecationHandler();

        try {
            class_exists(ThemeServiceProvider::class);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Laravel's error handler only logs deprecations, so PHPUnit never sees
     * them. Deprecations raised by the package itself become test errors,
     * everything else still goes to the previous handler.
     */
    private function pushPackageDeprecationHandler(): void
    {
        $previousHandler = set_error_handler(
            function (int $level, string $message, string $file = '', int $line = 0) use (&$previousHandler) {
                if ($this->isReportedPackageDeprecation($level, $file)) {
                    throw new ErrorException($message, 0, $level, $file, $line);
                }

                return $previousHandler ? $previousHandler($level, $message, $file, $line) : false;
            },
        );
    }

    private function isReportedPackageDeprecation(int $level, string $file): bool
    {
        if (!in_array($level, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
            return false;
        }

        // @ lowers error_reporting() to the levels it cannot silence. PHPUnit
        // lowers it as well while its own handler runs, so the level of the
        // deprecation itself says nothing about @.
        if (0 === (error_reporting() & ~self::UNSILENCEABLE_LEVELS)) {
            return false;
        }

        $packagePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        return str_starts_with($file, $packagePath);
    }
}
