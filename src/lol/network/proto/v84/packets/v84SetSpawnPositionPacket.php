<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\proto\v84\structure\v84WorldLimits;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;

class v84SetSpawnPositionPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::SET_SPAWN_POSITION_PACKET;

	public int $x = 0;

	public int $y = 0;

	public int $z = 0;

	static public function fromLatest(SetSpawnPositionPacket $packet) : self
	{
		$npk = new self();
		$npk->x = $packet->spawnPosition->getX();
		$npk->y = v84WorldLimits::clampBlockY($packet->spawnPosition->getY());
		$npk->z = $packet->spawnPosition->getZ();
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->x = BE::readSignedInt($in);
		$this->y = BE::readSignedInt($in);
		$this->z = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->x);
		BE::writeSignedInt($out, $this->y);
		BE::writeSignedInt($out, $this->z);
	}
}
