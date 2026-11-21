<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\blocks\v84LegacyBlock;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class v84UpdateBlockPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::UPDATE_BLOCK_PACKET;

	public const NORMAL_DATA_LAYER = 0;

	private const FLAGS_SHIFT = 4;

	private const FLAGS_MASK = 0xf;

	public int $x = 0;

	public int $y = 0;

	public int $z = 0;

	public int $blockId = 0;

	public int $blockData = 0;

	public int $flags = 0;

	static public function fromLatest(UpdateBlockPacket $packet, int $packedBlock) : self
	{
		$npk = new self();
		$npk->x = $packet->blockPosition->getX();
		$npk->y = $packet->blockPosition->getY();
		$npk->z = $packet->blockPosition->getZ();
		$npk->blockId = v84LegacyBlock::id($packedBlock);
		$npk->blockData = v84LegacyBlock::meta($packedBlock);
		$npk->flags = $packet->flags & self::FLAGS_MASK;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->x = BE::readSignedInt($in);
		$this->z = BE::readSignedInt($in);
		$this->y = Byte::readUnsigned($in);
		$this->blockId = Byte::readUnsigned($in);
		$flagsAndData = Byte::readUnsigned($in);
		$this->flags = $flagsAndData >> self::FLAGS_SHIFT;
		$this->blockData = $flagsAndData & v84LegacyBlock::META_MASK;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->x);
		BE::writeSignedInt($out, $this->z);
		Byte::writeUnsigned($out, $this->y);
		Byte::writeUnsigned($out, $this->blockId);
		Byte::writeUnsigned($out, ($this->flags << self::FLAGS_SHIFT) | $this->blockData);
	}
}
