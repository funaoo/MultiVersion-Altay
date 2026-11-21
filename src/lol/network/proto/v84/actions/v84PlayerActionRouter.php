<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

use pocketmine\math\Vector3;
use pocketmine\player\Player;

final class v84PlayerActionRouter implements v84PlayerActions
{

	public function __construct(
		private readonly Player           $player,
		private readonly v84BlockAttacks  $blockAttacks,
		private readonly v84ItemRelease   $itemRelease,
		private readonly v84PlayerStances $stances
	)
	{
	}

	public function perform(int $action, Vector3 $position, int $face) : void
	{
		if (!$this->player->isAlive() || !$this->player->isConnected()) {
			return;
		}

		match ($action) {
			v84PlayerAction::START_BREAK => $this->blockAttacks->start($position, $face),
			v84PlayerAction::ABORT_BREAK, v84PlayerAction::STOP_BREAK => $this->blockAttacks->stop($position),
			v84PlayerAction::RELEASE_ITEM => $this->itemRelease->release(),
			v84PlayerAction::STOP_SLEEPING => $this->stances->stopSleeping(),
			v84PlayerAction::JUMP => $this->stances->jump(),
			v84PlayerAction::START_SPRINT => $this->stances->setSprinting(true),
			v84PlayerAction::STOP_SPRINT => $this->stances->setSprinting(false),
			v84PlayerAction::START_SNEAK => $this->stances->setSneaking(true),
			v84PlayerAction::STOP_SNEAK => $this->stances->setSneaking(false),
			default => null
		};

		if (!v84PlayerAction::keepsItemUse($action)) {
			$this->itemRelease->cancel();
		}
	}
}
