<?php

declare(strict_types=1);

namespace lol\network\assemble\command;

class CommandData
{
	public function __construct(
		public string       $name,
		public string       $description,
		public int          $flags,
		public int          $permission,
		public ?CommandEnum $aliases,
		public array        $overloads,
		public array        $chainedSubCommandData
	)
	{
		(function (CommandOverload ...$overloads) : void {
		})(...$overloads);
		(function (ChainedSubCommandData ...$chainedSubCommandData) : void {
		})(...$chainedSubCommandData);
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getDescription() : string
	{
		return $this->description;
	}

	public function getFlags() : int
	{
		return $this->flags;
	}

	public function getPermission() : int
	{
		return $this->permission;
	}

	public function getAliases() : ?CommandEnum
	{
		return $this->aliases;
	}

	public function getOverloads() : array
	{
		return $this->overloads;
	}

	public function getChainedSubCommandData() : array
	{
		return $this->chainedSubCommandData;
	}
}
