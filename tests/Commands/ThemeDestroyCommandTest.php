<?php

namespace FelixFrey\LaravelTheme\Tests\Commands;

use FelixFrey\LaravelTheme\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ThemeDestroyCommandTest extends TestCase
{
    private string $themePath;

    private string $assetsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $basePath = $this->useTemporaryBasePath();

        $this->themePath = "{$basePath}/themes/default";
        $this->assetsPath = "{$basePath}/public/themes/default";

        File::ensureDirectoryExists("{$this->themePath}/views");
        File::ensureDirectoryExists("{$this->assetsPath}/css");
    }

    public function testItDeletesTheThemeAndItsPublicAssetsAfterConfirmation(): void
    {
        $this->artisan('theme:destroy', ['name' => 'default'])
            ->expectsConfirmation('Are you sure you want to permanently delete?', 'yes')
            ->expectsOutput('Theme "default" has been destroyed.')
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist($this->themePath);
        $this->assertDirectoryDoesNotExist($this->assetsPath);
    }

    public function testItKeepsTheThemeWhenDeletionIsNotConfirmed(): void
    {
        $this->artisan('theme:destroy', ['name' => 'default'])
            ->expectsConfirmation('Are you sure you want to permanently delete?', 'no')
            ->assertSuccessful();

        $this->assertDirectoryExists($this->themePath);
        $this->assertDirectoryExists($this->assetsPath);
    }

    public function testItFailsForAnUnknownTheme(): void
    {
        $this->artisan('theme:destroy', ['name' => 'missing'])
            ->expectsOutput('Theme "missing" does not exist.')
            ->assertFailed();

        $this->assertDirectoryExists($this->themePath);
    }

    public function testItRefusesAnEmptyNameInsteadOfDeletingAllThemes(): void
    {
        $this->artisan('theme:destroy', ['name' => ''])
            ->expectsOutput('Name is required.')
            ->assertFailed();

        $this->assertDirectoryExists($this->themePath);
        $this->assertDirectoryExists($this->assetsPath);
    }

    public function testItRefusesANameThatPointsOutsideTheThemesDirectory(): void
    {
        $this->artisan('theme:destroy', ['name' => '..'])
            ->expectsOutput('Name must be a single folder inside the themes path.')
            ->assertFailed();

        $this->assertDirectoryExists($this->themePath);
        $this->assertDirectoryExists($this->assetsPath);
    }

    public function testItDeletesAThemeWhoseFolderNameContainsADot(): void
    {
        $themePath = dirname($this->themePath) . '/v2.0';

        File::ensureDirectoryExists("{$themePath}/views");

        $this->artisan('theme:destroy', ['name' => 'v2.0'])
            ->expectsConfirmation('Are you sure you want to permanently delete?', 'yes')
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist($themePath);
        $this->assertDirectoryExists($this->themePath);
    }
}
