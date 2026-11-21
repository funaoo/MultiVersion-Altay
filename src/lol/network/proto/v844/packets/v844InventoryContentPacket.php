<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\global\MVTypeConverter;
use lol\network\proto\v844\structure\v844ItemStackSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use function count;

class v844InventoryContentPacket extends InventoryContentPacket {

	static public function fromLatest(InventoryContentPacket $packet, MVTypeConverter $converter) : self {
		$result = new self();
		$result->windowId = $packet->windowId;
		$result->items = $packet->items;
		$result->containerName = $packet->containerName ?? new FullContainerName(0, null);
		$result->storage = $packet->storage ?? new ItemStackWrapper(0, ItemStack::null());
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->windowId = VarInt::readUnsignedInt($in);
		$this->items = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$this->items[] = v844ItemStackSerializer::getItemStackWrapper($in);
		}
		$this->containerName = FullContainerName::read($in);
		$this->storage = v844ItemStackSerializer::getItemStackWrapper($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->windowId);
		VarInt::writeUnsignedInt($out, count($this->items));
		foreach($this->items as $item){
			v844ItemStackSerializer::putItemStackWrapper($out, $item);
		}
		$this->containerName->write($out);
		v844ItemStackSerializer::putItemStackWrapper($out, $this->storage);
	}
}
