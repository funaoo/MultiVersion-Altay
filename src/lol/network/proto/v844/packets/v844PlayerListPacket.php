<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\structure\v844SkinSerializer;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class v844PlayerListPacket extends PlayerListPacket {

	private const LEGACY_TYPE_ADD = 0;

	private const LEGACY_TYPE_REMOVE = 1;

	static public function fromLatest(PlayerListPacket $packet) : self {
		$result = new self();
		$result->type = $packet->type;
		$result->entries = $packet->entries;
		return $result;
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->type === self::TYPE_ADD ? self::LEGACY_TYPE_ADD : self::LEGACY_TYPE_REMOVE);

		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach($this->entries as $entry){
			if($this->type === self::TYPE_ADD){
				$skinData = $entry->skinData ?? throw new \InvalidArgumentException("Player list addition entries must have skin data");
				CommonTypes::putUUID($out, $entry->uuid);
				CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
				CommonTypes::putString($out, $entry->username);
				CommonTypes::putString($out, $entry->xboxUserId);
				CommonTypes::putString($out, $entry->platformChatId);
				LE::writeSignedInt($out, $entry->buildPlatform);
				v844SkinSerializer::putSkin($out, $skinData);
				CommonTypes::putBool($out, $entry->isTeacher);
				CommonTypes::putBool($out, $entry->isHost);
				CommonTypes::putBool($out, $entry->isSubClient);
				LE::writeUnsignedInt($out, ($entry->color ?? new Color(255, 255, 255))->toARGB());
			}else{
				CommonTypes::putUUID($out, $entry->uuid);
			}
		}

		if($this->type === self::TYPE_ADD){
			foreach($this->entries as $entry){
				CommonTypes::putBool($out, $entry->skinData->isVerified());
			}
		}
	}
}
