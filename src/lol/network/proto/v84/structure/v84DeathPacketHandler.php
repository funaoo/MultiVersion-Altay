<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use lol\network\proto\v84\actions\v84PlayerAction;
use lol\network\proto\v84\packets\v84PlayerActionPacket;
use pocketmine\network\mcpe\handler\DeathPacketHandler;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\player\Player;

final class v84DeathPacketHandler extends PacketHandler
{

	public function __construct(
		private readonly DeathPacketHandler $inner,
		private readonly Player             $player
	)
	{
	}

	public function setUp() : void
	{
		$this->inner->setUp();
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool
	{
		return $this->inner->handlePlayerAction($packet);
	}

	public function handleContainerClose(ContainerClosePacket $packet) : bool
	{
		return $this->inner->handleContainerClose($packet);
	}

	public function handleRespawn(RespawnPacket $packet) : bool
	{
		return $this->inner->handleRespawn($packet);
	}

	public function handleV84PlayerAction(v84PlayerActionPacket $packet) : bool
	{
		if (v84PlayerAction::isRespawn($packet->action) && $this->player->isConnected()) {
			$this->player->respawn();
		}

		return true;
	}
}
