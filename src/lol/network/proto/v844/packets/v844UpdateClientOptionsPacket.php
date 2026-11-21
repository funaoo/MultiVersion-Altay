<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\ReflectionUtils;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GraphicsMode;
use pocketmine\network\mcpe\protocol\UpdateClientOptionsPacket;

class v844UpdateClientOptionsPacket extends UpdateClientOptionsPacket {

	protected function decodePayload(ByteBufferReader $in) : void {
		ReflectionUtils::setProperty(UpdateClientOptionsPacket::class, $this, "graphicsMode", CommonTypes::readOptional($in, fn() => GraphicsMode::fromPacket(Byte::readUnsigned($in))));
		ReflectionUtils::setProperty(UpdateClientOptionsPacket::class, $this, "filterProfanityChange", null);
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		CommonTypes::writeOptional($out, $this->getGraphicsMode(), fn(ByteBufferWriter $out, GraphicsMode $v) => Byte::writeUnsigned($out, $v->value));
	}
}
