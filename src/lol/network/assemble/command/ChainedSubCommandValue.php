<?php

declare(strict_types=1);

namespace lol\network\assemble\command;

class ChainedSubCommandValue
{

	public function __construct(
		private string $name,
		private int    $type
	)
	{
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getType() : int
	{
		return $this->type;
	}

}
