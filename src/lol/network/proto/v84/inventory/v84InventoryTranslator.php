<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use lol\network\NetworkSession;
use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\packets\outgoing\v84OutgoingContainerSetContentPacket;
use lol\network\proto\v84\packets\outgoing\v84OutgoingContainerSetSlotPacket;
use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\player\Player;
use function array_pad;
use function count;
use function range;

final class v84InventoryTranslator
{

	private function __construct()
	{
	}

	public static function content(NetworkSession $session, InventoryContentPacket $packet, v84ItemEncoder $items) : ?v84OutgoingContainerSetContentPacket
	{
		$windowId = v84ContainerId::fromNetwork($packet->windowId);
		$inventory = self::inventory($session->getPlayer(), $packet->windowId);
		if ($windowId === null || $inventory === null || v84ClearingPass::isClearingContent($packet, $inventory)) {
			return null;
		}

		$slots = self::slots($inventory, $items);
		if ($windowId !== v84ContainerId::INVENTORY) {
			return v84OutgoingContainerSetContentPacket::fromParts($windowId, $slots, []);
		}

		return v84OutgoingContainerSetContentPacket::fromParts($windowId, self::withDummySlots($slots), self::hotbarLinks());
	}

	public static function slot(NetworkSession $session, InventorySlotPacket $packet, v84ItemEncoder $items) : ?v84OutgoingContainerSetSlotPacket
	{
		$windowId = v84ContainerId::fromNetwork($packet->windowId);
		$inventory = self::inventory($session->getPlayer(), $packet->windowId);
		if ($windowId === null || $inventory === null || !$inventory->slotExists($packet->inventorySlot)) {
			return null;
		}

		$item = $inventory->getItem($packet->inventorySlot);
		if (v84ClearingPass::isClearingSlot($packet, $item)) {
			return null;
		}

		return v84OutgoingContainerSetSlotPacket::fromParts($windowId, $packet->inventorySlot, v84InventoryLayout::UNSET_HOTBAR_SLOT, $items->slot($item));
	}

	private static function inventory(?Player $player, int $windowId) : ?Inventory
	{
		return match ($windowId) {
			ContainerIds::INVENTORY => $player?->getInventory(),
			ContainerIds::ARMOR => $player?->getArmorInventory(),
			default => null
		};
	}

	public static function slots(Inventory $inventory, v84ItemEncoder $items) : array
	{
		$slots = [];
		for ($index = 0; $index < $inventory->getSize(); ++$index) {
			$slots[] = $items->slot($inventory->getItem($index));
		}

		return $slots;
	}

	private static function withDummySlots(array $slots) : array
	{
		return array_pad($slots, count($slots) + v84InventoryLayout::DUMMY_SLOT_COUNT, v84ItemSlot::AIR);
	}

	private static function hotbarLinks() : array
	{
		return range(v84InventoryLayout::HOTBAR_LINK_OFFSET, v84InventoryLayout::HOTBAR_LINK_OFFSET + v84InventoryLayout::HOTBAR_SIZE - 1);
	}
}
