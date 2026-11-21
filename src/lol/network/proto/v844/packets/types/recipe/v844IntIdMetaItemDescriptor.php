<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\recipe;

use LogicException;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\recipe\ItemDescriptor;

final class v844IntIdMetaItemDescriptor implements ItemDescriptor
{

	public function __construct(
		private int $id,
		private int $meta
	)
	{

	}

	public function getId() : int
	{
		return $this->id;
	}

	public function getMeta() : int
	{
		return $this->meta;
	}

	public function getTypeId() : int
	{
		throw new LogicException(self::class . " is 844-specific and has no equivalent on Altay's own (2193) wire; it must only be consumed by v844CommonTypes::putRecipeIngredient()");
	}

	public function write(ByteBufferWriter $out) : void
	{
		throw new LogicException(self::class . " is 844-specific and has no equivalent on Altay's own (2193) wire; it must only be consumed by v844CommonTypes::putRecipeIngredient()");
	}
}
