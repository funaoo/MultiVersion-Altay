<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\utils\Limits;
use function count;
use const PHP_INT_MAX;

class v844LevelChunkPacket extends LevelChunkPacket {

	private const MAX_BLOB_HASHES = 64;

	private const CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT = Limits::UINT32_MAX;

	private const CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT = Limits::UINT32_MAX - 1;

	private ChunkPosition $v844ChunkPosition;
	private int $v844DimensionId;
	private int $v844SubChunkCount;
	private ?int $v844ClientRequestSubChunkLimit;
	private bool $v844CacheEnabled;
	private array $v844UsedBlobHashes = [];
	private string $v844ExtraPayload;

	static public function fromLatest(LevelChunkPacket $packet) : self {
		$result = new self();
		$result->v844ChunkPosition = $packet->getChunkPosition();
		$result->v844DimensionId = $packet->getDimensionId();
		$result->v844SubChunkCount = $packet->getSubChunkCount();
		$result->v844ClientRequestSubChunkLimit = $packet->getClientRequestSubChunkLimit();
		$result->v844CacheEnabled = $packet->isCacheEnabled();
		$result->v844UsedBlobHashes = $packet->getUsedBlobHashes();
		$result->v844ExtraPayload = $packet->getExtraPayload();
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->v844ChunkPosition = ChunkPosition::read($in);
		$this->v844DimensionId = VarInt::readSignedInt($in);

		$subChunkCountButNotReally = VarInt::readUnsignedInt($in);
		if($subChunkCountButNotReally === self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT){
			$this->v844SubChunkCount = PHP_INT_MAX;
			$this->v844ClientRequestSubChunkLimit = PHP_INT_MAX;
		}elseif($subChunkCountButNotReally === self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT){
			$this->v844ClientRequestSubChunkLimit = LE::readUnsignedShort($in);
			$this->v844SubChunkCount = $this->v844ClientRequestSubChunkLimit;
		}else{
			$this->v844SubChunkCount = $subChunkCountButNotReally;
			$this->v844ClientRequestSubChunkLimit = null;
		}

		$this->v844CacheEnabled = CommonTypes::getBool($in);
		$this->v844UsedBlobHashes = [];
		if($this->v844CacheEnabled){
			$count = VarInt::readUnsignedInt($in);
			if($count > self::MAX_BLOB_HASHES){
				throw new PacketDecodeException("Expected at most " . self::MAX_BLOB_HASHES . " blob hashes, got " . $count);
			}
			for($i = 0; $i < $count; ++$i){
				$this->v844UsedBlobHashes[] = LE::readUnsignedLong($in);
			}
		}

		$this->v844ExtraPayload = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		$this->v844ChunkPosition->write($out);
		VarInt::writeSignedInt($out, $this->v844DimensionId);

		if($this->v844ClientRequestSubChunkLimit === null){
			VarInt::writeUnsignedInt($out, $this->v844SubChunkCount);
		}elseif($this->v844ClientRequestSubChunkLimit === PHP_INT_MAX){
			VarInt::writeUnsignedInt($out, self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT);
		}else{
			VarInt::writeUnsignedInt($out, self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT);
			LE::writeUnsignedShort($out, $this->v844ClientRequestSubChunkLimit);
		}

		CommonTypes::putBool($out, $this->v844CacheEnabled);
		if($this->v844CacheEnabled){
			VarInt::writeUnsignedInt($out, count($this->v844UsedBlobHashes));
			foreach($this->v844UsedBlobHashes as $hash){
				LE::writeUnsignedLong($out, $hash);
			}
		}

		CommonTypes::putString($out, $this->v844ExtraPayload);
	}
}
