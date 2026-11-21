<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\global\MVTypeConverter;
use lol\network\proto\v844\packets\types\inventory\creative\v844CreativeGroupEntry;
use lol\network\proto\v844\packets\types\inventory\creative\v844CreativeItemEntry;
use lol\network\proto\v844\structure\v844Debug;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\inventory\CreativeInventory;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use function count;

class v844CreativeContentPacket extends CreativeContentPacket
{

	public static function fromLatest(CreativeContentPacket $packet, MVTypeConverter $converter, ?CreativeInventory $inventory = null) : self
	{
		$inventory ??= CreativeInventory::getInstance();

		$kept = [];
		$skipped = 0;
		foreach ($packet->getItems() as $entry) {
			$core = $inventory->getItem($entry->getEntryId());
			if ($core === null) {
				++$skipped;
				v844Debug::logOnce("creative-entry-missing", "CreativeContent", "entry #" . $entry->getEntryId() . " has no matching item in the creative inventory, leaving it out");
				continue;
			}

			$net = $converter->coreItemStackToNetQuiet($core);
			if ($net === null || $net->isNull()) {
				++$skipped;
				v844Debug::logOnce("creative-item-unmapped:" . $core->getName(), "CreativeContent", "\"" . $core->getName() . "\" has no 844 mapping, leaving it out of the creative inventory");
				continue;
			}

			$kept[] = [$entry->getEntryId(), $net, $entry->getGroupId()];
		}

		$used = [];
		foreach ($kept as [, , $groupId]) {
			$used[$groupId] = true;
		}

		$groups = [];
		$groupMap = [];
		foreach ($packet->getGroups() as $index => $group) {
			if (!isset($used[$index])) {
				continue;
			}

			$groupMap[$index] = count($groups);

			$icon = $group->getIcon();
			if ($converter->isUnmappedPlaceholder($icon)) {
				v844Debug::logOnce("creative-group-icon-unmapped:" . $index, "CreativeContent", "group #$index (" . $group->getCategoryName() . ") has an icon with no 844 mapping, sending its items ungrouped");
				$groups[] = new v844CreativeGroupEntry($group->getCategoryId(), "", ItemStack::null());
				continue;
			}

			$groups[] = new v844CreativeGroupEntry($group->getCategoryId(), $group->getCategoryName(), $icon);
		}

		$items = [];
		foreach ($kept as [$entryId, $net, $groupId]) {
			if (!isset($groupMap[$groupId])) {
				++$skipped;
				v844Debug::logOnce("creative-group-missing:" . $groupId, "CreativeContent", "group #$groupId is not part of the packet, leaving its items out");
				continue;
			}

			$items[] = new v844CreativeItemEntry($entryId, $net, $groupMap[$groupId]);
		}

		$result = new self();
		$result->v844Groups = $groups;
		$result->v844Items = $items;

		v844Debug::log("CreativeContent", "translated " . count($groups) . " group(s) and " . count($items) . " item(s) to 844, left out $skipped item(s) without a mapping");

		return $result;
	}

	private array $v844Groups = [];
	private array $v844Items = [];

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$groups = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$groups[] = v844CreativeGroupEntry::read($in);
		}

		$items = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$items[] = v844CreativeItemEntry::read($in);
		}

		$this->v844Groups = $groups;
		$this->v844Items = $items;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->v844Groups));
		foreach ($this->v844Groups as $group) {
			$group->write($out);
		}

		VarInt::writeUnsignedInt($out, count($this->v844Items));
		foreach ($this->v844Items as $item) {
			$item->write($out);
		}
	}
}
