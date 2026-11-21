<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\inventory;

use lol\network\proto\v844\structure\v844ItemStackSerializer;
use lol\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;

class v844ReleaseItemTransactionData extends ReleaseItemTransactionData {

	protected function decodeData(ByteBufferReader $in) : void{
		ReflectionUtils::setProperty(ReleaseItemTransactionData::class, $this, "actionType", VarInt::readUnsignedInt($in));
		ReflectionUtils::setProperty(ReleaseItemTransactionData::class, $this, "hotbarSlot", VarInt::readSignedInt($in));
		ReflectionUtils::setProperty(ReleaseItemTransactionData::class, $this, "itemInHand", v844ItemStackSerializer::getItemStackWrapper($in));
		ReflectionUtils::setProperty(ReleaseItemTransactionData::class, $this, "headPosition", CommonTypes::getVector3($in));
	}

	protected function encodeData(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->getActionType());
		VarInt::writeSignedInt($out, $this->getHotbarSlot());
		v844ItemStackSerializer::putItemStackWrapper($out, $this->getItemInHand());
		CommonTypes::putVector3($out, $this->getHeadPosition());
	}
}
