<?php

declare(strict_types=1);

namespace PHPolygon\Testing;

/**
 * Pixel-level image comparison inspired by Pixelmatch.
 * Uses YIQ color space for perceptually-weighted diff calculation.
 */
class ScreenshotComparer
{
    /**
     * Compare two PNG images.
     *
     * @param string $expectedPath Path to reference screenshot
     * @param string $actualPath   Path to actual screenshot
     * @param string|null $diffPath Path to write diff image (null = skip)
     * @param float $threshold Per-pixel color threshold in YIQ space (0.0–1.0, default 0.1)
     * @return ComparisonResult
     */
    public static function compare(
        string $expectedPath,
        string $actualPath,
        ?string $diffPath = null,
        float $threshold = 0.1,
    ): ComparisonResult {
        if (!file_exists($expectedPath)) {
            return new ComparisonResult(
                match: false,
                mismatchedPixels: -1,
                totalPixels: 0,
                mismatchRatio: 1.0,
                diffPath: null,
                error: "Reference screenshot not found: {$expectedPath}",
            );
        }

        if (!file_exists($actualPath)) {
            return new ComparisonResult(
                match: false,
                mismatchedPixels: -1,
                totalPixels: 0,
                mismatchRatio: 1.0,
                diffPath: null,
                error: "Actual screenshot not found: {$actualPath}",
            );
        }

        $expected = imagecreatefrompng($expectedPath);
        $actual = imagecreatefrompng($actualPath);

        if ($expected === false || $actual === false) {
            return new ComparisonResult(
                match: false,
                mismatchedPixels: -1,
                totalPixels: 0,
                mismatchRatio: 1.0,
                diffPath: null,
                error: 'Failed to load one or both PNG images',
            );
        }

        $ew = imagesx($expected);
        $eh = imagesy($expected);
        $aw = imagesx($actual);
        $ah = imagesy($actual);

        if ($ew !== $aw || $eh !== $ah) {
            unset($expected, $actual);
            return new ComparisonResult(
                match: false,
                mismatchedPixels: -1,
                totalPixels: $ew * $eh,
                mismatchRatio: 1.0,
                diffPath: null,
                error: "Image dimensions differ: expected {$ew}x{$eh}, got {$aw}x{$ah}",
            );
        }

        $totalPixels = $ew * $eh;
        $mismatched = 0;

        // Create diff image
        $diffImage = null;
        if ($diffPath !== null) {
            $diffImage = imagecreatetruecolor($ew, $eh);
            if ($diffImage !== false) {
                imagealphablending($diffImage, false);
                imagesavealpha($diffImage, true);
            } else {
                $diffImage = null;
            }
        }

        $maxDelta = 35215.0 * $threshold * $threshold;

        for ($y = 0; $y < $eh; $y++) {
            for ($x = 0; $x < $ew; $x++) {
                $ecRaw = imagecolorat($expected, $x, $y);
                $acRaw = imagecolorat($actual, $x, $y);
                if ($ecRaw === false || $acRaw === false) {
                    continue;
                }
                $ec = (int) $ecRaw;
                $ac = (int) $acRaw;

                if ($ec === $ac) {
                    // Identical pixel
                    if ($diffImage !== null) {
                        // Dim the pixel in the diff
                        $r = ($ec >> 16) & 0xFF;
                        $g = ($ec >> 8) & 0xFF;
                        $b = $ec & 0xFF;
                        $dimColor = imagecolorallocate($diffImage, max(0, min(255, (int)($r * 0.1))), max(0, min(255, (int)($g * 0.1))), max(0, min(255, (int)($b * 0.1))));
                        if ($dimColor !== false) {
                            imagesetpixel($diffImage, $x, $y, $dimColor);
                        }
                    }
                    continue;
                }

                $delta = self::colorDeltaYIQ($ec, $ac);

                if ($delta > $maxDelta) {
                    $mismatched++;
                    if ($diffImage !== null) {
                        // Highlight mismatch in red
                        $red = imagecolorallocate($diffImage, 255, 0, 0);
                        if ($red !== false) {
                            imagesetpixel($diffImage, $x, $y, $red);
                        }
                    }
                } else {
                    if ($diffImage !== null) {
                        // Within threshold — show as yellow
                        $yellow = imagecolorallocate($diffImage, 255, 255, 0);
                        if ($yellow !== false) {
                            imagesetpixel($diffImage, $x, $y, $yellow);
                        }
                    }
                }
            }
        }

        if ($diffImage !== null && $diffPath !== null) {
            @mkdir(dirname($diffPath), 0755, true);
            imagepng($diffImage, $diffPath);
            unset($diffImage);
        }

        unset($expected, $actual);

        $ratio = $mismatched / $totalPixels;

        return new ComparisonResult(
            match: $mismatched === 0,
            mismatchedPixels: $mismatched,
            totalPixels: $totalPixels,
            mismatchRatio: $ratio,
            diffPath: ($diffPath !== null && $mismatched > 0) ? $diffPath : null,
            error: null,
        );
    }

    /**
     * Compute squared color distance in YIQ color space.
     * YIQ is perceptually-weighted: Y (luminance) matters most.
     *
     * Based on Pixelmatch algorithm.
     */
    private static function colorDeltaYIQ(int $c1, int $c2): float
    {
        $r1 = ($c1 >> 16) & 0xFF;
        $g1 = ($c1 >> 8) & 0xFF;
        $b1 = $c1 & 0xFF;
        $a1 = ($c1 >> 24) & 0x7F;

        $r2 = ($c2 >> 16) & 0xFF;
        $g2 = ($c2 >> 8) & 0xFF;
        $b2 = $c2 & 0xFF;
        $a2 = ($c2 >> 24) & 0x7F;

        // Blend alpha (GD: 0=opaque, 127=transparent) into RGB
        $blend1 = 1.0 - ($a1 / 127.0);
        $blend2 = 1.0 - ($a2 / 127.0);

        $r1 *= $blend1;
        $g1 *= $blend1;
        $b1 *= $blend1;
        $r2 *= $blend2;
        $g2 *= $blend2;
        $b2 *= $blend2;

        // RGB to YIQ
        $y1 = $r1 * 0.29889531 + $g1 * 0.58662247 + $b1 * 0.11448223;
        $i1 = $r1 * 0.59597799 - $g1 * 0.27417610 - $b1 * 0.32180189;
        $q1 = $r1 * 0.21147017 - $g1 * 0.52261711 + $b1 * 0.31114694;

        $y2 = $r2 * 0.29889531 + $g2 * 0.58662247 + $b2 * 0.11448223;
        $i2 = $r2 * 0.59597799 - $g2 * 0.27417610 - $b2 * 0.32180189;
        $q2 = $r2 * 0.21147017 - $g2 * 0.52261711 + $b2 * 0.31114694;

        $dy = $y1 - $y2;
        $di = $i1 - $i2;
        $dq = $q1 - $q2;

        // Luminance-weighted: Y has 0.5053 weight
        return 0.5053 * $dy * $dy + 0.299 * $di * $di + 0.1957 * $dq * $dq;
    }
}
