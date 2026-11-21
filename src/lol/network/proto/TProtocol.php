<?php

declare(strict_types=1);

namespace lol\network\proto;

use lol\network\chunk\serializers\MVChunkSerializer;
use lol\network\etc\GlobalLoginPacket;
use lol\network\etc\MVFrameCodec;
use lol\network\global\MVPacketBroadcaster;
use lol\network\global\MVSkinHelper;
use lol\network\global\MVTypeConverter;
use lol\network\NetworkSession;
use Logger;
use pmmp\encoding\ByteBufferReader;
use pocketmine\crafting\CraftingManager;
use pocketmine\inventory\CreativeInventory;
use pocketmine\lang\Language;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\StandardEntityEventBroadcaster;
use pocketmine\player\Player;
use pocketmine\Server;
use Stringable;

abstract class TProtocol implements Stringable {

	protected ?StandardEntityEventBroadcaster $entityEventBroadcaster = null;
	protected PacketBroadcaster $broadcaster;
	protected Compressor $compressor;
	protected Logger $logger;
	protected ?MVTypeConverter $typeConverter = null;

	public function __construct(
		protected PacketPool        $packetPool,
		protected MVChunkSerializer $chunkSerializer,
		protected MVSkinHelper      $skinHelper
	)
	{
		$this->compressor = ZlibCompressor::getInstance();
		$this->broadcaster = new MVPacketBroadcaster($this);
		$this->logger = new ProtoLogger($this);
	}

	public function getBroadcaster() : PacketBroadcaster
	{
		return $this->broadcaster;
	}

	public function getEntityEventBroadcaster() : StandardEntityEventBroadcaster
	{
		return $this->entityEventBroadcaster ??= new StandardEntityEventBroadcaster($this->broadcaster, $this->getTypeConverter());
	}

	public function getLogger() : Logger
	{
		return $this->logger;
	}

	public function getPacketPool() : PacketPool
	{
		return $this->packetPool;
	}

	public function getTypeConverter() : MVTypeConverter
	{
		return $this->typeConverter ??= $this->createTypeConverter();
	}

	public function getCompressor() : Compressor
	{
		return $this->compressor;
	}

	public function getChunkSerializer() : MVChunkSerializer
	{
		return $this->chunkSerializer;
	}

	public function hasDebug() : bool{
		return false;
	}

	public function getFrameCodec() : ?MVFrameCodec
	{
		return null;
	}

	public function readLoginBuffer(ByteBufferReader $in) : ?string
	{
		return null;
	}

	protected abstract function createTypeConverter() : MVTypeConverter;

	public abstract function getProtocolId() : int;

	public abstract function getRaknetVersion() : int;

	public abstract function fetchPacketHandler(?PacketHandler $handler, NetworkSession $session) : ?PacketHandler;

	public abstract function incoming(ServerboundPacket $packet) : ?ServerboundPacket;

	public abstract function outcoming(ClientboundPacket $packet) : ?ClientboundPacket;

	public function outcomingForSession(NetworkSession $session, ClientboundPacket $packet) : ?ClientboundPacket
	{
		return $this->outcoming($packet);
	}

	public function companionForSession(NetworkSession $session, ClientboundPacket $packet) : ?ClientboundPacket
	{
		return null;
	}

	public function tickSession(NetworkSession $session) : ?ClientboundPacket
	{
		return null;
	}

	public function outcomingCreativeContent(CreativeContentPacket $packet, CreativeInventory $inventory) : ?ClientboundPacket
	{
		return $this->outcoming($packet);
	}

	public function outcomingCreativeContentForSession(NetworkSession $session, CreativeContentPacket $packet, CreativeInventory $inventory) : ?ClientboundPacket
	{
		return $this->outcomingCreativeContent($packet, $inventory);
	}

	public abstract function injectExtraData(array &$extraData) : void;

	public abstract function decodeConnection(string $buffer, GlobalLoginPacket $loginPacket) : void;

	public abstract function hasOldCompressionMethod() : bool;

	public abstract function assembleCommands(Server $server, Player $player, Language $lang) : ?ClientboundPacket;

	public abstract function getCraftingManager() : CraftingManager;

	protected function adaptSkinData(SkinData &$skinData) : void {
		$this->skinHelper->adaptSkinData($skinData);
	}
}
