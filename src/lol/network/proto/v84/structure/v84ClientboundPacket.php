<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;

abstract class v84ClientboundPacket extends DataPacket implements ClientboundPacket
{

	final public function handle(PacketHandlerInterface $handler) : bool
	{
		return false;
	}
}
