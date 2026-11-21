<?php

declare(strict_types=1);

namespace lol\network\proto\v84\scoreboard;

use lol\network\NetworkSession;
use lol\network\proto\v84\packets\outgoing\v84OutgoingTextPacket;
use lol\network\proto\v84\text\v84TextType;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use WeakMap;

final class v84TitleBridge
{

	private WeakMap $subtitles;

	public function __construct()
	{
		$this->subtitles = new WeakMap();
	}

	public function handle(NetworkSession $session, SetTitlePacket $packet) : ?v84OutgoingTextPacket
	{
		return match ($packet->type) {
			SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE => v84OutgoingTextPacket::fromParts(v84TextType::POPUP, $packet->text, ''),
			SetTitlePacket::TYPE_SET_SUBTITLE => $this->hold($session, $packet->text),
			SetTitlePacket::TYPE_SET_TITLE => $this->show($session, $packet->text),
			SetTitlePacket::TYPE_CLEAR_TITLE, SetTitlePacket::TYPE_RESET_TITLE => $this->clear($session),
			default => null
		};
	}

	private function hold(NetworkSession $session, string $subtitle) : ?v84OutgoingTextPacket
	{
		$this->subtitles[$session] = $subtitle;

		return null;
	}

	private function show(NetworkSession $session, string $title) : v84OutgoingTextPacket
	{
		$subtitle = $this->subtitles[$session] ?? '';
		unset($this->subtitles[$session]);

		return v84OutgoingTextPacket::fromParts(v84TextType::POPUP, $title, $subtitle);
	}

	private function clear(NetworkSession $session) : v84OutgoingTextPacket
	{
		unset($this->subtitles[$session]);

		return v84OutgoingTextPacket::fromParts(v84TextType::POPUP, '', '');
	}
}
