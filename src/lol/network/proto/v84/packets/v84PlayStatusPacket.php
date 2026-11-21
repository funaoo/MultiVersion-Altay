<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;

class v84PlayStatusPacket extends PlayStatusPacket
{
	public const NETWORK_ID = v84ProtocolInfo::PLAY_STATUS_PACKET;

	static public function fromLatest(PlayStatusPacket $packet) : ?v84PlayStatusPacket
	{
		if ($packet->status < self::LOGIN_SUCCESS || $packet->status > self::PLAYER_SPAWN) {
			return null;
		}

		$npk = new v84PlayStatusPacket();
		$npk->status = $packet->status;
		return $npk;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeUnsignedInt($out, $this->status);
	}
}
