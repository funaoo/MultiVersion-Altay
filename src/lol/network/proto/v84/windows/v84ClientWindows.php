<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use pocketmine\network\mcpe\InventoryManager;

final class v84ClientWindows
{

	private function __construct()
	{
	}

	public static function locate(InventoryManager $manager, int $clientId, int $slot) : ?v84LocatedWindow
	{
		$networkId = v84WindowIds::fromClient($clientId);
		if ($networkId === null) {
			return null;
		}

		$located = $manager->locateWindowAndSlot($networkId, $slot);
		if ($located === null || !v84WindowType::hasContent($located[0])) {
			return null;
		}

		return new v84LocatedWindow($clientId, $located[0], $located[1]);
	}
}
