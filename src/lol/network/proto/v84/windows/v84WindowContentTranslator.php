<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use lol\network\proto\v84\inventory\v84ClearingPass;
use lol\network\proto\v84\inventory\v84InventoryLayout;
use lol\network\proto\v84\inventory\v84InventoryTranslator;
use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\packets\outgoing\v84OutgoingContainerSetContentPacket;
use lol\network\proto\v84\packets\outgoing\v84OutgoingContainerSetSlotPacket;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;

final class v84WindowContentTranslator
{

	private const FIRST_SLOT = 0;

	private function __construct()
	{
	}

	public static function content(?InventoryManager $manager, InventoryContentPacket $packet, v84ItemEncoder $items) : ?v84OutgoingContainerSetContentPacket
	{
		$window = self::locate($manager, $packet->windowId, self::FIRST_SLOT);
		if ($window === null || v84ClearingPass::isClearingContent($packet, $window->inventory)) {
			return null;
		}

		return v84OutgoingContainerSetContentPacket::fromParts($window->clientId, v84InventoryTranslator::slots($window->inventory, $items), []);
	}

	public static function slot(?InventoryManager $manager, InventorySlotPacket $packet, v84ItemEncoder $items) : ?v84OutgoingContainerSetSlotPacket
	{
		$window = self::locate($manager, $packet->windowId, $packet->inventorySlot);
		if ($window === null) {
			return null;
		}

		$item = $window->inventory->getItem($window->slot);
		if (v84ClearingPass::isClearingSlot($packet, $item)) {
			return null;
		}

		return v84OutgoingContainerSetSlotPacket::fromParts($window->clientId, $window->slot, v84InventoryLayout::UNSET_HOTBAR_SLOT, $items->slot($item));
	}

	private static function locate(?InventoryManager $manager, int $networkId, int $slot) : ?v84LocatedWindow
	{
		$clientId = v84WindowIds::toClient($networkId);
		if ($manager === null || $clientId === null) {
			return null;
		}

		$located = $manager->locateWindowAndSlot($networkId, $slot);
		if ($located === null || !v84WindowType::hasContent($located[0])) {
			return null;
		}

		return new v84LocatedWindow($clientId, $located[0], $located[1]);
	}
}
