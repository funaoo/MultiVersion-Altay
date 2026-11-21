<?php

declare(strict_types=1);

namespace lol\network\proto\v84\players;

use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use function intdiv;
use function implode;
use function str_repeat;
use function str_starts_with;
use function substr;

final class v84SkinConverter
{

	private const LEGACY_WIDTH = 64;

	private const LEGACY_HEIGHTS = [32, 64];

	private const BYTES_PER_PIXEL = 4;

	private const FALLBACK_PIXEL = "\xb6\x8a\x6b\xff";

	private const FALLBACK_HEIGHT = 32;

	private const ID_PREFIX = 'Standard_';

	private const ID_WIDE = 'Standard_Custom';

	private const ID_SLIM = 'Standard_CustomSlim';

	private function __construct()
	{
	}

	public static function fromSkinData(SkinData $skin) : v84LegacySkin
	{
		return new v84LegacySkin(self::resolveId($skin), self::resolveData($skin->getSkinImage()));
	}

	private static function resolveId(SkinData $skin) : string
	{
		$id = $skin->getSkinId();
		if (str_starts_with($id, self::ID_PREFIX)) {
			return $id;
		}

		return $skin->getArmSize() === SkinData::ARM_SIZE_SLIM ? self::ID_SLIM : self::ID_WIDE;
	}

	private static function resolveData(SkinImage $image) : string
	{
		$width = $image->getWidth();
		$height = $image->getHeight();
		if ($width === self::LEGACY_WIDTH && self::isLegacyHeight($height)) {
			return $image->getData();
		}

		$factor = self::reductionFactor($width, $height);
		if ($factor === 0) {
			return self::fallback();
		}

		return self::reduce($image->getData(), $width, intdiv($height, $factor), $factor);
	}

	private static function isLegacyHeight(int $height) : bool
	{
		return $height === self::LEGACY_HEIGHTS[0] || $height === self::LEGACY_HEIGHTS[1];
	}

	private static function reductionFactor(int $width, int $height) : int
	{
		if ($width <= self::LEGACY_WIDTH || $width % self::LEGACY_WIDTH !== 0) {
			return 0;
		}

		$factor = intdiv($width, self::LEGACY_WIDTH);
		$reducedHeight = intdiv($height, $factor);
		if ($height % $factor !== 0 || !self::isLegacyHeight($reducedHeight)) {
			return 0;
		}

		return $factor;
	}

	private static function reduce(string $data, int $width, int $reducedHeight, int $factor) : string
	{
		$pixels = [];
		for ($row = 0; $row < $reducedHeight; ++$row) {
			$rowStart = $row * $factor * $width;
			for ($column = 0; $column < self::LEGACY_WIDTH; ++$column) {
				$pixels[] = substr($data, ($rowStart + $column * $factor) * self::BYTES_PER_PIXEL, self::BYTES_PER_PIXEL);
			}
		}

		return implode('', $pixels);
	}

	private static function fallback() : string
	{
		return str_repeat(self::FALLBACK_PIXEL, self::LEGACY_WIDTH * self::FALLBACK_HEIGHT);
	}
}
