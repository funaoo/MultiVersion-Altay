<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\inventory\stackrequest;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestSlotInfo;

final class v844ItemStackRequestSlotInfo
{

	private function __construct()
	{

	}

	public static function read(ByteBufferReader $in) : ItemStackRequestSlotInfo
	{
		$containerName = FullContainerName::read($in);
		$slotId = Byte::readUnsigned($in);
		$stackId = VarInt::readSignedInt($in);
		return new ItemStackRequestSlotInfo($containerName, $slotId, $stackId);
	}

	public static function write(ByteBufferWriter $out, ItemStackRequestSlotInfo $slotInfo) : void
	{
		$slotInfo->getContainerName()->write($out);
		Byte::writeUnsigned($out, $slotInfo->getSlotId());
		VarInt::writeSignedInt($out, $slotInfo->getStackId());
	}
}
