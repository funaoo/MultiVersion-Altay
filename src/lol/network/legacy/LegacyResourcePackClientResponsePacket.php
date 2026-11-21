<?php

declare(strict_types=1);

namespace lol\network\legacy;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class LegacyResourcePackClientResponsePacket extends ResourcePackClientResponsePacket {

	public const NETWORK_ID = -1;

	private const STATUS_OFFSET = 1;

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->status = Byte::readUnsigned($in) - self::STATUS_OFFSET;
		$this->packIds = [];
		$entryCount = LE::readUnsignedShort($in);
		while ($entryCount-- > 0) {
			$this->packIds[] = CommonTypes::getString($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		Byte::writeUnsigned($out, $this->status + self::STATUS_OFFSET);
		LE::writeUnsignedShort($out, count($this->packIds));
		foreach ($this->packIds as $id) {
			CommonTypes::putString($out, $id);
		}
	}
}
