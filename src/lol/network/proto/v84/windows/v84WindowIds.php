<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;

final class v84WindowIds
{

	public const RESERVED_NETWORK_ID = 1;

	public const REPLACEMENT_CLIENT_ID = ContainerIds::LAST;

	private function __construct()
	{
	}

	public static function toClient(int $networkId) : ?int
	{
		if ($networkId < ContainerIds::FIRST || $networkId >= ContainerIds::LAST) {
			return null;
		}

		return $networkId === self::RESERVED_NETWORK_ID ? self::REPLACEMENT_CLIENT_ID : $networkId;
	}

	public static function fromClient(int $clientId) : ?int
	{
		if ($clientId === self::REPLACEMENT_CLIENT_ID) {
			return self::RESERVED_NETWORK_ID;
		}

		if ($clientId <= self::RESERVED_NETWORK_ID || $clientId >= ContainerIds::LAST) {
			return null;
		}

		return $clientId;
	}
}
