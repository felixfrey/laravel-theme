<?php

namespace FelixFrey\LaravelTheme\Tests\Commands;

use FelixFrey\LaravelTheme\Tests\TestCase;

class ThemeCreateCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = $this->useTemporaryBasePath();
    }

    public function testItCreatesAViteTheme(): void
    {
        $this->artisan('theme:create', ['name' => 'default'])->assertSuccessful();

        $themePath = "{$this->basePath}/themes/default";

        $this->assertFileExists("{$themePath}/config.php");
        $this->assertFileExists("{$themePath}/lang/en/labels.php");
        $this->assertFileExists("{$themePath}/views/hello.blade.php");
        $this->assertFileExists("{$themePath}/views/layouts/master.blade.php");
        $this->assertFileExists("{$themePath}/views/partials/header.blade.php");
        $this->assertFileExists("{$themePath}/views/partials/footer.blade.php");
        $this->assertFileExists("{$themePath}/assets/img/favicon.png");
        $this->assertFileExists("{$themePath}/assets/sass/app.scss");
        $this->assertFileExists("{$themePath}/assets/js/app.js");
        $this->assertFileExists("{$themePath}/assets/js/bootstrap.js");
        $this->assertFileExists("{$this->basePath}/app/View/Components/AppLayout.php");

        $this->assertDirectoryDoesNotExist("{$this->basePath}/public/themes/default");
    }

    public function testItCreatesAMixTheme(): void
    {
        $this->artisan('theme:create', ['name' => 'default', 'assets' => 'mix'])->assertSuccessful();

        $themePath = "{$this->basePath}/themes/default";
        $publicPath = "{$this->basePath}/public/themes/default";

        $this->assertFileExists("{$themePath}/views/layouts/master.blade.php");
        $this->assertFileExists("{$themePath}/assets/js/app.js");
        $this->assertFileExists("{$publicPath}/css/.gitkeep");
        $this->assertFileExists("{$publicPath}/js/.gitkeep");
        $this->assertFileExists("{$publicPath}/fonts/.gitkeep");

        $this->assertFileDoesNotExist("{$themePath}/assets/js/bootstrap.js");
        $this->assertFileDoesNotExist("{$this->basePath}/app/View/Components/AppLayout.php");

        $this->assertStringContainsString(
            "mix.js(['themes/default/assets/js/app.js']",
            file_get_contents("{$this->basePath}/webpack.mix.js"),
        );
    }

    public function testItRejectsAnUnknownAssetsType(): void
    {
        $this->artisan('theme:create', ['name' => 'default', 'assets' => 'webpack'])
            ->expectsOutput('Assets type "webpack" is not supported. Use vite or mix.')
            ->assertFailed();

        $this->assertDirectoryDoesNotExist("{$this->basePath}/themes");
        $this->assertFileDoesNotExist("{$this->basePath}/webpack.mix.js");
    }

    public function testItReplacesTheThemeNamePlaceholderInStubs(): void
    {
        $this->artisan('theme:create', ['name' => 'default'])->assertSuccessful();

        $config = file_get_contents("{$this->basePath}/themes/default/config.php");

        $this->assertStringContainsString("'name' => 'default'", $config);
        $this->assertStringNotContainsString('%theme_name%', $config);
    }

    public function testItSnakeCasesTheThemeName(): void
    {
        $this->artisan('theme:create', ['name' => 'My Theme'])->assertSuccessful();

        $this->assertDirectoryExists("{$this->basePath}/themes/my_theme");
    }

    public function testItRejectsNamesWithInvalidCharacters(): void
    {
        $this->artisan('theme:create', ['name' => 'my/theme'])
            ->expectsOutput('Name must be alphanumeric, dash, space or underscore.')
            ->assertFailed();

        $this->assertDirectoryDoesNotExist("{$this->basePath}/themes");
    }

    public function testItRefusesToOverwriteAnExistingThemeWhenConfirmationIsDeclined(): void
    {
        $configPath = $this->createModifiedDefaultTheme();

        $this->artisan('theme:create', ['name' => 'default'])
            ->expectsOutput('Theme "default" already exists.')
            ->expectsConfirmation('Are you sure want to override "default" theme folder?', 'no')
            ->expectsOutput('Generation of Theme "default" has been canceled.')
            ->assertFailed();

        $this->assertStringEqualsFile($configPath, 'modified');
    }

    public function testItOverwritesAnExistingThemeWhenConfirmationIsAccepted(): void
    {
        $configPath = $this->createModifiedDefaultTheme();

        $this->artisan('theme:create', ['name' => 'default'])
            ->expectsConfirmation('Are you sure want to override "default" theme folder?', 'yes')
            ->expectsOutput('Overriding Theme "default".')
            ->assertSuccessful();

        $this->assertStringContainsString("'name' => 'default'", file_get_contents($configPath));
    }

    public function testItOverwritesAnExistingThemeWithoutConfirmationWhenForced(): void
    {
        $configPath = $this->createModifiedDefaultTheme();

        $this->artisan('theme:create', ['name' => 'default', '--force' => true])
            ->expectsOutput('Overriding Theme "default".')
            ->assertSuccessful();

        $this->assertStringContainsString("'name' => 'default'", file_get_contents($configPath));
    }

    private function createModifiedDefaultTheme(): string
    {
        $this->artisan('theme:create', ['name' => 'default'])->assertSuccessful();

        $configPath = "{$this->basePath}/themes/default/config.php";

        file_put_contents($configPath, 'modified');

        return $configPath;
    }
}
