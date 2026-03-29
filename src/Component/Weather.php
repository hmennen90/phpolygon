<?php

declare(strict_types=1);

namespace PHPolygon\Component;

use PHPolygon\ECS\AbstractComponent;
use PHPolygon\ECS\Attribute\Category;
use PHPolygon\ECS\Attribute\Hidden;
use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Serializable;

/**
 * Weather state driven by temperature, humidity, and atmospheric conditions.
 * Attach to a single entity per scene (usually the same as DayNightCycle).
 */
#[Serializable]
#[Category('Environment')]
class Weather extends AbstractComponent
{
    // --- Atmosphere ---

    /** Cloud coverage 0–1 (0 = clear, 1 = overcast) */
    #[Property]
    public float $cloudCoverage;

    /** Humidity 0–1 (affects precipitation probability) */
    #[Property]
    public float $humidity;

    /** Current temperature in °C (determines rain vs. snow) */
    #[Hidden]
    public float $temperature;

    // --- Precipitation (mutually exclusive based on temperature) ---

    /** Rain intensity 0–1 (only when temperature > 2°C) */
    #[Hidden]
    public float $rainIntensity = 0.0;

    /** Snow intensity 0–1 (only when temperature <= 2°C) */
    #[Hidden]
    public float $snowIntensity = 0.0;

    // --- Storm ---

    /** Storm intensity 0–1 (requires clouds + high temp + humidity) */
    #[Hidden]
    public float $stormIntensity = 0.0;

    /** Lightning flash 0–1 (brief impulse during storms) */
    #[Hidden]
    public float $lightningFlash = 0.0;

    #[Hidden]
    public float $lightningTimer = 0.0;

    // --- Visibility ---

    /** Fog density 0–1 */
    #[Hidden]
    public float $fogDensity = 0.0;

    /** Sandstorm intensity 0–1 (wind + dry conditions) */
    #[Hidden]
    public float $sandstormIntensity = 0.0;

    // --- State machine ---

    #[Hidden]
    public WeatherState $state;

    #[Hidden]
    public float $stateTimer = 0.0;

    /** Seconds for smooth transitions between weather states */
    #[Property]
    public float $transitionDuration;

    public function __construct(
        float $cloudCoverage = 0.3,
        float $humidity = 0.5,
        float $temperature = 22.0,
        float $transitionDuration = 30.0,
    ) {
        $this->cloudCoverage = $cloudCoverage;
        $this->humidity = $humidity;
        $this->temperature = $temperature;
        $this->transitionDuration = $transitionDuration;
        $this->state = WeatherState::Clear;
    }

    /** Whether precipitation is active (rain or snow) */
    public function isPrecipitating(): bool
    {
        return $this->rainIntensity > 0.05 || $this->snowIntensity > 0.05;
    }

    /** Whether it's snowing (temperature-dependent) */
    public function isSnowing(): bool
    {
        return $this->temperature <= 2.0 && $this->snowIntensity > 0.05;
    }
}
