<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\utils\Terminal;
use function bin2hex;
use function count;
use function date;
use function getenv;
use function implode;
use function rtrim;
use function str_split;
use function strlen;
use function strtoupper;
use function substr;
use function time;
use const PHP_EOL;

final class v844Debug
{

	private static ?bool $enabled = null;

	private static array $reported = [];

	private function __construct()
	{

	}

	public static function isEnabled() : bool
	{
		if (self::$enabled === null) {
			$env = getenv("MV_DEBUG_844");
			self::$enabled = !($env === "0" || $env === "false" || $env === "off");
		}
		return self::$enabled;
	}

	public static function setEnabled(bool $enabled) : void
	{
		self::$enabled = $enabled;
	}

	public static function log(string $context, string $message) : void
	{
		if (!self::isEnabled()) {
			return;
		}

		echo Terminal::$COLOR_GRAY . "[" . date("Y-m-d H:i:s", time()) . "] [MultiVersion: " . strtoupper("debug") . "] Protocol#844/$context: " . $message . PHP_EOL;
	}

	public static function logOnce(string $key, string $context, string $message) : void
	{
		if (isset(self::$reported[$key])) {
			return;
		}
		self::$reported[$key] = true;
		self::log($context, $message);
	}

	public static function hex(string $bytes, int $max = 96) : string
	{
		$shown = substr($bytes, 0, $max);
		$pairs = str_split(bin2hex($shown), 2);
		$out = rtrim(implode(" ", $pairs));
		if (strlen($bytes) > $max) {
			$out .= " ... (" . strlen($bytes) . " bytes total)";
		}
		return $out;
	}

	public static function summariseResponses(array $responses) : string
	{
		$parts = [];
		foreach ($responses as $response) {
			$slotCount = 0;
			foreach ($response->getContainerInfos() as $containerInfo) {
				$slotCount += count($containerInfo->getSlots());
			}
			$parts[] = "#" . $response->getRequestId() .
				" result=" . $response->getResult() .
				" containers=" . count($response->getContainerInfos()) .
				" slots=" . $slotCount;
		}
		return count($parts) === 0 ? "(none)" : implode(", ", $parts);
	}

	public static function dumpResponses(array $responses) : string
	{
		$lines = [];
		foreach ($responses as $response) {
			foreach ($response->getContainerInfos() as $containerInfo) {
				$containerName = $containerInfo->getContainerName();
				foreach ($containerInfo->getSlots() as $slot) {
					$lines[] = "container=" . $containerName->getContainerId() .
						"/dyn=" . ($containerName->getDynamicId() ?? "null") .
						" slot=" . $slot->getSlot() .
						" hotbar=" . $slot->getHotbarSlot() .
						" count=" . $slot->getCount() .
						" stackId=" . ($slot->getItemStackId() ?? "null") .
						" name='" . $slot->getCustomName() . "'" .
						" filtered=" . ($slot->getFilteredCustomName() === null ? "null" : "'" . $slot->getFilteredCustomName() . "'") .
						" durability=" . $slot->getDurabilityCorrection();
				}
			}
		}
		return count($lines) === 0 ? "(no slots)" : implode(" | ", $lines);
	}
}
