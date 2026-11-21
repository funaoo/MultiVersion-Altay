<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\packets\types\v84AttributeEntry;
use lol\network\proto\v84\structure\v84AttributeTable;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84CommonTypes;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use function count;

class v84UpdateAttributesPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::UPDATE_ATTRIBUTES_PACKET;

	public int $actorId = 0;

	public array $entries = [];

	static public function fromLatest(UpdateAttributesPacket $packet, int $actorId) : ?self
	{
		$entries = [];
		foreach ($packet->entries as $attribute) {
			$name = v84AttributeTable::nameOf($attribute->getId());
			if ($name === null) {
				continue;
			}

			$entries[] = new v84AttributeEntry($name, $attribute->getMin(), $attribute->getMax(), $attribute->getCurrent());
		}

		if ($entries === []) {
			return null;
		}

		$npk = new self();
		$npk->actorId = $actorId;
		$npk->entries = $entries;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->entries = [];
		for ($remaining = BE::readUnsignedShort($in); $remaining > 0; --$remaining) {
			$min = BE::readFloat($in);
			$max = BE::readFloat($in);
			$current = BE::readFloat($in);
			$this->entries[] = new v84AttributeEntry(v84CommonTypes::getString($in), $min, $max, $current);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		BE::writeUnsignedShort($out, count($this->entries));
		foreach ($this->entries as $entry) {
			BE::writeFloat($out, $entry->min);
			BE::writeFloat($out, $entry->max);
			BE::writeFloat($out, $entry->current);
			v84CommonTypes::putString($out, $entry->name);
		}
	}
}
