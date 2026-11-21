<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84CommonTypes;
use lol\network\proto\v84\structure\v84InGamePacketHandler;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\proto\v84\text\v84TextType;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v84TextPacket extends DataPacket implements ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::TEXT_PACKET;

	public int $type = v84TextType::RAW;

	public string $source = '';

	public string $message = '';

	public array $parameters = [];

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler instanceof v84InGamePacketHandler && $handler->handleV84Text($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->type = Byte::readUnsigned($in);
		switch ($this->type) {
			case v84TextType::POPUP:
			case v84TextType::CHAT:
				$this->source = v84CommonTypes::getString($in);
				$this->message = v84CommonTypes::getString($in);
				return;
			case v84TextType::TRANSLATION:
				$this->message = v84CommonTypes::getString($in);
				$count = Byte::readUnsigned($in);
				for ($i = 0; $i < $count; ++$i) {
					$this->parameters[] = v84CommonTypes::getString($in);
				}
				return;
			default:
				$this->message = v84CommonTypes::getString($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->type);
		if ($this->type === v84TextType::CHAT || $this->type === v84TextType::POPUP) {
			v84CommonTypes::putString($out, $this->source);
		}

		v84CommonTypes::putString($out, $this->message);
	}
}
