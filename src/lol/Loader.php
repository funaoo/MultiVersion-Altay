<?php

declare(strict_types=1);

namespace lol;

use altay\network\nethernet\NetherNetTransport;
use libs\muqsit\invmenu\InvMenuHandler;
use lol\network\async\MVChunkRequestTask;
use lol\command\defaults\CheckPermCommand;
use lol\command\defaults\ListPermsCommand;
use lol\network\diagnostic\BadPacketReportWriter;
use lol\network\etc\GlobalLoginPacket;
use lol\network\NetworkSession;
use lol\network\proto\v844\structure\v844Debug;
use lol\network\proto\v84\scoreboard\v84ScoreboardSettings;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\proto\v84\structure\v84StartupCheck;
use lol\network\proto\v84\tiles\v84ChestPairSync;
use lol\network\transport\MVNetherNetSettings;
use lol\network\transport\MVRakNetTransportFactory;
use lol\network\transport\MVTransportNetworkInterface;
use lol\network\utils\ProtocolCommand;
use lol\network\utils\WorldNetworkVersionFixer;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\StandardEntityEventBroadcaster;
use pocketmine\network\mcpe\StandardPacketBroadcaster;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\transport\ThreadedTransport;
use pocketmine\network\mcpe\transport\TransportFactory;
use pocketmine\network\mcpe\transport\TransportNetworkInterface;
use pocketmine\network\query\DedicatedQueryNetworkInterface;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Filesystem;
use Symfony\Component\Filesystem\Path;
use function count;
use function dirname;
use function is_array;
use function max;
use function mt_rand;
use const PHP_INT_MAX;

final class Loader extends PluginBase {

	private array $networkInterfaces = [];

	private bool $rakNetEnabled = true;

	public static function getPluginResourcePath() : string {
		return dirname(__DIR__, 2) . "/resources";
	}

	private static ?string $defaultSkinFormat = null;

	public static function getDefaultSkinFormat() : string {
		return self::$defaultSkinFormat ??= Filesystem::fileGetContents(
			Path::join(self::getPluginResourcePath(), "default_skin_geometry.json")
		);
	}

	protected function onLoad() : void {
		$this->saveDefaultConfig();

		$config = $this->getConfig();
		if(!$config->exists("debug")){
			$config->set("debug", false);
			$config->save();
		}
		v844Debug::setEnabled((bool) $config->get("debug", false));

		if(!$config->exists("bad-packet-diagnostics")){
			$config->set("bad-packet-diagnostics", true);
			$config->save();
		}
		BadPacketReportWriter::setEnabled((bool) $config->get("bad-packet-diagnostics", true));

		$missing = false;
		foreach ([
			"disabled-protocols" => [],
			"allowed-protocols" => [],
			"preload-on-startup" => true,
			"preload-caches" => true,
			"ice-servers" => ["stun:stun.l.google.com:19302", "stun:stun1.l.google.com:19302", "stun:stun.cloudflare.com:3478"],
			"ice-username" => "",
			"ice-password" => "",
			"ice-relay-only" => false,
			"scoreboard-bridge" => true,
			"scoreboard-refresh-ticks" => 10,
			"scoreboard-max-lines" => 12,
			"scoreboard-channel" => "tip",
			"scoreboard-show-numbers" => false
		] as $key => $default) {
			if (!$config->exists($key)) {
				$config->set($key, $default);
				$missing = true;
			}
		}
		if ($missing) {
			$config->save();
		}

		$disabled = $config->get("disabled-protocols", []);
		$allowed = $config->get("allowed-protocols", []);
		MCProtocols::configure(is_array($disabled) ? $disabled : [], is_array($allowed) ? $allowed : [], $this->getLogger());

		v84ScoreboardSettings::configure(
			(bool) $config->get("scoreboard-bridge", true),
			(int) $config->get("scoreboard-refresh-ticks", 10),
			(int) $config->get("scoreboard-max-lines", 12),
			(string) $config->get("scoreboard-channel", "tip"),
			(bool) $config->get("scoreboard-show-numbers", false)
		);

		if ($this->getConfig()->get("auto-fix-world-network-version", true)) {
			WorldNetworkVersionFixer::fixAll(
				Path::join($this->getServer()->getDataPath(), "worlds"),
				$this->getLogger()
			);
		}
	}

	protected function onEnable() : void {
		if(\Phar::running() === "") {
			$this->getLogger()->warning("MultiVersion was loaded without being a phar");
			$this->getLogger()->warning("Load startup will take longer!");
		}

		$server = $this->getServer();

		v84ChestPairSync::register($this);

		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}

		$server->getPluginManager()->registerEvent(
			NetworkInterfaceRegisterEvent::class,
			$this->handleNetworkInterfaceRegister(...),
			EventPriority::NORMAL,
			$this
		);

		if($this->getConfig()->get("enable-packet-warning", true)){
			$this->registerDataPacketEvent();
		}

		MVChunkRequestTask::$verifyPayloads = (bool) $this->getConfig()->get("verify-chunk-payloads", false);
		if(MVChunkRequestTask::$verifyPayloads){
			$server->getLogger()->notice("[MultiVersion] verify-chunk-payloads is enabled: every chunk will be re-parsed before sending. Turn it off for production.");
		}

		if(MCProtocols::isEnabled(v84ProtocolInfo::CURRENT_PROTOCOL) && !v84StartupCheck::run($server->getLogger())){
			MCProtocols::disable(v84ProtocolInfo::CURRENT_PROTOCOL);
		}

		$this->registerTransportInterface($server);

		$server->getCommandMap()->register("multiversion", new ProtocolCommand());
		$server->getCommandMap()->register("multiversion", new CheckPermCommand());
		$server->getCommandMap()->register("multiversion", new ListPermsCommand());

		PacketPool::getInstance()->registerPacket(new GlobalLoginPacket());

		$protocols = MCProtocols::preload();
		$logger = $server->getLogger();
		$logger->notice("[!] This server runs a total of " . count($protocols) . " protocols:");
		foreach ($protocols as $protocol) {
			$logger->notice("- " . $protocol . " (" . $protocol->getProtocolId() . ")");
		}

		$disabledProtocols = MCProtocols::getDisabledProtocols();
		if ($disabledProtocols !== []) {
			$logger->notice("");
			$logger->notice("[!] A total of " . count($disabledProtocols) . " protocols are disabled:");
			foreach ($disabledProtocols as $protocol) {
				$logger->notice("- " . MCProtocols::getVersionLabel($protocol) . " (" . $protocol . ")");
			}
		}

		if ($this->getConfig()->get("preload-on-startup", true)) {
			MCProtocols::warmupConverters($logger);

			if ($this->getConfig()->get("preload-caches", true)) {
				$this->getScheduler()->scheduleTask(new ClosureTask(static function () use ($logger) : void {
					MCProtocols::warmupCaches($logger);
				}));
			}
		}
	}

	protected function onDisable() : void {
		$network = $this->getServer()->getNetwork();
		$interfaces = $this->networkInterfaces;
		$this->networkInterfaces = [];

		foreach ($interfaces as $interface) {
			try {
				$network->unregisterInterface($interface);
			} catch (\InvalidArgumentException $e) {
			}
		}
	}

	private function registerDataPacketEvent() : void {
		$this->getServer()->getPluginManager()->registerEvent(
			DataPacketReceiveEvent::class,
			$this->handleDataPacketReceive(...),
			EventPriority::MONITOR,
			$this
		);
	}

	private function registerTransportInterface(Server $server) : void {
		$config = $this->getConfig();
		$this->rakNetEnabled = (bool) $config->get("enable-raknet", true);
		$netherNetEnabled = (bool) $config->get("enable-nethernet", true);
		if (!$this->rakNetEnabled && !$netherNetEnabled) {
			$server->getLogger()->warning("[MultiVersion] enable-raknet and enable-nethernet are both off, nobody will be able to join");
			return;
		}

		$configGroup = $server->getConfigGroup();
		$bindAddress = MVNetherNetSettings::getBindAddress($server);
		$typeConverter = TypeConverter::getInstance();
		$packetBroadcaster = new StandardPacketBroadcaster($server);
		$entityEventBroadcaster = new StandardEntityEventBroadcaster($packetBroadcaster, $typeConverter);

		if ($netherNetEnabled) {
			$this->registerInterface($server, MVNetherNetSettings::createFactory($server, $config), $packetBroadcaster, $entityEventBroadcaster, $typeConverter, 0);
			$server->getLogger()->info("[MultiVersion] NetherNet interface running on " . $bindAddress . ":" . NetherNetTransport::DISCOVERY_PORT);
		}

		if ($this->rakNetEnabled) {
			$serverId = mt_rand(0, PHP_INT_MAX);
			$this->registerInterface($server, new MVRakNetTransportFactory(
				$bindAddress,
				$server->getPort(),
				false,
				$configGroup->getPropertyInt('network.max-mtu-size', 1492),
				$serverId,
				MCProtocols::getRaknetProtocolVersions(),
				max(1, $configGroup->getPropertyInt('network.max-split-packet-parts', 192)),
				max(1, $configGroup->getPropertyInt('network.max-concurrent-split-packets', 4))
			), $packetBroadcaster, $entityEventBroadcaster, $typeConverter, $serverId);
			$server->getLogger()->info("[MultiVersion] RakNet interface running on " . $bindAddress . ":" . $server->getPort());
		}
	}

	private function registerInterface(Server $server, TransportFactory $factory, PacketBroadcaster $packetBroadcaster, EntityEventBroadcaster $entityEventBroadcaster, TypeConverter $typeConverter, int $serverId) : void {
		$interface = new MVTransportNetworkInterface(
			$server,
			new ThreadedTransport($server->getLogger(), $factory, $server->getTickSleeper()),
			$packetBroadcaster,
			$entityEventBroadcaster,
			$typeConverter,
			$serverId
		);

		if ($server->getNetwork()->registerInterface($interface)) {
			$this->networkInterfaces[] = $interface;
		}
	}

	protected function handleNetworkInterfaceRegister(NetworkInterfaceRegisterEvent $event) : void {
		$interface = $event->getInterface();

		if ($interface instanceof MVTransportNetworkInterface) {
			return;
		}

		if ($interface instanceof TransportNetworkInterface || ($this->rakNetEnabled && $interface instanceof DedicatedQueryNetworkInterface)) {
			$event->cancel();
		}
	}

	protected function handleDataPacketReceive(DataPacketReceiveEvent $event) : void {
		$pk = $event->getPacket();

		if ($pk instanceof PacketViolationWarningPacket) {
			$origin = $event->getOrigin();
			$pid = $pk->getPacketId();
			$protocol = $origin->safeProtocol();

			if ($protocol === null) {
				$this->getLogger()->debug("Received waring packet before initialization of the protocol from {$origin->getIp()}:{$origin->getPort()}");
				$this->getLogger()->debug("Packet violation: pid=$pid");
				return;
			}

			$source = $protocol->getPacketPool()->getPacketById($pid);
			$this->getLogger()->warning(
				"Packet Violation from {$origin->getIp()}:{$origin->getPort()} on protocol {$protocol->getProtocolId()}: pid={$pid} packet={$source?->getName()}"
				. " type={$pk->getType()} severity={$pk->getSeverity()} message={$pk->getMessage()}"
			);
		}
	}

}