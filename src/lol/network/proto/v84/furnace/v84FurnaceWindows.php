<?php

declare(strict_types=1);

namespace lol\network\proto\v84\furnace;

use lol\network\proto\v84\packets\outgoing\v84OutgoingContainerSetDataPacket;
use lol\network\proto\v84\windows\v84WindowIds;
use lol\network\proto\v84\windows\v84WindowType;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use WeakMap;

final class v84FurnaceWindows
{

	private const FIRST_SLOT = 0;

	private readonly WeakMap $sessions;

	public function __construct()
	{
		$this->sessions = new WeakMap();
	}

	public function reset(NetworkSession $session, int $networkId) : void
	{
		$windows = $this->sessions[$session] ?? [];
		unset($windows[$networkId]);
		$this->sessions[$session] = $windows;
	}

	public function data(NetworkSession $session, ContainerSetDataPacket $packet) : ?v84OutgoingContainerSetDataPacket
	{
		$clientId = v84WindowIds::toClient($packet->windowId);
		$located = $session->getInvManager()?->locateWindowAndSlot($packet->windowId, self::FIRST_SLOT);
		if ($clientId === null || $located === null || v84WindowType::fromInventory($located[0]) !== v84WindowType::FURNACE) {
			return null;
		}

		$reading = $this->progress($session, $packet->windowId)->translate($packet->property, $packet->value);

		return $reading === null ? null : v84OutgoingContainerSetDataPacket::fromParts($clientId, $reading->property, $reading->value);
	}

	private function progress(NetworkSession $session, int $networkId) : v84FurnaceProgress
	{
		$windows = $this->sessions[$session] ?? [];
		if (!isset($windows[$networkId])) {
			$windows[$networkId] = new v84FurnaceProgress();
			$this->sessions[$session] = $windows;
		}

		return $windows[$networkId];
	}
}
