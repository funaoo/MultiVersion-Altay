<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\BlockEventPacket;

class v84BlockEventPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::BLOCK_EVENT_PACKET;

	public int $x = 0;

	public int $y = 0;

	public int $z = 0;

	public int $eventType = 0;

	public int $eventData = 0;

	static public function fromLatest(BlockEventPacket $packet) : self
	{
		$npk = new self();
		$npk->x = $packet->blockPosition->getX();
		$npk->y = $packet->blockPosition->getY();
		$npk->z = $packet->blockPosition->getZ();
		$npk->eventType = $packet->eventType;
		$npk->eventData = $packet->eventData;
		return $npk;
	}

	static public function fromParts(int $x, int $y, int $z, int $eventType, int $eventData) : self
	{
		$npk = new self();
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		$npk->eventType = $eventType;
		$npk->eventData = $eventData;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->x = BE::readSignedInt($in);
		$this->y = BE::readSignedInt($in);
		$this->z = BE::readSignedInt($in);
		$this->eventType = BE::readSignedInt($in);
		$this->eventData = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->x);
		BE::writeSignedInt($out, $this->y);
		BE::writeSignedInt($out, $this->z);
		BE::writeSignedInt($out, $this->eventType);
		BE::writeSignedInt($out, $this->eventData);
	}
}
