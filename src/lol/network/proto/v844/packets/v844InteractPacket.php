<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\legacy\LegacyInteractPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class v844InteractPacket extends LegacyInteractPacket {

	public const NETWORK_ID = ProtocolInfo::INTERACT_PACKET;

}
