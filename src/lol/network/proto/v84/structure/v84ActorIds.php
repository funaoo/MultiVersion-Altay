<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use lol\network\NetworkSession;

final class v84ActorIds
{

	public const LOCAL_PLAYER = 0;

	private function __construct()
	{
	}

	public static function resolve(NetworkSession $session, int $runtimeId) : int
	{
		return $session->getPlayer()?->getId() === $runtimeId ? self::LOCAL_PLAYER : $runtimeId;
	}
}
