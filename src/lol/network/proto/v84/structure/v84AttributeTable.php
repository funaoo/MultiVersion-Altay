<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use pocketmine\entity\Attribute;

final class v84AttributeTable
{

	private const NAMES = [
		Attribute::ABSORPTION => "generic.absorption",
		Attribute::SATURATION => "player.saturation",
		Attribute::EXHAUSTION => "player.exhaustion",
		Attribute::KNOCKBACK_RESISTANCE => "generic.knockbackResistance",
		Attribute::HEALTH => "generic.health",
		Attribute::MOVEMENT_SPEED => "generic.movementSpeed",
		Attribute::FOLLOW_RANGE => "generic.followRange",
		Attribute::HUNGER => "player.hunger",
		Attribute::ATTACK_DAMAGE => "generic.attackDamage",
		Attribute::EXPERIENCE_LEVEL => "player.level",
		Attribute::EXPERIENCE => "player.experience",
	];

	private function __construct()
	{
	}

	public static function nameOf(string $attributeId) : ?string
	{
		return self::NAMES[$attributeId] ?? null;
	}
}
