<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;

final class v84ContainerId
{

	public const INVENTORY = 0;

	public const ARMOR = 0x78;

	public const CREATIVE = 0x79;

	private function __construct()
	{
	}

	public static function isScreen(int $windowId) : bool
	{
		return $windowId === self::INVENTORY || $windowId === self::ARMOR || $windowId === self::CREATIVE;
	}

	public static function fromNetwork(int $windowId) : ?int
	{
		return match ($windowId) {
			ContainerIds::INVENTORY => self::INVENTORY,
			ContainerIds::ARMOR => self::ARMOR,
			default => null
		};
	}
}
