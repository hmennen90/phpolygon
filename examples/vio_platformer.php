<?php

/**
 * PHPolygon -- vio 2D Platformer Demo
 *
 * A simple platformer with:
 *   - Player movement (A/D + Space to jump)
 *   - Gravity and collision with platforms
 *   - Coins to collect
 *   - Parallax scrolling background
 *   - Camera follow
 *
 * Requires: extension=vio
 * Run: php -d extension=vio examples/vio_platformer.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPolygon\Engine;
use PHPolygon\EngineConfig;
use PHPolygon\Math\Vec2;
use PHPolygon\Rendering\Color;

if (!extension_loaded('vio')) {
    echo "This example requires the vio extension.\n";
    exit(1);
}

// -- Constants --
const GRAVITY = 2200.0;
const JUMP_FORCE = -850.0;
const MOVE_SPEED = 400.0;
const PLAYER_W = 24.0;
const PLAYER_H = 36.0;
const TILE = 40.0;

// -- Colors --
$sky1 = new Color(0.15, 0.15, 0.25);
$sky2 = new Color(0.08, 0.08, 0.14);
$playerColor = new Color(0.95, 0.35, 0.2);
$playerEyeColor = new Color(1.0, 1.0, 1.0);
$platformColor = new Color(0.3, 0.6, 0.3);
$platformTopColor = new Color(0.4, 0.75, 0.35);
$groundColor = new Color(0.55, 0.38, 0.22);
$groundTopColor = new Color(0.3, 0.7, 0.15);
$coinColor = new Color(1.0, 0.85, 0.1);
$coinInnerColor = new Color(1.0, 0.95, 0.5);
$bgMountain = new Color(0.12, 0.14, 0.22);
$bgHill = new Color(0.15, 0.18, 0.26);
$starColor = new Color(1.0, 1.0, 1.0, 0.6);
$hudBg = new Color(0.0, 0.0, 0.0, 0.4);
$textColor = new Color(1.0, 1.0, 1.0);
$lavaColor = new Color(0.9, 0.2, 0.05);
$lavaGlow = new Color(1.0, 0.4, 0.1, 0.3);

// -- Player state --
$player = (object) [
    'x' => 80.0,
    'y' => 480.0,
    'vx' => 0.0,
    'vy' => 0.0,
    'onGround' => false,
    'facingRight' => true,
    'score' => 0,
    'dead' => false,
    'deathTimer' => 0.0,
    'walkFrame' => 0.0,
];

// -- Camera --
$camera = (object) [
    'x' => 0.0,
    'y' => 0.0,
];

// -- Level definition --
// Platforms: [x, y, width, height, type]
// type: 'ground', 'platform', 'lava'
// Ground is one continuous floor with gaps for lava pits
// Lava pits at: 400-480, 700-780, 1380-1460, 1760-1840
$lavaGaps = [[400, 80], [700, 80], [1380, 80], [1760, 80]];
$worldEnd = 2400;
$groundY = 540;
$groundH = 60;

// Build ground segments around lava gaps
$groundSegments = [];
$gx = 0;
foreach ($lavaGaps as $gap) {
    if ($gx < $gap[0]) {
        $groundSegments[] = [$gx, $groundY, $gap[0] - $gx, $groundH, 'ground'];
    }
    $gx = $gap[0] + $gap[1];
}
if ($gx < $worldEnd) {
    $groundSegments[] = [$gx, $groundY, $worldEnd - $gx, $groundH, 'ground'];
}

$platforms = array_merge($groundSegments, [
    // Floating platforms
    [200, 430, 120, 20, 'platform'],
    [400, 370, 100, 20, 'platform'],
    [580, 310, 100, 20, 'platform'],
    [750, 410, 80, 20, 'platform'],
    [900, 330, 120, 20, 'platform'],
    [1100, 390, 100, 20, 'platform'],
    [1250, 310, 80, 20, 'platform'],
    [1400, 250, 120, 20, 'platform'],
    [1600, 370, 100, 20, 'platform'],
    [1750, 290, 80, 20, 'platform'],
    [1900, 410, 140, 20, 'platform'],
    [2100, 330, 100, 20, 'platform'],

    // Lava pits (at ground surface level, in the gaps)
    [400, $groundY, 80, 40, 'lava'],
    [700, $groundY, 80, 40, 'lava'],
    [1380, $groundY, 80, 40, 'lava'],
    [1760, $groundY, 80, 40, 'lava'],
]);

// Coins: [x, y, collected]
$coins = [];
$coinPositions = [
    [250, 390], [420, 330], [610, 270],
    [780, 370], [950, 290], [1130, 350],
    [1280, 270], [1450, 210], [1640, 330],
    [1780, 250], [1950, 370], [2130, 290],
    // Ground coins
    [150, 500], [550, 500], [850, 500],
    [1000, 500], [1500, 500], [1900, 500],
];
foreach ($coinPositions as $cp) {
    $coins[] = (object) ['x' => $cp[0], 'y' => $cp[1], 'collected' => false, 'animOffset' => mt_rand(0, 100) / 10.0];
}

$totalCoins = count($coins);

// Stars for background
$stars = [];
for ($i = 0; $i < 60; $i++) {
    $stars[] = [mt_rand(0, 2400), mt_rand(0, 400), mt_rand(1, 3)];
}

$time = 0.0;
$spawnX = 80.0;
$spawnY = $groundY - PLAYER_H;

// -- Engine --
$engine = new Engine(new EngineConfig(
    title: 'PHPolygon -- Platformer Demo',
    width: 1024,
    height: 600,
));

// -- Collision helper --
function rectOverlap(float $ax, float $ay, float $aw, float $ah, float $bx, float $by, float $bw, float $bh): bool
{
    return $ax < $bx + $bw && $ax + $aw > $bx && $ay < $by + $bh && $ay + $ah > $by;
}

function respawn(object $player, float $spawnX, float $spawnY): void
{
    $player->x = $spawnX;
    $player->y = $spawnY;
    $player->vx = 0.0;
    $player->vy = 0.0;
    $player->dead = false;
    $player->deathTimer = 0.0;
    $player->onGround = false;
}

// -- Update --
$engine->onUpdate(function (Engine $engine, float $dt) use ($player, $camera, &$platforms, &$coins, &$time, $spawnX, $spawnY, $groundY): void {
    $time += $dt;
    $input = $engine->input;

    if ($input->isKeyPressed(256)) { // ESC
        $engine->stop();
        return;
    }

    // Respawn on R
    if ($input->isKeyPressed(82)) {
        respawn($player, $spawnX, $spawnY);
    }

    // Death animation
    if ($player->dead) {
        $player->deathTimer += $dt;
        $player->vy += GRAVITY * $dt;
        $player->y += $player->vy * $dt;
        if ($player->deathTimer > 1.5) {
            respawn($player, $spawnX, $spawnY);
        }
        return;
    }

    // Horizontal movement
    $player->vx = 0.0;
    if ($input->isKeyDown(65) || $input->isKeyDown(263)) { // A or Left
        $player->vx = -MOVE_SPEED;
        $player->facingRight = false;
    }
    if ($input->isKeyDown(68) || $input->isKeyDown(262)) { // D or Right
        $player->vx = MOVE_SPEED;
        $player->facingRight = true;
    }

    // Jump
    if ($player->onGround && ($input->isKeyPressed(32) || $input->isKeyPressed(87) || $input->isKeyPressed(265))) { // Space, W, Up
        $player->vy = JUMP_FORCE;
        $player->onGround = false;
    }

    // Walk animation
    if (abs($player->vx) > 0 && $player->onGround) {
        $player->walkFrame += $dt * 10.0;
    }

    // Gravity
    $player->vy += GRAVITY * $dt;
    if ($player->vy > 1200.0) {
        $player->vy = 1200.0; // Terminal velocity
    }

    // Move X
    $player->x += $player->vx * $dt;

    // Horizontal collision
    foreach ($platforms as $p) {
        if ($p[4] === 'lava') {
            continue;
        }
        if (rectOverlap($player->x, $player->y, PLAYER_W, PLAYER_H, $p[0], $p[1], $p[2], $p[3])) {
            if ($player->vx > 0) {
                $player->x = $p[0] - PLAYER_W;
            } else {
                $player->x = $p[0] + $p[2];
            }
            $player->vx = 0;
        }
    }

    // Move Y
    $player->y += $player->vy * $dt;
    $player->onGround = false;

    // Vertical collision
    foreach ($platforms as $p) {
        if ($p[4] === 'lava') {
            // Lava = death
            if (rectOverlap($player->x, $player->y, PLAYER_W, PLAYER_H, $p[0], $p[1], $p[2], $p[3])) {
                $player->dead = true;
                $player->vy = JUMP_FORCE * 0.6;
                return;
            }
            continue;
        }
        if (rectOverlap($player->x, $player->y, PLAYER_W, PLAYER_H, $p[0], $p[1], $p[2], $p[3])) {
            if ($player->vy > 0) {
                // Landing
                $player->y = $p[1] - PLAYER_H;
                $player->vy = 0;
                $player->onGround = true;
            } else {
                // Hit ceiling
                $player->y = $p[1] + $p[3];
                $player->vy = 0;
            }
        }
    }

    // Fall off screen = death
    if ($player->y > 700) {
        $player->dead = true;
        $player->vy = JUMP_FORCE * 0.6;
    }

    // Coin collection
    $pcx = $player->x + PLAYER_W / 2;
    $pcy = $player->y + PLAYER_H / 2;
    foreach ($coins as $coin) {
        if ($coin->collected) {
            continue;
        }
        $dx = $pcx - $coin->x;
        $dy = $pcy - $coin->y;
        if ($dx * $dx + $dy * $dy < 28 * 28) {
            $coin->collected = true;
            $player->score++;
        }
    }

    // Camera follow - keep ground at lower third of screen
    $screenW = $engine->renderer2D->getWidth();
    $screenH = $engine->renderer2D->getHeight();
    $targetX = $player->x - $screenW / 2 + PLAYER_W / 2;
    $targetY = $groundY - $screenH * 0.75; // Ground sits at 75% screen height
    $camera->x += ($targetX - $camera->x) * 6.0 * $dt;
    $camera->y += ($targetY - $camera->y) * 6.0 * $dt;

    // Clamp camera
    if ($camera->x < 0) {
        $camera->x = 0;
    }
});

// -- Render --
$engine->onRender(function (Engine $engine) use (
    $player, $camera, &$platforms, &$coins, &$stars, &$time, $totalCoins,
    $sky1, $sky2, $playerColor, $playerEyeColor,
    $platformColor, $platformTopColor, $groundColor, $groundTopColor,
    $coinColor, $coinInnerColor, $bgMountain, $bgHill, $starColor,
    $hudBg, $textColor, $lavaColor, $lavaGlow, $groundY,
): void {
    $r = $engine->renderer2D;
    $w = $r->getWidth();
    $h = $r->getHeight();
    $cx = $camera->x;
    $cy = $camera->y;

    // Sky gradient (two halves)
    $r->clear($sky1);
    $r->drawRect(0, $h * 0.5, $w, $h * 0.5, $sky2);

    // Stars (fixed, no parallax)
    foreach ($stars as $s) {
        $sx = fmod($s[0] - $cx * 0.05 + 2400, 2400.0);
        if ($sx > 0 && $sx < $w) {
            $r->drawCircle($sx, $s[1], $s[2], $starColor);
        }
    }

    // Background mountains (slow parallax)
    $mountainParallax = 0.15;
    for ($mx = -200; $mx < 2600; $mx += 300) {
        $bx = $mx - $cx * $mountainParallax;
        if ($bx > -300 && $bx < $w + 100) {
            // Triangle-ish mountain using overlapping rects
            $mh = 120 + ($mx % 200);
            $mw = 200;
            $my = $h * 0.6 - $mh * 0.5 - $cy * $mountainParallax;
            // Simple mountain: tall triangle approximation
            for ($i = 0; $i < $mh; $i += 4) {
                $ratio = $i / $mh;
                $rw = $mw * (1.0 - $ratio * 0.7);
                $r->drawRect($bx + ($mw - $rw) * 0.5, $my + $i, $rw, 5, $bgMountain);
            }
        }
    }

    // Background hills (medium parallax)
    $hillParallax = 0.3;
    for ($hx = -100; $hx < 2600; $hx += 200) {
        $bx = $hx - $cx * $hillParallax;
        if ($bx > -150 && $bx < $w + 100) {
            $hh = 60 + ($hx % 80);
            $hy = $h * 0.75 - $hh - $cy * $hillParallax;
            $r->drawCircle($bx + 75, $hy + $hh, 75, $bgHill);
            $r->drawRect($bx, $hy + $hh, 150, $hh, $bgHill);
        }
    }

    // -- World (with camera offset) --

    // Draw ground segments (earth extends to screen bottom)
    $darkEarth = new Color(0.35, 0.22, 0.12);
    $midEarth = new Color(0.45, 0.3, 0.16);
    $grassHighlight = new Color(0.4, 0.85, 0.2);
    foreach ($platforms as $p) {
        if ($p[4] !== 'ground') continue;
        $px = $p[0] - $cx;
        $py = $p[1] - $cy;
        $pw = $p[2];
        if ($px > $w + 50 || $px + $pw < -50) continue;

        // Earth body - extends to bottom of screen
        $earthTop = $py + 24;
        if ($earthTop < $h) {
            $r->drawRect($px, $earthTop, $pw, $h - $earthTop, $darkEarth);
        }
        // Mid earth layer
        $r->drawRect($px, $py + 10, $pw, 18, $midEarth);
        // Top soil layer
        $r->drawRect($px, $py, $pw, 14, $groundColor);

        // Bright green grass surface (thick stripe)
        $r->drawRect($px, $py - 8, $pw, 14, $groundTopColor);
        // Lighter grass highlight on very top
        $r->drawRect($px, $py - 8, $pw, 5, $grassHighlight);

        // Grass tufts on top
        for ($gx = 3; $gx < $pw - 3; $gx += 8) {
            $gh = 6 + sin($gx * 0.7 + $p[0] * 0.1) * 4;
            $r->drawRect($px + $gx, $py - 8 - $gh, 3, (int)$gh, $groundTopColor);
        }
    }

    // Lava pits (in gaps between ground segments)
    foreach ($platforms as $p) {
        if ($p[4] !== 'lava') continue;
        $px = $p[0] - $cx;
        $py = $p[1] - $cy;
        $pw = $p[2];
        $ph = $p[3];
        if ($px > $w + 50 || $px + $pw < -50) continue;

        // Lava pit walls (dark edges)
        $r->drawRect($px - 2, $py - 10, $pw + 4, $h - $py + 10, new Color(0.15, 0.1, 0.08));
        // Lava fill
        $r->drawRect($px, $py, $pw, $h - $py, $lavaColor);
        // Pulsing glow
        $pulse = 0.7 + 0.3 * sin($time * 4.0 + $p[0] * 0.1);
        $glowC = new Color($lavaGlow->r, $lavaGlow->g, $lavaGlow->b, $lavaGlow->a * $pulse);
        $r->drawRect($px - 6, $py - 6, $pw + 12, 20, $glowC);
        // Lava surface wobble
        for ($lx = 0; $lx < $pw; $lx += 8) {
            $ly = sin($time * 3.0 + $lx * 0.3) * 3.0;
            $r->drawRect($px + $lx, $py + $ly - 2, 8, 4, new Color(1.0, 0.6, 0.1));
        }
    }

    // Floating platforms
    foreach ($platforms as $p) {
        if ($p[4] !== 'platform') continue;
        $px = $p[0] - $cx;
        $py = $p[1] - $cy;
        $pw = $p[2];
        $ph = $p[3];
        if ($px > $w + 50 || $px + $pw < -50) continue;

        $r->drawRoundedRect($px, $py, $pw, $ph, 4.0, $platformColor);
        $r->drawRect($px + 2, $py, $pw - 4, 5, $platformTopColor);
    }

    // Coins
    foreach ($coins as $coin) {
        if ($coin->collected) {
            continue;
        }
        $coinX = $coin->x - $cx;
        $coinY = $coin->y - $cy + sin($time * 3.0 + $coin->animOffset) * 4.0;

        if ($coinX > -20 && $coinX < $w + 20) {
            $r->drawCircle($coinX, $coinY, 10, $coinColor);
            $r->drawCircle($coinX, $coinY, 6, $coinInnerColor);
        }
    }

    // Player
    if (!$player->dead || fmod($player->deathTimer, 0.2) < 0.1) {
        $ppx = $player->x - $cx;
        $ppy = $player->y - $cy;

        // Body
        $r->drawRoundedRect($ppx, $ppy + 4, PLAYER_W, PLAYER_H - 4, 6.0, $playerColor);

        // Head
        $r->drawCircle($ppx + PLAYER_W / 2, $ppy + 4, 13, $playerColor);

        // Eye
        $eyeOffX = $player->facingRight ? 4.0 : -4.0;
        $r->drawCircle($ppx + PLAYER_W / 2 + $eyeOffX, $ppy + 2, 4, $playerEyeColor);
        $r->drawCircle($ppx + PLAYER_W / 2 + $eyeOffX + ($player->facingRight ? 1 : -1), $ppy + 2, 2, new Color(0.1, 0.1, 0.2));

        // Legs (walking animation)
        if ($player->onGround && abs($player->vx) > 0) {
            $legOffset = sin($player->walkFrame) * 4;
            $r->drawRect($ppx + 4, $ppy + PLAYER_H - 2, 6, 6 + $legOffset, $playerColor);
            $r->drawRect($ppx + PLAYER_W - 10, $ppy + PLAYER_H - 2, 6, 6 - $legOffset, $playerColor);
        } else {
            $r->drawRect($ppx + 4, $ppy + PLAYER_H - 2, 6, 6, $playerColor);
            $r->drawRect($ppx + PLAYER_W - 10, $ppy + PLAYER_H - 2, 6, 6, $playerColor);
        }
    }

    // -- HUD (screen-space, no camera offset) --
    $r->drawRoundedRect(10, 10, 200, 36, 8.0, $hudBg);
    $r->drawText(sprintf('Coins: %d / %d', $player->score, $totalCoins), 20, 32, 20, $coinColor);

    if ($player->dead) {
        $r->drawRoundedRect($w / 2 - 100, $h / 2 - 30, 200, 60, 12.0, $hudBg);
        $r->drawTextCentered('Ouch!', $w / 2, $h / 2, 28, new Color(1.0, 0.3, 0.2));
    }

    if ($player->score >= $totalCoins) {
        $r->drawRoundedRect($w / 2 - 140, $h / 2 - 40, 280, 80, 12.0, new Color(0.0, 0.0, 0.0, 0.7));
        $r->drawTextCentered('All coins collected!', $w / 2, $h / 2 - 5, 26, $coinColor);
        $r->drawTextCentered('Press R to restart', $w / 2, $h / 2 + 22, 18, $textColor);
    }

    $r->drawText('A/D: Move  Space: Jump  R: Restart', 10, $h - 24, 14, new Color(0.5, 0.5, 0.5));
});

$engine->run();
