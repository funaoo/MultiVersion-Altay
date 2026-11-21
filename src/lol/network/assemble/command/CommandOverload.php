<?php

declare(strict_types=1);

namespace lol\network\assemble\command;

class CommandOverload
{

	public function __construct(
		private bool  $chaining,
		private array $parameters
	)
	{
		(function (CommandParameter ...$parameters) : void {
		})(...$parameters);
	}

	public function isChaining() : bool
	{
		return $this->chaining;
	}

	public function getParameters() : array
	{
		return $this->parameters;
	}

}
