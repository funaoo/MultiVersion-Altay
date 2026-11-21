<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;

final class v844NetworkInventoryAction
{

	private function __construct()
	{

	}

	public static function read(ByteBufferReader $in) : NetworkInventoryAction
	{
		$action = new NetworkInventoryAction();
		$action->sourceType = VarInt::readUnsignedInt($in);

		switch ($action->sourceType) {
			case NetworkInventoryAction::SOURCE_CONTAINER:
				$action->windowId = VarInt::readSignedInt($in);
				break;
			case NetworkInventoryAction::SOURCE_WORLD:
				$action->sourceFlags = VarInt::readUnsignedInt($in);
				break;
			case NetworkInventoryAction::SOURCE_CREATIVE:
				break;
			case NetworkInventoryAction::SOURCE_TODO:
				$action->windowId = VarInt::readSignedInt($in);
				break;
			default:
				throw new PacketDecodeException("Unknown inventory action source type $action->sourceType");
		}

		$action->inventorySlot = VarInt::readUnsignedInt($in);
		$action->oldItem = v844ItemStackSerializer::getItemStackWrapper($in);
		$action->newItem = v844ItemStackSerializer::getItemStackWrapper($in);

		return $action;
	}

	public static function write(ByteBufferWriter $out, NetworkInventoryAction $action) : void
	{
		VarInt::writeUnsignedInt($out, $action->sourceType);

		switch ($action->sourceType) {
			case NetworkInventoryAction::SOURCE_CONTAINER:
			case NetworkInventoryAction::SOURCE_TODO:
				if ($action->windowId === null) {
					throw new \InvalidArgumentException("WindowID must be set for inventory action source type $action->sourceType");
				}
				VarInt::writeSignedInt($out, $action->windowId);
				break;
			case NetworkInventoryAction::SOURCE_WORLD:
				if ($action->sourceFlags === null) {
					throw new \InvalidArgumentException("SourceFlags must be set for SOURCE_WORLD");
				}
				VarInt::writeUnsignedInt($out, $action->sourceFlags);
				break;
			case NetworkInventoryAction::SOURCE_CREATIVE:
				break;
			default:
				throw new \InvalidArgumentException("Unknown inventory action source type $action->sourceType");
		}

		VarInt::writeUnsignedInt($out, $action->inventorySlot);
		v844ItemStackSerializer::putItemStackWrapper($out, $action->oldItem);
		v844ItemStackSerializer::putItemStackWrapper($out, $action->newItem);
	}
}
