<?php

declare(strict_types=1);

namespace lol\network\utils;

use pocketmine\data\bedrock\WorldDataVersions;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\Binary;
use pocketmine\utils\Filesystem;
use Symfony\Component\Filesystem\Path;
use function copy;
use function file_exists;
use function is_dir;
use function scandir;
use function strlen;
use function substr;

final class WorldNetworkVersionFixer {

	private const LEVEL_DAT_FILENAME = "level.dat";
	private const TAG_NETWORK_VERSION = "NetworkVersion";
	private const TAG_STORAGE_VERSION = "StorageVersion";
	private const MISSING_SENTINEL = -1;

	public static function fixAll(string $worldsPath, \Logger $logger) : void {
		if(!is_dir($worldsPath)){
			return;
		}

		$entries = scandir($worldsPath);
		if($entries === false){
			return;
		}

		foreach ($entries as $entry) {
			if ($entry === "." || $entry === "..") {
				continue;
			}

			$worldDir = Path::join($worldsPath, $entry);
			if (!is_dir($worldDir)) {
				continue;
			}

			$levelDatPath = Path::join($worldDir, self::LEVEL_DAT_FILENAME);
			if (!file_exists($levelDatPath)) {
				continue;
			}

			self::fixOne($levelDatPath, $entry, $logger);
		}
	}

	private static function fixOne(string $levelDatPath, string $worldName, \Logger $logger) : void {
		try {
			$raw = Filesystem::fileGetContents($levelDatPath);
			if (strlen($raw) <= 8) {
				return;
			}

			$nbt = new LittleEndianNbtSerializer();
			$tree = $nbt->read(substr($raw, 8));
			$compound = $tree->mustGetCompoundTag();

			$networkVersion = $compound->getInt(self::TAG_NETWORK_VERSION, self::MISSING_SENTINEL);
			if ($networkVersion === self::MISSING_SENTINEL || $networkVersion <= WorldDataVersions::NETWORK) {
				return;
			}

			$backupPath = $levelDatPath . ".bak-networkversion-$networkVersion";
			if (!file_exists($backupPath)) {
				copy($levelDatPath, $backupPath);
			}

			$compound->setInt(self::TAG_NETWORK_VERSION, WorldDataVersions::NETWORK);

			$storageVersion = $compound->getInt(self::TAG_STORAGE_VERSION, WorldDataVersions::STORAGE);
			$buffer = $nbt->write(new TreeRoot($compound, $tree->getName()));
			Filesystem::safeFilePutContents($levelDatPath, Binary::writeLInt($storageVersion) . Binary::writeLInt(strlen($buffer)) . $buffer);

			$logger->notice(
				"[MultiVersion] Mundo \"$worldName\": level.dat traía NetworkVersion $networkVersion, superior al " .
				WorldDataVersions::NETWORK . " que soporta este build. Se ha bajado a " . WorldDataVersions::NETWORK .
				" para que cargue. Copia de seguridad del original: " . self::LEVEL_DAT_FILENAME . ".bak-networkversion-$networkVersion. " .
				"Si tras esto el mundo da error al leer chunks (no solo al abrir level.dat), restaura esa copia: " .
				"el formato interno de los chunks sí habrá cambiado y hace falta software que soporte protocolo $networkVersion."
			);
		} catch (\Throwable $e) {
			$logger->debug("[MultiVersion] No se pudo comprobar/parchear $levelDatPath: " . $e->getMessage());
		}
	}
}
