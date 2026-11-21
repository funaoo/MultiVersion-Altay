<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\SetScoreboardIdentityPacket;
use pocketmine\network\mcpe\protocol\types\ScoreboardIdentityPacketEntry;
use function count;

class v844SetScoreboardIdentityPacket extends SetScoreboardIdentityPacket {

	public static function fromLatest(SetScoreboardIdentityPacket $packet) : self {
		$result = new self();
		$result->type = $packet->type;
		$result->entries = $packet->entries;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->type = Byte::readUnsigned($in);
		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$entry = new ScoreboardIdentityPacketEntry();
			$entry->scoreboardId = VarInt::readSignedLong($in);
			if ($this->type === self::TYPE_REGISTER_IDENTITY) {
				$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
			} else {
				$entry->actorUniqueId = 0;
			}
			$this->entries[] = $entry;
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		Byte::writeUnsigned($out, $this->type);
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach ($this->entries as $entry) {
			VarInt::writeSignedLong($out, $entry->scoreboardId);
			if ($this->type === self::TYPE_REGISTER_IDENTITY) {
				CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
			}
		}
	}
}
