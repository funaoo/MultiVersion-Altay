<?php

declare(strict_types=1);

namespace lol\network\proto\v84\metadata;

final class v84MetadataType
{

	public const BYTE = 0;

	public const SHORT = 1;

	public const INT = 2;

	public const FLOAT = 3;

	public const STRING = 4;

	public const SLOT = 5;

	public const POSITION = 6;

	public const LONG = 7;

	private function __construct()
	{
	}
}
