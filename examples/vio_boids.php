<?php

/**
 * PHPolygon -- vio 2D Boids / Flocking Simulation
 *
 * Classic Craig Reynolds boids with:
 *   - 3 species (colored flocks), each flocking with own kind
 *   - Predator that chases nearest boid, boids flee from it
 *   - Spatial grid for efficient neighbor lookups
 *   - Subtle trail afterglow effect
 *   - Mouse click to spawn boids, keys 1/2/3 to switch species
 *   - Space to toggle predator, R to reset
 *
 * Run: php examples/vio_boids.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPolygon\Engine;
use PHPolygon\EngineConfig;
use PHPolygon\Math\Mat3;
use PHPolygon\Math\Vec2;
use PHPolygon\Rendering\Color;

if (!extension_loaded('vio')) {
    echo "This example requires the vio extension.\n";
    exit(1);
}

// -- Constants --
const SCREEN_W = 1200;
const SCREEN_H = 800;
const BOID_COUNT = 100; // per species (300 total)
const MAX_SPEED = 180.0;
const MIN_SPEED = 60.0;
const VISUAL_RANGE = 60.0;
const SEPARATION_DIST = 22.0;
const PREDATOR_FLEE_RANGE = 120.0;
const PREDATOR_SPEED = 130.0;
const PREDATOR_CHASE_RANGE = 300.0;
const TRAIL_LENGTH = 4;
const GRID_CELL = 65; // slightly larger than VISUAL_RANGE

// -- Colors --
$speciesColors = [
    new Color(0.2, 0.7, 1.0),   // species 0 - blue
    new Color(0.2, 1.0, 0.4),   // species 1 - green
    new Color(1.0, 0.7, 0.15),  // species 2 - orange
];
$speciesTrailColors = [
    new Color(0.2, 0.7, 1.0, 0.12),
    new Color(0.2, 1.0, 0.4, 0.12),
    new Color(1.0, 0.7, 0.15, 0.12),
];
$speciesNames = ['Blue', 'Green', 'Orange'];
$bgColor = new Color(0.02, 0.02, 0.06);
$predatorColor = new Color(1.0, 0.15, 0.15);
$predatorTrailColor = new Color(1.0, 0.15, 0.15, 0.1);
$hudBg = new Color(0.0, 0.0, 0.0, 0.5);
$textColor = new Color(1.0, 1.0, 1.0);

// -- Boid structure --
// Each boid: x, y, vx, vy, species, trail (array of [x,y])

/** @var list<object> */
$boids = [];

function makeBoid(float $x, float $y, int $species): object
{
    $angle = mt_rand(0, 628) / 100.0;
    $speed = MIN_SPEED + mt_rand(0, 100) / 100.0 * (MAX_SPEED - MIN_SPEED) * 0.5;
    return (object) [
        'x' => $x,
        'y' => $y,
        'vx' => cos($angle) * $speed,
        'vy' => sin($angle) * $speed,
        'species' => $species,
        'trail' => [],
    ];
}

// Spawn initial boids
for ($s = 0; $s < 3; $s++) {
    for ($i = 0; $i < BOID_COUNT; $i++) {
        $boids[] = makeBoid(
            (float) mt_rand(50, SCREEN_W - 50),
            (float) mt_rand(50, SCREEN_H - 50),
            $s,
        );
    }
}

// -- Predator --
$predator = (object) [
    'x' => SCREEN_W / 2.0,
    'y' => SCREEN_H / 2.0,
    'vx' => 0.0,
    'vy' => 0.0,
    'active' => true,
    'trail' => [],
];

// -- Tunable weights --
$weights = (object) [
    'separation' => 2.5,
    'alignment' => 1.0,
    'cohesion' => 1.0,
];

$activeSpecies = 0;
$time = 0.0;

// -- Spatial grid --
$gridCols = (int) ceil(SCREEN_W / GRID_CELL);
$gridRows = (int) ceil(SCREEN_H / GRID_CELL);

/**
 * Build spatial grid. Returns array indexed by cellIndex => list of boid indices.
 * @param list<object> $boids
 * @return array<int, list<int>>
 */
function buildGrid(array &$boids, int $gridCols, int $gridRows): array
{
    $grid = [];
    foreach ($boids as $i => $b) {
        $cx = (int) ($b->x / GRID_CELL);
        $cy = (int) ($b->y / GRID_CELL);
        if ($cx < 0) $cx = 0;
        if ($cy < 0) $cy = 0;
        if ($cx >= $gridCols) $cx = $gridCols - 1;
        if ($cy >= $gridRows) $cy = $gridRows - 1;
        $key = $cy * $gridCols + $cx;
        $grid[$key][] = $i;
    }
    return $grid;
}

/**
 * Get boid indices in neighboring cells.
 * @param array<int, list<int>> $grid
 * @return list<int>
 */
function getNeighborIndices(float $x, float $y, array &$grid, int $gridCols, int $gridRows): array
{
    $cx = (int) ($x / GRID_CELL);
    $cy = (int) ($y / GRID_CELL);
    $result = [];
    for ($dy = -1; $dy <= 1; $dy++) {
        $ny = $cy + $dy;
        if ($ny < 0 || $ny >= $gridRows) continue;
        for ($dx = -1; $dx <= 1; $dx++) {
            $nx = $cx + $dx;
            if ($nx < 0 || $nx >= $gridCols) continue;
            $key = $ny * $gridCols + $nx;
            if (isset($grid[$key])) {
                foreach ($grid[$key] as $idx) {
                    $result[] = $idx;
                }
            }
        }
    }
    return $result;
}

// -- Engine --
$engine = new Engine(new EngineConfig(
    title: 'PHPolygon -- Boids Flocking Simulation',
    width: SCREEN_W,
    height: SCREEN_H,
));

// -- Update --
$engine->onUpdate(function (Engine $engine, float $dt) use (
    &$boids, $predator, $weights, &$activeSpecies, &$time,
    $gridCols, $gridRows, $speciesNames,
): void {
    $time += $dt;
    $input = $engine->input;

    // ESC to quit
    if ($input->isKeyPressed(256)) {
        $engine->stop();
        return;
    }

    // R to reset
    if ($input->isKeyPressed(82)) {
        $boids = [];
        for ($s = 0; $s < 3; $s++) {
            for ($i = 0; $i < BOID_COUNT; $i++) {
                $boids[] = makeBoid(
                    (float) mt_rand(50, SCREEN_W - 50),
                    (float) mt_rand(50, SCREEN_H - 50),
                    $s,
                );
            }
        }
        $predator->x = SCREEN_W / 2.0;
        $predator->y = SCREEN_H / 2.0;
        $predator->vx = 0.0;
        $predator->vy = 0.0;
        $predator->trail = [];
        return;
    }

    // Species selection: 1, 2, 3
    if ($input->isKeyPressed(49)) $activeSpecies = 0;
    if ($input->isKeyPressed(50)) $activeSpecies = 1;
    if ($input->isKeyPressed(51)) $activeSpecies = 2;

    // Space toggles predator
    if ($input->isKeyPressed(32)) {
        $predator->active = !$predator->active;
    }

    // Mouse click spawns boids
    if ($input->isMouseButtonPressed(0)) {
        $r = $engine->renderer2D;
        $actualW = $r->getWidth();
        $actualH = $r->getHeight();
        $mx = $input->getMouseX() / ($actualW / SCREEN_W);
        $my = $input->getMouseY() / ($actualH / SCREEN_H);
        for ($i = 0; $i < 10; $i++) {
            $boids[] = makeBoid(
                $mx + mt_rand(-20, 20),
                $my + mt_rand(-20, 20),
                $activeSpecies,
            );
        }
    }

    // -- Weight adjustment with arrow keys --
    // Up/Down adjust separation, Left/Right adjust cohesion (alignment auto)
    $adjustSpeed = 1.5 * $dt;
    if ($input->isKeyDown(265)) $weights->separation = min(5.0, $weights->separation + $adjustSpeed); // Up
    if ($input->isKeyDown(264)) $weights->separation = max(0.0, $weights->separation - $adjustSpeed); // Down
    if ($input->isKeyDown(262)) $weights->cohesion = min(5.0, $weights->cohesion + $adjustSpeed);     // Right
    if ($input->isKeyDown(263)) $weights->cohesion = max(0.0, $weights->cohesion - $adjustSpeed);     // Left
    if ($input->isKeyDown(87))  $weights->alignment = min(5.0, $weights->alignment + $adjustSpeed);   // W
    if ($input->isKeyDown(83))  $weights->alignment = max(0.0, $weights->alignment - $adjustSpeed);   // S

    // -- Build spatial grid --
    $grid = buildGrid($boids, $gridCols, $gridRows);

    $visualRangeSq = VISUAL_RANGE * VISUAL_RANGE;
    $separationDistSq = SEPARATION_DIST * SEPARATION_DIST;
    $fleeRangeSq = PREDATOR_FLEE_RANGE * PREDATOR_FLEE_RANGE;

    $boidCount = count($boids);

    // -- Update each boid --
    foreach ($boids as $i => $b) {
        // Store trail position (every other frame to save memory)
        if (count($b->trail) === 0 || $time - (int)($time * 15) % 2 === 0) {
            $b->trail[] = [$b->x, $b->y];
            if (count($b->trail) > TRAIL_LENGTH) {
                array_shift($b->trail);
            }
        }

        $sepX = 0.0; $sepY = 0.0;
        $aliVx = 0.0; $aliVy = 0.0;
        $cohX = 0.0; $cohY = 0.0;
        $neighbors = 0;

        // Get candidate neighbors from grid
        $candidates = getNeighborIndices($b->x, $b->y, $grid, $gridCols, $gridRows);

        foreach ($candidates as $j) {
            if ($j === $i) continue;
            $other = $boids[$j];
            if ($other->species !== $b->species) continue;

            // Wrap-aware distance
            $dx = $other->x - $b->x;
            $dy = $other->y - $b->y;
            // Handle wrapping
            if ($dx > SCREEN_W * 0.5) $dx -= SCREEN_W;
            elseif ($dx < -SCREEN_W * 0.5) $dx += SCREEN_W;
            if ($dy > SCREEN_H * 0.5) $dy -= SCREEN_H;
            elseif ($dy < -SCREEN_H * 0.5) $dy += SCREEN_H;

            $distSq = $dx * $dx + $dy * $dy;
            if ($distSq > $visualRangeSq || $distSq < 0.001) continue;

            $neighbors++;

            // Separation
            if ($distSq < $separationDistSq) {
                $factor = 1.0 - sqrt($distSq) / SEPARATION_DIST;
                $sepX -= $dx * $factor;
                $sepY -= $dy * $factor;
            }

            // Alignment
            $aliVx += $other->vx;
            $aliVy += $other->vy;

            // Cohesion
            $cohX += $dx;
            $cohY += $dy;
        }

        $ax = 0.0;
        $ay = 0.0;

        if ($neighbors > 0) {
            // Separation force
            $ax += $sepX * $weights->separation * 50.0;
            $ay += $sepY * $weights->separation * 50.0;

            // Alignment force
            $aliVx /= $neighbors;
            $aliVy /= $neighbors;
            $ax += ($aliVx - $b->vx) * $weights->alignment * 2.0;
            $ay += ($aliVy - $b->vy) * $weights->alignment * 2.0;

            // Cohesion force
            $cohX /= $neighbors;
            $cohY /= $neighbors;
            $ax += $cohX * $weights->cohesion * 1.5;
            $ay += $cohY * $weights->cohesion * 1.5;
        }

        // Flee from predator
        if ($predator->active) {
            $pdx = $predator->x - $b->x;
            $pdy = $predator->y - $b->y;
            if ($pdx > SCREEN_W * 0.5) $pdx -= SCREEN_W;
            elseif ($pdx < -SCREEN_W * 0.5) $pdx += SCREEN_W;
            if ($pdy > SCREEN_H * 0.5) $pdy -= SCREEN_H;
            elseif ($pdy < -SCREEN_H * 0.5) $pdy += SCREEN_H;
            $pDistSq = $pdx * $pdx + $pdy * $pdy;

            if ($pDistSq < $fleeRangeSq && $pDistSq > 0.01) {
                $pDist = sqrt($pDistSq);
                $fleeFactor = (1.0 - $pDist / PREDATOR_FLEE_RANGE) * 400.0;
                $ax -= ($pdx / $pDist) * $fleeFactor;
                $ay -= ($pdy / $pDist) * $fleeFactor;
            }
        }

        // Apply acceleration
        $b->vx += $ax * $dt;
        $b->vy += $ay * $dt;

        // Clamp speed
        $speed = sqrt($b->vx * $b->vx + $b->vy * $b->vy);
        if ($speed > MAX_SPEED) {
            $b->vx = ($b->vx / $speed) * MAX_SPEED;
            $b->vy = ($b->vy / $speed) * MAX_SPEED;
        } elseif ($speed < MIN_SPEED && $speed > 0.01) {
            $b->vx = ($b->vx / $speed) * MIN_SPEED;
            $b->vy = ($b->vy / $speed) * MIN_SPEED;
        }

        // Move
        $b->x += $b->vx * $dt;
        $b->y += $b->vy * $dt;

        // Wrap around edges
        if ($b->x < 0) $b->x += SCREEN_W;
        if ($b->x >= SCREEN_W) $b->x -= SCREEN_W;
        if ($b->y < 0) $b->y += SCREEN_H;
        if ($b->y >= SCREEN_H) $b->y -= SCREEN_H;
    }

    // -- Update predator --
    if ($predator->active && $boidCount > 0) {
        // Store trail
        $predator->trail[] = [$predator->x, $predator->y];
        if (count($predator->trail) > TRAIL_LENGTH + 2) {
            array_shift($predator->trail);
        }

        // Find nearest boid
        $nearestDist = PHP_FLOAT_MAX;
        $nearestBoid = null;
        // Use grid around predator
        $pCandidates = getNeighborIndices($predator->x, $predator->y, $grid, $gridCols, $gridRows);
        foreach ($pCandidates as $j) {
            $b = $boids[$j];
            $dx = $b->x - $predator->x;
            $dy = $b->y - $predator->y;
            $dSq = $dx * $dx + $dy * $dy;
            if ($dSq < $nearestDist) {
                $nearestDist = $dSq;
                $nearestBoid = $b;
            }
        }

        // If no nearby boid found, search wider (fallback)
        if ($nearestBoid === null) {
            foreach ($boids as $b) {
                $dx = $b->x - $predator->x;
                $dy = $b->y - $predator->y;
                $dSq = $dx * $dx + $dy * $dy;
                if ($dSq < $nearestDist) {
                    $nearestDist = $dSq;
                    $nearestBoid = $b;
                }
            }
        }

        if ($nearestBoid !== null && $nearestDist < PREDATOR_CHASE_RANGE * PREDATOR_CHASE_RANGE) {
            $dx = $nearestBoid->x - $predator->x;
            $dy = $nearestBoid->y - $predator->y;
            $dist = sqrt($dx * $dx + $dy * $dy);
            if ($dist > 0.1) {
                $predator->vx += ($dx / $dist) * 200.0 * $dt;
                $predator->vy += ($dy / $dist) * 200.0 * $dt;
            }
        }

        // Clamp predator speed
        $pSpeed = sqrt($predator->vx * $predator->vx + $predator->vy * $predator->vy);
        if ($pSpeed > PREDATOR_SPEED) {
            $predator->vx = ($predator->vx / $pSpeed) * PREDATOR_SPEED;
            $predator->vy = ($predator->vy / $pSpeed) * PREDATOR_SPEED;
        }

        $predator->x += $predator->vx * $dt;
        $predator->y += $predator->vy * $dt;

        // Wrap
        if ($predator->x < 0) $predator->x += SCREEN_W;
        if ($predator->x >= SCREEN_W) $predator->x -= SCREEN_W;
        if ($predator->y < 0) $predator->y += SCREEN_H;
        if ($predator->y >= SCREEN_H) $predator->y -= SCREEN_H;

        // Eat boids that get too close
        foreach ($boids as $i => $b) {
            $dx = $b->x - $predator->x;
            $dy = $b->y - $predator->y;
            if ($dx * $dx + $dy * $dy < 100.0) { // ~10px radius
                unset($boids[$i]);
            }
        }
        $boids = array_values($boids);
    }
});

// -- Render --
$engine->onRender(function (Engine $engine) use (
    &$boids, $predator, $weights, &$activeSpecies, &$time,
    $speciesColors, $speciesTrailColors, $speciesNames,
    $bgColor, $predatorColor, $predatorTrailColor, $hudBg, $textColor,
): void {
    $r = $engine->renderer2D;
    $actualW = $r->getWidth();
    $actualH = $r->getHeight();

    $r->clear($bgColor);

    // Scale from virtual coords to actual window size
    $sx = $actualW / SCREEN_W;
    $sy = $actualH / SCREEN_H;
    $r->pushTransform(Mat3::trs(new Vec2(0, 0), 0.0, new Vec2($sx, $sy)));

    $w = SCREEN_W;
    $h = SCREEN_H;

    // -- Draw boid trails --
    foreach ($boids as $b) {
        $trailCount = count($b->trail);
        if ($trailCount < 2) continue;
        $color = $speciesTrailColors[$b->species];
        for ($t = 0; $t < $trailCount - 1; $t++) {
            $alpha = ($t + 1) / $trailCount * 0.15;
            $tc = new Color($color->r, $color->g, $color->b, $alpha);
            $sz = 2.0 + ($t / $trailCount) * 1.5;
            $r->drawCircle($b->trail[$t][0], $b->trail[$t][1], $sz, $tc);
        }
    }

    // -- Draw predator trail --
    if ($predator->active) {
        $trailCount = count($predator->trail);
        for ($t = 0; $t < $trailCount; $t++) {
            $alpha = ($t + 1) / ($trailCount + 1) * 0.2;
            $tc = new Color($predatorColor->r, $predatorColor->g, $predatorColor->b, $alpha);
            $sz = 3.0 + ($t / max(1, $trailCount)) * 3.0;
            $r->drawCircle($predator->trail[$t][0], $predator->trail[$t][1], $sz, $tc);
        }
    }

    // -- Draw boids as directional triangles --
    foreach ($boids as $b) {
        $angle = atan2($b->vy, $b->vx);
        $color = $speciesColors[$b->species];

        // Triangle vertices relative to center: nose forward, two tail points
        $cosA = cos($angle);
        $sinA = sin($angle);

        // Nose (forward): 5px ahead
        $nx = $b->x + $cosA * 5.0;
        $ny = $b->y + $sinA * 5.0;

        // Left tail: -3px back, 3px left
        $lx = $b->x - $cosA * 3.0 - $sinA * 3.0;
        $ly = $b->y - $sinA * 3.0 + $cosA * 3.0;

        // Right tail: -3px back, 3px right
        $rx = $b->x - $cosA * 3.0 + $sinA * 3.0;
        $ry = $b->y - $sinA * 3.0 - $cosA * 3.0;

        // Draw triangle as 3 lines
        $r->drawLine(new Vec2($nx, $ny), new Vec2($lx, $ly), $color, 1.5);
        $r->drawLine(new Vec2($lx, $ly), new Vec2($rx, $ry), $color, 1.5);
        $r->drawLine(new Vec2($rx, $ry), new Vec2($nx, $ny), $color, 1.5);
    }

    // -- Draw predator --
    if ($predator->active) {
        $angle = atan2($predator->vy, $predator->vx);
        $cosA = cos($angle);
        $sinA = sin($angle);

        // Larger triangle for predator
        $nx = $predator->x + $cosA * 10.0;
        $ny = $predator->y + $sinA * 10.0;
        $lx = $predator->x - $cosA * 6.0 - $sinA * 6.0;
        $ly = $predator->y - $sinA * 6.0 + $cosA * 6.0;
        $rx = $predator->x - $cosA * 6.0 + $sinA * 6.0;
        $ry = $predator->y - $sinA * 6.0 - $cosA * 6.0;

        // Glow
        $r->drawCircle($predator->x, $predator->y, 14.0, new Color(1.0, 0.1, 0.1, 0.15));

        $r->drawLine(new Vec2($nx, $ny), new Vec2($lx, $ly), $predatorColor, 2.5);
        $r->drawLine(new Vec2($lx, $ly), new Vec2($rx, $ry), $predatorColor, 2.5);
        $r->drawLine(new Vec2($rx, $ry), new Vec2($nx, $ny), $predatorColor, 2.5);
    }

    // -- HUD --
    $r->drawRoundedRect(8, 8, 260, 130, 6.0, $hudBg);

    $boidCount = count($boids);
    $speciesCounts = [0, 0, 0];
    foreach ($boids as $b) {
        $speciesCounts[$b->species]++;
    }

    $r->drawText(sprintf('Boids: %d', $boidCount), 16, 28, 16, $textColor);
    $r->drawText(sprintf('Species: %s (%d)', $speciesNames[$activeSpecies], $speciesCounts[$activeSpecies]), 16, 48, 14, $speciesColors[$activeSpecies]);
    $r->drawText(sprintf('Predator: %s', $predator->active ? 'ON' : 'OFF'), 16, 66, 14,
        $predator->active ? $predatorColor : new Color(0.4, 0.4, 0.4));

    $r->drawText(sprintf('Sep: %.1f  Ali: %.1f  Coh: %.1f', $weights->separation, $weights->alignment, $weights->cohesion), 16, 88, 12, new Color(0.6, 0.6, 0.7));

    // Species count bar
    $barY = 98;
    $barW = 240.0;
    $total = max(1, $boidCount);
    $bx = 16.0;
    for ($s = 0; $s < 3; $s++) {
        $segW = ($speciesCounts[$s] / $total) * $barW;
        if ($segW > 0.5) {
            $r->drawRect($bx, $barY, $segW, 8.0, $speciesColors[$s]);
            $bx += $segW;
        }
    }

    // Species legend
    for ($s = 0; $s < 3; $s++) {
        $lx = 16.0 + $s * 86.0;
        $r->drawRect($lx, 112, 8, 8, $speciesColors[$s]);
        $highlight = $s === $activeSpecies ? $textColor : new Color(0.5, 0.5, 0.5);
        $r->drawText(sprintf('%d: %s', $s + 1, $speciesNames[$s]), $lx + 12, 121, 11, $highlight);
    }

    // Controls hint
    $r->drawText('Click: Spawn  1/2/3: Species  Space: Predator  R: Reset  ESC: Quit', 8, $h - 30, 12, new Color(0.3, 0.3, 0.4));
    $r->drawText('Up/Down: Separation  W/S: Alignment  Left/Right: Cohesion', 8, $h - 14, 12, new Color(0.3, 0.3, 0.4));

    $r->popTransform();
});

$engine->run();
