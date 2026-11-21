<?php

declare(strict_types=1);

namespace lol\network\proto\v84\sound;

use Closure;
use lol\network\proto\v84\blocks\v84LegacyBlock;
use lol\network\proto\v84\packets\outgoing\v84OutgoingLevelEventPacket;
use lol\network\proto\v84\packets\v84BlockEventPacket;
use lol\network\proto\v84\structure\v84WorldLimits;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use function floor;

final class v84SoundEventTranslator
{

	private const NOTE_INSTRUMENT_SHIFT = 8;

	private const NOTE_VALUE_MASK = 0xff;

	private const NO_DATA = 0;

	private const NO_BLOCK = -1;

	private function __construct()
	{
	}

	public static function translate(LevelSoundEventPacket $packet, Closure $packedBlockFromRuntimeId) : ?ClientboundPacket
	{
		return match ($packet->sound) {
			LevelSoundEvent::BOW, LevelSoundEvent::THROW => self::levelEvent($packet, v84SoundId::SHOOT, self::NO_DATA),
			LevelSoundEvent::PLACE => self::blockPlace($packet, $packedBlockFromRuntimeId),
			LevelSoundEvent::NOTE => self::note($packet),
			default => null
		};
	}

	private static function blockPlace(LevelSoundEventPacket $packet, Closure $packedBlockFromRuntimeId) : ?v84OutgoingLevelEventPacket
	{
		if ($packet->extraData === self::NO_BLOCK) {
			return null;
		}

		$blockId = v84LegacyBlock::id($packedBlockFromRuntimeId($packet->extraData));
		if ($blockId === v84LegacyBlock::AIR) {
			return null;
		}

		return self::levelEvent($packet, v84SoundId::BLOCK_PLACE, $blockId);
	}

	private static function note(LevelSoundEventPacket $packet) : ?v84BlockEventPacket
	{
		$instrument = v84NoteInstrument::fromModern($packet->extraData >> self::NOTE_INSTRUMENT_SHIFT);
		$note = $packet->extraData & self::NOTE_VALUE_MASK;
		$y = (int) floor($packet->position->getY());
		if ($instrument === null || $note > v84NoteInstrument::NOTE_MAX || !v84WorldLimits::isBlockY($y)) {
			return null;
		}

		return v84BlockEventPacket::fromParts((int) floor($packet->position->getX()), $y, (int) floor($packet->position->getZ()), $instrument, $note);
	}

	private static function levelEvent(LevelSoundEventPacket $packet, int $soundId, int $data) : v84OutgoingLevelEventPacket
	{
		return v84OutgoingLevelEventPacket::fromParts($soundId, $packet->position->getX(), $packet->position->getY(), $packet->position->getZ(), $data);
	}
}
