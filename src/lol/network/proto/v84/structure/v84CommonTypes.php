<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use function strlen;

final class v84CommonTypes
{

	private function __construct()
	{
	}

	public static function putString(ByteBufferWriter $out, string $value) : void
	{
		BE::writeUnsignedShort($out, strlen($value));
		$out->writeByteArray($value);
	}

	public static function getString(ByteBufferReader $in) : string
	{
		return $in->readByteArray(BE::readUnsignedShort($in));
	}
}
