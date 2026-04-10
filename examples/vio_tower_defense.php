<?php

/**
 * PHPolygon -- vio 2D Tower Defense Demo
 *
 * A grid-based tower defense game with:
 *   - Winding enemy path across 20x14 tile grid
 *   - 4 tower types: Arrow, Cannon, Ice, Laser
 *   - 3 enemy types: Runner, Tank, Swarm
 *   - Wave system with progressive difficulty
 *   - Projectiles, AOE, slowing, beam attacks
 *   - Gold economy and tower placement
 *
 * Controls:
 *   1/2/3/4 - Select tower type
 *   Click   - Place tower on grass tile
 *   Space   - Start next wave
 *   R       - Restart
 *   ESC     - Quit
 *
 * Run: php examples/vio_tower_defense.php
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
const COLS = 20;
const ROWS = 14;
const TILE = 48;
const HUD_HEIGHT = 68;
const VIRTUAL_W = COLS * TILE; // 960
const VIRTUAL_H = ROWS * TILE + HUD_HEIGHT; // 740

// Tile types
const T_GRASS = 0;
const T_PATH = 1;

// Tower types
const TOWER_ARROW  = 0;
const TOWER_CANNON = 1;
const TOWER_ICE    = 2;
const TOWER_LASER  = 3;

// Enemy types
const ENEMY_RUNNER = 0;
const ENEMY_TANK   = 1;
const ENEMY_SWARM  = 2;

// -- Path waypoints (col, row) -- snake pattern across the map
$pathWaypoints = [
    [0, 2], [5, 2], [5, 5], [1, 5], [1, 8], [7, 8], [7, 3], [12, 3],
    [12, 8], [9, 8], [9, 11], [14, 11], [14, 6], [18, 6], [18, 11], [19, 11],
];

// Build path tile set and detailed segment list for enemy movement
$pathTiles = []; // "col,row" => true
$pathSegments = []; // list of [x, y] center positions for enemies to follow

for ($i = 0; $i < count($pathWaypoints) - 1; $i++) {
    [$c1, $r1] = $pathWaypoints[$i];
    [$c2, $r2] = $pathWaypoints[$i + 1];

    $dc = $c2 <=> $c1;
    $dr = $r2 <=> $r1;
    $c = $c1;
    $r = $r1;

    while (true) {
        $key = "$c,$r";
        if (!isset($pathTiles[$key])) {
            $pathTiles[$key] = true;
            $pathSegments[] = [$c * TILE + TILE / 2, $r * TILE + TILE / 2];
        }
        if ($c === $c2 && $r === $r2) break;
        $c += $dc;
        $r += $dr;
    }
}

// Build grid
$grid = [];
for ($r = 0; $r < ROWS; $r++) {
    for ($c = 0; $c < COLS; $c++) {
        $grid["$c,$r"] = isset($pathTiles["$c,$r"]) ? T_PATH : T_GRASS;
    }
}

// -- Colors --
$colors = (object) [
    'bg'          => new Color(0.12, 0.15, 0.10),
    'grass1'      => new Color(0.22, 0.45, 0.18),
    'grass2'      => new Color(0.25, 0.50, 0.20),
    'path'        => new Color(0.55, 0.42, 0.25),
    'pathEdge'    => new Color(0.45, 0.34, 0.20),
    'gridLine'    => new Color(0.0, 0.0, 0.0, 0.08),
    'hudBg'       => new Color(0.08, 0.08, 0.12, 0.95),
    'hudText'     => new Color(0.9, 0.9, 0.9),
    'goldText'    => new Color(1.0, 0.85, 0.2),
    'lifeText'    => new Color(1.0, 0.3, 0.3),
    'waveText'    => new Color(0.5, 0.8, 1.0),
    'white'       => new Color(1.0, 1.0, 1.0),
    'black'       => new Color(0.0, 0.0, 0.0),
    'highlight'   => new Color(1.0, 1.0, 1.0, 0.15),
    'canPlace'    => new Color(0.2, 1.0, 0.3, 0.3),
    'cantPlace'   => new Color(1.0, 0.2, 0.2, 0.3),
    'rangeCircle' => new Color(1.0, 1.0, 1.0, 0.1),

    // Tower colors
    'arrowBase'   => new Color(0.55, 0.35, 0.15),
    'arrowTop'    => new Color(0.4, 0.7, 0.3),
    'cannonBase'  => new Color(0.4, 0.4, 0.45),
    'cannonBarrel'=> new Color(0.25, 0.25, 0.3),
    'iceBase'     => new Color(0.3, 0.6, 0.9),
    'iceDiamond'  => new Color(0.5, 0.85, 1.0),
    'laserBase'   => new Color(0.35, 0.35, 0.35),
    'laserTip'    => new Color(1.0, 0.15, 0.15),

    // Enemy colors
    'runner'      => new Color(0.3, 0.85, 0.3),
    'tank'        => new Color(0.6, 0.25, 0.7),
    'swarm'       => new Color(1.0, 0.6, 0.15),
    'healthBg'    => new Color(0.2, 0.2, 0.2),
    'healthFg'    => new Color(0.1, 0.9, 0.1),
    'healthLow'   => new Color(0.9, 0.2, 0.1),

    // Projectile colors
    'arrowProj'   => new Color(0.6, 0.4, 0.1),
    'cannonProj'  => new Color(0.3, 0.3, 0.35),
    'iceProj'     => new Color(0.4, 0.8, 1.0),
    'laserBeam'   => new Color(1.0, 0.1, 0.1, 0.7),

    // Effects
    'splashRing'  => new Color(1.0, 0.5, 0.1, 0.4),
    'slowRing'    => new Color(0.3, 0.7, 1.0, 0.3),
    'fireGlow'    => new Color(1.0, 0.8, 0.2, 0.3),
];

// -- Tower definitions --
$towerDefs = [
    TOWER_ARROW  => (object) ['name' => 'Arrow',  'cost' => 50,  'range' => 120.0, 'damage' => 15,  'fireRate' => 2.0,  'splash' => 0.0,  'slow' => 0.0, 'beam' => false, 'desc' => 'Fast single target'],
    TOWER_CANNON => (object) ['name' => 'Cannon', 'cost' => 100, 'range' => 100.0, 'damage' => 40,  'fireRate' => 0.7,  'splash' => 40.0, 'slow' => 0.0, 'beam' => false, 'desc' => 'AOE splash damage'],
    TOWER_ICE    => (object) ['name' => 'Ice',    'cost' => 75,  'range' => 110.0, 'damage' => 5,   'fireRate' => 1.5,  'splash' => 0.0,  'slow' => 0.5, 'beam' => false, 'desc' => 'Slows enemies 50%'],
    TOWER_LASER  => (object) ['name' => 'Laser',  'cost' => 150, 'range' => 150.0, 'damage' => 25,  'fireRate' => 0.5,  'splash' => 0.0,  'slow' => 0.0, 'beam' => true,  'desc' => 'Beam hits all in line'],
];

// -- Enemy definitions --
$enemyDefs = [
    ENEMY_RUNNER => (object) ['name' => 'Runner', 'hp' => 40,  'speed' => 90.0,  'size' => 10.0, 'gold' => 10],
    ENEMY_TANK   => (object) ['name' => 'Tank',   'hp' => 150, 'speed' => 35.0,  'size' => 16.0, 'gold' => 20],
    ENEMY_SWARM  => (object) ['name' => 'Swarm',  'hp' => 60,  'speed' => 60.0,  'size' => 9.0,  'gold' => 15],
];

// -- Game state --
$game = (object) [
    'gold' => 200,
    'lives' => 20,
    'wave' => 0,
    'waveActive' => false,
    'selectedTower' => TOWER_ARROW,
    'gameOver' => false,
    'time' => 0.0,
    'spawnTimer' => 0.0,
    'spawnQueue' => [],
    'waveAnnounce' => 0.0,
];

/** @var list<object> */
$towers = [];
/** @var list<object> */
$enemies = [];
/** @var list<object> */
$projectiles = [];
/** @var list<object> */
$effects = []; // floating text, explosions, rings
/** @var list<object> */
$beams = []; // laser beams (visual only, short-lived)

$towerGrid = []; // "col,row" => tower index

function dist(float $x1, float $y1, float $x2, float $y2): float
{
    $dx = $x2 - $x1;
    $dy = $y2 - $y1;
    return sqrt($dx * $dx + $dy * $dy);
}

function spawnWaveEnemies(object $game, array $enemyDefs, array $pathSegments): array
{
    $wave = $game->wave;
    $queue = [];
    $baseCount = 3 + $wave * 2;

    // Rotate enemy types per wave, mix in later waves
    if ($wave <= 3) {
        $type = ($wave - 1) % 3;
        $count = $baseCount;
        for ($i = 0; $i < $count; $i++) {
            $queue[] = $type;
        }
    } else {
        // Mixed waves
        $types = [ENEMY_RUNNER, ENEMY_TANK, ENEMY_SWARM];
        $count = $baseCount;
        for ($i = 0; $i < $count; $i++) {
            $queue[] = $types[$i % 3];
        }
    }

    // Swarm type spawns in groups - add extras
    $extra = [];
    foreach ($queue as $t) {
        $extra[] = $t;
        if ($t === ENEMY_SWARM) {
            for ($j = 0; $j < 3; $j++) $extra[] = ENEMY_SWARM;
        }
    }

    return $extra;
}

function spawnEnemy(int $type, array $enemyDefs, array $pathSegments, int $wave): object
{
    $def = $enemyDefs[$type];
    $hpScale = 1.0 + ($wave - 1) * 0.25;
    $hp = (int)($def->hp * $hpScale);
    $start = $pathSegments[0];

    return (object) [
        'type' => $type,
        'x' => (float)$start[0],
        'y' => (float)$start[1],
        'hp' => $hp,
        'maxHp' => $hp,
        'speed' => $def->speed,
        'size' => $def->size,
        'gold' => $def->gold,
        'alive' => true,
        'pathIndex' => 0,
        'slowTimer' => 0.0,
        'slowAmount' => 0.0,
        'hitFlash' => 0.0,
    ];
}

function resetGame(object $game, array &$towers, array &$enemies, array &$projectiles, array &$effects, array &$beams, array &$towerGrid): void
{
    $game->gold = 200;
    $game->lives = 20;
    $game->wave = 0;
    $game->waveActive = false;
    $game->selectedTower = TOWER_ARROW;
    $game->gameOver = false;
    $game->time = 0.0;
    $game->spawnTimer = 0.0;
    $game->spawnQueue = [];
    $game->waveAnnounce = 0.0;
    $towers = [];
    $enemies = [];
    $projectiles = [];
    $effects = [];
    $beams = [];
    $towerGrid = [];
}

// -- Create engine --
$engine = new Engine(new EngineConfig(
    title: 'PHPolygon -- Tower Defense',
    width: VIRTUAL_W,
    height: VIRTUAL_H,
));

// -- Update --
$engine->onUpdate(function (Engine $engine, float $dt) use (
    $game, &$towers, &$enemies, &$projectiles, &$effects, &$beams, &$towerGrid,
    $grid, $pathSegments, $towerDefs, $enemyDefs, $colors,
): void {
    $game->time += $dt;
    $input = $engine->input;

    // ESC to quit
    if ($input->isKeyPressed(256)) {
        $engine->stop();
        return;
    }

    // Restart
    if ($game->gameOver && $input->isKeyPressed(82)) { // R
        resetGame($game, $towers, $enemies, $projectiles, $effects, $beams, $towerGrid);
        return;
    }

    if ($game->gameOver) return;

    // Tower selection (1-4)
    if ($input->isKeyPressed(49)) $game->selectedTower = TOWER_ARROW;
    if ($input->isKeyPressed(50)) $game->selectedTower = TOWER_CANNON;
    if ($input->isKeyPressed(51)) $game->selectedTower = TOWER_ICE;
    if ($input->isKeyPressed(52)) $game->selectedTower = TOWER_LASER;

    // Start wave (Space)
    if (!$game->waveActive && $input->isKeyPressed(32)) {
        $game->wave++;
        $game->waveActive = true;
        $game->spawnQueue = spawnWaveEnemies($game, $enemyDefs, $pathSegments);
        $game->spawnTimer = 0.0;
        $game->waveAnnounce = 2.5;
    }

    // Wave announce timer
    if ($game->waveAnnounce > 0) {
        $game->waveAnnounce -= $dt;
    }

    // Tower placement (mouse click)
    if ($input->isMouseButtonPressed(0)) {
        $r = $engine->renderer2D;
        $actualW = $r->getWidth();
        $actualH = $r->getHeight();
        $sx = $actualW / VIRTUAL_W;
        $sy = $actualH / VIRTUAL_H;
        $mx = $input->getMouseX() / $sx;
        $my = $input->getMouseY() / $sy;

        $col = (int)floor($mx / TILE);
        $row = (int)floor($my / TILE);

        if ($col >= 0 && $col < COLS && $row >= 0 && $row < ROWS) {
            $key = "$col,$row";
            if ($grid[$key] === T_GRASS && !isset($towerGrid[$key])) {
                $def = $towerDefs[$game->selectedTower];
                if ($game->gold >= $def->cost) {
                    $game->gold -= $def->cost;
                    $tower = (object) [
                        'type' => $game->selectedTower,
                        'col' => $col,
                        'row' => $row,
                        'x' => $col * TILE + TILE / 2,
                        'y' => $row * TILE + TILE / 2,
                        'cooldown' => 0.0,
                        'fireAnim' => 0.0,
                    ];
                    $towers[] = $tower;
                    $towerGrid[$key] = count($towers) - 1;
                }
            }
        }
    }

    // Spawn enemies from queue
    if ($game->waveActive && count($game->spawnQueue) > 0) {
        $game->spawnTimer -= $dt;
        if ($game->spawnTimer <= 0) {
            $type = array_shift($game->spawnQueue);
            $enemies[] = spawnEnemy($type, $enemyDefs, $pathSegments, $game->wave);
            $game->spawnTimer = 0.45;
        }
    }

    // Update enemies
    foreach ($enemies as $enemy) {
        if (!$enemy->alive) continue;

        // Slow decay
        if ($enemy->slowTimer > 0) {
            $enemy->slowTimer -= $dt;
            if ($enemy->slowTimer <= 0) {
                $enemy->slowAmount = 0.0;
            }
        }

        // Hit flash decay
        if ($enemy->hitFlash > 0) $enemy->hitFlash -= $dt;

        // Move along path
        $speedMod = 1.0 - $enemy->slowAmount;
        $moveSpeed = $enemy->speed * $speedMod * $dt;
        $idx = $enemy->pathIndex;

        while ($moveSpeed > 0 && $idx < count($pathSegments) - 1) {
            $tx = (float)$pathSegments[$idx + 1][0];
            $ty = (float)$pathSegments[$idx + 1][1];
            $d = dist($enemy->x, $enemy->y, $tx, $ty);

            if ($d <= $moveSpeed) {
                $enemy->x = $tx;
                $enemy->y = $ty;
                $moveSpeed -= $d;
                $idx++;
                $enemy->pathIndex = $idx;
            } else {
                $ratio = $moveSpeed / $d;
                $enemy->x += ($tx - $enemy->x) * $ratio;
                $enemy->y += ($ty - $enemy->y) * $ratio;
                $moveSpeed = 0;
            }
        }

        // Reached end of path
        if ($idx >= count($pathSegments) - 1) {
            $enemy->alive = false;
            $game->lives--;
            if ($game->lives <= 0) {
                $game->lives = 0;
                $game->gameOver = true;
            }
        }
    }

    // Tower shooting
    foreach ($towers as $tower) {
        $tower->cooldown -= $dt;
        if ($tower->fireAnim > 0) $tower->fireAnim -= $dt;
        if ($tower->cooldown > 0) continue;

        $def = $towerDefs[$tower->type];

        // Find nearest enemy in range
        $bestDist = $def->range + 1;
        $bestEnemy = null;
        foreach ($enemies as $enemy) {
            if (!$enemy->alive) continue;
            $d = dist($tower->x, $tower->y, $enemy->x, $enemy->y);
            if ($d <= $def->range && $d < $bestDist) {
                $bestDist = $d;
                $bestEnemy = $enemy;
            }
        }

        if ($bestEnemy === null) continue;

        $tower->cooldown = 1.0 / $def->fireRate;
        $tower->fireAnim = 0.2;

        if ($def->beam) {
            // Laser - hit all enemies in line from tower to target direction
            $dx = $bestEnemy->x - $tower->x;
            $dy = $bestEnemy->y - $tower->y;
            $len = sqrt($dx * $dx + $dy * $dy);
            if ($len > 0) {
                $nx = $dx / $len;
                $ny = $dy / $len;
                $endX = $tower->x + $nx * $def->range;
                $endY = $tower->y + $ny * $def->range;

                $beams[] = (object) ['x1' => $tower->x, 'y1' => $tower->y, 'x2' => $endX, 'y2' => $endY, 'life' => 0.15];

                // Hit all enemies near the beam line
                foreach ($enemies as $enemy) {
                    if (!$enemy->alive) continue;
                    // Point-to-line distance
                    $ex = $enemy->x - $tower->x;
                    $ey = $enemy->y - $tower->y;
                    $proj = $ex * $nx + $ey * $ny;
                    if ($proj < 0 || $proj > $def->range) continue;
                    $closestX = $tower->x + $nx * $proj;
                    $closestY = $tower->y + $ny * $proj;
                    $lineDist = dist($enemy->x, $enemy->y, $closestX, $closestY);
                    if ($lineDist <= $enemy->size + 8) {
                        $enemy->hp -= $def->damage;
                        $enemy->hitFlash = 0.1;
                    }
                }
            }
        } else {
            // Spawn projectile
            $projectiles[] = (object) [
                'x' => (float)$tower->x,
                'y' => (float)$tower->y,
                'targetEnemy' => $bestEnemy,
                'speed' => 250.0,
                'damage' => $def->damage,
                'splash' => $def->splash,
                'slow' => $def->slow,
                'towerType' => $tower->type,
                'alive' => true,
            ];
        }
    }

    // Update projectiles
    foreach ($projectiles as $proj) {
        if (!$proj->alive) continue;
        $target = $proj->targetEnemy;

        // If target died, keep going toward last known position
        $tx = $target->x;
        $ty = $target->y;
        $d = dist($proj->x, $proj->y, $tx, $ty);

        if ($d < 8 || !$target->alive) {
            if ($d < 8) {
                // Hit
                $target->hp -= $proj->damage;
                $target->hitFlash = 0.1;

                // Slow
                if ($proj->slow > 0) {
                    $target->slowTimer = 2.0;
                    $target->slowAmount = $proj->slow;
                    $effects[] = (object) ['type' => 'slow_ring', 'x' => $target->x, 'y' => $target->y, 'life' => 0.3, 'maxLife' => 0.3, 'radius' => 20.0];
                }

                // Splash
                if ($proj->splash > 0) {
                    $effects[] = (object) ['type' => 'splash', 'x' => $tx, 'y' => $ty, 'life' => 0.3, 'maxLife' => 0.3, 'radius' => $proj->splash];
                    foreach ($enemies as $other) {
                        if (!$other->alive || $other === $target) continue;
                        if (dist($tx, $ty, $other->x, $other->y) <= $proj->splash) {
                            $other->hp -= (int)($proj->damage * 0.5);
                            $other->hitFlash = 0.1;
                        }
                    }
                }
            }
            $proj->alive = false;
        } else {
            $ratio = min(1.0, $proj->speed * $dt / $d);
            $proj->x += ($tx - $proj->x) * $ratio;
            $proj->y += ($ty - $proj->y) * $ratio;
        }
    }

    // Check enemy deaths
    foreach ($enemies as $enemy) {
        if (!$enemy->alive) continue;
        if ($enemy->hp <= 0) {
            $enemy->alive = false;
            $goldReward = $enemyDefs[$enemy->type]->gold;
            $game->gold += $goldReward;
            $effects[] = (object) ['type' => 'gold_text', 'x' => $enemy->x, 'y' => $enemy->y - 10, 'life' => 1.0, 'maxLife' => 1.0, 'text' => "+{$goldReward}g"];
            // Small burst
            for ($i = 0; $i < 4; $i++) {
                $angle = ($i / 4) * M_PI * 2 + $game->time;
                $effects[] = (object) [
                    'type' => 'particle',
                    'x' => $enemy->x, 'y' => $enemy->y,
                    'vx' => cos($angle) * 60, 'vy' => sin($angle) * 60,
                    'life' => 0.4, 'maxLife' => 0.4,
                    'enemyType' => $enemy->type,
                ];
            }
        }
    }

    // Clean up dead enemies
    $enemies = array_values(array_filter($enemies, fn($e) => $e->alive));

    // Clean up dead projectiles
    $projectiles = array_values(array_filter($projectiles, fn($p) => $p->alive));

    // Check wave completion
    if ($game->waveActive && count($game->spawnQueue) === 0 && count($enemies) === 0) {
        $game->waveActive = false;
        // Bonus gold between waves
        $bonus = 10 + $game->wave * 5;
        $game->gold += $bonus;
        $effects[] = (object) ['type' => 'gold_text', 'x' => VIRTUAL_W / 2, 'y' => VIRTUAL_H / 2 - 40, 'life' => 2.0, 'maxLife' => 2.0, 'text' => "Wave complete! +{$bonus}g"];
    }

    // Update effects
    foreach ($effects as $fx) {
        $fx->life -= $dt;
        if ($fx->type === 'gold_text') {
            $fx->y -= 30 * $dt;
        }
        if ($fx->type === 'particle') {
            $fx->x += $fx->vx * $dt;
            $fx->y += $fx->vy * $dt;
        }
    }
    $effects = array_values(array_filter($effects, fn($fx) => $fx->life > 0));

    // Update beams
    foreach ($beams as $beam) {
        $beam->life -= $dt;
    }
    $beams = array_values(array_filter($beams, fn($b) => $b->life > 0));
});

// -- Render --
$engine->onRender(function (Engine $engine) use (
    $game, &$towers, &$enemies, &$projectiles, &$effects, &$beams, &$towerGrid,
    $grid, $pathSegments, $towerDefs, $enemyDefs, $colors,
): void {
    $r = $engine->renderer2D;
    $actualW = $r->getWidth();
    $actualH = $r->getHeight();

    $r->clear($colors->bg);

    // Scale virtual coords to actual window
    $sx = $actualW / VIRTUAL_W;
    $sy = $actualH / VIRTUAL_H;
    $r->pushTransform(Mat3::trs(new Vec2(0, 0), 0.0, new Vec2($sx, $sy)));

    $input = $engine->input;
    $mx = $input->getMouseX() / $sx;
    $my = $input->getMouseY() / $sy;
    $hoverCol = (int)floor($mx / TILE);
    $hoverRow = (int)floor($my / TILE);

    // -- Draw grid --
    for ($row = 0; $row < ROWS; $row++) {
        for ($col = 0; $col < COLS; $col++) {
            $x = $col * TILE;
            $y = $row * TILE;
            $key = "$col,$row";
            $isPath = $grid[$key] === T_PATH;

            if ($isPath) {
                $r->drawRect($x, $y, TILE, TILE, $colors->path);
                // Path edge lines
                $r->drawRect($x, $y, TILE, 1, $colors->pathEdge);
                $r->drawRect($x, $y + TILE - 1, TILE, 1, $colors->pathEdge);
                $r->drawRect($x, $y, 1, TILE, $colors->pathEdge);
                $r->drawRect($x + TILE - 1, $y, 1, TILE, $colors->pathEdge);
            } else {
                // Grass - checkerboard
                $grassColor = (($col + $row) % 2 === 0) ? $colors->grass1 : $colors->grass2;
                $r->drawRect($x, $y, TILE, TILE, $grassColor);
            }
        }
    }

    // Grid lines
    for ($row = 0; $row <= ROWS; $row++) {
        $r->drawLine(new Vec2(0, $row * TILE), new Vec2(COLS * TILE, $row * TILE), $colors->gridLine, 1.0);
    }
    for ($col = 0; $col <= COLS; $col++) {
        $r->drawLine(new Vec2($col * TILE, 0), new Vec2($col * TILE, ROWS * TILE), $colors->gridLine, 1.0);
    }

    // -- Path direction indicators (subtle arrows) --
    for ($i = 0; $i < count($pathSegments) - 1; $i += 3) {
        $px = (float)$pathSegments[$i][0];
        $py = (float)$pathSegments[$i][1];
        $nx = (float)$pathSegments[$i + 1][0];
        $ny = (float)$pathSegments[$i + 1][1];
        $r->drawLine(new Vec2($px, $py), new Vec2($nx, $ny), new Color(0.6, 0.5, 0.3, 0.15), 2.0);
    }

    // -- Hover highlight and ghost tower --
    if ($hoverCol >= 0 && $hoverCol < COLS && $hoverRow >= 0 && $hoverRow < ROWS && !$game->gameOver) {
        $hx = $hoverCol * TILE;
        $hy = $hoverRow * TILE;
        $key = "$hoverCol,$hoverRow";
        $isGrass = $grid[$key] === T_GRASS;
        $isEmpty = !isset($towerGrid[$key]);
        $def = $towerDefs[$game->selectedTower];
        $canAfford = $game->gold >= $def->cost;

        if ($isGrass && $isEmpty && $canAfford) {
            $r->drawRect($hx, $hy, TILE, TILE, $colors->canPlace);
            // Range preview
            $r->drawCircleOutline($hx + TILE / 2, $hy + TILE / 2, $def->range, $colors->rangeCircle, 1.0);
            // Ghost tower
            drawTowerGhost($r, $hx + TILE / 2, $hy + TILE / 2, $game->selectedTower, $colors);
        } elseif ($isGrass && $isEmpty && !$canAfford) {
            $r->drawRect($hx, $hy, TILE, TILE, $colors->cantPlace);
        } else {
            $r->drawRect($hx, $hy, TILE, TILE, $colors->highlight);
        }

        // Show range of existing tower on hover
        if (isset($towerGrid[$key])) {
            $t = $towers[$towerGrid[$key]];
            $td = $towerDefs[$t->type];
            $r->drawCircleOutline($t->x, $t->y, $td->range, new Color(1.0, 1.0, 1.0, 0.2), 1.0);
        }
    }

    // -- Draw towers --
    foreach ($towers as $tower) {
        $def = $towerDefs[$tower->type];

        // Fire glow
        if ($tower->fireAnim > 0) {
            $glowAlpha = $tower->fireAnim / 0.2;
            $r->drawCircle($tower->x, $tower->y, 20.0, new Color(1.0, 0.8, 0.2, 0.3 * $glowAlpha));
        }

        drawTower($r, $tower->x, $tower->y, $tower->type, $colors, false);
    }

    // -- Draw enemies --
    foreach ($enemies as $enemy) {
        $def = $enemyDefs[$enemy->type];
        $size = $enemy->size;

        // Shadow
        $r->drawCircle($enemy->x + 2, $enemy->y + 2, $size, new Color(0.0, 0.0, 0.0, 0.2));

        // Body color (flash white when hit)
        if ($enemy->hitFlash > 0) {
            $bodyColor = $colors->white;
        } else {
            $bodyColor = match ($enemy->type) {
                ENEMY_RUNNER => $colors->runner,
                ENEMY_TANK => $colors->tank,
                ENEMY_SWARM => $colors->swarm,
            };
        }

        // Slow visual
        if ($enemy->slowTimer > 0) {
            $r->drawCircleOutline($enemy->x, $enemy->y, $size + 4, $colors->slowRing, 2.0);
        }

        match ($enemy->type) {
            ENEMY_RUNNER => drawRunnerEnemy($r, $enemy->x, $enemy->y, $size, $bodyColor),
            ENEMY_TANK => drawTankEnemy($r, $enemy->x, $enemy->y, $size, $bodyColor),
            ENEMY_SWARM => drawSwarmEnemy($r, $enemy->x, $enemy->y, $size, $bodyColor),
        };

        // Health bar
        $barW = $size * 2.2;
        $barH = 3.0;
        $barX = $enemy->x - $barW / 2;
        $barY = $enemy->y - $size - 7;
        $hpRatio = max(0, $enemy->hp / $enemy->maxHp);
        $r->drawRect($barX, $barY, $barW, $barH, $colors->healthBg);
        $hpColor = $hpRatio > 0.4 ? $colors->healthFg : $colors->healthLow;
        $r->drawRect($barX, $barY, $barW * $hpRatio, $barH, $hpColor);
    }

    // -- Draw projectiles --
    foreach ($projectiles as $proj) {
        if (!$proj->alive) continue;
        $projColor = match ($proj->towerType) {
            TOWER_ARROW => $colors->arrowProj,
            TOWER_CANNON => $colors->cannonProj,
            TOWER_ICE => $colors->iceProj,
            default => $colors->white,
        };
        $projSize = $proj->towerType === TOWER_CANNON ? 5.0 : 3.0;
        $r->drawCircle($proj->x, $proj->y, $projSize, $projColor);
    }

    // -- Draw beams --
    foreach ($beams as $beam) {
        $alpha = $beam->life / 0.15;
        $r->drawLine(new Vec2($beam->x1, $beam->y1), new Vec2($beam->x2, $beam->y2), new Color(1.0, 0.1, 0.1, 0.7 * $alpha), 3.0);
        $r->drawLine(new Vec2($beam->x1, $beam->y1), new Vec2($beam->x2, $beam->y2), new Color(1.0, 0.5, 0.5, 0.3 * $alpha), 6.0);
    }

    // -- Draw effects --
    foreach ($effects as $fx) {
        $alpha = max(0, $fx->life / $fx->maxLife);
        if ($fx->type === 'gold_text') {
            $r->drawText($fx->text, $fx->x - 15, $fx->y, 14, new Color(1.0, 0.85, 0.2, $alpha));
        } elseif ($fx->type === 'splash') {
            $r->drawCircleOutline($fx->x, $fx->y, $fx->radius * (1 - $alpha * 0.5), new Color(1.0, 0.5, 0.1, 0.5 * $alpha), 2.0);
        } elseif ($fx->type === 'slow_ring') {
            $r->drawCircleOutline($fx->x, $fx->y, 20 + (1 - $alpha) * 10, new Color(0.3, 0.7, 1.0, 0.4 * $alpha), 2.0);
        } elseif ($fx->type === 'particle') {
            $pColor = match ($fx->enemyType) {
                ENEMY_RUNNER => new Color(0.3, 0.85, 0.3, $alpha),
                ENEMY_TANK => new Color(0.6, 0.25, 0.7, $alpha),
                ENEMY_SWARM => new Color(1.0, 0.6, 0.15, $alpha),
            };
            $r->drawCircle($fx->x, $fx->y, 3.0 * $alpha, $pColor);
        }
    }

    // -- HUD bar --
    $hudY = ROWS * TILE;
    $r->drawRect(0, $hudY, VIRTUAL_W, HUD_HEIGHT, $colors->hudBg);
    $r->drawRect(0, $hudY, VIRTUAL_W, 1, new Color(0.3, 0.3, 0.4));

    // Gold
    $r->drawText(sprintf('Gold: %d', $game->gold), 12, $hudY + 8, 18, $colors->goldText);

    // Lives
    $r->drawText(sprintf('Lives: %d', $game->lives), 12, $hudY + 32, 16, $colors->lifeText);

    // Wave
    $waveLabel = $game->waveActive ? sprintf('Wave %d', $game->wave) : ($game->wave === 0 ? 'Press SPACE' : sprintf('Wave %d done', $game->wave));
    $r->drawText($waveLabel, 170, $hudY + 8, 16, $colors->waveText);

    if (!$game->waveActive && !$game->gameOver) {
        $r->drawText('SPACE - Next wave', 170, $hudY + 32, 12, new Color(0.5, 0.5, 0.6));
    }

    // Tower selection panel
    $panelX = 380;
    for ($i = 0; $i < 4; $i++) {
        $td = $towerDefs[$i];
        $bx = $panelX + $i * 140;
        $isSelected = $game->selectedTower === $i;
        $canAfford = $game->gold >= $td->cost;

        // Selection highlight
        if ($isSelected) {
            $r->drawRoundedRect($bx - 2, $hudY + 4, 134, HUD_HEIGHT - 8, 4.0, new Color(1.0, 1.0, 1.0, 0.1));
            $r->drawRoundedRectOutline($bx - 2, $hudY + 4, 134, HUD_HEIGHT - 8, 4.0, new Color(1.0, 0.85, 0.2, 0.7), 1.5);
        }

        // Tower icon
        drawTower($r, $bx + 18, $hudY + 28, $i, $colors, false);

        // Name and cost
        $textColor = $canAfford ? $colors->hudText : new Color(0.5, 0.3, 0.3);
        $r->drawText(sprintf('%d: %s', $i + 1, $td->name), $bx + 36, $hudY + 10, 13, $textColor);
        $r->drawText(sprintf('%dg', $td->cost), $bx + 36, $hudY + 26, 11, $canAfford ? $colors->goldText : new Color(0.5, 0.3, 0.3));
        $r->drawText($td->desc, $bx + 36, $hudY + 42, 10, new Color(0.5, 0.5, 0.6));
    }

    // -- Wave announcement --
    if ($game->waveAnnounce > 0) {
        $alpha = min(1.0, $game->waveAnnounce / 0.5);
        $scale = 1.0 + (1.0 - min(1.0, $game->waveAnnounce / 0.3)) * 0.2;
        $r->drawTextCentered(sprintf('WAVE %d', $game->wave), VIRTUAL_W / 2, ROWS * TILE / 2 - 20, (int)(28 * $scale), new Color(1.0, 0.85, 0.2, $alpha));

        $typeName = match (($game->wave - 1) % 3) {
            0 => 'Runners incoming!',
            1 => 'Tanks approaching!',
            2 => 'Swarm detected!',
        };
        if ($game->wave > 3) $typeName = 'Mixed assault!';
        $r->drawTextCentered($typeName, VIRTUAL_W / 2, ROWS * TILE / 2 + 10, (int)(16 * $scale), new Color(1.0, 0.7, 0.3, $alpha * 0.8));
    }

    // -- Game over overlay --
    if ($game->gameOver) {
        $r->drawRect(0, 0, VIRTUAL_W, VIRTUAL_H, new Color(0.0, 0.0, 0.0, 0.6));
        $r->drawRoundedRect(VIRTUAL_W / 2 - 180, VIRTUAL_H / 2 - 80, 360, 160, 12.0, new Color(0.08, 0.06, 0.15, 0.95));
        $r->drawTextCentered('GAME OVER', VIRTUAL_W / 2, VIRTUAL_H / 2 - 45, 32, new Color(1.0, 0.2, 0.2));
        $r->drawTextCentered(sprintf('Survived %d waves', $game->wave), VIRTUAL_W / 2, VIRTUAL_H / 2 - 10, 20, $colors->waveText);
        $r->drawTextCentered(sprintf('Towers built: %d', count($towers)), VIRTUAL_W / 2, VIRTUAL_H / 2 + 15, 16, $colors->hudText);
        $r->drawTextCentered('Press R to restart', VIRTUAL_W / 2, VIRTUAL_H / 2 + 50, 16, new Color(0.5, 0.5, 0.6));
    }

    // Controls hint
    $r->drawText('1-4: Tower  Click: Place  Space: Wave  R: Restart  ESC: Quit', 4, VIRTUAL_H - 14, 10, new Color(0.3, 0.3, 0.4));

    $r->popTransform();
});

// -- Drawing helpers --

function drawTower(object $r, float $cx, float $cy, int $type, object $colors, bool $ghost): void
{
    $a = $ghost ? 0.4 : 1.0;
    $s = 10.0; // half-size of base

    match ($type) {
        TOWER_ARROW => drawArrowTower($r, $cx, $cy, $s, $colors, $a),
        TOWER_CANNON => drawCannonTower($r, $cx, $cy, $s, $colors, $a),
        TOWER_ICE => drawIceTower($r, $cx, $cy, $s, $colors, $a),
        TOWER_LASER => drawLaserTower($r, $cx, $cy, $s, $colors, $a),
    };
}

function drawTowerGhost(object $r, float $cx, float $cy, int $type, object $colors): void
{
    drawTower($r, $cx, $cy, $type, $colors, true);
}

function drawArrowTower(object $r, float $cx, float $cy, float $s, object $colors, float $a): void
{
    // Square base
    $r->drawRect($cx - $s, $cy - $s + 2, $s * 2, $s * 2, new Color(0.55, 0.35, 0.15, $a));
    // Triangle top (approximated with a small rect narrowing + peak)
    $r->drawRect($cx - $s * 0.6, $cy - $s - 4, $s * 1.2, 6, new Color(0.4, 0.7, 0.3, $a));
    $r->drawRect($cx - 2, $cy - $s - 8, 4, 5, new Color(0.4, 0.7, 0.3, $a));
}

function drawCannonTower(object $r, float $cx, float $cy, float $s, object $colors, float $a): void
{
    // Square base
    $r->drawRect($cx - $s, $cy - $s + 2, $s * 2, $s * 2, new Color(0.4, 0.4, 0.45, $a));
    // Circle barrel
    $r->drawCircle($cx, $cy - 4, $s * 0.7, new Color(0.25, 0.25, 0.3, $a));
    $r->drawCircle($cx, $cy - 4, $s * 0.4, new Color(0.15, 0.15, 0.2, $a));
}

function drawIceTower(object $r, float $cx, float $cy, float $s, object $colors, float $a): void
{
    // Diamond shape (rotated square - approximate with 4 rects)
    $r->drawRect($cx - $s, $cy - 2, $s * 2, 4, new Color(0.3, 0.6, 0.9, $a));
    $r->drawRect($cx - 2, $cy - $s, 4, $s * 2, new Color(0.3, 0.6, 0.9, $a));
    $r->drawRect($cx - $s * 0.6, $cy - $s * 0.6, $s * 1.2, $s * 1.2, new Color(0.5, 0.85, 1.0, $a));
    // Center gem
    $r->drawCircle($cx, $cy, 3, new Color(0.7, 0.95, 1.0, $a));
}

function drawLaserTower(object $r, float $cx, float $cy, float $s, object $colors, float $a): void
{
    // Tall thin tower
    $r->drawRect($cx - $s * 0.5, $cy - $s + 2, $s, $s * 2, new Color(0.35, 0.35, 0.35, $a));
    // Upper body
    $r->drawRect($cx - $s * 0.35, $cy - $s - 6, $s * 0.7, 8, new Color(0.45, 0.45, 0.5, $a));
    // Red tip
    $r->drawCircle($cx, $cy - $s - 8, 3, new Color(1.0, 0.15, 0.15, $a));
}

function drawRunnerEnemy(object $r, float $x, float $y, float $size, Color $color): void
{
    // Small diamond shape
    $r->drawRect($x - $size * 0.7, $y - 2, $size * 1.4, 4, $color);
    $r->drawRect($x - 2, $y - $size * 0.7, 4, $size * 1.4, $color);
    $r->drawCircle($x, $y, $size * 0.5, $color);
}

function drawTankEnemy(object $r, float $x, float $y, float $size, Color $color): void
{
    // Large square body
    $r->drawRect($x - $size, $y - $size * 0.8, $size * 2, $size * 1.6, $color);
    // Darker center
    $r->drawRect($x - $size * 0.5, $y - $size * 0.4, $size, $size * 0.8, new Color($color->r * 0.6, $color->g * 0.6, $color->b * 0.6, $color->a));
}

function drawSwarmEnemy(object $r, float $x, float $y, float $size, Color $color): void
{
    // Small circle
    $r->drawCircle($x, $y, $size, $color);
    $r->drawCircle($x, $y, $size * 0.5, new Color($color->r * 1.2, $color->g * 1.2, $color->b * 1.2, $color->a));
}

$engine->run();
