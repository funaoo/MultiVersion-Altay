<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\inventory\stackresponse;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponseContainerInfo;
use function array_map;
use function count;

final class v844ItemStackResponseContainerInfo
{

	public function __construct(
		private FullContainerName $containerName,
		private array             $slots
	)
	{

	}

	public static function fromLatest(ItemStackResponseContainerInfo $containerInfo) : self
	{
		return new self(
			$containerInfo->getContainerName(),
			array_map(
				static fn($slot) : v844ItemStackResponseSlotInfo => v844ItemStackResponseSlotInfo::fromLatest($slot),
				$containerInfo->getSlots()
			)
		);
	}

	public function toLatest() : ItemStackResponseContainerInfo
	{
		return new ItemStackResponseContainerInfo(
			$this->containerName,
			array_map(
				static fn(v844ItemStackResponseSlotInfo $slot) => $slot->toLatest(),
				$this->slots
			)
		);
	}

	public static function read(ByteBufferReader $in) : self
	{
		$containerName = FullContainerName::read($in);
		$slots = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$slots[] = v844ItemStackResponseSlotInfo::read($in);
		}
		return new self($containerName, $slots);
	}

	public function write(ByteBufferWriter $out) : void
	{
		$this->containerName->write($out);
		VarInt::writeUnsignedInt($out, count($this->slots));
		foreach ($this->slots as $slot) {
			$slot->write($out);
		}
	}

	public function getContainerName() : FullContainerName
	{
		return $this->containerName;
	}

	public function getSlots() : array
	{
		return $this->slots;
	}
}
