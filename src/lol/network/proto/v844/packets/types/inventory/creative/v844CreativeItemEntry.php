<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\inventory\creative;

use lol\network\proto\v844\structure\v844ItemStackSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

final class v844CreativeItemEntry
{

	public function __construct(
		private int      $entryId,
		private ItemStack $item,
		private int      $groupId
	)
	{

	}

	public static function read(ByteBufferReader $in) : self
	{
		$entryId = CommonTypes::readCreativeItemNetId($in);
		$item = v844ItemStackSerializer::getItemStackWithoutStackId($in);
		$groupId = VarInt::readUnsignedInt($in);
		return new self($entryId, $item, $groupId);
	}

	public function write(ByteBufferWriter $out) : void
	{
		CommonTypes::writeCreativeItemNetId($out, $this->entryId);
		v844ItemStackSerializer::putItemStackWithoutStackId($out, $this->item);
		VarInt::writeUnsignedInt($out, $this->groupId);
	}

	public function getEntryId() : int
	{
		return $this->entryId;
	}

	public function getItem() : ItemStack
	{
		return $this->item;
	}

	public function getGroupId() : int
	{
		return $this->groupId;
	}
}
