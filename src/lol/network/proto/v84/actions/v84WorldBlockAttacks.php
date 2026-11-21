<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

use lol\network\proto\v84\interaction\v84BlockResync;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function in_array;

final class v84WorldBlockAttacks implements v84BlockAttacks
{

	public function __construct(
		private readonly Player         $player,
		private readonly v84BlockResync $resync,
		private readonly v84BlockBreaks $breaks
	)
	{
	}

	public function start(Vector3 $block, int $face) : void
	{
		if (!in_array($face, Facing::ALL, true) || $this->player->getCurrentWindow() !== null) {
			return;
		}

		if (!$this->player->attackBlock($block, $face)) {
			$this->resync->around($block, $face);
			return;
		}

		$this->breaks->begin($this->player, $block);
	}

	public function stop(Vector3 $block) : void
	{
		$this->player->stopBreakBlock($block);
		$this->breaks->end($this->player, $block);
	}
}
