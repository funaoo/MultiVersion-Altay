<?php

declare(strict_types=1);

namespace lol\network\proto\v84;

use GlobalLogger;
use LogicException;
use lol\network\etc\GlobalLoginPacket;
use lol\network\etc\MVFrameCodec;
use lol\network\global\MVTypeConverter;
use lol\network\NetworkSession;
use lol\network\proto\latest\LatestSkinHelper;
use lol\network\proto\latest\LatestTypeConverter;
use lol\network\proto\TProtocol;
use lol\network\proto\v84\actions\v84BlockBreaks;
use lol\network\proto\v84\actions\v84HeldItemRelease;
use lol\network\proto\v84\actions\v84PlayerActionRouter;
use lol\network\proto\v84\actions\v84PlayerStateActions;
use lol\network\proto\v84\actions\v84WorldBlockAttacks;
use lol\network\proto\v84\blocks\v84BlockChangeTranslator;
use lol\network\proto\v84\chunk\v84ChunkSerializer;
use lol\network\proto\v84\crafting\v84CraftingEventRouter;
use lol\network\proto\v84\crafting\v84CraftingExecutor;
use lol\network\proto\v84\crafting\v84CraftingGridMatcher;
use lol\network\proto\v84\crafting\v84CraftingInputs;
use lol\network\proto\v84\crafting\v84CraftingItemBridge;
use lol\network\proto\v84\crafting\v84CraftingItems;
use lol\network\proto\v84\crafting\v84CraftingPlanner;
use lol\network\proto\v84\crafting\v84CraftingResidues;
use lol\network\proto\v84\crafting\v84CraftingStock;
use lol\network\proto\v84\crafting\v84CraftingTableGate;
use lol\network\proto\v84\crafting\v84RecipeIndex;
use lol\network\proto\v84\creative\v84CreativeContent;
use lol\network\proto\v84\creative\v84CreativeReconciler;
use lol\network\proto\v84\creative\v84CreativeTable;
use lol\network\proto\v84\creative\v84CreativeTranslator;
use lol\network\proto\v84\dimension\v84DimensionTranslator;
use lol\network\proto\v84\effects\v84EffectAuras;
use lol\network\proto\v84\effects\v84EffectTable;
use lol\network\proto\v84\effects\v84EffectTranslator;
use lol\network\proto\v84\entities\v84EntityTranslator;
use lol\network\proto\v84\entities\v84EntityTypeTable;
use lol\network\proto\v84\entities\v84ItemEntityTranslator;
use lol\network\proto\v84\entities\v84PickupTranslator;
use lol\network\proto\v84\equipment\v84EquipmentTranslator;
use lol\network\proto\v84\events\v84EventTable;
use lol\network\proto\v84\events\v84EventTranslator;
use lol\network\proto\v84\events\v84SwingFilter;
use lol\network\proto\v84\explosion\v84ExplosionTranslator;
use lol\network\proto\v84\frames\v84WorldItemFrameStrikes;
use lol\network\proto\v84\framing\v84FrameCodec;
use lol\network\proto\v84\framing\v84ZlibCompressor;
use lol\network\proto\v84\furnace\v84FurnaceWindows;
use lol\network\proto\v84\inventory\v84ClientItemResolver;
use lol\network\proto\v84\inventory\v84ClientItems;
use lol\network\proto\v84\inventory\v84FloatingInventory;
use lol\network\proto\v84\interaction\v84AirInteractions;
use lol\network\proto\v84\interaction\v84BlockInteractions;
use lol\network\proto\v84\interaction\v84HeldItemCheck;
use lol\network\proto\v84\interaction\v84HeldItemConsumption;
use lol\network\proto\v84\interaction\v84HeldItems;
use lol\network\proto\v84\interaction\v84VehicleInteractions;
use lol\network\proto\v84\interaction\v84WorldBlockResync;
use lol\network\proto\v84\inventory\v84InventoryTransactions;
use lol\network\proto\v84\inventory\v84InventoryTranslator;
use lol\network\proto\v84\inventory\v84ServerTicks;
use lol\network\proto\v84\items\v84ItemDecoder;
use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\items\v84ItemTable;
use lol\network\proto\v84\items\v84LegacyItem;
use lol\network\proto\v84\links\v84LinkTranslator;
use lol\network\proto\v84\metadata\v84MetadataConverter;
use lol\network\proto\v84\metadata\v84MetadataTable;
use lol\network\proto\v84\metadata\v84MetadataTranslator;
use lol\network\proto\v84\movement\v84MovementTranslator;
use lol\network\proto\v84\packets\v84ChunkRadiusUpdatedPacket;
use lol\network\proto\v84\packets\v84AdventureSettingsPacket;
use lol\network\proto\v84\packets\v84AnimatePacket;
use lol\network\proto\v84\packets\v84BlockEntityDataPacket;
use lol\network\proto\v84\blocks\v84LegacyBlock;
use lol\network\proto\v84\text\v84ChatActivity;
use lol\network\proto\v84\tiles\v84VirtualChest;
use lol\network\proto\v84\tiles\v84VirtualChestRestorer;
use lol\network\proto\v84\packets\v84BlockEventPacket;
use lol\network\proto\v84\packets\v84ContainerClosePacket;
use lol\network\proto\v84\packets\v84ContainerOpenPacket;
use lol\network\proto\v84\packets\v84ContainerSetSlotPacket;
use lol\network\proto\v84\packets\v84CraftingEventPacket;
use lol\network\proto\v84\packets\v84CraftingDataPacket;
use lol\network\proto\v84\packets\v84DisconnectPacket;
use lol\network\proto\v84\packets\v84DropItemPacket;
use lol\network\proto\v84\packets\v84EntityEventPacket;
use lol\network\proto\v84\packets\v84FullChunkDataPacket;
use lol\network\proto\v84\packets\v84InteractPacket;
use lol\network\proto\v84\packets\v84ItemFrameDropItemPacket;
use lol\network\proto\v84\packets\v84MobEquipmentPacket;
use lol\network\proto\v84\packets\v84MovePlayerPacket;
use lol\network\proto\v84\packets\v84PlayerActionPacket;
use lol\network\proto\v84\packets\outgoing\v84OutgoingRespawnPacket;
use lol\network\proto\v84\packets\v84PlayStatusPacket;
use lol\network\proto\v84\packets\v84RemoveBlockPacket;
use lol\network\proto\v84\packets\v84RequestChunkRadiusPacket;
use lol\network\proto\v84\packets\v84SetDifficultyPacket;
use lol\network\proto\v84\packets\v84SetHealthPacket;
use lol\network\proto\v84\packets\v84SetPlayerGameTypePacket;
use lol\network\proto\v84\packets\v84SetSpawnPositionPacket;
use lol\network\proto\v84\packets\v84SetTimePacket;
use lol\network\proto\v84\packets\v84StartGamePacket;
use lol\network\proto\v84\packets\v84TextPacket;
use lol\network\proto\v84\packets\v84UpdateAttributesPacket;
use lol\network\proto\v84\packets\v84UpdateBlockPacket;
use lol\network\proto\v84\packets\v84UseItemPacket;
use lol\network\proto\v84\paintings\v84PaintingMotiveTable;
use lol\network\proto\v84\paintings\v84PaintingTranslator;
use lol\network\proto\v84\particles\v84LevelEventParticles;
use lol\network\proto\v84\players\v84HeldItemTranslator;
use lol\network\proto\v84\players\v84PlayerTranslator;
use lol\network\proto\v84\recipes\v84RecipeTable;
use lol\network\proto\v84\forms\v84FormBridge;
use lol\network\proto\v84\scoreboard\v84ScoreboardBridge;
use lol\network\proto\v84\scoreboard\v84TitleBridge;
use lol\network\proto\v84\sound\v84LevelEventSounds;
use lol\network\proto\v84\sound\v84SoundEventTranslator;
use lol\network\proto\v84\structure\v84ActorIds;
use lol\network\proto\v84\structure\v84ClientDataFiller;
use lol\network\proto\v84\structure\v84HandshakeBridge;
use lol\network\proto\v84\structure\v84DeathPacketHandler;
use lol\network\proto\v84\structure\v84InGamePacketHandler;
use lol\network\proto\v84\structure\v84PacketPool;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\proto\v84\structure\v84WorldLimits;
use lol\network\proto\v84\text\v84TextTranslator;
use lol\network\proto\v84\latency\v84LatencyResponder;
use lol\network\proto\v84\windows\v84DeferredWindows;
use lol\network\proto\v84\windows\v84DisplayedWindows;
use lol\network\proto\v84\windows\v84ExcessOpenAck;
use lol\network\proto\v84\windows\v84WindowCloser;
use lol\network\proto\v84\windows\v84WindowContentTranslator;
use lol\network\proto\v84\windows\v84WindowTranslator;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pocketmine\block\tile\Spawnable;
use pocketmine\block\tile\Chest as ChestTile;
use pocketmine\crafting\CraftingManager;
use pocketmine\entity\Attribute;
use pocketmine\inventory\CreativeInventory;
use pocketmine\lang\Language;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddPaintingPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\handler\DeathPacketHandler;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\SetDifficultyPacket;
use pocketmine\network\mcpe\protocol\ClientboundCloseFormPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\SetTimePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Utils;

final class v84Protocol extends TProtocol
{

	private const EYE_HEIGHT = 1.62;

	public const RAKNET_VERSION = 8;

	public const ACCEPTED_RAKNET_VERSIONS = [6, 8];

	private readonly v84FrameCodec $frameCodec;

	private readonly v84HandshakeBridge $handshakeBridge;

	private readonly v84ChunkSerializer $v84Chunks;

	private readonly v84EntityTypeTable $entityTypes;

	private readonly v84PaintingMotiveTable $paintingMotives;

	private readonly v84MetadataConverter $metadata;

	private readonly v84EventTable $events;

	private readonly v84SwingFilter $swings;

	private readonly v84EffectTable $effects;

	private readonly v84EffectAuras $effectAuras;

	private readonly v84ScoreboardBridge $scoreboard;

	private readonly v84TitleBridge $titles;

	private readonly v84FormBridge $forms;

	private ?v84BlockChangeTranslator $blockChanges = null;

	private ?v84ItemEncoder $itemEncoder = null;

	private ?v84ItemDecoder $itemDecoder = null;

	private ?v84ClientItems $clientItems = null;

	private ?v84HeldItems $heldItems = null;

	private ?v84CraftingDataPacket $craftingData = null;

	private ?v84CreativeContent $creativeContent = null;

	private ?v84RecipeIndex $recipeIndex = null;

	private ?v84CraftingPlanner $craftingPlanner = null;

	private ?v84CraftingItems $craftingItems = null;

	private ?int $neutralItemId = null;

	private ?v84WindowCloser $windowCloser = null;

	private ?v84DeferredWindows $deferredWindows = null;

	private ?v84DisplayedWindows $displayedWindows = null;

	private ?v84LatencyResponder $latencyResponder = null;

	private ?v84VirtualChestRestorer $chestRestorer = null;

	private ?v84BlockBreaks $blockBreaks = null;

	private ?v84FurnaceWindows $furnaceWindows = null;

	public function __construct()
	{
		$this->v84Chunks = new v84ChunkSerializer();
		parent::__construct(new v84PacketPool(), $this->v84Chunks, new LatestSkinHelper());

		$compressor = new v84ZlibCompressor();
		$this->compressor = $compressor;
		$this->frameCodec = new v84FrameCodec($compressor);
		$this->handshakeBridge = new v84HandshakeBridge();
		$this->entityTypes = new v84EntityTypeTable();
		$this->paintingMotives = new v84PaintingMotiveTable();
		$this->metadata = new v84MetadataConverter(new v84MetadataTable());
		$this->events = new v84EventTable();
		$this->swings = new v84SwingFilter(static fn() : int => Server::getInstance()->getTick());
		$this->effects = new v84EffectTable();
		$this->effectAuras = new v84EffectAuras($this->effects);
		$this->scoreboard = new v84ScoreboardBridge(static fn() : int => Server::getInstance()->getTick());
		$this->titles = new v84TitleBridge();
		$this->forms = v84FormBridge::shared();
	}

	public function getProtocolId() : int
	{
		return v84ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function getRaknetVersion() : int
	{
		return self::RAKNET_VERSION;
	}

	public function __toString() : string
	{
		return v84ProtocolInfo::MINECRAFT_VERSION;
	}

	public function getFrameCodec() : ?MVFrameCodec
	{
		return $this->frameCodec;
	}

	public function readLoginBuffer(ByteBufferReader $in) : ?string
	{
		return $in->readByteArray(BE::readUnsignedInt($in));
	}

	protected function createTypeConverter() : MVTypeConverter
	{
		return LatestTypeConverter::getInstance()->getConverter();
	}

	public function fetchPacketHandler(?PacketHandler $handler, NetworkSession $session) : ?PacketHandler
	{
		$this->handshakeBridge->bridge($handler, $session);

		if ($handler instanceof InGamePacketHandler && !$handler instanceof v84InGamePacketHandler) {
			$player = Utils::assumeNotFalse($session->getPlayer() ?: false);
			$inventoryManager = $session->getInvManager();
			$blockResync = new v84WorldBlockResync($player, $session);
			$consumption = new v84HeldItemConsumption($player, $inventoryManager);
			$floating = new v84FloatingInventory();
			$airInteractions = new v84AirInteractions($player, $inventoryManager, $this->heldItems());

			return new v84InGamePacketHandler(
				$player,
				$session,
				$inventoryManager,
				new v84InventoryTransactions($player, $inventoryManager, $this->clientItems(), v84ServerTicks::after(...), $floating),
				new v84BlockInteractions($player, $inventoryManager, $this->heldItems(), $blockResync, $airInteractions),
				$airInteractions,
				$consumption,
				new v84VehicleInteractions($player),
				new v84PlayerActionRouter(
					$player,
					new v84WorldBlockAttacks($player, $blockResync, $this->blockBreaks()),
					new v84HeldItemRelease($player, $inventoryManager, $consumption),
					new v84PlayerStateActions($player)
				),
				new v84CraftingEventRouter(
					$player,
					$inventoryManager,
					$this->recipeIndex(),
					new v84CraftingTableGate($player),
					$this->craftingPlanner(),
					new v84CraftingExecutor($player, $this->craftingItems(), $this->neutralItemId(), new v84CraftingResidues(), $floating),
					$floating
				),
				new v84WorldItemFrameStrikes($player, $blockResync)
			);
		}

		if ($handler instanceof DeathPacketHandler && !$handler instanceof v84DeathPacketHandler) {
			$player = $session->getPlayer();
			if ($player !== null) {
				return new v84DeathPacketHandler($handler, $player);
			}
		}

		return $handler;
	}

	public function incoming(ServerboundPacket $packet) : ?ServerboundPacket
	{
		return match (true) {
			$packet instanceof GlobalLoginPacket => $packet,
			$packet instanceof v84DisconnectPacket => DisconnectPacket::create(0, $packet->message, null),
			$packet instanceof v84RequestChunkRadiusPacket => RequestChunkRadiusPacket::create($packet->radius, $packet->radius),
			$packet instanceof v84RemoveBlockPacket => $packet,
			$packet instanceof v84MovePlayerPacket => $packet,
			$packet instanceof v84AnimatePacket => $packet,
			$packet instanceof v84MobEquipmentPacket => $packet,
			$packet instanceof v84ContainerSetSlotPacket => $packet,
			$packet instanceof v84ContainerClosePacket => $packet,
			$packet instanceof v84DropItemPacket => $packet,
			$packet instanceof v84UseItemPacket => $packet,
			$packet instanceof v84EntityEventPacket => $packet,
			$packet instanceof v84InteractPacket => $packet,
			$packet instanceof v84ItemFrameDropItemPacket => $packet,
			$packet instanceof v84PlayerActionPacket => $packet,
			$packet instanceof v84CraftingEventPacket => $packet,
			$packet instanceof v84TextPacket => $packet,
			default => null
		};
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{
		return match (true) {
			$packet instanceof PlayStatusPacket => v84PlayStatusPacket::fromLatest($packet),
			$packet instanceof DisconnectPacket => v84DisconnectPacket::fromLatest($packet),
			$packet instanceof SetTimePacket => v84SetTimePacket::fromLatest($packet, true),
			$packet instanceof SetSpawnPositionPacket => v84SetSpawnPositionPacket::fromLatest($packet),
			$packet instanceof SetDifficultyPacket => v84SetDifficultyPacket::fromLatest($packet),
			$packet instanceof SetPlayerGameTypePacket => v84SetPlayerGameTypePacket::fromLatest($packet),
			$packet instanceof ChunkRadiusUpdatedPacket => v84ChunkRadiusUpdatedPacket::fromLatest($packet),
			$packet instanceof LevelChunkPacket => v84FullChunkDataPacket::fromLatest($packet),
			$packet instanceof UpdateBlockPacket => $this->translateUpdateBlock($packet),
			$packet instanceof BlockEventPacket => $this->translateBlockEvent($packet),
			$packet instanceof BlockActorDataPacket => $this->translateBlockEntityData($packet),
			$packet instanceof AddPlayerPacket => v84PlayerTranslator::addPlayer($packet, $this->metadata),
			$packet instanceof PlayerListPacket => v84PlayerTranslator::playerList($packet),
			$packet instanceof RemoveActorPacket => v84PlayerTranslator::removeActor($packet),
			$packet instanceof AddPaintingPacket => v84PaintingTranslator::addPainting($packet, $this->paintingMotives),
			$packet instanceof CraftingDataPacket => $this->craftingData(),
			$packet instanceof RespawnPacket => $this->translateRespawn($packet),
			$packet instanceof TextPacket => v84TextTranslator::translate($packet),
			$packet instanceof ChangeDimensionPacket => v84DimensionTranslator::changeDimension($packet),
			$packet instanceof LevelEventPacket => $this->translateLevelEvent($packet),
			$packet instanceof LevelSoundEventPacket => v84SoundEventTranslator::translate($packet, $this->blockChanges()->packedFromRuntimeId(...)),
			default => null
		};
	}

	private function translateLevelEvent(LevelEventPacket $packet) : ?ClientboundPacket
	{
		return v84ExplosionTranslator::translate($packet) ?? v84LevelEventSounds::translate($packet) ?? v84LevelEventParticles::translate($packet, $this->blockChanges()->packedFromRuntimeId(...));
	}

	private function closeForms(NetworkSession $session) : ?ClientboundPacket
	{
		$this->forms->closeAll($session);

		return null;
	}

	private function translateRespawn(RespawnPacket $packet) : ?v84OutgoingRespawnPacket
	{
		if ($packet->respawnState !== RespawnPacket::SEARCHING_FOR_SPAWN) {
			return null;
		}

		return v84OutgoingRespawnPacket::fromParts($packet->position->getX(), $packet->position->getY() - self::EYE_HEIGHT, $packet->position->getZ());
	}

	public function outcomingForSession(NetworkSession $session, ClientboundPacket $packet) : ?ClientboundPacket
	{
		return match (true) {
			$packet instanceof StartGamePacket => v84StartGamePacket::fromLatest($packet, v84ActorIds::LOCAL_PLAYER),
			$packet instanceof UpdateAttributesPacket => v84UpdateAttributesPacket::fromLatest($packet, v84ActorIds::resolve($session, $packet->actorRuntimeId)),
			$packet instanceof UpdateAbilitiesPacket, $packet instanceof UpdateAdventureSettingsPacket => $this->translateAdventureSettings($session, $packet),
			$packet instanceof MovePlayerPacket => v84MovementTranslator::playerMove($session, $packet),
			$packet instanceof MoveActorAbsolutePacket => v84MovementTranslator::actorMove($session, $packet, $this->entityTypes),
			$packet instanceof AddActorPacket => v84EntityTranslator::addEntity($session, $packet, $this->entityTypes, $this->metadata),
			$packet instanceof SetActorMotionPacket => v84MovementTranslator::motion($session, $packet),
			$packet instanceof ActorEventPacket => $this->swings->filter($session, v84EventTranslator::actorEvent($session, $packet, $this->events)),
			$packet instanceof AnimatePacket => $this->swings->filter($session, v84EventTranslator::animate($session, $packet)),
			$packet instanceof SetActorDataPacket => v84MetadataTranslator::entityData($session, $packet, $this->metadata),
			$packet instanceof AddPlayerPacket => v84PlayerTranslator::addPlayer($packet, $this->metadata, v84HeldItemTranslator::slot($session, $packet, $this->itemEncoder())),
			$packet instanceof BlockActorDataPacket => $this->translateTileData($session, $packet),
			$packet instanceof AddItemActorPacket => v84ItemEntityTranslator::addItemEntity($session, $packet, $this->itemEncoder()),
			$packet instanceof TakeItemActorPacket => v84PickupTranslator::takeItem($session, $packet),
			($packet instanceof InventoryContentPacket || $packet instanceof InventorySlotPacket) && $this->deferredWindows()->holds($session, $packet->windowId) => null,
			$packet instanceof InventoryContentPacket => v84InventoryTranslator::content($session, $packet, $this->itemEncoder()) ?? v84WindowContentTranslator::content($session->getInvManager(), $packet, $this->itemEncoder()),
			$packet instanceof InventorySlotPacket => v84InventoryTranslator::slot($session, $packet, $this->itemEncoder()) ?? v84WindowContentTranslator::slot($session->getInvManager(), $packet, $this->itemEncoder()),
			$packet instanceof ContainerOpenPacket => $this->openWindow($session, $packet),
			$packet instanceof ContainerSetDataPacket => $this->furnaceWindows()->data($session, $packet),
			$packet instanceof ContainerClosePacket => $this->closeWindow($session, $packet),
			$packet instanceof UpdateBlockPacket => $this->updateBlock($session, $packet),
			$packet instanceof NetworkStackLatencyPacket => $this->answerLatency($session, $packet),
			$packet instanceof MobEquipmentPacket => v84EquipmentTranslator::heldItem($session, $packet, $this->itemEncoder()),
			$packet instanceof MobArmorEquipmentPacket => v84EquipmentTranslator::armor($session, $packet, $this->itemEncoder()),
			$packet instanceof MobEffectPacket => v84EffectTranslator::mobEffect($session, $packet, $this->effects),
			$packet instanceof SetActorLinkPacket => v84LinkTranslator::link($session, $packet, $this->entityTypes),
			$packet instanceof SetDisplayObjectivePacket, $packet instanceof SetScorePacket, $packet instanceof RemoveObjectivePacket => $this->scoreboard->handle($session, $packet),
			$packet instanceof SetTitlePacket => $this->titles->handle($session, $packet),
			$packet instanceof ModalFormRequestPacket => $this->forms->open($session, $packet),
			$packet instanceof ClientboundCloseFormPacket => $this->closeForms($session),
			default => $this->outcoming($packet)
		};
	}

	public function outcomingCreativeContentForSession(NetworkSession $session, CreativeContentPacket $packet, CreativeInventory $inventory) : ?ClientboundPacket
	{
		return v84CreativeTranslator::content($session, $this->creativeContent());
	}

	public function tickSession(NetworkSession $session) : ?ClientboundPacket
	{
		$this->forms->tick($session);

		$player = $session->getPlayer();
		if ($player !== null) {
			$this->blockBreaks()->release($player);
		}

		$this->chestRestorer()->sweep($session);

		return $this->deferredWindows()->release($session) ?? $this->scoreboard->refresh($session);
	}

	public function companionForSession(NetworkSession $session, ClientboundPacket $packet) : ?ClientboundPacket
	{
		if ($packet instanceof ChangeDimensionPacket) {
			return v84DimensionTranslator::playerSpawn($packet);
		}

		if ($packet instanceof UpdateBlockPacket) {
			return $this->pairedChestData($session, $packet) ?? v84VirtualChest::tile($session, $packet, $this->blockChanges()->packedFromRuntimeId($packet->blockRuntimeId), $this->v84Chunks);
		}

		if ($packet instanceof MobEffectPacket) {
			return $this->effectAuras->update($session, $packet);
		}

		if (!$packet instanceof UpdateAttributesPacket || $packet->actorRuntimeId !== $session->getPlayer()?->getId()) {
			return null;
		}

		foreach ($packet->entries as $attribute) {
			if ($attribute->getId() === Attribute::HEALTH) {
				return v84SetHealthPacket::fromCurrent($attribute->getCurrent());
			}
		}

		return null;
	}

	private function translateUpdateBlock(UpdateBlockPacket $packet) : ?v84UpdateBlockPacket
	{
		if ($packet->dataLayerId !== v84UpdateBlockPacket::NORMAL_DATA_LAYER || !v84WorldLimits::isBlockY($packet->blockPosition->getY())) {
			return null;
		}

		return v84UpdateBlockPacket::fromLatest($packet, $this->blockChanges()->packedFromRuntimeId($packet->blockRuntimeId));
	}

	private function updateBlock(NetworkSession $session, UpdateBlockPacket $packet) : ?v84UpdateBlockPacket
	{
		$translated = $this->translateUpdateBlock($packet);
		if ($translated === null) {
			return null;
		}

		$position = $packet->blockPosition;
		$chest = v84LegacyBlock::id($this->blockChanges()->packedFromRuntimeId($packet->blockRuntimeId)) === v84VirtualChest::CHEST_BLOCK_ID;
		$virtual = $chest && $session->getPlayer()?->getWorld()->getTileAt($position->getX(), $position->getY(), $position->getZ()) === null;
		$this->chestRestorer()->observe($session, $position->getX(), $position->getY(), $position->getZ(), $chest, $virtual);

		return $translated;
	}

	private function chestRestorer() : v84VirtualChestRestorer
	{
		return $this->chestRestorer ??= new v84VirtualChestRestorer(
			v84ServerTicks::after(...),
			static fn(NetworkSession $session) : bool => $session->getPlayer()?->getCurrentWindow() !== null,
			static function (NetworkSession $session, int $x, int $y, int $z) : void {
				$player = $session->getPlayer();
				if ($player === null || !$player->isConnected()) {
					return;
				}

				foreach ($player->getWorld()->createBlockUpdatePackets([new Vector3($x, $y, $z)]) as $packet) {
					$session->sendDataPacket($packet);
				}
			},
			static fn() : int => Server::getInstance()->getTick()
		);
	}

	private function translateBlockEvent(BlockEventPacket $packet) : ?v84BlockEventPacket
	{
		if (!v84WorldLimits::isBlockY($packet->blockPosition->getY())) {
			return null;
		}

		return v84BlockEventPacket::fromLatest($packet);
	}

	private function translateBlockEntityData(BlockActorDataPacket $packet) : ?v84BlockEntityDataPacket
	{
		if (!v84WorldLimits::isBlockY($packet->blockPosition->getY())) {
			return null;
		}

		$namedTag = $this->v84Chunks->serializeBlockEntity($packet->nbt->getRoot());
		if ($namedTag === '') {
			return null;
		}

		return v84BlockEntityDataPacket::fromLatest($packet, $namedTag);
	}

	private function pairedChestData(NetworkSession $session, UpdateBlockPacket $packet) : ?v84BlockEntityDataPacket
	{
		if ($packet->dataLayerId !== v84UpdateBlockPacket::NORMAL_DATA_LAYER) {
			return null;
		}

		$position = $packet->blockPosition;
		$tile = $session->getPlayer()?->getWorld()->getTileAt($position->getX(), $position->getY(), $position->getZ());
		if (!$tile instanceof ChestTile || !$tile->isPaired()) {
			return null;
		}

		$pair = $tile->getPair();
		if ($pair === null || !v84WorldLimits::isBlockY($pair->getPosition()->getFloorY())) {
			return null;
		}

		$namedTag = $this->v84Chunks->serializeTile($pair);
		if ($namedTag === '') {
			\GlobalLogger::get()->debug('Paired chest at ' . $pair->getPosition()->getFloorX() . ' ' . $pair->getPosition()->getFloorY() . ' ' . $pair->getPosition()->getFloorZ() . ' could not be serialized for 0.15.10');

			return null;
		}

		$npk = new v84BlockEntityDataPacket();
		$npk->x = $pair->getPosition()->getFloorX();
		$npk->y = $pair->getPosition()->getFloorY();
		$npk->z = $pair->getPosition()->getFloorZ();
		$npk->namedTag = $namedTag;

		return $npk;
	}

	private function translateTileData(NetworkSession $session, BlockActorDataPacket $packet) : ?v84BlockEntityDataPacket
	{
		$position = $packet->blockPosition;
		$tile = $session->getPlayer()?->getWorld()->getTileAt($position->getX(), $position->getY(), $position->getZ());
		if (!$tile instanceof Spawnable || !v84WorldLimits::isBlockY($position->getY())) {
			return $this->translateBlockEntityData($packet);
		}

		$namedTag = $this->v84Chunks->serializeTile($tile);

		return $namedTag === '' ? null : v84BlockEntityDataPacket::fromLatest($packet, $namedTag);
	}

	private function blockChanges() : v84BlockChangeTranslator
	{
		return $this->blockChanges ??= v84BlockChangeTranslator::create();
	}

	private function craftingData() : v84CraftingDataPacket
	{
		return $this->craftingData ??= v84CraftingDataPacket::fromTable(v84RecipeTable::load());
	}

	private function recipeIndex() : v84RecipeIndex
	{
		return $this->recipeIndex ??= v84RecipeIndex::fromTable(v84RecipeTable::load());
	}

	private function craftingPlanner() : v84CraftingPlanner
	{
		return $this->craftingPlanner ??= new v84CraftingPlanner(
			new v84CraftingInputs(),
			new v84CraftingGridMatcher(),
			new v84CraftingStock(),
			$this->craftingItems()
		);
	}

	private function craftingItems() : v84CraftingItems
	{
		return $this->craftingItems ??= new v84CraftingItemBridge($this->itemEncoder(), $this->itemDecoder());
	}

	private function neutralItemId() : int
	{
		return $this->neutralItemId ??= v84LegacyItem::id(v84ItemTable::load()->neutral());
	}

	private function creativeContent() : v84CreativeContent
	{
		return $this->creativeContent ??= v84CreativeContent::fromTable($this->creativeTable());
	}

	private function creativeTable() : v84CreativeTable
	{
		$reconciler = new v84CreativeReconciler($this->itemDecoder(), $this->itemEncoder(), v84ItemTable::load()->neutral());
		$reconciliation = $reconciler->reconcile(v84CreativeTable::load());
		$reconciliation->report(GlobalLogger::get());

		return $reconciliation->table;
	}

	private function openWindow(NetworkSession $session, ContainerOpenPacket $packet) : ?v84ContainerOpenPacket
	{
		$this->furnaceWindows()->reset($session, $packet->windowId);

		$window = v84WindowTranslator::open($session->getInvManager(), $packet);
		if ($window === null) {
			$this->windowCloser()->dismiss($session, $packet);

			return null;
		}

		if (!$this->displayedWindows()->admit($session, $packet->windowId)) {
			return null;
		}

		$player = $session->getPlayer();

		return $this->deferredWindows()->admit($session, $packet->windowId, $window, $player === null ? 0 : v84ChatActivity::shared()->remaining($player));
	}

	private function closeWindow(NetworkSession $session, ContainerClosePacket $packet) : ?ClientboundPacket
	{
		if ($packet->server) {
			$this->displayedWindows()->forget($session, $packet->windowId);
		}

		$this->chestRestorer()->release($session);

		return $this->windowCloser()->close($session, $packet);
	}

	private function answerLatency(NetworkSession $session, NetworkStackLatencyPacket $packet) : ?ClientboundPacket
	{
		$this->latencyResponder ??= new v84LatencyResponder(v84ServerTicks::after(...), v84LatencyResponder::deliver(...));
		$this->latencyResponder->answer($session, $packet);

		return null;
	}

	private function displayedWindows() : v84DisplayedWindows
	{
		return $this->displayedWindows ??= new v84DisplayedWindows(v84ServerTicks::after(...), v84ExcessOpenAck::deliver(...));
	}

	private function furnaceWindows() : v84FurnaceWindows
	{
		return $this->furnaceWindows ??= new v84FurnaceWindows();
	}

	private function windowCloser() : v84WindowCloser
	{
		return $this->windowCloser ??= new v84WindowCloser(v84ServerTicks::after(...), $this->deferredWindows());
	}

	private function deferredWindows() : v84DeferredWindows
	{
		return $this->deferredWindows ??= new v84DeferredWindows(v84ServerTicks::after(...));
	}

	private function blockBreaks() : v84BlockBreaks
	{
		return $this->blockBreaks ??= new v84BlockBreaks();
	}

	private function itemEncoder() : v84ItemEncoder
	{
		return $this->itemEncoder ??= v84ItemEncoder::create();
	}

	private function itemDecoder() : v84ItemDecoder
	{
		return $this->itemDecoder ??= v84ItemDecoder::create($this->itemEncoder());
	}

	private function heldItems() : v84HeldItems
	{
		return $this->heldItems ??= new v84HeldItemCheck($this->itemEncoder());
	}

	private function clientItems() : v84ClientItems
	{
		return $this->clientItems ??= new v84ClientItemResolver($this->itemEncoder(), $this->itemDecoder());
	}

	private function translateAdventureSettings(NetworkSession $session, ClientboundPacket $packet) : ?v84AdventureSettingsPacket
	{
		$player = $session->getPlayer();
		if ($player === null) {
			return null;
		}

		if ($packet instanceof UpdateAbilitiesPacket && $packet->getData()->getTargetActorUniqueId() !== $player->getId()) {
			return null;
		}

		return v84AdventureSettingsPacket::fromPlayer($player);
	}

	public function injectExtraData(array &$extraData) : void
	{
		v84ClientDataFiller::fill($extraData);
	}

	public function decodeConnection(string $buffer, GlobalLoginPacket $loginPacket) : void
	{
		try {
			$decompressed = $this->compressor->decompress($buffer);
		} catch (DecompressionException $e) {
			throw PacketDecodeException::wrap($e, "Login payload decode error");
		}

		$loginPacket->decodeLegacyConnectionRequest($decompressed);
	}

	public function hasOldCompressionMethod() : bool
	{
		return true;
	}

	public function assembleCommands(Server $server, Player $player, Language $lang) : ?ClientboundPacket
	{
		return null;
	}

	public function getCraftingManager() : CraftingManager
	{
		$this->notImplemented(__FUNCTION__);
	}

	private function notImplemented(string $method) : never
	{
		throw new LogicException(self::class . "::" . $method . " is not implemented");
	}
}