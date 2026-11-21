<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\recipe;

use pmmp\encoding\ByteBufferWriter;

abstract class v844RecipeWithTypeId
{

	protected function __construct(
		private int $typeId
	)
	{

	}

	final public function getTypeId() : int
	{
		return $this->typeId;
	}

	abstract public function write(ByteBufferWriter $out) : void;
}
