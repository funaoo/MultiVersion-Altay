<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v84PlayerInputPacket extends DataPacket implements ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::PLAYER_INPUT_PACKET;

	private const JUMPING_FLAG = 0x80;

	private const SNEAKING_FLAG = 0x40;

	public float $motionX = 0.0;

	public float $motionY = 0.0;

	public bool $jumping = false;

	public bool $sneaking = false;

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return false;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->motionX = BE::readFloat($in);
		$this->motionY = BE::readFloat($in);
		$flags = Byte::readUnsigned($in);
		$this->jumping = ($flags & self::JUMPING_FLAG) !== 0;
		$this->sneaking = ($flags & self::SNEAKING_FLAG) !== 0;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeFloat($out, $this->motionX);
		BE::writeFloat($out, $this->motionY);
		Byte::writeUnsigned($out, ($this->jumping ? self::JUMPING_FLAG : 0) | ($this->sneaking ? self::SNEAKING_FLAG : 0));
	}
}
