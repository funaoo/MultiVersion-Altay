<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use lol\network\NetworkSession;
use pocketmine\entity\Entity;

final class v84ActorLookup
{

	private function __construct()
	{
	}

	public static function find(NetworkSession $session, int $runtimeId) : ?Entity
	{
		$player = $session->getPlayer();
		if ($player === null) {
			return null;
		}

		if ($player->getId() === $runtimeId) {
			return $player;
		}

		return $player->getWorld()->getEntity($runtimeId);
	}
}
