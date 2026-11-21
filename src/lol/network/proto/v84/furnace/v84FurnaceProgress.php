<?php

declare(strict_types=1);

namespace lol\network\proto\v84\furnace;

final class v84FurnaceProgress
{

	public const SOURCE_COOK_PROPERTY = 0;

	public const SOURCE_REMAINING_FUEL_PROPERTY = 1;

	public const SOURCE_MAX_FUEL_PROPERTY = 2;

	public const COOK_PROPERTY = 0;

	public const FIRE_PROPERTY = 1;

	public const GAUGE_FULL = 200;

	private int $remainingFuel = 0;

	private int $maxFuel = 0;

	public function translate(int $sourceProperty, int $value) : ?v84FurnaceReading
	{
		return match ($sourceProperty) {
			self::SOURCE_COOK_PROPERTY => new v84FurnaceReading(self::COOK_PROPERTY, self::clamp($value)),
			self::SOURCE_REMAINING_FUEL_PROPERTY => $this->fire($value, max($this->maxFuel, $value)),
			self::SOURCE_MAX_FUEL_PROPERTY => $this->fire($this->remainingFuel, $value),
			default => null
		};
	}

	private function fire(int $remainingFuel, int $maxFuel) : v84FurnaceReading
	{
		$this->remainingFuel = $remainingFuel;
		$this->maxFuel = $maxFuel;

		if ($maxFuel <= 0) {
			return new v84FurnaceReading(self::FIRE_PROPERTY, 0);
		}

		return new v84FurnaceReading(self::FIRE_PROPERTY, self::clamp((int) ceil($remainingFuel / $maxFuel * self::GAUGE_FULL)));
	}

	private static function clamp(int $value) : int
	{
		return max(0, min(self::GAUGE_FULL, $value));
	}
}
