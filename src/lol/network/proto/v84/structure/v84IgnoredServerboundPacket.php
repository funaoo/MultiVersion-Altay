<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

abstract class v84IgnoredServerboundPacket extends DataPacket implements ServerboundPacket
{

	final public function handle(PacketHandlerInterface $handler) : bool
	{
		return false;
	}

	final protected function decodePayload(ByteBufferReader $in) : void
	{
		$remaining = $in->getUnreadLength();
		if ($remaining > 0) {
			$in->readByteArray($remaining);
		}
	}

	final protected function encodePayload(ByteBufferWriter $out) : void
	{
	}
}
