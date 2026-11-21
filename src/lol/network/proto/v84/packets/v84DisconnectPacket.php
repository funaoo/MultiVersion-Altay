<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use function ord;
use function strlen;
use function substr;

class v84DisconnectPacket extends DisconnectPacket
{
	public const NETWORK_ID = v84ProtocolInfo::DISCONNECT_PACKET;

	private const MAX_MESSAGE_LENGTH = 0xffff;

	private const CONTINUATION_BYTE_MASK = 0xc0;

	private const CONTINUATION_BYTE_BITS = 0x80;

	static public function fromLatest(DisconnectPacket $packet) : v84DisconnectPacket
	{
		$npk = new v84DisconnectPacket();
		$npk->message = $packet->message;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->message = $in->readByteArray(BE::readUnsignedShort($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		$message = self::clamp($this->message ?? "");
		BE::writeUnsignedShort($out, strlen($message));
		$out->writeByteArray($message);
	}

	static private function clamp(string $message) : string
	{
		if (strlen($message) <= self::MAX_MESSAGE_LENGTH) {
			return $message;
		}

		$length = self::MAX_MESSAGE_LENGTH;
		while ($length > 0 && (ord($message[$length]) & self::CONTINUATION_BYTE_MASK) === self::CONTINUATION_BYTE_BITS) {
			--$length;
		}

		return substr($message, 0, $length);
	}
}
