<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use lol\network\global\MVTypeConverter;
use pocketmine\data\bedrock\item\ItemTypeSerializeException;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\utils\SingletonTrait;

final class v844ItemIdMetaTranslator
{
	use SingletonTrait;

	private const META_MASK = 0xFFFF;

	public function translate(int $latestEventData, MVTypeConverter $converter) : ?int
	{
		try {
			$item = TypeConverter::getInstance()->getItemTranslator()->fromNetworkId(
				$latestEventData >> 16,
				$latestEventData & self::META_MASK,
				ItemTranslator::NO_BLOCK_RUNTIME_ID
			);
			[$targetId, $targetMeta] = $converter->getMVItemTranslator()->toNetworkId($item);
		} catch (TypeConversionException | ItemTypeSerializeException) {
			return null;
		}

		return ($targetId << 16) | ($targetMeta & self::META_MASK);
	}
}
