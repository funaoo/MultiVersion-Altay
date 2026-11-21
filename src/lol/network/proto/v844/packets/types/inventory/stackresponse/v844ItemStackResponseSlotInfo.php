<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\inventory\stackresponse;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponseSlotInfo;

final class v844ItemStackResponseSlotInfo
{

	public function __construct(
		private int    $slot,
		private int    $hotbarSlot,
		private int    $count,
		private int    $itemStackId,
		private string $customName,
		private string $filteredCustomName,
		private int    $durabilityCorrection
	)
	{

	}

	public static function fromLatest(ItemStackResponseSlotInfo $slot) : self
	{
		return new self(
			$slot->getSlot(),
			$slot->getHotbarSlot(),
			$slot->getCount(),
			$slot->getItemStackId() ?? 0,
			$slot->getCustomName(),
			$slot->getFilteredCustomName() ?? $slot->getCustomName(),
			$slot->getDurabilityCorrection()
		);
	}

	public function toLatest() : ItemStackResponseSlotInfo
	{
		return new ItemStackResponseSlotInfo(
			$this->slot,
			$this->hotbarSlot,
			$this->count,
			$this->itemStackId,
			$this->customName,
			$this->filteredCustomName,
			$this->durabilityCorrection
		);
	}

	public static function read(ByteBufferReader $in) : self
	{
		$slot = Byte::readUnsigned($in);
		$hotbarSlot = Byte::readUnsigned($in);
		$count = Byte::readUnsigned($in);
		$itemStackId = CommonTypes::readServerItemStackId($in);
		$customName = CommonTypes::getString($in);
		$filteredCustomName = CommonTypes::getString($in);
		$durabilityCorrection = VarInt::readSignedInt($in);
		return new self($slot, $hotbarSlot, $count, $itemStackId, $customName, $filteredCustomName, $durabilityCorrection);
	}

	public function write(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->slot);
		Byte::writeUnsigned($out, $this->hotbarSlot);
		Byte::writeUnsigned($out, $this->count);
		CommonTypes::writeServerItemStackId($out, $this->itemStackId);
		CommonTypes::putString($out, $this->customName);
		CommonTypes::putString($out, $this->filteredCustomName);
		VarInt::writeSignedInt($out, $this->durabilityCorrection);
	}

	public function getSlot() : int
	{
		return $this->slot;
	}

	public function getHotbarSlot() : int
	{
		return $this->hotbarSlot;
	}

	public function getCount() : int
	{
		return $this->count;
	}

	public function getItemStackId() : int
	{
		return $this->itemStackId;
	}

	public function getCustomName() : string
	{
		return $this->customName;
	}

	public function getFilteredCustomName() : string
	{
		return $this->filteredCustomName;
	}

	public function getDurabilityCorrection() : int
	{
		return $this->durabilityCorrection;
	}
}
