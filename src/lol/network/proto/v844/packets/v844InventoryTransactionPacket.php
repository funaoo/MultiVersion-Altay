<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\packets\types\inventory\v844ReleaseItemTransactionData;
use lol\network\proto\v844\packets\types\inventory\v844UseItemOnEntityTransactionData;
use lol\network\proto\v844\packets\types\inventory\v844UseItemTransactionData;
use lol\network\proto\v844\structure\v844NetworkInventoryAction;
use lol\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;

class v844InventoryTransactionPacket extends InventoryTransactionPacket {

	public static function fromLatest(InventoryTransactionPacket $packet) : self{
		$result = new self();
		$result->requestId = $packet->requestId;
		$result->requestChangedSlots = $packet->requestChangedSlots;
		$result->trData = $packet->trData;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->requestId = CommonTypes::readLegacyItemStackRequestId($in);
		$this->requestChangedSlots = [];
		if($this->requestId !== 0){
			for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
				$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}

		$transactionType = VarInt::readUnsignedInt($in);

		$this->trData = match($transactionType){
			NormalTransactionData::ID => new NormalTransactionData(),
			MismatchTransactionData::ID => new MismatchTransactionData(),
			UseItemTransactionData::ID => new v844UseItemTransactionData(),
			UseItemOnEntityTransactionData::ID => new v844UseItemOnEntityTransactionData(),
			ReleaseItemTransactionData::ID => new v844ReleaseItemTransactionData(),
			default => throw new PacketDecodeException("Unknown transaction type $transactionType"),
		};

		$actions = [];
		for($i = 0, $actionCount = VarInt::readUnsignedInt($in); $i < $actionCount; ++$i){
			$actions[] = v844NetworkInventoryAction::read($in);
		}
		ReflectionUtils::setProperty(TransactionData::class, $this->trData, "actions", $actions);
		ReflectionUtils::invoke(get_class($this->trData), $this->trData, "decodeData", $in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::writeLegacyItemStackRequestId($out, $this->requestId);
		if($this->requestId !== 0){
			VarInt::writeUnsignedInt($out, count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlots){
				$changedSlots->write($out);
			}
		}

		VarInt::writeUnsignedInt($out, $this->trData->getTypeId());

		VarInt::writeUnsignedInt($out, count($this->trData->getActions()));
		foreach($this->trData->getActions() as $action){
			v844NetworkInventoryAction::write($out, $action);
		}
		ReflectionUtils::invoke(get_class($this->trData), $this->trData, "encodeData", $out);
	}
}
