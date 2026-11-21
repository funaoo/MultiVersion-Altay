<?php

declare(strict_types=1);

namespace lol\network\proto\v84\creative;

use lol\network\NetworkSession;
use lol\network\proto\v84\packets\outgoing\v84OutgoingContainerSetContentPacket;

final class v84CreativeTranslator
{

	private function __construct()
	{
	}

	public static function content(NetworkSession $session, v84CreativeContent $creative) : ?v84OutgoingContainerSetContentPacket
	{
		$player = $session->getPlayer();
		if ($player === null) {
			return null;
		}

		return $creative->forPlayer($player->isSpectator());
	}
}
