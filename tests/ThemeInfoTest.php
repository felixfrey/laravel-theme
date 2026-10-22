<?php

namespace FelixFrey\LaravelTheme\Tests;

use FelixFrey\LaravelTheme\ThemeInfo;

class ThemeInfoTest extends TestCase
{
    public function testNameIsNormalisedToSnakeCase(): void
    {
        $theme = new ThemeInfo('My Theme', '/themes');

        $this->assertSame('my_theme', $theme->getName());
        $this->assertSame('/themes/my_theme', $theme->getRootDirectoryPath());
    }

    public function testPathForItemJoinsPathParts(): void
    {
        $theme = new ThemeInfo('default', '/themes');

        $this->assertSame('/themes/default/views/layouts', $theme->pathForItem(['views', 'layouts']));
        $this->assertSame('/themes/default/config.php', $theme->getConfigPath());
    }

    public function testReadConfigExposesTheParentThemeName(): void
    {
        $theme = new ThemeInfo('child', $this->fixturesPath('themes'));

        $theme->readConfig();

        $this->assertSame('default', $theme->getParentThemeName());
    }

    public function testReadConfigIgnoresAMissingConfigFile(): void
    {
        $theme = new ThemeInfo('not_a_theme', $this->fixturesPath('themes'));

        $theme->readConfig();

        $this->assertSame([], $theme->getConfigs());
        $this->assertNull($theme->getParentThemeName());
    }

    public function testHasValidNameOnlyAcceptsASingleFolderName(): void
    {
        $this->assertTrue((new ThemeInfo('default', '/themes'))->hasValidName());
        $this->assertTrue((new ThemeInfo('v2.0', '/themes'))->hasValidName());
        $this->assertTrue((new ThemeInfo('0', '/themes'))->hasValidName());

        $this->assertFalse((new ThemeInfo('', '/themes'))->hasValidName());
        $this->assertFalse((new ThemeInfo('..', '/themes'))->hasValidName());
        $this->assertFalse((new ThemeInfo('. .', '/themes'))->hasValidName());
        $this->assertFalse((new ThemeInfo('../other', '/themes'))->hasValidName());
        $this->assertFalse((new ThemeInfo('other\\theme', '/themes'))->hasValidName());
    }
}
