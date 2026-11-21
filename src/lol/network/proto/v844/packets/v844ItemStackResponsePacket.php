<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\packets\types\inventory\stackresponse\v844ItemStackResponse;
use lol\network\proto\v844\structure\v844Debug;
use lol\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use function array_map;
use function count;
use function strlen;
use function substr;

class v844ItemStackResponsePacket extends ItemStackResponsePacket
{

	public static function fromLatest(ItemStackResponsePacket $packet) : self
	{
		$result = new self();
		$responses = $packet->getResponses();
		ReflectionUtils::setProperty(ItemStackResponsePacket::class, $result, "responses", $responses);

		v844Debug::log("ItemStackResponse", "translating " . count($responses) . " response(s) from 2193 to 844: " . v844Debug::summariseResponses($responses));
		v844Debug::log("ItemStackResponse", "slots: " . v844Debug::dumpResponses($responses));

		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$responses = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$responses[] = v844ItemStackResponse::read($in)->toLatest();
		}
		ReflectionUtils::setProperty(ItemStackResponsePacket::class, $this, "responses", $responses);

		v844Debug::log("ItemStackResponse", "decoded " . count($responses) . " response(s) from an 844 payload: " . v844Debug::summariseResponses($responses));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		$responses = array_map(
			static fn(ItemStackResponse $response) : v844ItemStackResponse => v844ItemStackResponse::fromLatest($response),
			$this->getResponses()
		);

		$start = $out->getOffset();

		VarInt::writeUnsignedInt($out, count($responses));
		foreach ($responses as $response) {
			$response->write($out);
		}

		$written = substr($out->getData(), $start, $out->getOffset() - $start);
		v844Debug::log("ItemStackResponse", "encoded payload (" . strlen($written) . " bytes, 844 layout): " . v844Debug::hex($written));
	}
}
