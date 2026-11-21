<?php

declare(strict_types=1);

namespace lol\network\assemble\command;

class CommandEnum
{

	public function __construct(
		private string $enumName,
		private array  $enumValues,
		private bool   $isSoft = false
	)
	{
	}

	public function getName() : string
	{
		return $this->enumName;
	}

	public function getValues() : array
	{
		return $this->enumValues;
	}

	public function isSoft() : bool
	{
		return $this->isSoft;
	}
}
