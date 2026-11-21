<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;

class v84BlockEntityDataPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::BLOCK_ENTITY_DATA_PACKET;

	public int $x = 0;

	public int $y = 0;

	public int $z = 0;

	public string $namedTag = '';

	static public function fromLatest(BlockActorDataPacket $packet, string $namedTag) : self
	{
		$npk = new self();
		$npk->x = $packet->blockPosition->getX();
		$npk->y = $packet->blockPosition->getY();
		$npk->z = $packet->blockPosition->getZ();
		$npk->namedTag = $namedTag;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->x = BE::readSignedInt($in);
		$this->y = BE::readSignedInt($in);
		$this->z = BE::readSignedInt($in);
		$this->namedTag = $in->readByteArray($in->getUnreadLength());
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->x);
		BE::writeSignedInt($out, $this->y);
		BE::writeSignedInt($out, $this->z);
		$out->writeByteArray($this->namedTag);
	}
}
