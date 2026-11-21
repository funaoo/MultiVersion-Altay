<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844BossEventPacket extends BossEventPacket {

	private int $playerActorUniqueId = 0;
	private bool $darkenScreen = false;

	public static function fromLatest(BossEventPacket $packet) : self {
		$result = new self();
		$result->bossActorUniqueId = $packet->bossActorUniqueId;
		$result->eventType = $packet->eventType;
		$result->playerActorUniqueId = $packet->bossActorUniqueId;
		$result->healthPercent = $packet->healthPercent;
		$result->title = $packet->title;
		$result->filteredTitle = $packet->filteredTitle;
		$result->darkenScreen = false;
		$result->color = $packet->color;
		$result->overlay = $packet->overlay;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->bossActorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->eventType = VarInt::readUnsignedInt($in);
		switch ($this->eventType) {
			case self::TYPE_REGISTER_PLAYER:
			case self::TYPE_UNREGISTER_PLAYER:
			case self::TYPE_QUERY:
				$this->playerActorUniqueId = CommonTypes::getActorUniqueId($in);
				break;
			case self::TYPE_SHOW:
				$this->title = CommonTypes::getString($in);
				$this->filteredTitle = CommonTypes::getString($in);
				$this->healthPercent = LE::readFloat($in);
				$this->darkenScreen = match ($raw = LE::readUnsignedShort($in)) {
					0 => false,
					1 => true,
					default => throw new PacketDecodeException("Invalid darkenScreen value $raw"),
				};
				$this->color = VarInt::readUnsignedInt($in);
				$this->overlay = VarInt::readUnsignedInt($in);
				break;
			case self::TYPE_PROPERTIES:
				$this->darkenScreen = match ($raw = LE::readUnsignedShort($in)) {
					0 => false,
					1 => true,
					default => throw new PacketDecodeException("Invalid darkenScreen value $raw"),
				};
				$this->color = VarInt::readUnsignedInt($in);
				$this->overlay = VarInt::readUnsignedInt($in);
				break;
			case self::TYPE_TEXTURE:
				$this->color = VarInt::readUnsignedInt($in);
				$this->overlay = VarInt::readUnsignedInt($in);
				break;
			case self::TYPE_HEALTH_PERCENT:
				$this->healthPercent = LE::readFloat($in);
				break;
			case self::TYPE_TITLE:
				$this->title = CommonTypes::getString($in);
				$this->filteredTitle = CommonTypes::getString($in);
				break;
			default:
				break;
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		CommonTypes::putActorUniqueId($out, $this->bossActorUniqueId);
		VarInt::writeUnsignedInt($out, $this->eventType);
		switch ($this->eventType) {
			case self::TYPE_REGISTER_PLAYER:
			case self::TYPE_UNREGISTER_PLAYER:
			case self::TYPE_QUERY:
				CommonTypes::putActorUniqueId($out, $this->playerActorUniqueId);
				break;
			case self::TYPE_SHOW:
				CommonTypes::putString($out, $this->title);
				CommonTypes::putString($out, $this->filteredTitle);
				LE::writeFloat($out, $this->healthPercent);
				LE::writeUnsignedShort($out, $this->darkenScreen ? 1 : 0);
				VarInt::writeUnsignedInt($out, $this->color);
				VarInt::writeUnsignedInt($out, $this->overlay);
				break;
			case self::TYPE_PROPERTIES:
				LE::writeUnsignedShort($out, $this->darkenScreen ? 1 : 0);
				VarInt::writeUnsignedInt($out, $this->color);
				VarInt::writeUnsignedInt($out, $this->overlay);
				break;
			case self::TYPE_TEXTURE:
				VarInt::writeUnsignedInt($out, $this->color);
				VarInt::writeUnsignedInt($out, $this->overlay);
				break;
			case self::TYPE_HEALTH_PERCENT:
				LE::writeFloat($out, $this->healthPercent);
				break;
			case self::TYPE_TITLE:
				CommonTypes::putString($out, $this->title);
				CommonTypes::putString($out, $this->filteredTitle);
				break;
			default:
				break;
		}
	}
}
