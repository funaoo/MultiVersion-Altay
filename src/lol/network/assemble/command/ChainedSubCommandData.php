<?php

declare(strict_types=1);

namespace lol\network\assemble\command;

class ChainedSubCommandData
{

	public function __construct(
		private string $name,
		private array  $values
	)
	{
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getValues() : array
	{
		return $this->values;
	}
}
