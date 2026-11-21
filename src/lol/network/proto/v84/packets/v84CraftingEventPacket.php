<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\structure\v84InGamePacketHandler;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v84CraftingEventPacket extends DataPacket implements ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CRAFTING_EVENT_PACKET;

	public const RECIPE_ID_LENGTH = 16;

	public const SLOTS_MAX = 128;

	public int $windowId = 0;

	public int $type = 0;

	public string $recipeId = '';

	public array $input = [];

	public array $output = [];

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler instanceof v84InGamePacketHandler && $handler->handleV84CraftingEvent($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
		$this->type = BE::readSignedInt($in);
		$this->recipeId = $in->readByteArray(self::RECIPE_ID_LENGTH);
		$this->input = self::readSlots($in);
		$this->output = self::readSlots($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
	}

	private static function readSlots(ByteBufferReader $in) : array
	{
		$count = BE::readSignedInt($in);
		if ($count < 0 || $count > self::SLOTS_MAX) {
			throw new PacketDecodeException('Crafting event slot count out of range: ' . $count);
		}

		$slots = [];
		for ($index = 0; $index < $count; ++$index) {
			$slots[] = v84ItemSlot::decode($in);
		}

		return $slots;
	}
}
