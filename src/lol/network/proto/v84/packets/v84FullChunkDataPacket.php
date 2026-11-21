<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\chunk\v84ChunkLayout;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use function strlen;

class v84FullChunkDataPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::FULL_CHUNK_DATA_PACKET;

	public int $chunkX = 0;

	public int $chunkZ = 0;

	public int $order = v84ChunkLayout::ORDER_LAYERED;

	public string $data = '';

	static public function fromLatest(LevelChunkPacket $packet) : self
	{
		$npk = new self();
		$position = $packet->getChunkPosition();
		$npk->chunkX = $position->getX();
		$npk->chunkZ = $position->getZ();
		$npk->data = $packet->getExtraPayload();
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->chunkX = BE::readSignedInt($in);
		$this->chunkZ = BE::readSignedInt($in);
		$this->order = Byte::readUnsigned($in);
		$this->data = $in->readByteArray(BE::readUnsignedInt($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->chunkX);
		BE::writeSignedInt($out, $this->chunkZ);
		Byte::writeUnsigned($out, $this->order);
		BE::writeUnsignedInt($out, strlen($this->data));
		$out->writeByteArray($this->data);
	}
}
