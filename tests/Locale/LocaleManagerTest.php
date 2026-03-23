<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Locale;

use PHPUnit\Framework\TestCase;
use PHPolygon\Locale\LocaleManager;

class LocaleManagerTest extends TestCase
{
    private LocaleManager $locale;
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->locale = new LocaleManager('en', 'en');
        $this->fixturesPath = sys_get_temp_dir() . '/phpolygon_locale_test_' . uniqid();
        mkdir($this->fixturesPath, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->fixturesPath . '/*.json');
        foreach ($files as $file) {
            unlink($file);
        }
        if (is_dir($this->fixturesPath)) {
            rmdir($this->fixturesPath);
        }
    }

    public function testDefaultLocale(): void
    {
        $this->assertEquals('en', $this->locale->getLocale());
        $this->assertEquals('en', $this->locale->getFallbackLocale());
    }

    public function testSetLocale(): void
    {
        $this->locale->setLocale('de');
        $this->assertEquals('de', $this->locale->getLocale());
    }

    public function testSetFallbackLocale(): void
    {
        $this->locale->setFallbackLocale('fr');
        $this->assertEquals('fr', $this->locale->getFallbackLocale());
    }

    public function testAddAndGet(): void
    {
        $this->locale->add('en', ['greeting' => 'Hello']);
        $this->assertEquals('Hello', $this->locale->get('greeting'));
    }

    public function testGetReturnsKeyWhenMissing(): void
    {
        $this->assertEquals('missing.key', $this->locale->get('missing.key'));
    }

    public function testFallbackLocale(): void
    {
        $this->locale->add('en', ['title' => 'Game Title']);
        $this->locale->setLocale('de');

        $this->assertEquals('Game Title', $this->locale->get('title'));
    }

    public function testCurrentLocaleOverridesFallback(): void
    {
        $this->locale->add('en', ['greeting' => 'Hello']);
        $this->locale->add('de', ['greeting' => 'Hallo']);
        $this->locale->setLocale('de');

        $this->assertEquals('Hallo', $this->locale->get('greeting'));
    }

    public function testPlaceholderReplacement(): void
    {
        $this->locale->add('en', ['welcome' => 'Welcome, :name! You have :count items.']);

        $result = $this->locale->get('welcome', ['name' => 'Max', 'count' => 5]);
        $this->assertEquals('Welcome, Max! You have 5 items.', $result);
    }

    public function testPlaceholderWithMissingParam(): void
    {
        $this->locale->add('en', ['msg' => 'Hello :name']);

        $result = $this->locale->get('msg', []);
        $this->assertEquals('Hello :name', $result);
    }

    public function testTIsAliasForGet(): void
    {
        $this->locale->add('en', ['key' => 'Value']);
        $this->assertEquals($this->locale->get('key'), $this->locale->t('key'));
    }

    public function testHas(): void
    {
        $this->locale->add('en', ['exists' => 'Yes']);

        $this->assertTrue($this->locale->has('exists'));
        $this->assertFalse($this->locale->has('nope'));
    }

    public function testHasChecksFallback(): void
    {
        $this->locale->add('en', ['fallback_key' => 'Value']);
        $this->locale->setLocale('de');

        $this->assertTrue($this->locale->has('fallback_key'));
    }

    public function testLoadFile(): void
    {
        $filePath = $this->fixturesPath . '/en.json';
        file_put_contents($filePath, json_encode([
            'menu' => [
                'start' => 'Start Game',
                'quit' => 'Quit',
            ],
            'title' => 'My Game',
        ]));

        $this->locale->loadFile('en', $filePath);

        $this->assertEquals('Start Game', $this->locale->get('menu.start'));
        $this->assertEquals('Quit', $this->locale->get('menu.quit'));
        $this->assertEquals('My Game', $this->locale->get('title'));
    }

    public function testLoadFileThrowsOnMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->locale->loadFile('en', '/nonexistent/path.json');
    }

    public function testLoadDirectory(): void
    {
        file_put_contents($this->fixturesPath . '/en.json', json_encode(['hi' => 'Hello']));
        file_put_contents($this->fixturesPath . '/de.json', json_encode(['hi' => 'Hallo']));

        $this->locale->loadDirectory($this->fixturesPath);

        $this->assertEquals('Hello', $this->locale->get('hi'));

        $this->locale->setLocale('de');
        $this->assertEquals('Hallo', $this->locale->get('hi'));
    }

    public function testLoadDirectoryThrowsOnMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->locale->loadDirectory('/nonexistent/dir');
    }

    public function testGetAvailableLocales(): void
    {
        $this->locale->add('en', ['a' => 'A']);
        $this->locale->add('de', ['a' => 'A']);
        $this->locale->add('fr', ['a' => 'A']);

        $locales = $this->locale->getAvailableLocales();
        $this->assertCount(3, $locales);
        $this->assertContains('en', $locales);
        $this->assertContains('de', $locales);
        $this->assertContains('fr', $locales);
    }

    public function testAddMergesTranslations(): void
    {
        $this->locale->add('en', ['a' => '1']);
        $this->locale->add('en', ['b' => '2']);

        $this->assertEquals('1', $this->locale->get('a'));
        $this->assertEquals('2', $this->locale->get('b'));
    }

    public function testNestedFlatten(): void
    {
        $this->locale->add('en', []);
        file_put_contents($this->fixturesPath . '/en.json', json_encode([
            'a' => [
                'b' => [
                    'c' => 'deep',
                ],
            ],
        ]));

        $this->locale->loadFile('en', $this->fixturesPath . '/en.json');
        $this->assertEquals('deep', $this->locale->get('a.b.c'));
    }
}
