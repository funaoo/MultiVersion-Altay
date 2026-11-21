<?php

declare(strict_types=1);

namespace lol\network\proto\v84\text;

final class v84TextType
{

	public const RAW = 0;

	public const CHAT = 1;

	public const TRANSLATION = 2;

	public const POPUP = 3;

	public const TIP = 4;

	public const SYSTEM = 5;

	private function __construct()
	{
	}
}
