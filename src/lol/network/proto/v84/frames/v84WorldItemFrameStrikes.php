<?php

declare(strict_types=1);

namespace lol\network\proto\v84\frames;

use lol\network\proto\v84\interaction\v84BlockResync;
use pocketmine\block\ItemFrame;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

final class v84WorldItemFrameStrikes implements v84ItemFrameStrikes
{

	public function __construct(
		private readonly Player         $player,
		private readonly v84BlockResync $resync
	)
	{
	}

	public function strike(Vector3 $frame) : void
	{
		if (!$this->player->isAlive() || !$this->player->isConnected()) {
			return;
		}

		$world = $this->player->getWorld();
		if (!$world->isInWorld($frame->getFloorX(), $frame->getFloorY(), $frame->getFloorZ())) {
			return;
		}

		$block = $world->getBlock($frame);
		if (!$block instanceof ItemFrame || $block->getFramedItem() === null) {
			return;
		}

		$facing = $block->getFacing();
		if (!$this->player->attackBlock($frame, $facing)) {
			$this->resync->around($frame, $facing);
		}
	}
}
