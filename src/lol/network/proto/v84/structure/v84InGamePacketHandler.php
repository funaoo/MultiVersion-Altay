<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use lol\network\proto\v84\actions\v84PlayerActions;
use lol\network\proto\v84\crafting\v84CraftingEvents;
use lol\network\proto\v84\crafting\v84CraftingRequest;
use lol\network\proto\v84\equipment\v84HotbarSelection;
use lol\network\proto\v84\events\v84AnimateAction;
use lol\network\proto\v84\events\v84EntityEventId;
use lol\network\proto\v84\frames\v84ItemFrameStrikes;
use lol\network\proto\v84\interaction\v84BlockUse;
use lol\network\proto\v84\interaction\v84InteractAction;
use lol\network\proto\v84\interaction\v84ItemConsumption;
use lol\network\proto\v84\interaction\v84ItemUse;
use lol\network\proto\v84\interaction\v84VehicleActions;
use lol\network\proto\v84\interaction\v84WorldBlockResync;
use lol\network\proto\v84\inventory\v84FloatingRelease;
use lol\network\proto\v84\inventory\v84InventoryChanges;
use lol\network\proto\v84\packets\v84AnimatePacket;
use lol\network\proto\v84\packets\v84ContainerClosePacket;
use lol\network\proto\v84\packets\v84ContainerSetSlotPacket;
use lol\network\proto\v84\packets\v84CraftingEventPacket;
use lol\network\proto\v84\packets\v84DropItemPacket;
use lol\network\proto\v84\packets\v84EntityEventPacket;
use lol\network\proto\v84\packets\v84InteractPacket;
use lol\network\proto\v84\packets\v84ItemFrameDropItemPacket;
use lol\network\proto\v84\packets\v84MobEquipmentPacket;
use lol\network\proto\v84\packets\v84PlayerActionPacket;
use lol\network\proto\v84\packets\v84RemoveBlockPacket;
use lol\network\proto\v84\packets\v84TextPacket;
use lol\network\proto\v84\packets\v84UseItemPacket;
use lol\network\proto\v84\text\v84ChatInput;
use lol\network\proto\v84\windows\v84LocalWindowClose;
use lol\network\proto\v84\windows\v84WindowIds;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\player\Player;

final class v84InGamePacketHandler extends InGamePacketHandler
{

	private const RESYNC_MAX_DISTANCE_SQUARED = v84WorldBlockResync::MAX_DISTANCE_SQUARED;

	public function __construct(private Player $player, private NetworkSession $session, private InventoryManager $inventoryManager, private v84InventoryChanges $inventoryChanges, private v84BlockUse $blockUse, private v84ItemUse $itemUse, private v84ItemConsumption $itemConsumption, private v84VehicleActions $vehicleActions, private v84PlayerActions $playerActions, private v84CraftingEvents $craftingEvents, private v84ItemFrameStrikes $itemFrameStrikes)
	{
		parent::__construct($player, $session, $inventoryManager);
	}

	public function handleRemoveBlock(v84RemoveBlockPacket $packet) : bool
	{
		$position = new Vector3($packet->x, $packet->y, $packet->z);
		if ($this->player->getCurrentWindow() !== null || !$this->attackCreativeBlock($position) || !$this->player->breakBlock($position)) {
			$this->resyncAround($position);
		}

		return true;
	}

	public function handleV84Text(v84TextPacket $packet) : bool
	{
		v84ChatInput::deliver($this->player, $packet);
		return true;
	}

	public function handleSwingAnimation(v84AnimatePacket $packet) : bool
	{
		if ($packet->action !== v84AnimateAction::SWING_ARM || !$this->player->isAlive() || !$this->player->isConnected()) {
			return true;
		}

		$this->player->broadcastAnimation(new ArmSwingAnimation($this->player), $this->player->getViewers());
		return true;
	}

	public function handleHeldSlotSelection(v84MobEquipmentPacket $packet) : bool
	{
		if (!$this->player->isAlive() || !$this->player->isConnected()) {
			return true;
		}

		$this->inventoryManager->onClientSelectHotbarSlot($packet->selectedSlot);
		if (!$this->player->selectHotbarSlot($packet->selectedSlot)) {
			$this->inventoryManager->syncSelectedHotbarSlot();
			return true;
		}

		$inventory = $this->player->getInventory();
		if (v84HotbarSelection::isRemap($packet->selectedSlot, $packet->slot, $inventory->getSize())) {
			$this->inventoryManager->syncContents($inventory);
		}

		return true;
	}

	public function handleContainerSetSlot(v84ContainerSetSlotPacket $packet) : bool
	{
		$this->inventoryChanges->changeSlot($packet->windowId, $packet->slot, $packet->item);
		return true;
	}

	public function handleV84ContainerClose(v84ContainerClosePacket $packet) : bool
	{
		if ($this->inventoryChanges instanceof v84FloatingRelease) {
			$this->inventoryChanges->release();
		}

		if (!$this->player->isConnected()) {
			return true;
		}

		$networkId = v84WindowIds::fromClient($packet->windowId);
		if ($networkId !== null) {
			$this->inventoryManager->onClientRemoveWindow($networkId);
			return true;
		}

		if (v84LocalWindowClose::closes($packet->windowId, $this->player->getCurrentWindow())) {
			$this->inventoryManager->onClientRemoveWindow($this->inventoryManager->getCurrentWindowId());
		}

		return true;
	}

	public function handleV84CraftingEvent(v84CraftingEventPacket $packet) : bool
	{
		$this->craftingEvents->receive(new v84CraftingRequest($packet->windowId, $packet->type, $packet->recipeId, $packet->input, $packet->output));
		return true;
	}

	public function handleDropItem(v84DropItemPacket $packet) : bool
	{
		$this->inventoryChanges->dropItem($packet->item);
		return true;
	}

	public function handleUseItem(v84UseItemPacket $packet) : bool
	{
		if ($packet->face === v84UseItemPacket::FACE_AIR) {
			$this->itemUse->useInAir($packet->item);
			return true;
		}

		$this->blockUse->useItemOn(
			new Vector3($packet->x, $packet->y, $packet->z),
			$packet->face,
			new Vector3($packet->clickX, $packet->clickY, $packet->clickZ),
			$packet->item
		);
		return true;
	}

	public function handleEntityEvent(v84EntityEventPacket $packet) : bool
	{
		if ($packet->event === v84EntityEventId::USE_ITEM) {
			$this->itemConsumption->finish();
		}

		return true;
	}

	public function handleV84Interact(v84InteractPacket $packet) : bool
	{
		match ($packet->action) {
			v84InteractAction::RIGHT_CLICK => $this->vehicleActions->mount($packet->targetId),
			v84InteractAction::LEFT_CLICK => $this->vehicleActions->breakVehicle($packet->targetId),
			v84InteractAction::LEAVE_VEHICLE => $this->vehicleActions->dismount(),
			default => null
		};

		return true;
	}

	public function handleItemFrameDropItem(v84ItemFrameDropItemPacket $packet) : bool
	{
		$this->itemFrameStrikes->strike(new Vector3($packet->x, $packet->y, $packet->z));
		return true;
	}

	public function handleV84PlayerAction(v84PlayerActionPacket $packet) : bool
	{
		$this->playerActions->perform($packet->action, new Vector3($packet->x, $packet->y, $packet->z), $packet->face);
		return true;
	}

	private function attackCreativeBlock(Vector3 $position) : bool
	{
		return !$this->player->isCreative() || $this->player->attackBlock($position, Facing::UP);
	}

	private function resyncAround(Vector3 $position) : void
	{
		if ($position->distanceSquared($this->player->getLocation()) >= self::RESYNC_MAX_DISTANCE_SQUARED) {
			return;
		}

		$blocks = $position->sidesArray();
		$blocks[] = $position;
		foreach ($this->player->getWorld()->createBlockUpdatePackets($blocks) as $packet) {
			$this->session->sendDataPacket($packet);
		}
	}
}
