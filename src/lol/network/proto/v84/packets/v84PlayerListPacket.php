<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\packets\types\v84PlayerListEntry;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84CommonTypes;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use function count;

class v84PlayerListPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::PLAYER_LIST_PACKET;

	public const TYPE_ADD = 0;

	public const TYPE_REMOVE = 1;

	private const UUID_LENGTH = 16;

	public int $type = self::TYPE_ADD;

	public array $entries = [];

	static public function add(array $entries) : self
	{
		return self::create(self::TYPE_ADD, $entries);
	}

	static public function remove(array $entries) : self
	{
		return self::create(self::TYPE_REMOVE, $entries);
	}

	static private function create(int $type, array $entries) : self
	{
		$npk = new self();
		$npk->type = $type;
		$npk->entries = $entries;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->type = Byte::readUnsigned($in);
		$count = BE::readUnsignedInt($in);
		$this->entries = [];
		for ($index = 0; $index < $count; ++$index) {
			$this->entries[] = $this->type === self::TYPE_ADD ? $this->decodeAddition($in) : v84PlayerListEntry::removal($in->readByteArray(self::UUID_LENGTH));
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->type);
		BE::writeUnsignedInt($out, count($this->entries));
		foreach ($this->entries as $entry) {
			$out->writeByteArray($entry->uuidBytes);
			if ($this->type !== self::TYPE_ADD) {
				continue;
			}

			BE::writeSignedLong($out, $entry->actorId);
			v84CommonTypes::putString($out, $entry->name);
			v84CommonTypes::putString($out, $entry->skinId);
			v84CommonTypes::putString($out, $entry->skinData);
		}
	}

	private function decodeAddition(ByteBufferReader $in) : v84PlayerListEntry
	{
		return new v84PlayerListEntry(
			$in->readByteArray(self::UUID_LENGTH),
			BE::readSignedLong($in),
			v84CommonTypes::getString($in),
			v84CommonTypes::getString($in),
			v84CommonTypes::getString($in)
		);
	}
}
