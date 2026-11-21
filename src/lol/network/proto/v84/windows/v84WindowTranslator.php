<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use lol\network\proto\v84\packets\v84ContainerOpenPacket;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

final class v84WindowTranslator
{

	private function __construct()
	{
	}

	public static function open(?InventoryManager $manager, ContainerOpenPacket $packet) : ?v84ContainerOpenPacket
	{
		$type = v84WindowType::fromNetwork($packet->windowType);
		$windowId = v84WindowIds::toClient($packet->windowId);
		if ($type === null || $windowId === null || $packet->actorUniqueId !== v84ContainerOpenPacket::NO_ENTITY) {
			return null;
		}

		$slots = self::slots($manager, $packet->windowId, $type);
		if ($slots === null) {
			return null;
		}

		$position = $packet->blockPosition;

		return v84ContainerOpenPacket::fromParts($windowId, $type, $slots, $position->getX(), $position->getY(), $position->getZ());
	}

	private static function slots(?InventoryManager $manager, int $networkId, int $type) : ?int
	{
		if ($type === v84WindowType::WORKBENCH) {
			return v84WindowType::WORKBENCH_SLOTS;
		}

		$located = $manager?->locateWindowAndSlot($networkId, 0);

		return $located === null ? null : $located[0]->getSize();
	}
}
