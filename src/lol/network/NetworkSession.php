<?php

declare(strict_types=1);

namespace lol\network;

use lol\MCProtocols;
use lol\network\anvil\MVAnvilRequestInterceptor;
use lol\network\chunk\MVChunkCache;
use lol\network\diagnostic\BadPacketContext;
use lol\network\etc\GlobalLoginPacket;
use lol\network\etc\MVBatch;
use lol\network\global\MVLoginPacketHandler;
use lol\network\inventory\MVWindowCloseGuard;
use lol\network\proto\TProtocol;
use lol\network\proto\v84\framing\v84LoginSniffer;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\transport\MVTransportPacketSender;
use lol\network\utils\ReflectionUtils;
use lol\network\utils\TranslationGuard;
use Closure;
use InvalidArgumentException;
use Throwable;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\network\FilterNoisyPacketException;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\handler\PacketHandlerAction;
use pocketmine\network\mcpe\NetworkSession as BaseNetworkSession;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\PacketSender;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\network\mcpe\protocol\types\DisconnectReason;
use pocketmine\network\NetworkSessionManager;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\PlayerInfo;
use pocketmine\player\UsedChunkStatus;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\ObjectSet;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use pocketmine\world\World;
use ReflectionException;
use function base64_encode;
use function bin2hex;
use function count;
use function debug_backtrace;
use function get_class;
use function implode;
use function in_array;
use function is_string;
use function ord;
use function random_bytes;
use function str_split;
use function strlen;
use function strrchr;
use function substr;
use function time;

class NetworkSession extends BaseNetworkSession {

	protected ?TProtocol $protocol = null;

	private bool $enableCompression = true;

	public function getProtocolId() : ?int
	{
		return $this->protocol?->getProtocolId();
	}

	private bool $isFirstPacket = true;

	private bool $rakNetTransport = true;

	private ?BadPacketContext $badPacketContext = null;

	private readonly MVWindowCloseGuard $windowCloseGuard;

	public function __construct(Server $server, NetworkSessionManager $manager, PacketPool $packetPool, PacketSender $sender, PacketBroadcaster $broadcaster, EntityEventBroadcaster $entityEventBroadcaster, Compressor $compressor, TypeConverter $typeConverter, string $ip, int $port, ?string $expectedIdentityPublicKey = null)
	{
		$this->windowCloseGuard = new MVWindowCloseGuard();
		parent::__construct($server, $manager, $packetPool, $sender, $broadcaster, $entityEventBroadcaster, $compressor, $typeConverter, $ip, $port, $expectedIdentityPublicKey);
		$this->rakNetTransport = !($sender instanceof MVTransportPacketSender) || $sender->usesRakNetFraming();
		$this->setHandler(new MVLoginPacketHandler(
			$server,
			$this,
			function (PlayerInfo $info) : void {
				ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "info", $info);
				$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
				$this->getLogger()->setPrefix("NetworkSession: " . $this->getDisplayName());
				ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "manager")->markLoginReceived($this);
			},
			function (bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void {
				ReflectionUtils::invoke(BaseNetworkSession::class, $this, "setAuthenticationStatus", $authenticated, $authRequired, $error, $clientPubKey);
			},
			$this->onSessionStartSuccess(...)
		));
	}

	public function setHandler(?PacketHandler $handler) : void
	{
		$newHandler = $handler;
		if (isset($this->protocol)) {
			$newHandler = $this->protocol->fetchPacketHandler($handler, $this) ?? $handler;
		}
		parent::setHandler($newHandler);
		try {
			$actions = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "handlerActions");
			if($actions !== null) {
				$actions[GlobalLoginPacket::class] = PacketHandlerAction::HANDLED;
			}
			ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "handlerActions", $actions);
		}catch (ReflectionException $exception){

		}

	}

	private function adoptProtocolFromLoginSignature(string $payload) : void
	{
		$protocolId = v84ProtocolInfo::CURRENT_PROTOCOL;
		if ($this->protocol !== null || !$this->isFirstPacket || !v84LoginSniffer::matches($payload) || !MCProtocols::isSupported($protocolId)) {
			return;
		}

		if (!$this->rakNetTransport) {
			$this->disconnect("Minecraft PE 0.15.10 clients can only connect over RakNet");
			return;
		}

		$this->setProtocol(MCProtocols::getProtocolInstance($protocolId));
	}

	public function handleEncoded(string $payload) : void
	{
		$this->adoptProtocolFromLoginSignature($payload);

		if (!ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connected")) {
			return;
		}

		$context = new BadPacketContext();
		$context->stage = "decompress";
		$context->protocolId = $this->protocol?->getProtocolId();
		$context->wrapperClass = $this->protocol !== null ? $this->protocol::class : null;
		$context->payload = $payload;
		$this->badPacketContext = $context;

		Timings::$playerNetworkReceive->startTiming();
		try {
			if (strlen($payload) < 1) {
				throw new PacketHandlingException("No bytes in payload");
			}

			$frameCodec = $this->protocol?->getFrameCodec();

			if ($frameCodec !== null || !$this->enableCompression) {
				$decompressed = $payload;
			} else {
				Timings::$playerNetworkReceiveDecompress->startTiming();
				if (!$this->isFirstPacket && !$this->protocol->hasOldCompressionMethod()) {
					$compressionType = ord($payload[0]);
					$compressed = substr($payload, 1);
					if ($compressionType === CompressionAlgorithm::NONE) {
						$decompressed = $compressed;
					} elseif ($compressionType === $this->getCompressor()->getNetworkId()) {
						try {
							$decompressed = $this->getCompressor()->decompress($compressed);
						} catch (DecompressionException $e) {
							$this->getLogger()->debug("Failed to decompress packet: " . base64_encode($compressed));
							throw PacketHandlingException::wrap($e, "Compressed packet batch decode error");
						} finally {
							Timings::$playerNetworkReceiveDecompress->stopTiming();
						}
					} else {
						throw new PacketHandlingException("Packet compressed with unexpected compression type $compressionType");
					}
				} else {
					try {
						$decompressed = $this->getCompressor()->decompress($payload);
					} catch (DecompressionException $e) {
						if (!$this->isFirstPacket) {
							$this->getLogger()->debug("Failed to decompress packet: " . base64_encode($payload));
							throw PacketHandlingException::wrap($e, "Compressed packet batch decode error");
						} else {
							$this->getLogger()->debug("Failed to decompress packet: " . base64_encode($payload));

							$this->enableCompression = false;
							$this->setHandler(new MVLoginPacketHandler(
								Server::getInstance(),
								$this,
								function (PlayerInfo $info) : void {
									ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "info", $info);
									$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
									$this->getLogger()->setPrefix("NetworkSession: " . $this->getDisplayName());
									ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "manager")->markLoginReceived($this);
								},
								function (bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void {
									ReflectionUtils::invoke(BaseNetworkSession::class, $this, "setAuthenticationStatus", $authenticated, $authRequired, $error, $clientPubKey);
								},
								$this->onSessionStartSuccess(...)
							));

							$decompressed = $payload;
						}
					} finally {
						Timings::$playerNetworkReceiveDecompress->stopTiming();
					}
				}
			}

			$context->stage = "batch_decode";
			$context->decompressedBatch = $decompressed;

			$count = 0;
			try {
				$stream = new ByteBufferReader($decompressed);
				foreach ($frameCodec?->unwrap($payload) ?? PacketBatch::decodeRaw($stream) as $buffer) {
					if(++$count >= 300){
						throw new PacketHandlingException("Reached hard limit of " . 300 . " per batch packet");
					}
					$context->stage = "packet_lookup";
					$context->currentPacketBuffer = $buffer;
					$packet = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "packetPool")->getPacket($buffer);
					if ($packet === null) {
						if ($this->protocol?->getProtocolId() === v84ProtocolInfo::CURRENT_PROTOCOL) {
							$this->getLogger()->debug("Ignoring unsupported packet from 0.15.10 client: " . base64_encode($buffer));
							continue;
						}
						$this->getLogger()->debug("Unknown packet: " . base64_encode($buffer));
						$context->packetId = VarInt::unpackUnsignedInt($buffer) & DataPacket::PID_MASK;
						throw new PacketHandlingException("Unknown packet received: PID: " . $context->packetId);
					}
					if (!($packet instanceof ServerboundPacket) && $this->protocol?->getProtocolId() === v84ProtocolInfo::CURRENT_PROTOCOL) {
						$this->getLogger()->debug("Ignoring clientbound packet " . $packet->getName() . " sent by 0.15.10 client: " . base64_encode($buffer));
						continue;
					}
					$context->packetId = $packet->pid();
					$context->packetName = $packet->getName();
					$context->stage = "packet_decode";

					try {
						$this->handleDataPacket($packet, $buffer);
					} catch (PacketHandlingException $e) {
						$this->getLogger()->debug($packet->getName() . ": " . base64_encode($buffer));
						throw PacketHandlingException::wrap($e, "Error processing " . $packet->getName());
					}

				}
			} catch (PacketDecodeException|BinaryDataException $e) {
				$this->getLogger()->logException($e);
				$this->captureBufferState(isset($stream) ? $stream : null);
				throw PacketHandlingException::wrap($e, "Packet batch decode error");
			} finally {
				$this->isFirstPacket = false;
			}
		} finally {
			Timings::$playerNetworkReceive->stopTiming();
		}
	}

	public function consumeBadPacketContext() : ?BadPacketContext {
		$context = $this->badPacketContext;
		$this->badPacketContext = null;
		return $context;
	}

	private function captureBufferState(?ByteBufferReader $stream) : void {
		$context = $this->badPacketContext;
		if ($context === null || $stream === null) {
			return;
		}

		try {
			$context->bufferOffset = $stream->getOffset();
			$context->bufferUnreadLength = $stream->getUnreadLength();
			if ($context->bufferUnreadLength > 0) {
				$context->remainingBytes = bin2hex(substr($stream->getData(), $context->bufferOffset));
			}
		} catch (Throwable $ignored) {

		}
	}

	public function handleDataPacket(Packet $packet, string $buffer) : void{
		if(!($packet instanceof ServerboundPacket)){
			throw new PacketHandlingException("Unexpected non-serverbound packet");
		}

		$timings = Timings::getReceiveDataPacketTimings($packet);
		$timings->startTiming();

		try{

			if(DataPacketDecodeEvent::hasHandlers()){
				$ev = new DataPacketDecodeEvent($this, $packet->pid(), $buffer);
				$ev->call();
				if($ev->isCancelled()){
					return;
				}
			}

			$decodeTimings = Timings::getDecodeDataPacketTimings($packet);
			$decodeTimings->startTiming();
			try{
				$stream = new ByteBufferReader($buffer);
				try{
					$packet->decode($stream);
				}catch(PacketDecodeException $e){
					$this->captureBufferState($stream);
					throw PacketHandlingException::wrap($e);
				}
				if($stream->getUnreadLength() > 0){
					$remains = substr($stream->getData(), $stream->getOffset());
					$this->getLogger()->debug("Still " . strlen($remains) . " bytes unread in " . $packet->getName() . ": " . bin2hex($remains));
				}
			}finally{
				$decodeTimings->stopTiming();
			}

			$this->traceIncoming($packet);

			if(isset($this->protocol)){
				$pk = $this->protocol->incoming(clone $packet);

				if($pk === null){
					$this->protocol->getLogger()->debug("Ignoring packet {$packet->getName()}");
					return;
				}

				$packet = $pk;
			}

			if(DataPacketReceiveEvent::hasHandlers()){
				$ev = new DataPacketReceiveEvent($this, $packet);
				$ev->call();
				if($ev->isCancelled()){
					return;
				}
			}
			$handlerTimings = Timings::getHandleDataPacketTimings($packet);
			$handlerTimings->startTiming();
			$this->windowCloseGuard->begin($packet);
			try{
				if($packet instanceof NetworkStackLatencyPacket){
					if($packet->needResponse){
						$this->sendDataPacket(NetworkStackLatencyPacket::response($packet->timestamp));
					}
					$handled = true;
				}elseif($packet instanceof ItemStackRequestPacket){
					$intercept = MVAnvilRequestInterceptor::shouldIntercept($this, $packet);
					if($intercept){
						MVAnvilRequestInterceptor::intercept($this, $packet);
						$handled = true;
					}else{
						$currentHandler = $this->getHandler();
						$handled = $currentHandler !== null && $packet->handle($currentHandler);
					}
				}else{
					$currentHandler = $this->getHandler();
					$handled = $currentHandler !== null && $packet->handle($currentHandler);
				}
				if(!$handled){
					$this->getLogger()->debug("Handler Rejected: {$packet->getName()}: " . base64_encode($buffer));
				}
			}catch(FilterNoisyPacketException $exception){

			}finally{
				$this->windowCloseGuard->end();
				$handlerTimings->stopTiming();
			}
		}finally{
			$timings->stopTiming();
		}
	}

	public function syncAvailableCommands() : void {
		Utils::assumeNotFalse(isset($this->protocol), "Protocol should be already provided");
		$player = $this->getPlayer();
		$packet = $this->protocol->assembleCommands(
			$player->getServer(),
			$player,
			$player->getLanguage()
		);

		if ($packet !== null) {
			$this->addToSendBuffer(self::encodePacketTimed(new ByteBufferWriter(), $packet));
			return;
		}

		parent::syncAvailableCommands();
	}

	public function addToSendBuffer(string $buffer) : void {
		parent::addToSendBuffer($buffer);
	}

	public function tick() : void
	{
		if (!ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connected")) {
			ReflectionUtils::invoke(BaseNetworkSession::class, $this, "dispose");
			return;
		}

		if (ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "info") === null) {
			if (time() >= ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connectTime") + 10) {
				$this->disconnectWithError(KnownTranslationFactory::pocketmine_disconnect_error_loginTimeout());
			}

			return;
		}

		$player = $this->getPlayer();
		if ($player !== null) {
			$player->doChunkRequests();

			$dirtyAttributes = $player->getAttributeMap()->needSend();
			$this->getEntityEventBroadcaster()->syncAttributes([$this], $player, $dirtyAttributes);
			foreach ($dirtyAttributes as $attribute) {
				$attribute->markSynchronized();
			}
		}

		$periodic = $this->protocol?->tickSession($this);
		if ($periodic !== null) {
			$this->addToSendBuffer(self::encodePacketTimed(new ByteBufferWriter(), $periodic));
		}
		Timings::$playerNetworkSendInventorySync->startTiming();
		try {
			$this->getInvManager()?->flushPendingUpdates();
		} finally {
			Timings::$playerNetworkSendInventorySync->stopTiming();
		}

		$this->flushGamePacketQueue();
	}

	public static function generateErrorId() : string {
		return implode("-", str_split(bin2hex(random_bytes(6)), 4));
	}

	public function disconnectWithError(Translatable|string $reason, Translatable|string|null $disconnectScreenMessage = null, ?string $errorId = null) : void
	{
		$errorId ??= self::generateErrorId();

		$this->disconnect(
			reason: KnownTranslationFactory::pocketmine_disconnect_error($reason, $errorId)->prefix(TextFormat::RED),
			disconnectScreenMessage: KnownTranslationFactory::pocketmine_disconnect_error($disconnectScreenMessage ?? $reason, $errorId),
		);
	}

	public function disconnect(Translatable|string $reason, Translatable|string|null $disconnectScreenMessage = null, bool $notify = true) : void
	{
		$this->tryDisconnect(function () use ($reason, $disconnectScreenMessage, $notify) : void {
			if ($notify) {
				$this->sendDisconnectPacket($disconnectScreenMessage ?? $reason);
			}
			$this->getPlayer()?->onPostDisconnect($reason, null);
		}, $reason);
	}

	public function tryDisconnect(Closure $func, Translatable|string $reason) : void
	{
		if (ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connected") && !ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "disconnectGuard")) {
			ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "disconnectGuard", true);
			$func();
			ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "disconnectGuard", false);
			$this->flushGamePacketQueue();
			ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "sender")->close();
			$disposeHooks = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "disposeHooks");
			foreach ($disposeHooks as $callback) {
				$callback();
			}
			$disposeHooks->clear();
			$this->setHandler(null);
			ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "connected", false);

			$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_close($reason)));
		}
	}

	private function sendDisconnectPacket(Translatable|string $message, DisconnectReason $reason = DisconnectReason::UNKNOWN) : void
	{
		if ($message instanceof Translatable) {
			$message = Server::getInstance()->getLanguage()->translate($message);
		}
		$this->sendDataPacket(DisconnectPacket::create($reason->value, $message, ""));
	}

	public function sendDataPacket(ClientboundPacket $packet, bool $immediate = false) : bool {
		if (!ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connected")) {
			return false;
		}

		if (!ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "loggedIn") && !$packet->canBeSentBeforeLogin()) {
			throw new InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->getDisplayName() . " too early");
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		try {
			$packets = [$packet];
			if (DataPacketSendEvent::hasHandlers()) {
				$ev = new DataPacketSendEvent([$this], $packets);
				$ev->call();
				if ($ev->isCancelled()) {
					return false;
				}

				$packets = $ev->getPackets();
			}

			$writer = new ByteBufferWriter();

			foreach ($packets as $packet) {
				$writer->clear();

				try {
					$translated = $this->outcoming($packet);
					$buffer = $translated === null ? null : self::encodePacketTimed($writer, $translated);
				} catch (Throwable $e) {
					TranslationGuard::report($this->getLogger(), $packet, $e);
					continue;
				}

				$this->traceOutgoing($packet, $buffer !== null);
				if ($buffer !== null) {
					$this->addToSendBuffer($buffer);
				}

				$this->sendCompanion($packet, $writer);
			}

			if ($immediate) {
				$this->flushGamePacketQueue();
			}

			return true;
		} finally {
			$timings->stopTiming();
		}
	}

	public function sendDataPacketWithReceipt(ClientboundPacket $packet, bool $immediate = false) : Promise
	{
		$resolver = new PromiseResolver();

		$pending = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "sendBufferAckPromises");
		$pending[] = $resolver;
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "sendBufferAckPromises", $pending);

		try {
			$sent = $this->sendDataPacket($packet, $immediate);
		} catch (Throwable $e) {
			$this->discardAckPromise($resolver);
			throw $e;
		}

		if (!$sent) {
			$this->discardAckPromise($resolver);
			$resolver->reject();
		}

		return $resolver->getPromise();
	}

	private function discardAckPromise(PromiseResolver $resolver) : void
	{
		$remaining = [];
		foreach (ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "sendBufferAckPromises") as $pending) {
			if ($pending !== $resolver) {
				$remaining[] = $pending;
			}
		}
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "sendBufferAckPromises", $remaining);
	}

	private const TRACED_OUTGOING = ['ContainerOpenPacket', 'ContainerClosePacket', 'InventoryContentPacket', 'InventorySlotPacket', 'ContainerSetDataPacket', 'UpdateBlockPacket', 'BlockActorDataPacket', 'BlockEventPacket', 'NetworkStackLatencyPacket', 'PlaySoundPacket', 'ChangeDimensionPacket', 'SetPlayerGameTypePacket', 'UpdateAbilitiesPacket', 'UpdateAdventureSettingsPacket', 'ModalFormRequestPacket', 'TextPacket'];

	private const CALLER_TRACED_OUTGOING = ['ContainerOpenPacket', 'ContainerClosePacket'];

	private const CALLER_DEPTH = 14;

	private function traceOutgoing(ClientboundPacket $packet, bool $sent) : void
	{
		$name = substr(strrchr(get_class($packet), '\\'), 1);
		if (in_array($name, self::TRACED_OUTGOING, true)) {
			$window = property_exists($packet, 'windowId') ? ' window=' . $packet->windowId : '';
			$server = property_exists($packet, 'server') ? ' server=' . var_export($packet->server, true) : '';
			$callers = in_array($name, self::CALLER_TRACED_OUTGOING, true) ? ' <- ' . self::callers() : '';
			$this->getLogger()->debug('[Trace] out ' . $name . $window . $server . ($sent ? ' sent' : ' dropped') . $callers);
		}
	}

	private static function callers() : string
	{
		$chain = [];
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::CALLER_DEPTH) as $frame) {
			$class = $frame['class'] ?? '';
			if ($class === self::class || !isset($frame['function'])) {
				continue;
			}
			$chain[] = ($class === '' ? '' : substr(strrchr('\\' . $class, '\\'), 1) . '::') . $frame['function'];
		}

		return implode(' < ', $chain);
	}

	private function traceIncoming(Packet $packet) : void
	{
		$name = $packet->getName();
		if (!str_contains($name, 'Move') && !str_contains($name, 'AuthInput')) {
			$window = property_exists($packet, 'windowId') ? ' window=' . $packet->windowId : '';
			$action = property_exists($packet, 'action') ? ' action=' . $packet->action : '';
			$this->getLogger()->debug('[Trace] in ' . $name . $window . $action);
		}
	}

	private function outcoming(ClientboundPacket $packet) : ?ClientboundPacket {
		$packet = $this->windowCloseGuard->filter($packet);
		if($packet === null){
			return null;
		}

		if($this->protocol === null){
			return $packet;
		}

		if ($packet instanceof CreativeContentPacket && ($player = $this->getPlayer()) !== null) {
			return $this->protocol->outcomingCreativeContentForSession($this, clone $packet, $player->getCreativeInventory());
		}

		return $this->protocol->outcomingForSession($this, clone $packet);
	}

	private function sendCompanion(ClientboundPacket $packet, ByteBufferWriter $writer) : void {
		if ($this->protocol === null) {
			return;
		}

		try {
			$companion = $this->protocol->companionForSession($this, clone $packet);
			if ($companion === null) {
				return;
			}

			$writer->clear();
			$this->addToSendBuffer(self::encodePacketTimed($writer, $companion));
		} catch (Throwable $e) {
			TranslationGuard::report($this->getLogger(), $packet, $e);
		}
	}

	public function startUsingChunk(int $chunkX, int $chunkZ, Closure $onCompletion) : void {
		$world = $this->getPlayer()->getLocation()->getWorld();

		$promiseOrPacket = MVChunkCache::getInstance($world, $this->getCompressor(), $this->protocol)->request($chunkX, $chunkZ);

		if(is_string($promiseOrPacket)) {
			$this->sendChunkPacket($promiseOrPacket, $onCompletion, $world);
			return;
		}

		$promiseOrPacket->onResolve(function (CompressBatchPromise $promise) use ($world, $onCompletion, $chunkX, $chunkZ) : void {
			if(!$this->isConnected()){
				return;
			}
			$currentWorld = $this->getPlayer()->getLocation()->getWorld();
			if ($world !== $currentWorld || ($status = $this->getPlayer()->getUsedChunkStatus($chunkX, $chunkZ)) === null) {
				$this->getLogger()->debug("Tried to send no-longer-active chunk $chunkX $chunkZ in world " . $world->getFolderName());
				return;
			}

			if ($status !== UsedChunkStatus::REQUESTED_SENDING) {
				return;
			}

			$this->sendChunkPacket($promise->getResult(), $onCompletion, $world);
		});
	}

	private function sendChunkPacket(string $chunkPacket, Closure $onCompletion, World $world) : void
	{
		$world->timings->syncChunkSend->startTiming();
		try {
			$this->queueCompressed($chunkPacket, true);
			$onCompletion();
		} finally {
			$world->timings->syncChunkSend->stopTiming();
		}
	}

	public function queueCompressed(CompressBatchPromise|string $payload, bool $immediate = false) : void
	{
		Timings::$playerNetworkSend->startTiming();
		try {
			$this->flushGamePacketQueue();
			ReflectionUtils::invoke(BaseNetworkSession::class, $this, "queueCompressedNoGamePacketFlush", $payload, $immediate);
		} finally {
			Timings::$playerNetworkSend->stopTiming();
		}
	}

	public function onClientDisconnect(Translatable|string $reason) : void
	{
		$this->tryDisconnect(function () use ($reason) : void {
			$this->getPlayer()?->onPostDisconnect($reason, null);
		}, $reason);
	}

	public function onPlayerDestroyed(Translatable|string $reason, Translatable|string $disconnectScreenMessage) : void
	{
		$this->tryDisconnect(function () use ($disconnectScreenMessage) : void {
			ReflectionUtils::invoke(BaseNetworkSession::class, $this, "sendDisconnectPacket", $disconnectScreenMessage);
		}, $reason);
	}

	private function onSessionStartSuccess() : void
	{
		$this->getLogger()->debug("Session start handshake completed, awaiting login packet");
		$this->flushGamePacketQueue();
		$this->enableCompression = true;
		$this->setHandler(new MVLoginPacketHandler(
			Server::getInstance(),
			$this,
			function (PlayerInfo $info) : void {
				ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "info", $info);
				$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
				$this->getLogger()->setPrefix("NetworkSession: " . $this->getDisplayName());
				ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "manager")->markLoginReceived($this);
			},
			function (bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void {
				ReflectionUtils::invoke(BaseNetworkSession::class, $this, "setAuthenticationStatus", $authenticated, $authRequired, $error, $clientPubKey);
			},
			$this->onSessionStartSuccess(...)
		));
	}

	private function flushGamePacketQueue() : void
	{
		$sendBuffer = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "sendBuffer");
		if (count($sendBuffer) > 0 && $this->protocol !== null) {
			Timings::$playerNetworkSend->startTiming();
			try {
				$syncMode = null;
				if (ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "forceAsyncCompression")) {
					$syncMode = false;
				}

				$frameCodec = $this->protocol->getFrameCodec();
				if ($frameCodec !== null) {
					$batch = $frameCodec->wrap($sendBuffer, $this->protocol, $syncMode, Timings::$playerNetworkSendCompressSessionBuffer);
				} else {
					$stream = new ByteBufferWriter();
					PacketBatch::encodeRaw($stream, $sendBuffer);

					$batch = $this->enableCompression ? MVBatch::prepareBatch($stream->getData(), $this->getProtocol(), $this->getCompressor(), $syncMode, Timings::$playerNetworkSendCompressSessionBuffer) : $stream->getData();
				}

				ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "sendBuffer", []);
				$ackPromises = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "sendBufferAckPromises");
				ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "sendBufferAckPromises", []);

				ReflectionUtils::invoke(BaseNetworkSession::class, $this, "queueCompressedNoGamePacketFlush", $batch, true, $ackPromises);
			} finally {
				Timings::$playerNetworkSend->stopTiming();
			}
		}
	}

	public function getProtocol() : TProtocol {
		return $this->protocol ?? throw new \RuntimeException("Protocol was not initialized");
	}

	public function safeProtocol() : ?TProtocol {
		return $this->protocol;
	}

	public function setProtocol(TProtocol $protocol) : void
	{
		$this->protocol = $protocol;
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "packetPool", $protocol->getPacketPool());
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "broadcaster", $protocol->getBroadcaster());
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "entityEventBroadcaster", $protocol->getEntityEventBroadcaster());
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "compressor", $protocol->getCompressor());
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "typeConverter", $protocol->getTypeConverter());

	}
}
