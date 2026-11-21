<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\player\Player;

final class v84WorldBlockResync implements v84BlockResync
{

	public const MAX_DISTANCE_SQUARED = 10000;

	public function __construct(
		private readonly Player         $player,
		private readonly NetworkSession $session
	)
	{
	}

	public function around(Vector3 $block, int $face) : void
	{
		if ($block->distanceSquared($this->player->getLocation()) > self::MAX_DISTANCE_SQUARED) {
			return;
		}

		foreach ($this->player->getWorld()->createBlockUpdatePackets([$block, $block->getSide($face)]) as $packet) {
			$this->session->sendDataPacket($packet);
		}
	}
}
