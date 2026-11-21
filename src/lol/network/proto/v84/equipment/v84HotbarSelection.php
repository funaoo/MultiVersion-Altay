<?php

declare(strict_types=1);

namespace lol\network\proto\v84\equipment;

use lol\network\proto\v84\inventory\v84InventoryLayout;

final class v84HotbarSelection
{

	private function __construct()
	{
	}

	public static function linkedInventorySlot(int $clientSlot, int $inventorySize) : ?int
	{
		$linked = $clientSlot - v84InventoryLayout::HOTBAR_LINK_OFFSET;
		if ($linked < 0 || $linked >= $inventorySize) {
			return null;
		}

		return $linked;
	}

	public static function isRemap(int $selectedSlot, int $clientSlot, int $inventorySize) : bool
	{
		$linked = self::linkedInventorySlot($clientSlot, $inventorySize);

		return $linked !== null && $linked !== $selectedSlot;
	}
}
