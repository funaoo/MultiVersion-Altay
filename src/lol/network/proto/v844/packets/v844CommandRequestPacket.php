<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\assemble\CommandOriginData;
use lol\network\proto\v844\structure\v844CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844CommandRequestPacket extends CommandRequestPacket
{

	public int $newVersion;
	public CommandOriginData $_originData;

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->command = CommonTypes::getString($in);
		$this->_originData = v844CommonTypes::getCommandOriginData($in);
		$this->isInternal = CommonTypes::getBool($in);
		$this->newVersion = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->command);
		v844CommonTypes::putCommandOriginData($out, $this->_originData);
		CommonTypes::putBool($out, $this->isInternal);
		VarInt::writeSignedInt($out, $this->newVersion);
	}
}
