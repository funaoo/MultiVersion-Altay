<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\packets\types\inventory\v844UseItemTransactionData;
use lol\network\proto\v844\packets\types\inventory\stackrequest\v844ItemStackRequest;
use lol\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\ItemInteractionData;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;

class v844PlayerAuthInputPacket extends PlayerAuthInputPacket
{

	public const NETWORK_ID = ProtocolInfo::PLAYER_AUTH_INPUT_PACKET;

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "pitch", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "yaw", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "position", CommonTypes::getVector3($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "moveVecX", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "moveVecZ", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "headYaw", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "inputFlags", BitSet::read($in, 65));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "inputMode", VarInt::readUnsignedInt($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "playMode", VarInt::readUnsignedInt($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "interactionMode", VarInt::readUnsignedInt($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "interactRotation", CommonTypes::getVector2($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "tick", VarInt::readUnsignedLong($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "delta", CommonTypes::getVector3($in));

		if ($this->getInputFlags()->get(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION)) {
			$requestId = VarInt::readSignedInt($in);
			$requestChangedSlots = [];
			if ($requestId !== 0) {
				for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
					$requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
				}
			}
			$transactionData = new v844UseItemTransactionData();
			$transactionData->decode($in);
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "itemInteractionData", new ItemInteractionData($requestId, $requestChangedSlots, $transactionData));
		}
		if ($this->getInputFlags()->get(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST)) {
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "itemStackRequest", v844ItemStackRequest::read($in));
		}
		if ($this->getInputFlags()->get(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS)) {
			$blockActions = [];
			$max = VarInt::readSignedInt($in);
			for ($i = 0; $i < $max; ++$i) {
				$actionType = VarInt::readSignedInt($in);
				$blockActions[] = match (true) {
					PlayerBlockActionWithBlockInfo::isValidActionType($actionType) => PlayerBlockActionWithBlockInfo::read($in, $actionType),
					$actionType === PlayerAction::STOP_BREAK => new PlayerBlockActionStopBreak(),
					default => throw new PacketDecodeException("Unexpected block action type $actionType")
				};
			}
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "blockActions", $blockActions);
		}
		if ($this->getInputFlags()->get(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE)) {
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "vehicleRotation", CommonTypes::getVector2($in));
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "predictedVehicleActorUniqueId", CommonTypes::getActorUniqueId($in));
		}

		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "analogMoveVecX", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "analogMoveVecZ", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "cameraOrientation", CommonTypes::getVector3($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "rawMove", CommonTypes::getVector2($in));
	}
}
