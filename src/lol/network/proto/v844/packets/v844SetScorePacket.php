<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use function count;

class v844SetScorePacket extends SetScorePacket {

	private const PACKET_TYPE_CHANGE = 0;
	private const PACKET_TYPE_REMOVE = 1;

	private int $legacyType = self::PACKET_TYPE_CHANGE;

	public static function fromLatest(SetScorePacket $pk) : self {
		$npk = new self();
		$npk->entries = $pk->entries;

		$npk->legacyType = self::PACKET_TYPE_CHANGE;
		foreach ($pk->entries as $entry) {
			$npk->legacyType = $entry->type === ScorePacketEntry::TYPE_REMOVE ? self::PACKET_TYPE_REMOVE : self::PACKET_TYPE_CHANGE;
			break;
		}

		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->legacyType = Byte::readUnsigned($in);
		$this->entries = [];

		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$entry = new ScorePacketEntry();
			$entry->scoreboardId = VarInt::readSignedLong($in);
			$entry->objectiveName = CommonTypes::getString($in);
			$entry->score = LE::readSignedInt($in);

			if ($this->legacyType !== self::PACKET_TYPE_REMOVE) {
				$entry->type = Byte::readUnsigned($in);
				switch ($entry->type) {
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ENTITY:
						$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						$entry->customName = CommonTypes::getString($in);
						break;
				}
			} else {
				$entry->type = ScorePacketEntry::TYPE_REMOVE;
			}

			$this->entries[] = $entry;
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		Byte::writeUnsigned($out, $this->legacyType);
		VarInt::writeUnsignedInt($out, count($this->entries));

		foreach ($this->entries as $entry) {
			VarInt::writeSignedLong($out, $entry->scoreboardId);
			CommonTypes::putString($out, $entry->objectiveName ?? "");
			LE::writeSignedInt($out, $entry->score);

			if ($this->legacyType !== self::PACKET_TYPE_REMOVE) {
				Byte::writeUnsigned($out, $entry->type);
				switch ($entry->type) {
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ENTITY:
						CommonTypes::putActorUniqueId($out, $entry->actorUniqueId ?? 0);
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						CommonTypes::putString($out, $entry->customName ?? "");
						break;
				}
			}
		}
	}
}
