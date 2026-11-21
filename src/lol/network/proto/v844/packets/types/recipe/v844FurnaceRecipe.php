<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\recipe;

use lol\network\proto\v844\packets\v844CraftingDataPacket;
use lol\network\proto\v844\structure\v844ItemStackSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

final class v844FurnaceRecipe extends v844RecipeWithTypeId
{

	public function __construct(
		int               $typeId,
		private int       $inputId,
		private ?int      $inputMeta,
		private ItemStack $result,
		private string    $blockName
	)
	{
		parent::__construct($typeId);
	}

	public function getInputId() : int
	{
		return $this->inputId;
	}

	public function getInputMeta() : ?int
	{
		return $this->inputMeta;
	}

	public function getResult() : ItemStack
	{
		return $this->result;
	}

	public function getBlockName() : string
	{
		return $this->blockName;
	}

	public static function read(int $typeId, ByteBufferReader $in) : self
	{
		$inputId = VarInt::readSignedInt($in);
		$inputMeta = null;
		if ($typeId === v844CraftingDataPacket::ENTRY_FURNACE_DATA) {
			$inputMeta = VarInt::readSignedInt($in);
		}
		$result = v844ItemStackSerializer::getItemStackWithoutStackId($in);
		$blockName = CommonTypes::getString($in);

		return new self($typeId, $inputId, $inputMeta, $result, $blockName);
	}

	public function write(ByteBufferWriter $out) : void
	{
		VarInt::writeSignedInt($out, $this->inputId);
		if ($this->getTypeId() === v844CraftingDataPacket::ENTRY_FURNACE_DATA) {
			VarInt::writeSignedInt($out, $this->inputMeta ?? 0);
		}
		v844ItemStackSerializer::putItemStackWithoutStackId($out, $this->result);
		CommonTypes::putString($out, $this->blockName);
	}
}
