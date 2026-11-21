<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use Closure;
use lol\network\NetworkSession;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\handler\ResourcePacksPacketHandler;
use pocketmine\network\mcpe\handler\SpawnResponsePacketHandler;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

final class v84HandshakeBridge
{

	private const PLUGIN_NAME = "MultiVersion";

	private const DEFER_TICKS = 1;

	private const SCHEDULER_MISSING_MESSAGE = "Minecraft PE 0.15.10 handshake could not be scheduled";

	public function bridge(?PacketHandler $handler, NetworkSession $session) : void
	{
		if ($handler instanceof ResourcePacksPacketHandler) {
			$this->defer($session, $handler, static function () use ($handler) : void {
				$response = new ResourcePackClientResponsePacket();
				$response->status = ResourcePackClientResponsePacket::STATUS_COMPLETED;
				$response->packIds = [];
				$handler->handleResourcePackClientResponse($response);
			});
			return;
		}

		if ($handler instanceof SpawnResponsePacketHandler) {
			$this->defer($session, $handler, static function () use ($handler) : void {
				$handler->handleSetLocalPlayerAsInitialized(new SetLocalPlayerAsInitializedPacket());
			});
		}
	}

	private function defer(NetworkSession $session, PacketHandler $handler, Closure $action) : void
	{
		$plugin = Server::getInstance()->getPluginManager()->getPlugin(self::PLUGIN_NAME);
		if (!$plugin instanceof PluginBase) {
			$session->disconnect(self::SCHEDULER_MISSING_MESSAGE);
			return;
		}

		$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($session, $handler, $action) : void {
			if ($session->isConnected() && $session->getHandler() === $handler) {
				$action();
			}
		}), self::DEFER_TICKS);
	}
}
