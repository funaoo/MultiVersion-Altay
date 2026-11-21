<?php

declare(strict_types=1);

namespace lol\network\assemble\command;

class CommandParameter
{

	public string $paramName;
	public int $paramType;
	public bool $isOptional;
	public int $flags = 0;
	public ?CommandEnum $enum = null;
	public ?string $postfix = null;

	public static function standard(string $name, int $flag_valid, int $type, int $flags = 0, bool $optional = false) : self
	{
		return self::baseline($name, $flag_valid | $type, $flags, $optional);
	}

	private static function baseline(string $name, int $type, int $flags, bool $optional) : self
	{
		$result = new self();
		$result->paramName = $name;
		$result->paramType = $type;
		$result->flags = $flags;
		$result->isOptional = $optional;
		return $result;
	}
}
