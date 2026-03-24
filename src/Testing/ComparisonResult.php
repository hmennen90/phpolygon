<?php

declare(strict_types=1);

namespace PHPolygon\Testing;

/**
 * Result of a visual regression screenshot comparison.
 */
class ComparisonResult
{
    public function __construct(
        public readonly bool $match,
        public readonly int $mismatchedPixels,
        public readonly int $totalPixels,
        public readonly float $mismatchRatio,
        public readonly ?string $diffPath,
        public readonly ?string $error,
    ) {}

    /**
     * Check if the comparison passes within the given tolerance.
     */
    public function passes(?int $maxDiffPixels = null, ?float $maxDiffPixelRatio = null): bool
    {
        if ($this->error !== null) {
            return false;
        }

        if ($this->match) {
            return true;
        }

        if ($maxDiffPixels !== null && $this->mismatchedPixels <= $maxDiffPixels) {
            return true;
        }

        if ($maxDiffPixelRatio !== null && $this->mismatchRatio <= $maxDiffPixelRatio) {
            return true;
        }

        return false;
    }

    public function summary(): string
    {
        if ($this->error !== null) {
            return "Error: {$this->error}";
        }

        if ($this->match) {
            return "Match: {$this->totalPixels} pixels compared, 0 differences";
        }

        return sprintf(
            'Mismatch: %d/%d pixels differ (%.2f%%)',
            $this->mismatchedPixels,
            $this->totalPixels,
            $this->mismatchRatio * 100,
        );
    }
}
