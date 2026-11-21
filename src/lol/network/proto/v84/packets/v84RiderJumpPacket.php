<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84IgnoredServerboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;

class v84RiderJumpPacket extends v84IgnoredServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::RIDER_JUMP_PACKET;
}
