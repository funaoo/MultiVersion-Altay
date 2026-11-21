<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\structure\v844ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use function count;

class v844ResourcePackStackPacket extends ResourcePackStackPacket
{

	protected array $behaviorPackStack = [];

	public static function fromLatest(ResourcePackStackPacket $pk) : self
	{
		$npk = new self();
		$npk->resourcePackStack = $pk->resourcePackStack;
		$npk->mustAccept = $pk->mustAccept;
		$npk->baseGameVersion = v844ProtocolInfo::MINECRAFT_VERSION_NETWORK;
		$npk->experiments = $pk->experiments;
		$npk->useVanillaEditorPacks = $pk->useVanillaEditorPacks;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->mustAccept = CommonTypes::getBool($in);
		$behaviorPackCount = VarInt::readUnsignedInt($in);
		while ($behaviorPackCount-- > 0) {
			$this->behaviorPackStack[] = ResourcePackStackEntry::read($in);
		}

		$resourcePackCount = VarInt::readUnsignedInt($in);
		while ($resourcePackCount-- > 0) {
			$this->resourcePackStack[] = ResourcePackStackEntry::read($in);
		}

		$this->baseGameVersion = CommonTypes::getString($in);
		$this->experiments = Experiments::read($in);
		$this->useVanillaEditorPacks = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putBool($out, $this->mustAccept);

		VarInt::writeUnsignedInt($out, count($this->behaviorPackStack));
		foreach ($this->behaviorPackStack as $entry) {
			$entry->write($out);
		}

		VarInt::writeUnsignedInt($out, count($this->resourcePackStack));
		foreach ($this->resourcePackStack as $entry) {
			$entry->write($out);
		}

		CommonTypes::putString($out, $this->baseGameVersion);
		$this->experiments->write($out);
		CommonTypes::putBool($out, $this->useVanillaEditorPacks);
	}
}
