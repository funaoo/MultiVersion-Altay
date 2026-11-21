<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use pocketmine\block\inventory\AnvilInventory;
use pocketmine\block\inventory\BrewingStandInventory;
use pocketmine\block\inventory\CartographyTableInventory;
use pocketmine\block\inventory\CraftingTableInventory;
use pocketmine\block\inventory\EnchantInventory;
use pocketmine\block\inventory\FurnaceInventory;
use pocketmine\block\inventory\HopperInventory;
use pocketmine\block\inventory\LoomInventory;
use pocketmine\block\inventory\SmithingTableInventory;
use pocketmine\block\inventory\StonecutterInventory;
use pocketmine\crafting\FurnaceType;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerCraftingInventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerEnderInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

final class v84WindowType
{

	public const CONTAINER = 0;

	public const WORKBENCH = 1;

	public const FURNACE = 2;

	public const WORKBENCH_SLOTS = 10;

	private const DISCARDED_INVENTORIES = [
		AnvilInventory::class,
		BrewingStandInventory::class,
		CartographyTableInventory::class,
		EnchantInventory::class,
		HopperInventory::class,
		LoomInventory::class,
		SmithingTableInventory::class,
		StonecutterInventory::class,
		PlayerInventory::class,
		ArmorInventory::class,
		PlayerCursorInventory::class,
		PlayerOffHandInventory::class,
		PlayerCraftingInventory::class,
		PlayerEnderInventory::class,
	];

	private function __construct()
	{
	}

	public static function fromNetwork(int $windowType) : ?int
	{
		return match ($windowType) {
			WindowTypes::CONTAINER => self::CONTAINER,
			WindowTypes::WORKBENCH => self::WORKBENCH,
			WindowTypes::FURNACE => self::FURNACE,
			default => null
		};
	}

	public static function fromInventory(Inventory $inventory) : ?int
	{
		if ($inventory instanceof CraftingTableInventory) {
			return self::WORKBENCH;
		}

		if ($inventory instanceof FurnaceInventory) {
			return $inventory->getFurnaceType() === FurnaceType::FURNACE ? self::FURNACE : null;
		}

		foreach (self::DISCARDED_INVENTORIES as $class) {
			if ($inventory instanceof $class) {
				return null;
			}
		}

		return self::CONTAINER;
	}

	public static function hasContent(Inventory $inventory) : bool
	{
		$type = self::fromInventory($inventory);

		return $type === self::CONTAINER || $type === self::FURNACE;
	}
}
