<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\structure\v844SkinSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844PlayerSkinPacket extends PlayerSkinPacket {

	static public function fromLatest(PlayerSkinPacket $packet) : self {
		$result = new self();
		$result->uuid = $packet->uuid;
		$result->oldSkinName = $packet->oldSkinName;
		$result->newSkinName = $packet->newSkinName;
		$result->skin = $packet->skin;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->uuid = CommonTypes::getUUID($in);
		$this->skin = v844SkinSerializer::getSkin($in);
		$this->newSkinName = CommonTypes::getString($in);
		$this->oldSkinName = CommonTypes::getString($in);
		$this->skin->setVerified(CommonTypes::getBool($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putUUID($out, $this->uuid);
		v844SkinSerializer::putSkin($out, $this->skin);
		CommonTypes::putString($out, $this->newSkinName);
		CommonTypes::putString($out, $this->oldSkinName);
		CommonTypes::putBool($out, $this->skin->isVerified());
	}
}
