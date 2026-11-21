<?php

declare(strict_types=1);

namespace lol\network\proto\v84\text;

use lol\network\proto\v84\packets\outgoing\v84OutgoingTextPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use function array_values;

final class v84TextTranslator
{

	private function __construct()
	{
	}

	public static function channel(int $type, string $text) : v84OutgoingTextPacket
	{
		return $type === v84TextType::POPUP
			? v84OutgoingTextPacket::fromParts(v84TextType::POPUP, $text, '')
			: v84OutgoingTextPacket::fromParts(v84TextType::TIP, '', $text);
	}

	public static function translate(TextPacket $packet) : ?v84OutgoingTextPacket
	{
		return match ($packet->type) {
			TextPacket::TYPE_RAW => self::plain(v84TextType::RAW, $packet),
			TextPacket::TYPE_TIP => self::plain(v84TextType::TIP, $packet),
			TextPacket::TYPE_SYSTEM => self::plain(v84TextType::SYSTEM, $packet),
			TextPacket::TYPE_CHAT, TextPacket::TYPE_WHISPER, TextPacket::TYPE_ANNOUNCEMENT => v84OutgoingTextPacket::fromParts(v84TextType::CHAT, $packet->sourceName, v84TextFormat::normalize($packet->message)),
			TextPacket::TYPE_TRANSLATION => self::translation($packet),
			TextPacket::TYPE_POPUP => self::popup($packet),
			default => null
		};
	}

	private static function plain(int $type, TextPacket $packet) : v84OutgoingTextPacket
	{
		return v84OutgoingTextPacket::fromParts($type, '', v84TextFormat::normalize($packet->message));
	}

	private static function translation(TextPacket $packet) : v84OutgoingTextPacket
	{
		return v84OutgoingTextPacket::fromParts(v84TextType::TRANSLATION, '', $packet->message, array_values($packet->parameters));
	}

	private static function popup(TextPacket $packet) : ?v84OutgoingTextPacket
	{
		if ($packet->needsTranslation) {
			return null;
		}

		return v84OutgoingTextPacket::fromParts(v84TextType::POPUP, $packet->message, '');
	}
}
