<?php

declare(strict_types=1);

namespace lol\network\legacy;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class LegacyBookEditPacket extends BookEditPacket {

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->type = Byte::readUnsigned($in);
		$this->inventorySlot = Byte::readUnsigned($in);

		switch($this->type){
			case self::TYPE_REPLACE_PAGE:
			case self::TYPE_ADD_PAGE:
				$this->pageNumber = Byte::readUnsigned($in);
				$this->text = CommonTypes::getString($in);
				$this->photoName = CommonTypes::getString($in);
				break;
			case self::TYPE_DELETE_PAGE:
				$this->pageNumber = Byte::readUnsigned($in);
				break;
			case self::TYPE_SWAP_PAGES:
				$this->pageNumber = Byte::readUnsigned($in);
				$this->secondaryPageNumber = Byte::readUnsigned($in);
				break;
			case self::TYPE_SIGN_BOOK:
				$this->title = CommonTypes::getString($in);
				$this->author = CommonTypes::getString($in);
				$this->xuid = CommonTypes::getString($in);
				break;
			default:
				throw new PacketDecodeException("Unknown book edit type $this->type!");
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->type);
		Byte::writeUnsigned($out, $this->inventorySlot);

		switch($this->type){
			case self::TYPE_REPLACE_PAGE:
			case self::TYPE_ADD_PAGE:
				Byte::writeUnsigned($out, $this->pageNumber);
				CommonTypes::putString($out, $this->text);
				CommonTypes::putString($out, $this->photoName);
				break;
			case self::TYPE_DELETE_PAGE:
				Byte::writeUnsigned($out, $this->pageNumber);
				break;
			case self::TYPE_SWAP_PAGES:
				Byte::writeUnsigned($out, $this->pageNumber);
				Byte::writeUnsigned($out, $this->secondaryPageNumber);
				break;
			case self::TYPE_SIGN_BOOK:
				CommonTypes::putString($out, $this->title);
				CommonTypes::putString($out, $this->author);
				CommonTypes::putString($out, $this->xuid);
				break;
			default:
				throw new \InvalidArgumentException("Unknown book edit type $this->type!");
		}
	}
}
