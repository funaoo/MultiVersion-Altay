<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\legacy\LegacyResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class v844ResourcePackClientResponsePacket extends LegacyResourcePackClientResponsePacket {

	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET;
}
