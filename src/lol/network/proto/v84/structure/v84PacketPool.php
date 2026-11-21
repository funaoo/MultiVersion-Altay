<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use lol\network\etc\GlobalLoginPacket;
use lol\network\utils\ProtocolUtils;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketPool;
use function str_starts_with;

final class v84PacketPool extends PacketPool
{

	public function __construct()
	{
		parent::__construct();

		ProtocolUtils::registerPackets($this, v84ProtocolInfo::CURRENT_PROTOCOL);

		$this->registerPacket(new GlobalLoginPacket());
	}

	public function getPacketById(int $pid) : ?Packet
	{
		$packet = parent::getPacketById($pid);
		if ($packet === null) {
			return null;
		}

		return $packet instanceof GlobalLoginPacket || str_starts_with($packet::class, "lol\\network\\proto\\v84\\packets\\") ? $packet : null;
	}
}
