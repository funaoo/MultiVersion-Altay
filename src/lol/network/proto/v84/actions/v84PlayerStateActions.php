<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

use pocketmine\player\Player;

final class v84PlayerStateActions implements v84PlayerStances
{

	public function __construct(private readonly Player $player)
	{
	}

	public function setSprinting(bool $sprinting) : void
	{
		$this->resyncIfRefused($this->player->toggleSprint($sprinting));
	}

	public function setSneaking(bool $sneaking) : void
	{
		$this->resyncIfRefused($this->player->toggleSneak($sneaking, $sneaking));
	}

	public function stopSleeping() : void
	{
		$this->player->stopSleep();
	}

	public function jump() : void
	{
		$this->player->jump();
	}

	private function resyncIfRefused(bool $accepted) : void
	{
		if (!$accepted) {
			$this->player->sendData([$this->player]);
		}
	}
}
