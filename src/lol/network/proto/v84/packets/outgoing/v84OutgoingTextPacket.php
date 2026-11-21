<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84CommonTypes;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\proto\v84\text\v84TextType;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use function array_slice;
use function count;
use function substr;

class v84OutgoingTextPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::TEXT_PACKET;

	private const MAX_STRING_LENGTH = 0xffff;

	private const MAX_PARAMETERS = 0xff;

	public int $type = v84TextType::RAW;

	public string $source = '';

	public string $message = '';

	public array $parameters = [];

	static public function fromParts(int $type, string $source, string $message, array $parameters = []) : self
	{
		$npk = new self();
		$npk->type = $type;
		$npk->source = $source;
		$npk->message = $message;
		$npk->parameters = $parameters;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->type = Byte::readUnsigned($in);
		switch ($this->type) {
			case v84TextType::POPUP:
			case v84TextType::CHAT:
				$this->source = v84CommonTypes::getString($in);
				$this->message = v84CommonTypes::getString($in);
				return;
			case v84TextType::TRANSLATION:
				$this->message = v84CommonTypes::getString($in);
				$count = Byte::readUnsigned($in);
				for ($i = 0; $i < $count; ++$i) {
					$this->parameters[] = v84CommonTypes::getString($in);
				}
				return;
			default:
				$this->message = v84CommonTypes::getString($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->type);
		switch ($this->type) {
			case v84TextType::POPUP:
			case v84TextType::CHAT:
				v84CommonTypes::putString($out, self::clip($this->source));
				v84CommonTypes::putString($out, self::clip($this->message));
				return;
			case v84TextType::TRANSLATION:
				$this->encodeTranslation($out);
				return;
			default:
				v84CommonTypes::putString($out, self::clip($this->message));
		}
	}

	private function encodeTranslation(ByteBufferWriter $out) : void
	{
		$parameters = array_slice($this->parameters, 0, self::MAX_PARAMETERS);
		v84CommonTypes::putString($out, self::clip($this->message));
		Byte::writeUnsigned($out, count($parameters));
		foreach ($parameters as $parameter) {
			v84CommonTypes::putString($out, self::clip($parameter));
		}
	}

	private static function clip(string $value) : string
	{
		return substr($value, 0, self::MAX_STRING_LENGTH);
	}
}
