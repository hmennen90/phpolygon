<?php

/**
 * PHPolygon -- vio 2D Space Attack Demo
 *
 * A classic space shooter with:
 *   - Player ship (A/D to move, Space to shoot)
 *   - Enemy waves descending from above
 *   - Power-ups (spread shot, shield, rapid fire)
 *   - Explosions and particle effects
 *   - Score and lives
 *
 * Run: php examples/vio_space_attack.php
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
const SHIP_SPEED = 420.0;
const BULLET_SPEED = 600.0;
const ENEMY_BULLET_SPEED = 280.0;
const FIRE_COOLDOWN = 0.15;
const SCREEN_W = 800;
const SCREEN_H = 700;

// -- Colors --
$colors = (object) [
    'bg'            => new Color(0.04, 0.02, 0.1),
    'shipBody'      => new Color(0.2, 0.6, 0.95),
    'shipCockpit'   => new Color(0.5, 0.85, 1.0),
    'shipEngine'    => new Color(1.0, 0.5, 0.1),
    'shipEngineHot' => new Color(1.0, 0.9, 0.3),
    'bullet'        => new Color(0.3, 1.0, 0.4),
    'bulletGlow'    => new Color(0.3, 1.0, 0.4, 0.3),
    'enemyA'        => new Color(0.9, 0.25, 0.2),
    'enemyB'        => new Color(0.85, 0.5, 0.1),
    'enemyC'        => new Color(0.7, 0.2, 0.8),
    'enemyBullet'   => new Color(1.0, 0.3, 0.3),
    'starDim'       => new Color(1.0, 1.0, 1.0, 0.3),
    'starBright'    => new Color(1.0, 1.0, 1.0, 0.7),
    'hud'           => new Color(0.0, 0.0, 0.0, 0.5),
    'text'          => new Color(1.0, 1.0, 1.0),
    'scoreText'     => new Color(0.3, 1.0, 0.4),
    'shield'        => new Color(0.2, 0.6, 1.0, 0.25),
    'shieldRim'     => new Color(0.4, 0.8, 1.0, 0.6),
    'powerSpread'   => new Color(1.0, 0.8, 0.1),
    'powerShield'   => new Color(0.2, 0.6, 1.0),
    'powerRapid'    => new Color(1.0, 0.3, 0.6),
    'explosion'     => new Color(1.0, 0.7, 0.2),
    'white'         => new Color(1.0, 1.0, 1.0),
    'waveText'      => new Color(1.0, 0.85, 0.2),
];

// -- Game state --
$player = (object) [
    'x' => SCREEN_W / 2,
    'y' => SCREEN_H - 70,
    'w' => 36.0,
    'h' => 40.0,
    'lives' => 3,
    'score' => 0,
    'fireCooldown' => 0.0,
    'spreadShot' => 0.0,   // timer
    'shield' => 0.0,       // timer
    'rapidFire' => 0.0,    // timer
    'invincible' => 0.0,   // after-hit invincibility
    'dead' => false,
    'engineAnim' => 0.0,
];

/** @var list<object> */
$bullets = [];
/** @var list<object> */
$enemyBullets = [];
/** @var list<object> */
$enemies = [];
/** @var list<object> */
$particles = [];
/** @var list<object> */
$powerups = [];
/** @var list<object> */
$stars = [];

// Star field (three layers)
for ($i = 0; $i < 80; $i++) {
    $layer = $i % 3;
    $stars[] = (object) [
        'x' => mt_rand(0, SCREEN_W),
        'y' => mt_rand(0, SCREEN_H),
        'speed' => 30.0 + $layer * 40.0,
        'size' => 1.0 + $layer * 0.5,
        'bright' => $layer === 2,
    ];
}

$time = 0.0;
$wave = 0;
$waveTimer = 2.0; // countdown to first wave
$waveAnnounce = 0.0;
$enemiesAlive = 0;
$gameOver = false;
$highScore = 0;

// -- Formation types --
// Each formation defines how enemies are spawned and how they move.
// 'grid'     - classic Space Invaders grid, sways left/right
// 'v'        - V-formation, sweeps in from top
// 'circle'   - enemies orbit a center point
// 'dive'     - enemies line up then take turns diving at the player
// 'sine'     - enemies move in sine waves across the screen
// 'split'    - two groups enter from left and right

const FORMATION_GRID   = 0;
const FORMATION_V      = 1;
const FORMATION_CIRCLE = 2;
const FORMATION_DIVE   = 3;
const FORMATION_SINE   = 4;
const FORMATION_SPLIT  = 5;
const FORMATION_COUNT  = 6;

function spawnWave(int $wave, array &$enemies, int &$enemiesAlive): void
{
    $hp = 1 + intdiv($wave, 3);
    $speed = 25.0 + $wave * 5.0;
    $shootChance = 0.002 + $wave * 0.001;
    $formation = ($wave - 1) % FORMATION_COUNT;

    $spawned = [];

    match ($formation) {
        FORMATION_GRID => $spawned = spawnGrid($wave, $hp, $speed, $shootChance),
        FORMATION_V => $spawned = spawnV($wave, $hp, $speed, $shootChance),
        FORMATION_CIRCLE => $spawned = spawnCircle($wave, $hp, $speed, $shootChance),
        FORMATION_DIVE => $spawned = spawnDive($wave, $hp, $speed, $shootChance),
        FORMATION_SINE => $spawned = spawnSine($wave, $hp, $speed, $shootChance),
        FORMATION_SPLIT => $spawned = spawnSplit($wave, $hp, $speed, $shootChance),
    };

    foreach ($spawned as $e) {
        $enemies[] = $e;
        $enemiesAlive++;
    }
}

function makeEnemy(float $x, float $y, int $type, int $hp, float $speed, float $shootChance, int $formation, array $extra = []): object
{
    return (object) array_merge([
        'x' => $x, 'y' => $y,
        'w' => 32.0, 'h' => 28.0,
        'hp' => $hp, 'maxHp' => $hp,
        'type' => $type,
        'speed' => $speed, 'dir' => 1.0,
        'shootChance' => $shootChance,
        'alive' => true,
        'entering' => true,
        'enterY' => 100.0,
        'formation' => $formation,
        'anim' => mt_rand(0, 100) / 10.0,
        // Formation-specific
        'homeX' => $x, 'homeY' => 100.0,
        'phase' => 0.0,
        'orbitAngle' => 0.0,
        'orbitRadius' => 0.0,
        'orbitCX' => SCREEN_W / 2, 'orbitCY' => 160.0,
        'diving' => false, 'diveDelay' => 0.0,
        'sineAmp' => 0.0, 'sineFreq' => 0.0,
        'groupDir' => 1.0,
    ], $extra);
}

function spawnGrid(int $wave, int $hp, float $speed, float $shootChance): array
{
    $rows = min(3 + intdiv($wave, 2), 5);
    $cols = min(6 + intdiv($wave, 3), 10);
    $out = [];
    for ($r = 0; $r < $rows; $r++) {
        for ($c = 0; $c < $cols; $c++) {
            $ex = 100 + $c * 65;
            $ey = -40 - $r * 50;
            $enterY = 50.0 + $r * 50.0;
            $out[] = makeEnemy($ex, $ey, $r % 3, $hp, $speed, $shootChance, FORMATION_GRID, [
                'enterY' => $enterY, 'homeX' => (float)$ex, 'homeY' => $enterY,
            ]);
        }
    }
    return $out;
}

function spawnV(int $wave, int $hp, float $speed, float $shootChance): array
{
    $size = min(5 + intdiv($wave, 2), 9);
    $out = [];
    $cx = SCREEN_W / 2;
    for ($i = 0; $i < $size; $i++) {
        $side = ($i % 2 === 0) ? -1 : 1;
        $rank = intdiv($i + 1, 2);
        $ex = $cx + $side * $rank * 55;
        $ey = -40 - $rank * 40;
        $enterY = 60.0 + $rank * 35.0;
        $out[] = makeEnemy($ex, $ey, $i % 3, $hp, $speed * 1.3, $shootChance, FORMATION_V, [
            'enterY' => $enterY, 'homeX' => (float)$ex, 'homeY' => $enterY,
            'sineAmp' => 80.0 + $rank * 20.0, 'sineFreq' => 1.2,
        ]);
    }
    return $out;
}

function spawnCircle(int $wave, int $hp, float $speed, float $shootChance): array
{
    $count = min(8 + intdiv($wave, 2), 14);
    $out = [];
    $cx = SCREEN_W / 2;
    $cy = 170.0;
    $radius = 100.0 + $count * 4;
    for ($i = 0; $i < $count; $i++) {
        $angle = ($i / $count) * 2 * M_PI;
        $ex = $cx + cos($angle) * $radius;
        $ey = -40 - $i * 15;
        $out[] = makeEnemy($ex, $ey, $i % 3, $hp, $speed, $shootChance * 0.7, FORMATION_CIRCLE, [
            'enterY' => $cy + sin($angle) * $radius,
            'orbitAngle' => $angle,
            'orbitRadius' => $radius,
            'orbitCX' => $cx, 'orbitCY' => $cy,
        ]);
    }
    return $out;
}

function spawnDive(int $wave, int $hp, float $speed, float $shootChance): array
{
    $count = min(8 + intdiv($wave, 2), 12);
    $out = [];
    for ($i = 0; $i < $count; $i++) {
        $ex = 80 + $i * ((SCREEN_W - 160) / max(1, $count - 1));
        $ey = -40 - $i * 20;
        $enterY = 60.0 + ($i % 3) * 30.0;
        $out[] = makeEnemy($ex, $ey, $i % 3, $hp, $speed * 1.5, $shootChance, FORMATION_DIVE, [
            'enterY' => $enterY, 'homeX' => (float)$ex, 'homeY' => $enterY,
            'diveDelay' => 2.0 + $i * 0.8,
        ]);
    }
    return $out;
}

function spawnSine(int $wave, int $hp, float $speed, float $shootChance): array
{
    $count = min(10 + intdiv($wave, 2), 16);
    $out = [];
    for ($i = 0; $i < $count; $i++) {
        $ex = 60 + $i * ((SCREEN_W - 120) / max(1, $count - 1));
        $ey = -40 - $i * 18;
        $enterY = 70.0 + ($i % 4) * 30.0;
        $out[] = makeEnemy($ex, $ey, $i % 3, $hp, $speed * 0.8, $shootChance, FORMATION_SINE, [
            'enterY' => $enterY, 'homeX' => (float)$ex, 'homeY' => $enterY,
            'sineAmp' => 40.0 + ($i % 3) * 25.0,
            'sineFreq' => 1.5 + ($i % 2) * 0.8,
            'phase' => $i * 0.5,
        ]);
    }
    return $out;
}

function spawnSplit(int $wave, int $hp, float $speed, float $shootChance): array
{
    $perSide = min(4 + intdiv($wave, 3), 7);
    $out = [];
    for ($i = 0; $i < $perSide; $i++) {
        // Left group enters from left
        $ey = -40 - $i * 30;
        $enterY = 70.0 + $i * 40.0;
        $out[] = makeEnemy(-40.0, $ey, 0, $hp, $speed * 1.2, $shootChance, FORMATION_SPLIT, [
            'enterY' => $enterY, 'homeX' => 150.0 + $i * 40.0, 'homeY' => $enterY,
            'groupDir' => 1.0,
        ]);
        // Right group enters from right
        $out[] = makeEnemy((float)(SCREEN_W + 40), $ey, 2, $hp, $speed * 1.2, $shootChance, FORMATION_SPLIT, [
            'enterY' => $enterY, 'homeX' => SCREEN_W - 150.0 - $i * 40.0, 'homeY' => $enterY,
            'groupDir' => -1.0,
        ]);
    }
    return $out;
}

function spawnExplosion(float $x, float $y, int $count, Color $color, array &$particles): void
{
    for ($i = 0; $i < $count; $i++) {
        $angle = mt_rand(0, 360) * M_PI / 180.0;
        $speed = 40.0 + mt_rand(0, 200);
        $particles[] = (object) [
            'x' => $x,
            'y' => $y,
            'vx' => cos($angle) * $speed,
            'vy' => sin($angle) * $speed,
            'life' => 0.4 + mt_rand(0, 40) / 100.0,
            'maxLife' => 0.4 + mt_rand(0, 40) / 100.0,
            'size' => 2.0 + mt_rand(0, 30) / 10.0,
            'color' => $color,
        ];
    }
}

function spawnPowerup(float $x, float $y, array &$powerups): void
{
    if (mt_rand(0, 100) > 25) return; // 25% chance
    $types = ['spread', 'shield', 'rapid'];
    $type = $types[mt_rand(0, 2)];
    $powerups[] = (object) [
        'x' => $x,
        'y' => $y,
        'w' => 20.0,
        'h' => 20.0,
        'type' => $type,
        'speed' => 80.0,
        'alive' => true,
        'anim' => 0.0,
    ];
}

// -- Engine --
$engine = new Engine(new EngineConfig(
    title: 'PHPolygon -- Space Attack',
    width: SCREEN_W,
    height: SCREEN_H,
));

// -- Helpers --
function overlap(float $ax, float $ay, float $aw, float $ah, float $bx, float $by, float $bw, float $bh): bool
{
    return $ax < $bx + $bw && $ax + $aw > $bx && $ay < $by + $bh && $ay + $ah > $by;
}

// -- Update --
$engine->onUpdate(function (Engine $engine, float $dt) use (
    $player, &$bullets, &$enemyBullets, &$enemies, &$particles, &$powerups, &$stars,
    &$time, &$wave, &$waveTimer, &$waveAnnounce, &$enemiesAlive, &$gameOver, &$highScore, $colors,
): void {
    $time += $dt;
    $input = $engine->input;

    if ($input->isKeyPressed(256)) { // ESC
        $engine->stop();
        return;
    }

    // Restart
    if ($gameOver && $input->isKeyPressed(82)) { // R
        $player->lives = 3;
        $player->score = 0;
        $player->dead = false;
        $player->x = SCREEN_W / 2;
        $player->y = SCREEN_H - 70;
        $player->spreadShot = 0;
        $player->shield = 0;
        $player->rapidFire = 0;
        $player->invincible = 0;
        $bullets = [];
        $enemyBullets = [];
        $enemies = [];
        $particles = [];
        $powerups = [];
        $wave = 0;
        $waveTimer = 2.0;
        $enemiesAlive = 0;
        $gameOver = false;
    }

    if ($gameOver) return;

    // -- Stars --
    foreach ($stars as $star) {
        $star->y += $star->speed * $dt;
        if ($star->y > SCREEN_H) {
            $star->y = 0;
            $star->x = mt_rand(0, SCREEN_W);
        }
    }

    // -- Timers --
    $player->fireCooldown -= $dt;
    $player->spreadShot = max(0, $player->spreadShot - $dt);
    $player->shield = max(0, $player->shield - $dt);
    $player->rapidFire = max(0, $player->rapidFire - $dt);
    $player->invincible = max(0, $player->invincible - $dt);
    $player->engineAnim += $dt * 12.0;

    // -- Player movement --
    if ($input->isKeyDown(65) || $input->isKeyDown(263)) { // A / Left
        $player->x -= SHIP_SPEED * $dt;
    }
    if ($input->isKeyDown(68) || $input->isKeyDown(262)) { // D / Right
        $player->x += SHIP_SPEED * $dt;
    }
    $player->x = max($player->w / 2, min(SCREEN_W - $player->w / 2, $player->x));

    // -- Shooting --
    $cooldown = $player->rapidFire > 0 ? FIRE_COOLDOWN * 0.4 : FIRE_COOLDOWN;
    if ($input->isKeyDown(32) && $player->fireCooldown <= 0) { // Space
        $player->fireCooldown = $cooldown;

        // Center bullet
        $bullets[] = (object) ['x' => $player->x, 'y' => $player->y - $player->h / 2, 'vx' => 0.0, 'vy' => -BULLET_SPEED];

        // Spread shot
        if ($player->spreadShot > 0) {
            $bullets[] = (object) ['x' => $player->x - 10, 'y' => $player->y - $player->h / 2 + 4, 'vx' => -80.0, 'vy' => -BULLET_SPEED];
            $bullets[] = (object) ['x' => $player->x + 10, 'y' => $player->y - $player->h / 2 + 4, 'vx' => 80.0, 'vy' => -BULLET_SPEED];
        }
    }

    // -- Bullets --
    foreach ($bullets as $i => $b) {
        $b->x += $b->vx * $dt;
        $b->y += $b->vy * $dt;
        if ($b->y < -10 || $b->x < -10 || $b->x > SCREEN_W + 10) {
            unset($bullets[$i]);
        }
    }
    $bullets = array_values($bullets);

    // -- Enemy bullets --
    foreach ($enemyBullets as $i => $b) {
        $b->y += $b->vy * $dt;
        $b->x += $b->vx * $dt;
        if ($b->y > SCREEN_H + 10) {
            unset($enemyBullets[$i]);
        }
    }
    $enemyBullets = array_values($enemyBullets);

    // -- Wave management --
    if ($enemiesAlive <= 0 && count($enemies) === 0) {
        $waveTimer -= $dt;
        if ($waveTimer <= 0) {
            $wave++;
            $waveAnnounce = 2.5;
            spawnWave($wave, $enemies, $enemiesAlive);
            $waveTimer = 3.0;
        }
    }
    $waveAnnounce = max(0, $waveAnnounce - $dt);

    // -- Enemies (formation AI) --

    // Grid formation: determine group bounds for direction change
    $gridLeft = SCREEN_W;
    $gridRight = 0.0;
    foreach ($enemies as $e) {
        if (!$e->alive || $e->formation !== FORMATION_GRID) continue;
        $gridLeft = min($gridLeft, $e->x - $e->w / 2);
        $gridRight = max($gridRight, $e->x + $e->w / 2);
    }

    foreach ($enemies as $ei => $e) {
        if (!$e->alive) continue;

        $e->anim += $dt;

        // Enter animation (all formations)
        if ($e->entering) {
            if ($e->formation === FORMATION_SPLIT) {
                // Fly toward home position
                $dx = $e->homeX - $e->x;
                $dy = $e->homeY - $e->y;
                $dist = sqrt($dx * $dx + $dy * $dy);
                if ($dist < 5.0) {
                    $e->x = $e->homeX;
                    $e->y = $e->homeY;
                    $e->entering = false;
                } else {
                    $e->x += ($dx / $dist) * 200.0 * $dt;
                    $e->y += ($dy / $dist) * 200.0 * $dt;
                }
            } elseif ($e->formation === FORMATION_CIRCLE) {
                $e->y += 150.0 * $dt;
                if ($e->y >= $e->enterY) {
                    $e->y = $e->enterY;
                    $e->entering = false;
                }
            } else {
                $e->y += 120.0 * $dt;
                if ($e->y >= $e->enterY) {
                    $e->y = $e->enterY;
                    $e->entering = false;
                }
            }
            continue;
        }

        // Formation-specific movement
        match ($e->formation) {
            FORMATION_GRID => (function () use ($e, $dt, $gridLeft, $gridRight) {
                $e->x += $e->speed * $e->dir * $dt;
                if ($gridRight > SCREEN_W - 20 && $e->dir > 0) {
                    $e->dir = -1.0;
                    $e->y += 8.0;
                } elseif ($gridLeft < 20 && $e->dir < 0) {
                    $e->dir = 1.0;
                    $e->y += 8.0;
                }
            })(),

            FORMATION_V => (function () use ($e, $dt, $time) {
                // Sweep left/right in sync using sine
                $e->x = $e->homeX + sin($time * $e->sineFreq) * $e->sineAmp;
                // Slowly advance downward
                $e->y += 6.0 * $dt;
            })(),

            FORMATION_CIRCLE => (function () use ($e, $dt) {
                // Orbit around center
                $e->orbitAngle += $e->speed * 0.012 * $dt;
                $e->x = $e->orbitCX + cos($e->orbitAngle) * $e->orbitRadius;
                $e->y = $e->orbitCY + sin($e->orbitAngle) * $e->orbitRadius;
                // Slowly descend
                $e->orbitCY += 4.0 * $dt;
            })(),

            FORMATION_DIVE => (function () use ($e, $dt, $player) {
                if (!$e->diving) {
                    // Hover at home, countdown to dive
                    $e->x = $e->homeX + sin($e->anim * 2.0) * 20.0;
                    $e->diveDelay -= $dt;
                    if ($e->diveDelay <= 0) {
                        $e->diving = true;
                        // Aim at player's current position
                        $dx = $player->x - $e->x;
                        $dy = $player->y - $e->y;
                        $dist = max(1.0, sqrt($dx * $dx + $dy * $dy));
                        $e->dir = $dx / $dist;
                        $e->phase = $dy / $dist;
                    }
                } else {
                    // Dive toward player
                    $diveSpeed = $e->speed * 4.0;
                    $e->x += $e->dir * $diveSpeed * $dt;
                    $e->y += $e->phase * $diveSpeed * $dt;
                    // If missed (went off screen sides), loop back up
                    if ($e->y > SCREEN_H + 30) {
                        $e->y = -40.0;
                        $e->x = $e->homeX;
                        $e->diving = false;
                        $e->diveDelay = 1.5 + mt_rand(0, 200) / 100.0;
                    }
                }
            })(),

            FORMATION_SINE => (function () use ($e, $dt, $time) {
                // Sinusoidal wave pattern
                $e->x = $e->homeX + sin($time * $e->sineFreq + $e->phase) * $e->sineAmp;
                $e->y = $e->homeY + cos($time * $e->sineFreq * 0.7 + $e->phase) * 20.0;
                // Slowly advance
                $e->homeY += 5.0 * $dt;
            })(),

            FORMATION_SPLIT => (function () use ($e, $dt, $time) {
                // Two groups oscillate, gradually closing in
                $e->x = $e->homeX + sin($time * 1.5) * 60.0 * $e->groupDir;
                $e->y = $e->homeY + sin($time * 2.0 + $e->homeX * 0.01) * 15.0;
                // Groups slowly move toward center and down
                $e->homeX += (SCREEN_W / 2 - $e->homeX) * 0.3 * $dt;
                $e->homeY += 8.0 * $dt;
            })(),
        };

        // Shooting (all formations)
        if (mt_rand(0, 10000) / 10000.0 < $e->shootChance) {
            // Aim roughly toward player
            $dx = $player->x - $e->x;
            $aimSpread = max(30.0, abs($dx) * 0.3);
            $vx = ($dx * 0.4) + (mt_rand(-100, 100) / 100.0) * $aimSpread * 0.5;
            $enemyBullets[] = (object) ['x' => $e->x, 'y' => $e->y + $e->h / 2, 'vx' => $vx, 'vy' => ENEMY_BULLET_SPEED];
        }

        // Reached bottom = lose a life
        if ($e->y > SCREEN_H - 40 && $e->formation !== FORMATION_DIVE) {
            $e->alive = false;
            $enemiesAlive--;
            $player->lives--;
            spawnExplosion($e->x, $e->y, 8, $colors->explosion, $particles);
            if ($player->lives <= 0) {
                $gameOver = true;
                $highScore = max($highScore, $player->score);
            }
        }
    }

    // -- Bullet vs Enemy collision --
    foreach ($bullets as $bi => $b) {
        $hit = false;
        foreach ($enemies as $ei => $e) {
            if (!$e->alive) continue;
            if (overlap($b->x - 3, $b->y - 6, 6, 12, $e->x - $e->w / 2, $e->y - $e->h / 2, $e->w, $e->h)) {
                $e->hp--;
                if ($e->hp <= 0) {
                    $e->alive = false;
                    $enemiesAlive--;
                    $player->score += 100 * ($e->type + 1);
                    spawnExplosion($e->x, $e->y, 15, $colors->explosion, $particles);
                    spawnPowerup($e->x, $e->y, $powerups);
                } else {
                    spawnExplosion($b->x, $b->y, 4, $colors->bullet, $particles);
                }
                $hit = true;
                break;
            }
        }
        if ($hit) {
            unset($bullets[$bi]);
        }
    }
    $bullets = array_values($bullets);

    // Remove dead enemies
    $enemies = array_values(array_filter($enemies, fn($e) => $e->alive));

    // -- Enemy bullet vs Player --
    if ($player->invincible <= 0) {
        foreach ($enemyBullets as $bi => $b) {
            if (overlap($b->x - 3, $b->y - 3, 6, 6,
                $player->x - $player->w / 2, $player->y - $player->h / 2, $player->w, $player->h)) {
                unset($enemyBullets[$bi]);
                if ($player->shield > 0) {
                    $player->shield = max(0, $player->shield - 1.0);
                    spawnExplosion($b->x, $b->y, 6, $colors->shieldRim, $particles);
                } else {
                    $player->lives--;
                    $player->invincible = 1.5;
                    spawnExplosion($player->x, $player->y, 12, $colors->explosion, $particles);
                    if ($player->lives <= 0) {
                        $gameOver = true;
                        $highScore = max($highScore, $player->score);
                    }
                }
            }
        }
        $enemyBullets = array_values($enemyBullets);
    }

    // -- Power-ups --
    foreach ($powerups as $pi => $p) {
        if (!$p->alive) continue;
        $p->y += $p->speed * $dt;
        $p->anim += $dt;
        if ($p->y > SCREEN_H + 30) {
            $p->alive = false;
            continue;
        }
        if (overlap($p->x - $p->w / 2, $p->y - $p->h / 2, $p->w, $p->h,
            $player->x - $player->w / 2, $player->y - $player->h / 2, $player->w, $player->h)) {
            $p->alive = false;
            match ($p->type) {
                'spread' => $player->spreadShot = 8.0,
                'shield' => $player->shield = 10.0,
                'rapid'  => $player->rapidFire = 6.0,
            };
            spawnExplosion($p->x, $p->y, 8, $colors->white, $particles);
        }
    }
    $powerups = array_values(array_filter($powerups, fn($p) => $p->alive));

    // -- Particles --
    foreach ($particles as $pi => $p) {
        $p->x += $p->vx * $dt;
        $p->y += $p->vy * $dt;
        $p->life -= $dt;
        if ($p->life <= 0) {
            unset($particles[$pi]);
        }
    }
    $particles = array_values($particles);
});

// -- Render --
$engine->onRender(function (Engine $engine) use (
    $player, &$bullets, &$enemyBullets, &$enemies, &$particles, &$powerups, &$stars,
    &$time, &$wave, &$waveAnnounce, &$gameOver, &$highScore, $colors,
): void {
    $r = $engine->renderer2D;
    $actualW = $r->getWidth();
    $actualH = $r->getHeight();

    // -- Background --
    $r->clear($colors->bg);

    // Scale from virtual 800x700 coords to actual window size
    $sx = $actualW / SCREEN_W;
    $sy = $actualH / SCREEN_H;
    $r->pushTransform(Mat3::trs(new Vec2(0, 0), 0.0, new Vec2($sx, $sy)));

    $w = SCREEN_W;
    $h = SCREEN_H;

    // Stars
    foreach ($stars as $star) {
        $c = $star->bright ? $colors->starBright : $colors->starDim;
        $r->drawCircle($star->x, $star->y, $star->size, $c);
    }

    // -- Particles --
    foreach ($particles as $p) {
        $alpha = max(0, $p->life / $p->maxLife);
        $pc = new Color($p->color->r, $p->color->g, $p->color->b, $alpha);
        $size = $p->size * $alpha;
        $r->drawCircle($p->x, $p->y, $size, $pc);
    }

    // -- Power-ups --
    foreach ($powerups as $p) {
        $bob = sin($p->anim * 4.0) * 3.0;
        $px = $p->x;
        $py = $p->y + $bob;
        $pc = match ($p->type) {
            'spread' => $colors->powerSpread,
            'shield' => $colors->powerShield,
            'rapid'  => $colors->powerRapid,
        };
        // Glow
        $r->drawCircle($px, $py, 14.0, new Color($pc->r, $pc->g, $pc->b, 0.2));
        // Diamond shape using rotated rect
        $r->drawRoundedRect($px - 8, $py - 8, 16, 16, 3.0, $pc);
        // Letter
        $letter = match ($p->type) { 'spread' => 'S', 'shield' => 'D', 'rapid' => 'R' };
        $r->drawTextCentered($letter, $px, $py, 12, $colors->bg);
    }

    // -- Enemy bullets --
    foreach ($enemyBullets as $b) {
        $r->drawCircle($b->x, $b->y, 4.0, $colors->enemyBullet);
        $r->drawCircle($b->x, $b->y, 2.0, new Color(1.0, 0.8, 0.6));
    }

    // -- Player bullets --
    foreach ($bullets as $b) {
        $r->drawRect($b->x - 2, $b->y - 6, 4, 12, $colors->bullet);
        $r->drawCircle($b->x, $b->y - 4, 5.0, $colors->bulletGlow);
    }

    // -- Enemies --
    foreach ($enemies as $e) {
        if (!$e->alive) continue;
        $ex = $e->x;
        $ey = $e->y;
        $ew = $e->w;
        $eh = $e->h;

        // Flash white when damaged
        $damaged = $e->hp < $e->maxHp;
        $bodyColor = match ($e->type) {
            0 => $colors->enemyA,
            1 => $colors->enemyB,
            2 => $colors->enemyC,
            default => $colors->enemyA,
        };
        if ($damaged && fmod($e->anim, 0.15) < 0.07) {
            $bodyColor = $colors->white;
        }

        // Body
        $r->drawRoundedRect($ex - $ew / 2, $ey - $eh / 2, $ew, $eh, 5.0, $bodyColor);

        // Wings
        $wingBob = sin($e->anim * 3.0) * 2.0;
        $r->drawRect($ex - $ew / 2 - 6, $ey - 4 + $wingBob, 8, 10, $bodyColor);
        $r->drawRect($ex + $ew / 2 - 2, $ey - 4 - $wingBob, 8, 10, $bodyColor);

        // Eyes (menacing)
        $r->drawCircle($ex - 5, $ey - 3, 4.0, new Color(0.0, 0.0, 0.0));
        $r->drawCircle($ex + 5, $ey - 3, 4.0, new Color(0.0, 0.0, 0.0));
        $r->drawCircle($ex - 5, $ey - 3, 2.5, new Color(1.0, 0.2, 0.1));
        $r->drawCircle($ex + 5, $ey - 3, 2.5, new Color(1.0, 0.2, 0.1));

        // HP bar (only if multi-hp)
        if ($e->maxHp > 1) {
            $barW = $ew * 0.8;
            $hpRatio = $e->hp / $e->maxHp;
            $r->drawRect($ex - $barW / 2, $ey - $eh / 2 - 6, $barW, 3, new Color(0.3, 0.0, 0.0));
            $r->drawRect($ex - $barW / 2, $ey - $eh / 2 - 6, $barW * $hpRatio, 3, new Color(0.0, 1.0, 0.0));
        }
    }

    // -- Player --
    if (!$gameOver) {
        $px = $player->x;
        $py = $player->y;
        $visible = $player->invincible <= 0 || fmod($player->invincible, 0.15) < 0.07;

        if ($visible) {
            // Engine flame
            $flicker = sin($player->engineAnim) * 3;
            $flameH = 12 + $flicker;
            $r->drawRect($px - 5, $py + $player->h / 2 - 4, 4, $flameH, $colors->shipEngine);
            $r->drawRect($px + 1, $py + $player->h / 2 - 4, 4, $flameH, $colors->shipEngine);
            $r->drawRect($px - 3, $py + $player->h / 2, 2, $flameH - 4, $colors->shipEngineHot);
            $r->drawRect($px + 1, $py + $player->h / 2, 2, $flameH - 4, $colors->shipEngineHot);

            // Ship body (triangle-ish using layered shapes)
            $halfW = $player->w / 2;
            $halfH = $player->h / 2;
            // Main hull
            $r->drawRoundedRect($px - $halfW * 0.5, $py - $halfH, $halfW, $player->h, 4.0, $colors->shipBody);
            // Wings
            $r->drawRect($px - $halfW, $py, $halfW * 0.6, 10, $colors->shipBody);
            $r->drawRect($px + $halfW * 0.4, $py, $halfW * 0.6, 10, $colors->shipBody);
            // Wing tips
            $r->drawRect($px - $halfW, $py - 2, 4, 14, $colors->shipCockpit);
            $r->drawRect($px + $halfW - 4, $py - 2, 4, 14, $colors->shipCockpit);
            // Cockpit
            $r->drawRoundedRect($px - 5, $py - $halfH + 4, 10, 14, 4.0, $colors->shipCockpit);
            // Nose highlight
            $r->drawRect($px - 2, $py - $halfH - 2, 4, 6, $colors->shipCockpit);

            // Shield bubble
            if ($player->shield > 0) {
                $pulse = 0.8 + 0.2 * sin($time * 5.0);
                $r->drawCircle($px, $py, 30.0 * $pulse, $colors->shield);
                $r->drawCircleOutline($px, $py, 30.0 * $pulse, $colors->shieldRim, 2.0);
            }
        }
    }

    // -- HUD --
    $r->drawRoundedRect(8, 8, 200, 32, 6.0, $colors->hud);
    $r->drawText(sprintf('Score: %d', $player->score), 16, 28, 18, $colors->scoreText);

    // Lives
    for ($i = 0; $i < $player->lives; $i++) {
        $lx = $w - 30 - $i * 24;
        $r->drawRoundedRect($lx, 12, 16, 20, 3.0, $colors->shipBody);
        $r->drawRect($lx + 6, 10, 4, 6, $colors->shipCockpit);
    }

    // Active power-up indicators
    $indicatorY = 46;
    if ($player->spreadShot > 0) {
        $r->drawRoundedRect(8, $indicatorY, 80, 18, 4.0, new Color($colors->powerSpread->r, $colors->powerSpread->g, $colors->powerSpread->b, 0.3));
        $r->drawText(sprintf('SPREAD %.0f', $player->spreadShot), 12, $indicatorY + 14, 11, $colors->powerSpread);
        $indicatorY += 22;
    }
    if ($player->shield > 0) {
        $r->drawRoundedRect(8, $indicatorY, 80, 18, 4.0, new Color($colors->powerShield->r, $colors->powerShield->g, $colors->powerShield->b, 0.3));
        $r->drawText(sprintf('SHIELD %.0f', $player->shield), 12, $indicatorY + 14, 11, $colors->powerShield);
        $indicatorY += 22;
    }
    if ($player->rapidFire > 0) {
        $r->drawRoundedRect(8, $indicatorY, 80, 18, 4.0, new Color($colors->powerRapid->r, $colors->powerRapid->g, $colors->powerRapid->b, 0.3));
        $r->drawText(sprintf('RAPID %.0f', $player->rapidFire), 12, $indicatorY + 14, 11, $colors->powerRapid);
    }

    // Wave number
    $r->drawTextCentered(sprintf('Wave %d', $wave), $w / 2, 20, 16, new Color(0.5, 0.5, 0.6));

    // Wave announcement
    if ($waveAnnounce > 0) {
        $alpha = min(1.0, $waveAnnounce / 0.5) * min(1.0, ($waveAnnounce) / 2.5);
        $scale = 1.0 + (1.0 - min(1.0, $waveAnnounce / 0.3)) * 0.3;
        $formationName = match (($wave - 1) % FORMATION_COUNT) {
            FORMATION_GRID => 'Grid',
            FORMATION_V => 'V-Formation',
            FORMATION_CIRCLE => 'Orbit',
            FORMATION_DIVE => 'Dive Bomb',
            FORMATION_SINE => 'Sine Wave',
            FORMATION_SPLIT => 'Pincer',
        };
        $r->drawTextCentered(sprintf('WAVE %d', $wave), $w / 2, $h * 0.32, (int)(32 * $scale), new Color(1.0, 0.85, 0.2, $alpha));
        $r->drawTextCentered($formationName, $w / 2, $h * 0.39, (int)(18 * $scale), new Color(1.0, 0.7, 0.3, $alpha * 0.8));
    }

    // Game over
    if ($gameOver) {
        $r->drawRect(0, 0, $w, $h, new Color(0.0, 0.0, 0.0, 0.5));
        $r->drawRoundedRect($w / 2 - 160, $h / 2 - 70, 320, 140, 12.0, new Color(0.05, 0.02, 0.15, 0.9));
        $r->drawTextCentered('GAME OVER', $w / 2, $h / 2 - 35, 32, new Color(1.0, 0.2, 0.2));
        $r->drawTextCentered(sprintf('Score: %d', $player->score), $w / 2, $h / 2, 22, $colors->scoreText);
        $r->drawTextCentered(sprintf('High Score: %d', $highScore), $w / 2, $h / 2 + 25, 16, $colors->text);
        $r->drawTextCentered('Press R to restart', $w / 2, $h / 2 + 50, 16, new Color(0.5, 0.5, 0.6));
    }

    // Controls hint
    $r->drawText('A/D: Move  Space: Shoot  R: Restart  ESC: Quit', 8, $h - 16, 12, new Color(0.3, 0.3, 0.4));

    $r->popTransform();
});

$engine->run();
