<?php

namespace FelixFrey\LaravelTheme\Tests;

use FelixFrey\LaravelTheme\Exceptions\ThemeException;
use FelixFrey\LaravelTheme\Facades\ThemeLoader;
use FelixFrey\LaravelTheme\ThemeInfo;

class ThemeLoaderTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('theme.path', $this->fixturesPath('themes'));
    }

    public function testInitMakesThemeViewsAvailable(): void
    {
        ThemeLoader::init('default');

        $this->assertSame('Hello from default', trim(view('hello')->render()));
    }

    public function testInitPrefersChildThemeViewsOverParentThemeViews(): void
    {
        ThemeLoader::init('child');

        $this->assertSame('Hello from child', trim(view('hello')->render()));
    }

    public function testInitFallsBackToParentThemeViews(): void
    {
        ThemeLoader::init('child');

        $this->assertSame('About from default', trim(view('about')->render()));
    }

    public function testInitRegistersTranslationsUnderTheThemeNamespace(): void
    {
        ThemeLoader::init('default');

        $this->assertSame('Default theme label', __('default::labels.intro'));
    }

    public function testInitThrowsForAnEmptyThemeName(): void
    {
        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage('Theme name should not be empty.');

        ThemeLoader::init('');
    }

    public function testInitThrowsForAMissingTheme(): void
    {
        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage('Theme folder missing not found.');

        ThemeLoader::init('missing');
    }

    public function testGetListOnlyReturnsDirectoriesWithAThemeConfig(): void
    {
        $themeNames = array_map(
            fn (ThemeInfo $theme) => $theme->getName(),
            ThemeLoader::getList(),
        );

        sort($themeNames);

        $this->assertSame(['child', 'default'], $themeNames);
    }

    public function testGetListIsEmptyWhenTheThemesPathDoesNotExist(): void
    {
        config()->set('theme.path', $this->fixturesPath('missing'));

        $this->assertSame([], ThemeLoader::getList());
    }
}
