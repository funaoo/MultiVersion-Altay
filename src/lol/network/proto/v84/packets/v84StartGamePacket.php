<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84CommonTypes;
use lol\network\proto\v84\structure\v84GameModeTranslator;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\proto\v84\structure\v84WorldLimits;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\StartGamePacket;

class v84StartGamePacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::START_GAME_PACKET;

	private const UNKNOWN_SEED = -1;

	private const INFINITE_GENERATOR = 1;

	private const RESERVED_FLAGS = [1, 1, 0];

	public int $seed = self::UNKNOWN_SEED;

	public int $dimension = v84WorldLimits::DIMENSION_OVERWORLD;

	public int $generator = self::INFINITE_GENERATOR;

	public int $gameMode = v84GameModeTranslator::SURVIVAL;

	public int $actorId = 0;

	public int $spawnX = 0;

	public int $spawnY = 0;

	public int $spawnZ = 0;

	public float $x = 0.0;

	public float $y = 0.0;

	public float $z = 0.0;

	static public function fromLatest(StartGamePacket $packet, int $actorId) : self
	{
		$settings = $packet->levelSettings;

		$npk = new self();
		$npk->dimension = v84WorldLimits::dimension($settings->spawnSettings->getDimension());
		$npk->gameMode = v84GameModeTranslator::fromProtocol($packet->playerGamemode);
		$npk->actorId = $actorId;
		$npk->spawnX = $settings->spawnPosition->getX();
		$npk->spawnY = v84WorldLimits::clampBlockY($settings->spawnPosition->getY());
		$npk->spawnZ = $settings->spawnPosition->getZ();
		$npk->x = $packet->playerPosition->x;
		$npk->y = v84WorldLimits::clampY($packet->playerPosition->y);
		$npk->z = $packet->playerPosition->z;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->seed = BE::readSignedInt($in);
		$this->dimension = Byte::readUnsigned($in);
		$this->generator = BE::readSignedInt($in);
		$this->gameMode = BE::readSignedInt($in);
		$this->actorId = BE::readSignedLong($in);
		$this->spawnX = BE::readSignedInt($in);
		$this->spawnY = BE::readSignedInt($in);
		$this->spawnZ = BE::readSignedInt($in);
		$this->x = BE::readFloat($in);
		$this->y = BE::readFloat($in);
		$this->z = BE::readFloat($in);
		foreach (self::RESERVED_FLAGS as $ignored) {
			Byte::readUnsigned($in);
		}
		v84CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->seed);
		Byte::writeUnsigned($out, $this->dimension);
		BE::writeSignedInt($out, $this->generator);
		BE::writeSignedInt($out, $this->gameMode);
		BE::writeSignedLong($out, $this->actorId);
		BE::writeSignedInt($out, $this->spawnX);
		BE::writeSignedInt($out, $this->spawnY);
		BE::writeSignedInt($out, $this->spawnZ);
		BE::writeFloat($out, $this->x);
		BE::writeFloat($out, $this->y);
		BE::writeFloat($out, $this->z);
		foreach (self::RESERVED_FLAGS as $flag) {
			Byte::writeUnsigned($out, $flag);
		}
		v84CommonTypes::putString($out, "");
	}
}
