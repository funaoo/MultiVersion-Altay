<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\inventory\creative;

use lol\network\proto\v844\structure\v844ItemStackSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

final class v844CreativeGroupEntry
{

	public function __construct(
		private int    $categoryId,
		private string $categoryName,
		private ItemStack $icon
	)
	{

	}

	public static function read(ByteBufferReader $in) : self
	{
		$categoryId = LE::readSignedInt($in);
		$categoryName = CommonTypes::getString($in);
		$icon = v844ItemStackSerializer::getItemStackWithoutStackId($in);
		return new self($categoryId, $categoryName, $icon);
	}

	public function write(ByteBufferWriter $out) : void
	{
		LE::writeSignedInt($out, $this->categoryId);
		CommonTypes::putString($out, $this->categoryName);
		v844ItemStackSerializer::putItemStackWithoutStackId($out, $this->icon);
	}

	public function getCategoryId() : int
	{
		return $this->categoryId;
	}

	public function getCategoryName() : string
	{
		return $this->categoryName;
	}

	public function getIcon() : ItemStack
	{
		return $this->icon;
	}
}
