<?php

declare(strict_types=1);

namespace lol\network\proto\v84\entities;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

final readonly class v84EntityTypeTable
{

	private const UNDECLARED = 0.0;

	private const TYPES = [
		EntityIds::CHICKEN => [v84EntityTypeId::CHICKEN, 1.8],
		EntityIds::COW => [v84EntityTypeId::COW, 1.8],
		EntityIds::PIG => [v84EntityTypeId::PIG, 1.9],
		EntityIds::SHEEP => [v84EntityTypeId::SHEEP, 1.8],
		EntityIds::WOLF => [v84EntityTypeId::WOLF, 1.8],
		EntityIds::VILLAGER => [v84EntityTypeId::VILLAGER, 1.8],
		EntityIds::VILLAGER_V2 => [v84EntityTypeId::VILLAGER, 1.8],
		EntityIds::MOOSHROOM => [v84EntityTypeId::MOOSHROOM, 1.8],
		EntityIds::SQUID => [v84EntityTypeId::SQUID, 0.95],
		EntityIds::RABBIT => [v84EntityTypeId::RABBIT, 0.5],
		EntityIds::BAT => [v84EntityTypeId::BAT, 0.6],
		EntityIds::IRON_GOLEM => [v84EntityTypeId::IRON_GOLEM, 2.8],
		EntityIds::SNOW_GOLEM => [v84EntityTypeId::SNOW_GOLEM, 1.8],
		EntityIds::OCELOT => [v84EntityTypeId::OCELOT, 0.75],
		EntityIds::ZOMBIE => [v84EntityTypeId::ZOMBIE, 1.8],
		EntityIds::CREEPER => [v84EntityTypeId::CREEPER, self::UNDECLARED],
		EntityIds::SKELETON => [v84EntityTypeId::SKELETON, self::UNDECLARED],
		EntityIds::SPIDER => [v84EntityTypeId::SPIDER, 1.9],
		EntityIds::ZOMBIE_PIGMAN => [v84EntityTypeId::PIG_ZOMBIE, 1.8],
		EntityIds::SLIME => [v84EntityTypeId::SLIME, 5.0],
		EntityIds::ENDERMAN => [v84EntityTypeId::ENDERMAN, 1.8],
		EntityIds::SILVERFISH => [v84EntityTypeId::SILVERFISH, self::UNDECLARED],
		EntityIds::CAVE_SPIDER => [v84EntityTypeId::CAVE_SPIDER, 0.5],
		EntityIds::GHAST => [v84EntityTypeId::GHAST, 6.0],
		EntityIds::MAGMA_CUBE => [v84EntityTypeId::LAVA_SLIME, self::UNDECLARED],
		EntityIds::BLAZE => [v84EntityTypeId::BLAZE, 1.8],
		EntityIds::ZOMBIE_VILLAGER => [v84EntityTypeId::ZOMBIE_VILLAGER, 2.125],
		EntityIds::ZOMBIE_VILLAGER_V2 => [v84EntityTypeId::ZOMBIE_VILLAGER, 2.125],
		EntityIds::WITCH => [v84EntityTypeId::WITCH, self::UNDECLARED],
		EntityIds::STRAY => [v84EntityTypeId::STRAY, self::UNDECLARED],
		EntityIds::HUSK => [v84EntityTypeId::HUSK, 1.8],
		EntityIds::TNT => [v84EntityTypeId::PRIMED_TNT, 0.98],
		EntityIds::FALLING_BLOCK => [v84EntityTypeId::FALLING_SAND, 0.98],
		EntityIds::XP_BOTTLE => [v84EntityTypeId::THROWN_EXP_BOTTLE, 0.25],
		EntityIds::XP_ORB => [v84EntityTypeId::XP_ORB, 0.25],
		EntityIds::FISHING_HOOK => [v84EntityTypeId::FISHING_HOOK, 0.25],
		EntityIds::ARROW => [v84EntityTypeId::ARROW, 0.5],
		EntityIds::SNOWBALL => [v84EntityTypeId::SNOWBALL, 0.25],
		EntityIds::EGG => [v84EntityTypeId::EGG, 0.25],
		EntityIds::MINECART => [v84EntityTypeId::MINECART, 0.7],
		EntityIds::SPLASH_POTION => [v84EntityTypeId::THROWN_POTION, 0.25],
		EntityIds::BOAT => [v84EntityTypeId::BOAT, 0.7],
		EntityIds::LIGHTNING_BOLT => [v84EntityTypeId::LIGHTNING, 1.8],
		EntityIds::HOPPER_MINECART => [v84EntityTypeId::MINECART_HOPPER, 0.7],
		EntityIds::TNT_MINECART => [v84EntityTypeId::MINECART_TNT, 0.7],
		EntityIds::CHEST_MINECART => [v84EntityTypeId::MINECART_CHEST, 0.7],
	];

	private array $types;

	public function __construct()
	{
		$types = [];
		foreach (self::TYPES as $identifier => [$id, $height]) {
			$types[$identifier] = v84EntityType::fromHeight($id, $height);
		}

		$this->types = $types;
	}

	public function lookup(string $identifier) : ?v84EntityType
	{
		return $this->types[$identifier] ?? null;
	}

	public function eyeHeight(Entity $entity) : float
	{
		return $this->lookup($entity::getNetworkTypeId())?->eyeHeight ?? $entity->getEyeHeight();
	}
}
