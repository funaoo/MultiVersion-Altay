<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PlayerEnchantOptionsPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\Enchant;
use pocketmine\network\mcpe\protocol\types\EnchantOption;
use function count;

class v844PlayerEnchantOptionsPacket extends PlayerEnchantOptionsPacket
{

	private array $v844Options = [];

	public static function fromLatest(PlayerEnchantOptionsPacket $pk) : self
	{
		$npk = new self();
		$npk->v844Options = $pk->getOptions();
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$options = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$options[] = self::readOption($in);
		}
		$this->v844Options = $options;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->v844Options));
		foreach ($this->v844Options as $option) {
			self::writeOption($out, $option);
		}
	}

	private static function readOption(ByteBufferReader $in) : EnchantOption
	{
		$cost = VarInt::readUnsignedInt($in);
		$slotFlags = LE::readUnsignedInt($in);
		$equip = self::readEnchantList($in);
		$held = self::readEnchantList($in);
		$selfActivated = self::readEnchantList($in);
		$name = CommonTypes::getString($in);
		$optionId = VarInt::readUnsignedInt($in);

		return new EnchantOption($cost, $slotFlags, $equip, $held, $selfActivated, $name, $optionId);
	}

	private static function writeOption(ByteBufferWriter $out, EnchantOption $option) : void
	{
		VarInt::writeUnsignedInt($out, $option->getCost());
		LE::writeUnsignedInt($out, $option->getSlotFlags());
		self::writeEnchantList($out, $option->getEquipActivatedEnchantments());
		self::writeEnchantList($out, $option->getHeldActivatedEnchantments());
		self::writeEnchantList($out, $option->getSelfActivatedEnchantments());
		CommonTypes::putString($out, $option->getName());
		VarInt::writeUnsignedInt($out, $option->getOptionId());
	}

	private static function readEnchantList(ByteBufferReader $in) : array
	{
		$result = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$id = Byte::readUnsigned($in);
			$level = Byte::readUnsigned($in);
			$result[] = new Enchant($id, $level);
		}
		return $result;
	}

	private static function writeEnchantList(ByteBufferWriter $out, array $list) : void
	{
		VarInt::writeUnsignedInt($out, count($list));
		foreach ($list as $item) {
			Byte::writeUnsigned($out, $item->getId());
			Byte::writeUnsigned($out, $item->getLevel());
		}
	}
}
