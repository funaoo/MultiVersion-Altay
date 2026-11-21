<?php

declare(strict_types=1);

namespace lol\network\proto\v84\metadata;

final class v84MetadataFlag
{

	public const ON_FIRE = 0;

	public const SNEAKING = 1;

	public const RIDING = 2;

	public const SPRINTING = 3;

	public const ACTION = 4;

	public const INVISIBLE = 5;

	public const PLAYER_SLEEP = 1;

	public const PLAYER_DEAD = 2;

	private function __construct()
	{
	}
}
