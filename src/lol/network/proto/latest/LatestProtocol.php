<?php

declare(strict_types=1);

namespace lol\network\proto\latest;

use lol\network\chunk\serializers\FormattedChunkSerializer;
use lol\network\etc\GlobalLoginPacket;
use lol\network\global\MVTypeConverter;
use lol\network\NetworkSession;
use lol\network\proto\TProtocol;
use pocketmine\crafting\CraftingManager;
use pocketmine\lang\Language;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\player\Player;
use pocketmine\Server;

class LatestProtocol extends TProtocol
{

	public const RAKNET_VERSION = 11;

	public function __construct()
	{
		parent::__construct(PacketPool::getInstance(), new FormattedChunkSerializer(), new LatestSkinHelper());
	}

	protected function createTypeConverter() : MVTypeConverter
	{
		return LatestTypeConverter::getInstance()->getConverter();
	}

	public function getRaknetVersion() : int
	{
		return self::RAKNET_VERSION;
	}

	public function getProtocolId() : int
	{
		return ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function incoming(ServerboundPacket $packet) : ?ServerboundPacket
	{
		return $packet;
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{
		if ($packet instanceof PlayerListPacket) {
			$entries = [];
			foreach ($packet->entries as $key => $entry) {
				if (!isset($entry->skinData)) {
					$entries[$key] = $entry;
					continue;
				}

				$entry = clone $entry;
				$this->adaptSkinData($entry->skinData);
				$entries[$key] = $entry;
			}
			$packet->entries = $entries;
		} elseif ($packet instanceof PlayerSkinPacket) {
			$this->adaptSkinData($packet->skin);
		}
		return $packet;
	}

	public function injectExtraData(array &$extraData) : void
	{

	}

	public function fetchPacketHandler(?PacketHandler $handler, NetworkSession $session) : ?PacketHandler
	{
		return null;
	}

	public function hasOldCompressionMethod() : bool
	{
		return false;
	}

	public function decodeConnection(string $buffer, GlobalLoginPacket $loginPacket) : void
	{
		$loginPacket->decodeConnectionRequest($buffer);
	}

	public function getCraftingManager() : CraftingManager
	{
		return Server::getInstance()->getCraftingManager();
	}

	public function assembleCommands(Server $server, Player $player, Language $lang) : ?ClientboundPacket
	{
		return null;
	}

	public function __toString() : string
	{
		return "v" . ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	}

}
