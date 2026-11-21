<?php

declare(strict_types=1);

namespace lol\network\proto\v844;

use lol\network\global\MVTypeConverter;
use lol\network\assemble\command\CommandData;
use lol\network\assemble\command\CommandEnum;
use lol\network\assemble\command\CommandOverload;
use lol\network\assemble\command\CommandParameter;
use lol\network\mcpe\MVRuntimeIdToStateId;
use lol\network\proto\latest\LatestProtocol;
use lol\network\proto\v844\packets\v844ActorEventPacket;
use lol\network\proto\v844\packets\v844AddItemActorPacket;
use lol\network\proto\v844\packets\v844AddVolumeEntityPacket;
use lol\network\proto\v844\packets\v844AnimatePacket;
use lol\network\proto\v844\packets\v844AnvilDamagePacket;
use lol\network\proto\v844\packets\v844AvailableCommandsPacket;
use lol\network\proto\v844\packets\v844BlockActorDataPacket;
use lol\network\proto\v844\packets\v844BlockEventPacket;
use lol\network\proto\v844\packets\v844BossEventPacket;
use lol\network\proto\v844\packets\v844CommandRequestPacket;
use lol\network\proto\v844\packets\v844ContainerOpenPacket;
use lol\network\proto\v844\packets\v844CreativeContentPacket;
use lol\network\proto\v844\packets\v844DisconnectPacket;
use lol\network\proto\v844\packets\v844InteractPacket;
use lol\network\proto\v844\packets\v844InventoryContentPacket;
use lol\network\proto\v844\packets\v844InventorySlotPacket;
use lol\network\proto\v844\packets\v844InventoryTransactionPacket;
use lol\network\proto\v844\packets\v844ItemStackRequestPacket;
use lol\network\proto\v844\packets\v844ItemStackResponsePacket;
use lol\network\proto\v844\packets\v844LevelChunkPacket;
use lol\network\proto\v844\packets\v844LevelSoundEventPacket;
use lol\network\proto\v844\packets\v844MobArmorEquipmentPacket;
use lol\network\proto\v844\packets\v844MobEffectPacket;
use lol\network\proto\v844\packets\v844MoveActorDeltaPacket;
use lol\network\proto\v844\packets\v844SetScoreboardIdentityPacket;
use lol\network\proto\v844\packets\v844MobEquipmentPacket;
use lol\network\proto\v844\packets\v844OpenSignPacket;
use lol\network\proto\v844\packets\v844PlayerActionPacket;
use lol\network\proto\v844\packets\v844PlayerEnchantOptionsPacket;
use lol\network\proto\v844\packets\v844PlaySoundPacket;
use lol\network\proto\v844\packets\v844ResourcePackStackPacket;
use lol\network\proto\v844\packets\v844SetScorePacket;
use lol\network\proto\v844\packets\v844SetSpawnPositionPacket;
use lol\network\proto\v844\packets\v844StartGamePacket;
use lol\network\proto\v844\packets\v844TextPacket;
use lol\network\proto\v844\packets\v844UpdateBlockPacket;
use lol\network\proto\v844\packets\v844AddActorPacket;
use lol\network\proto\v844\packets\v844AddPlayerPacket;
use lol\network\proto\v844\packets\v844MovePlayerPacket;
use lol\network\proto\v844\packets\v844PlayerListPacket;
use lol\network\proto\v844\packets\v844PlayerSkinPacket;
use lol\network\proto\v844\packets\v844ResourcePacksInfoPacket;
use lol\network\proto\v844\packets\v844SetActorDataPacket;
use lol\network\proto\v844\packets\v844TransferPacket;
use lol\network\proto\v844\packets\v844UpdateClientOptionsPacket;
use lol\network\proto\v844\structure\v844AvailableCommandsPacketAssembler;
use lol\network\proto\v844\structure\v844Debug;
use lol\network\proto\v844\structure\v844PacketPool;
use lol\network\proto\v844\structure\v844LevelSoundTranslator;
use lol\network\proto\v844\structure\v844ParticleTranslator;
use lol\network\proto\v844\structure\v844StaticPacketCache;
use lol\network\proto\v844\structure\v844UnsupportedPackets;
use lol\network\utils\RawPacketHelper;
use pocketmine\crafting\CraftingManager;
use pocketmine\inventory\CreativeInventory;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AnvilDamagePacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MoveActorDeltaPacket;
use pocketmine\network\mcpe\protocol\SetScoreboardIdentityPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\OpenSignPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerEnchantOptionsPacket;
use pocketmine\network\mcpe\protocol\ClientboundUpdateSoundDataPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\network\mcpe\protocol\UpdateClientOptionsPacket;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use function array_values;
use function in_array;
use function strtolower;
use function ucfirst;

class v844Protocol extends LatestProtocol
{

	private ?v844StaticPacketCache $staticPacketCache = null;
	private ?CraftingManager $craftingManager = null;

	public function __construct()
	{
		parent::__construct();
		unset($this->packetPool);
		$this->packetPool = new v844PacketPool();
	}

	protected function createTypeConverter() : MVTypeConverter
	{
		return v844TypeConverter::getInstance()->getConverter();
	}

	public function getProtocolId() : int
	{
		return 844;
	}

	public function __toString() : string {
		return "v1.21.114";
	}

	public function hasDebug() : bool {
		return true;
	}

	public function getStaticPacketCache() : v844StaticPacketCache
	{
		return $this->staticPacketCache ??= new v844StaticPacketCache($this);
	}

	public function getCraftingManager() : CraftingManager
	{
		return $this->craftingManager ??= Server::getInstance()->getCraftingManager();
	}

	public function incoming(ServerboundPacket $packet) : ?ServerboundPacket {
		return match (true) {
			$packet instanceof v844AnimatePacket => AnimatePacket::create($packet->actorRuntimeId, $packet->action, $packet->data, null),
			$packet instanceof v844InteractPacket => RawPacketHelper::translateInteractPacketToLatest($packet),
			$packet instanceof v844InventoryTransactionPacket => InventoryTransactionPacket::create($packet->requestId, $packet->requestChangedSlots, $packet->trData),
			$packet instanceof v844ItemStackRequestPacket => ItemStackRequestPacket::create($packet->getRequests()),
			$packet instanceof v844LevelSoundEventPacket => LevelSoundEventPacket::create($packet->sound, $packet->position, $packet->extraData, $packet->entityType, $packet->isBabyMob, $packet->disableRelativeVolume, $packet->actorUniqueId, null),
			$packet instanceof v844DisconnectPacket => DisconnectPacket::create($packet->reason, $packet->message, $packet->filteredMessage),
			$packet instanceof v844ActorEventPacket => ActorEventPacket::create($packet->actorRuntimeId, $packet->eventId, $packet->eventData, null),
			$packet instanceof v844CommandRequestPacket => CommandRequestPacket::create($packet->command, $packet->_originData->toLatest(), $packet->isInternal, (string) $packet->newVersion),
			$packet instanceof v844UpdateClientOptionsPacket => UpdateClientOptionsPacket::create($packet->getGraphicsMode(), null),
			default => $this->fallbackIncoming($packet)
		};
	}

	private function fallbackOutcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{
		v844Debug::logOnce("out:" . $packet::class, "outcoming", "no v844 translation for " . $packet->getName() . " (pid " . $packet->pid() . "), forwarding with the 2193 layout");
		return parent::outcoming($packet);
	}

	private function fallbackIncoming(ServerboundPacket $packet) : ?ServerboundPacket
	{
		v844Debug::logOnce("in:" . $packet::class, "incoming", "no v844 translation for " . $packet->getName() . " (pid " . $packet->pid() . "), decoded with the 2193 layout");
		return parent::incoming($packet);
	}

	private function translatePlayerList(PlayerListPacket $packet) : v844PlayerListPacket
	{
		if ($packet->type === PlayerListPacket::TYPE_ADD) {
			$entries = [];
			foreach ($packet->entries as $entry) {
				if ($entry->skinData !== null) {
					$entry = clone $entry;
					$this->adaptSkinData($entry->skinData);
				}
				$entries[] = $entry;
			}
			$packet->entries = $entries;
		}

		return v844PlayerListPacket::fromLatest($packet);
	}

	private function translatePlayerSkin(PlayerSkinPacket $packet) : v844PlayerSkinPacket
	{
		$this->adaptSkinData($packet->skin);
		return v844PlayerSkinPacket::fromLatest($packet);
	}

	private function translateCraftingData() : ?ClientboundPacket
	{
		return $this->getStaticPacketCache()->getCraftingDataPacket();
	}

	public function outcomingCreativeContent(CreativeContentPacket $packet, CreativeInventory $inventory) : ?ClientboundPacket
	{
		v844Debug::log("outcoming", "inventory packet " . $packet->getName() . " (pid " . $packet->pid() . ") -> v844 translation");

		return v844CreativeContentPacket::fromLatest($packet, $this->getTypeConverter(), $inventory);
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{
		if (v844UnsupportedPackets::isUnsupported($packet->pid())) {
			v844Debug::logOnce("unsupported:" . $packet->pid(), "outcoming", "dropping " . $packet->getName() . " (pid " . $packet->pid() . "): not present in the 844 packet table");
			return null;
		}

		if ($packet instanceof InventorySlotPacket || $packet instanceof InventoryContentPacket ||
			$packet instanceof ContainerOpenPacket || $packet instanceof MobEquipmentPacket ||
			$packet instanceof ItemStackResponsePacket || $packet instanceof CreativeContentPacket) {
			v844Debug::log("outcoming", "inventory packet " . $packet->getName() . " (pid " . $packet->pid() . ") -> v844 translation");
		}

		if ($packet instanceof UpdateBlockPacket) {
			$translated = v844UpdateBlockPacket::fromLatest($packet);
			$translated->blockRuntimeId = $this->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(
				MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->blockRuntimeId)
			);
			return $translated;
		}

		if ($packet instanceof LevelEventPacket) {
			return v844ParticleTranslator::getInstance()->translate($packet, $this->getTypeConverter());
		}

		if ($packet instanceof LevelSoundEventPacket) {
			return v844LevelSoundTranslator::getInstance()->translate($packet, $this->getTypeConverter());
		}

		if ($packet instanceof ClientboundUpdateSoundDataPacket) {
			v844Debug::logOnce("out:" . $packet::class, "outcoming", "dropping " . $packet->getName() . " (pid " . $packet->pid() . "): 2193 layout has no 844 equivalent");
			return null;
		}

		return match (true) {
			$packet instanceof LevelChunkPacket => v844LevelChunkPacket::fromLatest($packet),
			$packet instanceof PlayerActionPacket => v844PlayerActionPacket::fromLatest($packet),
			$packet instanceof PlaySoundPacket => v844PlaySoundPacket::fromLatest($packet),
			$packet instanceof SetSpawnPositionPacket => v844SetSpawnPositionPacket::fromLatest($packet),
			$packet instanceof SetScorePacket => v844SetScorePacket::fromLatest($packet),
			$packet instanceof ContainerOpenPacket => v844ContainerOpenPacket::fromLatest($packet),
			$packet instanceof BlockEventPacket => v844BlockEventPacket::fromLatest($packet),
			$packet instanceof BossEventPacket => v844BossEventPacket::fromLatest($packet),
			$packet instanceof BlockActorDataPacket => v844BlockActorDataPacket::fromLatest($packet),
			$packet instanceof AddVolumeEntityPacket => v844AddVolumeEntityPacket::fromLatest($packet),
			$packet instanceof AnvilDamagePacket => v844AnvilDamagePacket::fromLatest($packet),
			$packet instanceof OpenSignPacket => v844OpenSignPacket::fromLatest($packet),
			$packet instanceof InventoryTransactionPacket => v844InventoryTransactionPacket::fromLatest($packet),
			$packet instanceof AnimatePacket => v844AnimatePacket::fromLatest($packet),
			$packet instanceof BiomeDefinitionListPacket => $this->getStaticPacketCache()->getBiomeDefinitionListPacket(),
			$packet instanceof ResourcePackStackPacket => v844ResourcePackStackPacket::fromLatest($packet),
			$packet instanceof StartGamePacket => v844StartGamePacket::fromLatest($packet),
			$packet instanceof TextPacket => v844TextPacket::fromLatest($packet),
			$packet instanceof MobEffectPacket => v844MobEffectPacket::fromLatest($packet),
			$packet instanceof MoveActorDeltaPacket => v844MoveActorDeltaPacket::fromLatest($packet),
			$packet instanceof SetScoreboardIdentityPacket => v844SetScoreboardIdentityPacket::fromLatest($packet),
			$packet instanceof AddPlayerPacket => v844AddPlayerPacket::fromLatest($packet),
			$packet instanceof AddActorPacket => v844AddActorPacket::fromLatest($packet),
			$packet instanceof SetActorDataPacket => v844SetActorDataPacket::fromLatest($packet),
			$packet instanceof MovePlayerPacket => v844MovePlayerPacket::fromLatest($packet),
			$packet instanceof PlayerListPacket => $this->translatePlayerList($packet),
			$packet instanceof PlayerSkinPacket => $this->translatePlayerSkin($packet),
			$packet instanceof ResourcePacksInfoPacket => v844ResourcePacksInfoPacket::fromLatest($packet),
			$packet instanceof TransferPacket => v844TransferPacket::fromLatest($packet),
			$packet instanceof AvailableActorIdentifiersPacket => $this->getStaticPacketCache()->getActorIdentifiersPacket(),
			$packet instanceof AvailableCommandsPacket => v844AvailableCommandsPacket::fromLatest($packet),
			$packet instanceof CraftingDataPacket => $this->translateCraftingData(),
			$packet instanceof CreativeContentPacket => v844CreativeContentPacket::fromLatest($packet, $this->getTypeConverter()),
			$packet instanceof DisconnectPacket => v844DisconnectPacket::fromLatest($packet),
			$packet instanceof ActorEventPacket => v844ActorEventPacket::fromLatest($packet, $this->getTypeConverter()),
			$packet instanceof InventorySlotPacket => v844InventorySlotPacket::fromLatest($packet, $this->getTypeConverter()),
			$packet instanceof InventoryContentPacket => v844InventoryContentPacket::fromLatest($packet, $this->getTypeConverter()),
			$packet instanceof ItemStackResponsePacket => v844ItemStackResponsePacket::fromLatest($packet),
			$packet instanceof MobEquipmentPacket => v844MobEquipmentPacket::fromLatest($packet, $this->getTypeConverter()),
			$packet instanceof MobArmorEquipmentPacket => v844MobArmorEquipmentPacket::fromLatest($packet, $this->getTypeConverter()),
			$packet instanceof AddItemActorPacket => v844AddItemActorPacket::fromLatest($packet, $this->getTypeConverter()),
			$packet instanceof ItemRegistryPacket => ItemRegistryPacket::create($this->getTypeConverter()->getItemTypeDictionary()->getEntries()),
			$packet instanceof PlayerEnchantOptionsPacket => v844PlayerEnchantOptionsPacket::fromLatest($packet),
			default => $this->fallbackOutcoming($packet)
		};

	}
}
