<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use WeakMap;

final class v84BlockBreaks
{

	private readonly WeakMap $started;

	public function __construct()
	{
		$this->started = new WeakMap();
	}

	public function begin(Player $player, Vector3 $block) : void
	{
		$this->started[$player] = $block;
	}

	public function end(Player $player, Vector3 $block) : void
	{
		$started = $this->started[$player] ?? null;
		if ($started !== null && $started->equals($block)) {
			unset($this->started[$player]);
		}
	}

	public function release(Player $player) : void
	{
		$block = $this->started[$player] ?? null;
		if ($block === null || $player->getCurrentWindow() === null) {
			return;
		}

		$player->stopBreakBlock($block);
		unset($this->started[$player]);
	}
}
