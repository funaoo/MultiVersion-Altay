<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use function count;

final class v84ClearingPass
{

	private const AIR_NETWORK_ID = 0;

	private function __construct()
	{
	}

	public static function isClearingContent(InventoryContentPacket $packet, Inventory $inventory) : bool
	{
		foreach ($packet->items as $wrapper) {
			if ($wrapper->getItemStack()->getId() !== self::AIR_NETWORK_ID) {
				return false;
			}
		}

		return count($inventory->getContents()) > 0;
	}

	public static function isClearingSlot(InventorySlotPacket $packet, Item $current) : bool
	{
		return $packet->item->getItemStack()->getId() === self::AIR_NETWORK_ID && !$current->isNull();
	}
}
