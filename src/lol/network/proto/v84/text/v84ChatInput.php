<?php

declare(strict_types=1);

namespace lol\network\proto\v84\text;

use lol\network\proto\v84\forms\v84FormBridge;
use lol\network\proto\v84\packets\v84TextPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class v84ChatInput
{

	private function __construct()
	{
	}

	public static function deliver(Player $player, v84TextPacket $packet) : void
	{
		if ($packet->type !== v84TextType::CHAT || !$player->isAlive() || !$player->isConnected()) {
			return;
		}

		v84ChatActivity::shared()->record($player);

		if (v84FormBridge::shared()->consume($player, $packet->message)) {
			return;
		}

		$player->chat(TextFormat::colorize($packet->message));
	}
}
