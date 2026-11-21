<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\player\Player;

class v84AdventureSettingsPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::ADVENTURE_SETTINGS_PACKET;

	public const WORLD_IMMUTABLE = 0x01;

	public const NO_PVP = 0x02;

	public const NO_PVM = 0x04;

	public const NO_PVE = 0x08;

	public const AUTO_JUMP = 0x40;

	public const ALLOW_FLIGHT = 0x80;

	public const SPECTATOR = 0x100;

	private const PERMISSION_LEVEL = 2;

	public int $flags = 0;

	public int $userPermission = self::PERMISSION_LEVEL;

	public int $globalPermission = self::PERMISSION_LEVEL;

	static public function fromPlayer(Player $player) : self
	{
		$flags = self::NO_PVP | self::NO_PVM | self::NO_PVE;
		$flags |= $player->isAdventure() ? self::WORLD_IMMUTABLE : 0;
		$flags |= $player->hasAutoJump() ? self::AUTO_JUMP : 0;
		$flags |= $player->getAllowFlight() ? self::ALLOW_FLIGHT : 0;
		$flags |= $player->isSpectator() ? self::SPECTATOR : 0;

		$npk = new self();
		$npk->flags = $flags;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->flags = BE::readSignedInt($in);
		$this->userPermission = BE::readSignedInt($in);
		$this->globalPermission = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->flags);
		BE::writeSignedInt($out, $this->userPermission);
		BE::writeSignedInt($out, $this->globalPermission);
	}
}
