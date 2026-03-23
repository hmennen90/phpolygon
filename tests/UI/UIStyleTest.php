<?php

declare(strict_types=1);

namespace PHPolygon\Tests\UI;

use PHPUnit\Framework\TestCase;
use PHPolygon\UI\UIStyle;

class UIStyleTest extends TestCase
{
    public function testDarkPreset(): void
    {
        $style = UIStyle::dark();
        $this->assertEqualsWithDelta(0.15, $style->backgroundColor->r, 0.01);
        $this->assertEquals(16.0, $style->fontSize);
        $this->assertEquals(4.0, $style->borderRadius);
    }

    public function testLightPreset(): void
    {
        $style = UIStyle::light();
        $this->assertEqualsWithDelta(0.92, $style->backgroundColor->r, 0.01);
        $this->assertEqualsWithDelta(0.1, $style->textColor->r, 0.01);
    }

    public function testCustomValues(): void
    {
        $style = new UIStyle(fontSize: 24.0, padding: 10.0);
        $this->assertEquals(24.0, $style->fontSize);
        $this->assertEquals(10.0, $style->padding);
    }
}
