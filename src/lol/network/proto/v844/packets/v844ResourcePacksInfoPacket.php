<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use Ramsey\Uuid\UuidInterface;
use function count;

class v844ResourcePacksInfoPacket extends ResourcePacksInfoPacket {

	private UuidInterface $v844WorldTemplateId;
	private string $v844WorldTemplateVersion;
	private bool $v844ForceDisableVibrantVisuals;

	static public function fromLatest(ResourcePacksInfoPacket $packet) : self {
		$result = new self();
		$result->resourcePackEntries = $packet->resourcePackEntries;
		$result->mustAccept = $packet->mustAccept;
		$result->hasAddons = $packet->hasAddons;
		$result->hasScripts = $packet->hasScripts;
		$result->v844WorldTemplateId = $packet->getWorldTemplateId();
		$result->v844WorldTemplateVersion = $packet->getWorldTemplateVersion();
		$result->v844ForceDisableVibrantVisuals = $packet->isForceDisableVibrantVisuals();
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->mustAccept = CommonTypes::getBool($in);
		$this->hasAddons = CommonTypes::getBool($in);
		$this->hasScripts = CommonTypes::getBool($in);
		$this->v844ForceDisableVibrantVisuals = CommonTypes::getBool($in);
		$this->v844WorldTemplateId = CommonTypes::getUUID($in);
		$this->v844WorldTemplateVersion = CommonTypes::getString($in);

		$this->resourcePackEntries = [];
		$count = LE::readUnsignedShort($in);
		while($count-- > 0){
			$this->resourcePackEntries[] = ResourcePackInfoEntry::read($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putBool($out, $this->mustAccept);
		CommonTypes::putBool($out, $this->hasAddons);
		CommonTypes::putBool($out, $this->hasScripts);
		CommonTypes::putBool($out, $this->v844ForceDisableVibrantVisuals);
		CommonTypes::putUUID($out, $this->v844WorldTemplateId);
		CommonTypes::putString($out, $this->v844WorldTemplateVersion);

		LE::writeUnsignedShort($out, count($this->resourcePackEntries));
		foreach($this->resourcePackEntries as $entry){
			$entry->write($out);
		}
	}
}
