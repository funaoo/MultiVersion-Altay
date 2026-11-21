<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use lol\network\etc\GlobalLoginPacket;
use lol\network\utils\ProtocolUtils;
use pocketmine\network\mcpe\protocol\PacketPool;

class v844PacketPool extends PacketPool
{

	public function __construct()
	{
		parent::__construct();

		ProtocolUtils::registerPackets($this, v844ProtocolInfo::CURRENT_PROTOCOL);

		$this->registerPacket(new GlobalLoginPacket());

	}
}
