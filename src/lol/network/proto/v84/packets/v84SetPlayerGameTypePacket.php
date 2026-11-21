<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84GameModeTranslator;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;

class v84SetPlayerGameTypePacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::SET_PLAYER_GAMETYPE_PACKET;

	public int $gameMode = v84GameModeTranslator::SURVIVAL;

	static public function fromLatest(SetPlayerGameTypePacket $packet) : self
	{
		$npk = new self();
		$npk->gameMode = v84GameModeTranslator::fromProtocol($packet->gamemode);
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->gameMode = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->gameMode);
	}
}
