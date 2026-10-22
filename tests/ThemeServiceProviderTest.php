<?php

namespace FelixFrey\LaravelTheme\Tests;

use FelixFrey\LaravelTheme\ThemeLoader;
use Illuminate\Support\Facades\Artisan;

class ThemeServiceProviderTest extends TestCase
{
    public function testItMergesThePackageConfig(): void
    {
        $this->assertSame('themes', config('theme.assets_path'));
        $this->assertSame('views', config('theme.containerDir.view'));
    }

    public function testItRegistersTheThemeLoaderAsASingleton(): void
    {
        $this->assertInstanceOf(ThemeLoader::class, $this->app->make('theme-loader'));
        $this->assertSame($this->app->make('theme-loader'), $this->app->make('theme-loader'));
    }

    public function testItRegistersTheThemeCommands(): void
    {
        $this->assertArrayHasKey('theme:create', Artisan::all());
        $this->assertArrayHasKey('theme:destroy', Artisan::all());
    }
}
