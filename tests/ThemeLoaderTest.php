<?php

namespace FelixFrey\LaravelTheme\Tests;

use FelixFrey\LaravelTheme\Exceptions\ThemeException;
use FelixFrey\LaravelTheme\Facades\ThemeLoader;
use FelixFrey\LaravelTheme\ThemeInfo;
use Illuminate\Support\Facades\File;

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

    public function testInitThrowsForAMissingParentTheme(): void
    {
        $this->useTemporaryBasePath();

        $this->createTheme('orphan', parent: 'missing');

        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage('Theme folder missing not found.');

        ThemeLoader::init('orphan');
    }

    public function testInitThrowsForCircularInheritance(): void
    {
        $this->useTemporaryBasePath();

        $this->createTheme('first', parent: 'second');
        $this->createTheme('second', parent: 'first');

        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage('Circular theme inheritance: first -> second -> first.');

        ThemeLoader::init('first');
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

    private function createTheme(string $name, string $parent): void
    {
        $themePath = ThemeLoader::themesPath() . "/{$name}";

        File::ensureDirectoryExists($themePath);
        File::put("{$themePath}/config.php", "<?php return ['inherit' => '{$parent}'];");
    }
}
