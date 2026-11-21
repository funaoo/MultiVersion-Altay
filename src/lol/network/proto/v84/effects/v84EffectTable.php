<?php

declare(strict_types=1);

namespace lol\network\proto\v84\effects;

use pocketmine\data\bedrock\EffectIds;

final readonly class v84EffectTable
{

	private array $effects;

	public function __construct()
	{
		$this->effects = [
			EffectIds::SPEED => v84EffectId::SPEED,
			EffectIds::SLOWNESS => v84EffectId::SLOWNESS,
			EffectIds::HASTE => v84EffectId::HASTE,
			EffectIds::MINING_FATIGUE => v84EffectId::FATIGUE,
			EffectIds::STRENGTH => v84EffectId::STRENGTH,
			EffectIds::INSTANT_HEALTH => v84EffectId::HEALING,
			EffectIds::INSTANT_DAMAGE => v84EffectId::HARMING,
			EffectIds::JUMP_BOOST => v84EffectId::JUMP,
			EffectIds::NAUSEA => v84EffectId::NAUSEA,
			EffectIds::REGENERATION => v84EffectId::REGENERATION,
			EffectIds::RESISTANCE => v84EffectId::DAMAGE_RESISTANCE,
			EffectIds::FIRE_RESISTANCE => v84EffectId::FIRE_RESISTANCE,
			EffectIds::WATER_BREATHING => v84EffectId::WATER_BREATHING,
			EffectIds::INVISIBILITY => v84EffectId::INVISIBILITY,
			EffectIds::BLINDNESS => v84EffectId::BLINDNESS,
			EffectIds::NIGHT_VISION => v84EffectId::NIGHT_VISION,
			EffectIds::HUNGER => v84EffectId::HUNGER,
			EffectIds::WEAKNESS => v84EffectId::WEAKNESS,
			EffectIds::POISON => v84EffectId::POISON,
			EffectIds::WITHER => v84EffectId::WITHER,
			EffectIds::HEALTH_BOOST => v84EffectId::HEALTH_BOOST,
			EffectIds::ABSORPTION => v84EffectId::ABSORPTION,
			EffectIds::SATURATION => v84EffectId::SATURATION,
		];
	}

	public function lookup(int $modernEffect) : ?int
	{
		return $this->effects[$modernEffect] ?? null;
	}
}
