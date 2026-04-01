<?php

declare(strict_types=1);

namespace PHPolygon\Component;

use PHPolygon\ECS\AbstractComponent;
use PHPolygon\ECS\Attribute\Category;
use PHPolygon\ECS\Attribute\Hidden;
use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Serializable;

/**
 * Full atmospheric state. Attach to the same entity as Weather and DayNightCycle.
 *
 * Two roles:
 *  1. Cloud rendering data (read by CloudSystem to set cloud type fractions and base altitude)
 *  2. Atmospheric simulation state (written by AtmosphericEnvironmentalSystem:
 *     air pressure, visibility, dew point, thermal convection)
 */
#[Serializable]
#[Category('Environment')]
class Atmosphere extends AbstractComponent
{
    // ── Cloud rendering ────────────────────────────────────────────────────

    /**
     * Altitude of the cloud base in game units above sea level.
     * Set by AtmosphericEnvironmentalSystem based on pressure and thermals.
     * Read by CloudSystem to position cloud entities vertically.
     */
    #[Property]
    public float $cloudBaseAltitude = 45.0;

    /**
     * Fraction of clouds rendered as cumulus (white, puffy). 0–1.
     * The three fractions should sum to ≤ 1; remainder is clear sky.
     */
    #[Hidden]
    public float $cumulusFraction = 0.3;

    /** Fraction of clouds rendered as stratus (flat, grey sheets). 0–1. */
    #[Hidden]
    public float $stratusFraction = 0.0;

    /** Fraction of clouds rendered as cumulonimbus (tall storm towers). 0–1. */
    #[Hidden]
    public float $cumulonimbusFraction = 0.0;

    // ── Air pressure ───────────────────────────────────────────────────────

    /**
     * Sea-level air pressure in hPa (typical range 945–1050).
     * ISA standard: 1013.25 hPa.
     * Values below 1000 → low pressure / bad weather approaching.
     * Values above 1020 → high pressure / settled, clear weather.
     */
    #[Property]
    public float $airPressure = 1013.25;

    /** Rate of pressure change in hPa/s (negative = falling = deteriorating). */
    #[Hidden]
    public float $pressureTrend = 0.0;

    /** Previous-frame pressure for trend calculation (internal). */
    #[Hidden]
    public float $pressurePrev = 1013.25;

    // ── Visibility ─────────────────────────────────────────────────────────

    /**
     * Atmospheric visibility in game units (roughly metres).
     * Clear day: ~30 000. Dense fog: < 200. Heavy rain: ~2 000.
     * Consumed by DayNightSystem (SetFog far plane) and any LOD system.
     */
    #[Hidden]
    public float $visibility = 30000.0;

    // ── Thermal / dew point ────────────────────────────────────────────────

    /**
     * Dew point in °C (August–Roche–Magnus approximation).
     * When temperature − dewPoint < 2 °C, fog formation probability rises.
     */
    #[Hidden]
    public float $dewPoint = 10.0;

    /**
     * Thermal convection intensity 0–1.
     * High sun + warm ground → thermals → cumulus cloud seeding + humidity rise.
     */
    #[Hidden]
    public float $thermalIntensity = 0.0;

    // ── Simulation clock ───────────────────────────────────────────────────

    /** Accumulated simulation time — drives the slow pressure oscillation (internal). */
    #[Hidden]
    public float $simulationTime = 0.0;

    public function __construct(
        float $airPressure = 1013.25,
        float $cloudBaseAltitude = 45.0,
    ) {
        $this->airPressure      = $airPressure;
        $this->pressurePrev     = $airPressure;
        $this->cloudBaseAltitude = $cloudBaseAltitude;
    }
}
