<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\packets\types\inventory\stackrequest\v844ItemStackRequest;
use lol\network\proto\v844\structure\v844Debug;
use lol\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use function count;
use function implode;

class v844ItemStackRequestPacket extends ItemStackRequestPacket
{

	public static function fromLatest(ItemStackRequestPacket $packet) : self
	{
		$result = new self();
		ReflectionUtils::setProperty(ItemStackRequestPacket::class, $result, "requests", $packet->getRequests());
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$requests = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$requests[] = v844ItemStackRequest::read($in);
		}
		ReflectionUtils::setProperty(ItemStackRequestPacket::class, $this, "requests", $requests);

		v844Debug::log("ItemStackRequest", "decoded " . count($requests) . " request(s) from an 844 payload: " . self::summarise($requests));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		$requests = $this->getRequests();
		VarInt::writeUnsignedInt($out, count($requests));
		foreach ($requests as $request) {
			v844ItemStackRequest::write($out, $request);
		}
	}

	private static function summarise(array $requests) : string
	{
		$parts = [];
		foreach ($requests as $request) {
			$actions = [];
			foreach ($request->getActions() as $action) {
				$actions[] = $action::class . "(type " . $action->getTypeId() . ")";
			}
			$parts[] = "#" . $request->getRequestId() . " [" . implode(", ", $actions) . "]";
		}
		return $parts === [] ? "(none)" : implode(" | ", $parts);
	}
}
