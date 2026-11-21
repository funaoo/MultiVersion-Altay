<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use GlobalLogger;
use lol\network\proto\v84\structure\v84WorldLimits;
use pocketmine\block\tile\Chest as ChestTile;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\format\Chunk;
use Throwable;

final class v84TileSerializer
{

	private readonly LittleEndianNbtSerializer $nbt;

	private array $reported = [];

	public function __construct(
		private readonly v84TileTable $table
	)
	{
		$this->nbt = new LittleEndianNbtSerializer();
	}

	public function serialize(Chunk $chunk) : string
	{
		$tiles = '';
		foreach ($chunk->getTiles() as $tile) {
			if ($tile instanceof Spawnable) {
				$tiles .= $this->encodeTile($tile);
			}
		}

		return $tiles;
	}

	public function encodeCompound(CompoundTag $modern) : string
	{
		return $this->encodeWith($modern, null);
	}

	public function encodeTile(Spawnable $tile) : string
	{
		if ($tile instanceof ChestTile) {
			return $this->encodeChest($tile);
		}

		try {
			return $this->encodeWith($tile->getSerializedSpawnCompound()->getRoot(), $tile);
		} catch (Throwable $e) {
			$this->reportOnce($tile::class, 'cannot be read, it is omitted: ' . self::describe($e));

			return '';
		}
	}

	private function encodeWith(CompoundTag $modern, ?Spawnable $tile) : string
	{
		try {
			if (!v84WorldLimits::isBlockY(v84TileFields::intOf($modern, v84TileTag::Y, 0))) {
				return '';
			}

			$translator = $this->table->translatorFor($modern);
			if ($translator === null) {
				$this->reportOnce(v84TileFields::stringOf($modern, v84TileTag::ID, ''), 'has no 0.15.10 equivalent, it is omitted');

				return '';
			}

			$legacy = $tile !== null && $translator instanceof v84ItemTileTranslator ? $translator->translateTile($tile, $modern) : $translator->translate($modern);

			return $this->nbt->write(new TreeRoot($legacy));
		} catch (Throwable $e) {
			$this->reportOnce(v84TileFields::stringOf($modern, v84TileTag::ID, ''), 'cannot be converted, it is omitted: ' . self::describe($e));

			return '';
		}
	}

	private function encodeChest(ChestTile $tile) : string
	{
		try {
			$position = $tile->getPosition();
			if (!v84WorldLimits::isBlockY($position->getFloorY())) {
				return '';
			}

			$legacy = CompoundTag::create()
				->setString(v84TileTag::ID, v84TileId::CHEST)
				->setInt(v84TileTag::X, $position->getFloorX())
				->setInt(v84TileTag::Y, $position->getFloorY())
				->setInt(v84TileTag::Z, $position->getFloorZ());

			if ($tile->isPaired()) {
				$pair = $tile->getPair();
				if ($pair !== null) {
					$legacy->setInt(v84TileTag::PAIR_X, $pair->getPosition()->getFloorX());
					$legacy->setInt(v84TileTag::PAIR_Z, $pair->getPosition()->getFloorZ());
				}
			}

			if ($tile->hasName()) {
				$legacy->setString(v84TileTag::CUSTOM_NAME, $tile->getName());
			}

			return $this->nbt->write(new TreeRoot($legacy));
		} catch (Throwable $e) {
			GlobalLogger::get()->debug('Chest tile cannot be converted for 0.15.10: ' . self::describe($e));

			return '';
		}
	}

	private static function describe(Throwable $e) : string
	{
		return $e::class . ' at ' . $e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage();
	}

	private function reportOnce(string $key, string $message) : void
	{
		$key .= '|' . $message;
		if (isset($this->reported[$key])) {
			return;
		}

		$this->reported[$key] = true;
		GlobalLogger::get()->debug('Tile ' . $key . ' ' . $message);
	}
}
