<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\inventory\stackresponse;

use InvalidArgumentException;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use function array_map;
use function count;

final class v844ItemStackResponse
{

	public const RESULT_OK = 0;
	public const RESULT_ERROR = 1;

	public function __construct(
		private int   $result,
		private int   $requestId,
		private array $containerInfos = []
	)
	{
		if ($this->result !== self::RESULT_OK && count($this->containerInfos) !== 0) {
			throw new InvalidArgumentException("Container infos must be empty if rejecting the request");
		}
	}

	public static function fromLatest(ItemStackResponse $response) : self
	{
		$result = $response->getResult();
		$containerInfos = $result === self::RESULT_OK ?
			array_map(
				static fn($containerInfo) : v844ItemStackResponseContainerInfo => v844ItemStackResponseContainerInfo::fromLatest($containerInfo),
				$response->getContainerInfos()
			) :
			[];

		return new self($result, $response->getRequestId(), $containerInfos);
	}

	public function toLatest() : ItemStackResponse
	{
		return new ItemStackResponse(
			$this->result,
			$this->requestId,
			array_map(
				static fn(v844ItemStackResponseContainerInfo $containerInfo) => $containerInfo->toLatest(),
				$this->containerInfos
			)
		);
	}

	public static function read(ByteBufferReader $in) : self
	{
		$result = Byte::readUnsigned($in);
		$requestId = CommonTypes::readItemStackRequestId($in);
		$containerInfos = [];
		if ($result === self::RESULT_OK) {
			for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
				$containerInfos[] = v844ItemStackResponseContainerInfo::read($in);
			}
		}
		return new self($result, $requestId, $containerInfos);
	}

	public function write(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->result);
		CommonTypes::writeItemStackRequestId($out, $this->requestId);
		if ($this->result === self::RESULT_OK) {
			VarInt::writeUnsignedInt($out, count($this->containerInfos));
			foreach ($this->containerInfos as $containerInfo) {
				$containerInfo->write($out);
			}
		}
	}

	public function getResult() : int
	{
		return $this->result;
	}

	public function getRequestId() : int
	{
		return $this->requestId;
	}

	public function getContainerInfos() : array
	{
		return $this->containerInfos;
	}
}
