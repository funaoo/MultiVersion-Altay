<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\SetDifficultyPacket;

class v84SetDifficultyPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::SET_DIFFICULTY_PACKET;

	public int $difficulty = 0;

	static public function fromLatest(SetDifficultyPacket $packet) : self
	{
		$npk = new self();
		$npk->difficulty = $packet->difficulty;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->difficulty = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->difficulty);
	}
}
