<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Runtime;

use PHPolygon\Runtime\Input;
use PHPolygon\Runtime\InputInterface;
use PHPolygon\Runtime\VioInput;
use PHPUnit\Framework\TestCase;

/**
 * Auto-repeat for text editing.
 *
 * A held key must keep deleting or moving the caret, at the rate the OS picked
 * - that is what every text field on the machine does. Before this, editing
 * read the key through isKeyPressed(), which reports the physical press once
 * and nothing more: holding backspace removed a single character, and fixing a
 * line of code meant tapping twenty times.
 *
 * The other half matters just as much: GAMEPLAY must NOT see repeats. Jump,
 * interact and skip all read isKeyPressed(), and a held key that re-triggered
 * them would be a different bug. Hence a separate channel rather than folding
 * GLFW_REPEAT into the press edge.
 *
 * Tested through {@see VioInput::consumeTypedEdge()} rather than isKeyTyped():
 * the latter first checks a VioContext, which comes from the php-vio extension
 * and can neither be constructed nor stubbed here.
 */
final class KeyRepeatTest extends TestCase
{
    private const int KEY_BACKSPACE = 259;

    /** Feed an edge in the way the GLFW callback would. */
    private function pushEdge(VioInput $input, string $field, int $key): void
    {
        $ref = new \ReflectionProperty(VioInput::class, $field);
        $value = $ref->getValue($input);
        $value[$key] = true;
        $ref->setValue($input, $value);
    }

    public function testRepeatCountsAsTypedButNotAsPressed(): void
    {
        $input = new VioInput();
        $this->pushEdge($input, 'keyRepeated', self::KEY_BACKSPACE);

        self::assertFalse(
            $input->isKeyPressed(self::KEY_BACKSPACE),
            'a repeat must not read as a press - gameplay would re-trigger on a held key',
        );
        self::assertTrue(
            $input->consumeTypedEdge(self::KEY_BACKSPACE),
            'a repeat must read as typed - that is what a held backspace is',
        );
    }

    public function testPressCountsAsTyped(): void
    {
        $input = new VioInput();
        $this->pushEdge($input, 'keyJustPressed', self::KEY_BACKSPACE);

        self::assertTrue($input->consumeTypedEdge(self::KEY_BACKSPACE));
    }

    /** Consumed on read, like every other edge - one repeat, one deletion. */
    public function testTypedEdgeIsConsumed(): void
    {
        $input = new VioInput();
        $this->pushEdge($input, 'keyRepeated', self::KEY_BACKSPACE);

        self::assertTrue($input->consumeTypedEdge(self::KEY_BACKSPACE));
        self::assertFalse($input->consumeTypedEdge(self::KEY_BACKSPACE), 'the repeat fired twice');
    }

    /**
     * A press and a repeat arriving in the same frame are ONE typed event, not
     * two - otherwise the first character after the repeat delay goes twice.
     */
    public function testPressAndRepeatInOneFrameCountOnce(): void
    {
        $input = new VioInput();
        $this->pushEdge($input, 'keyJustPressed', self::KEY_BACKSPACE);
        $this->pushEdge($input, 'keyRepeated', self::KEY_BACKSPACE);

        self::assertTrue($input->consumeTypedEdge(self::KEY_BACKSPACE));
        self::assertFalse($input->consumeTypedEdge(self::KEY_BACKSPACE));
    }

    public function testClearKeyEdgesDropsPendingRepeats(): void
    {
        $input = new VioInput();
        $this->pushEdge($input, 'keyRepeated', self::KEY_BACKSPACE);

        $input->clearKeyEdges();

        self::assertFalse(
            $input->consumeTypedEdge(self::KEY_BACKSPACE),
            'a repeat buffered across a modal handoff would fire the moment input resumes',
        );
    }

    /** Without a context nothing is readable at all - the gate stays shut. */
    public function testTypedNeedsAContext(): void
    {
        $input = new VioInput();
        $this->pushEdge($input, 'keyRepeated', self::KEY_BACKSPACE);

        self::assertFalse($input->isKeyTyped(self::KEY_BACKSPACE));
    }

    /** The windowless fallback has no repeats; a press is all it can report. */
    public function testPlainInputTreatsTypedAsPressed(): void
    {
        $input = new Input();

        self::assertFalse($input->isKeyTyped(self::KEY_BACKSPACE));
        self::assertInstanceOf(InputInterface::class, $input);
    }
}
