<?php

declare(strict_types=1);

namespace lol\network\utils;

use lol\Loader;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;
use function is_array;
use function json_decode;

final class LevelSoundMap {
	use SingletonTrait;

	private static function make() : self{
		$maps = json_decode(Filesystem::fileGetContents(Path::join(Loader::getPluginResourcePath(), "level_sound_id_map.json")), true);
		if(!is_array($maps)){
			throw new AssumptionFailedError("expected level_soundmap to be array while decoded");
		}
		return new self($maps);
	}

	private array $intToStringMap = [];
	private array $stringToIntMap = [];

	public function __construct(array $map){
		foreach ($map as $key => $value) {
			$this->intToStringMap[$value] = $key;
			$this->stringToIntMap[$key] = $value;
		}
	}

	public function lookupInt(string $key) : int{
		return $this->stringToIntMap[$key] ??= 0;
	}

	public function lookupString(int $key) : string{
		return $this->intToStringMap[$key] ??= 'item.use.on';
	}

}
