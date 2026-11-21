<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use lol\network\global\MVTypeConverter;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\utils\SingletonTrait;
use function array_map;

final class v844ItemStackTranslator
{
	use SingletonTrait;

	public function translateStack(ItemStack $latest, MVTypeConverter $converter) : ItemStack
	{
		if ($latest->getId() === 0) {
			return ItemStack::null();
		}

		try {
			$item = TypeConverter::getInstance()->netItemStackToCore($latest);
		} catch (TypeConversionException) {
			return ItemStack::null();
		}

		return $converter->coreItemStackToNet($item);
	}

	public function translateWrapper(ItemStackWrapper $latest, MVTypeConverter $converter) : ItemStackWrapper
	{
		return new ItemStackWrapper($latest->getStackId(), $this->translateStack($latest->getItemStack(), $converter));
	}

	public function translateWrapperList(array $latest, MVTypeConverter $converter) : array
	{
		return array_map(fn(ItemStackWrapper $wrapper) => $this->translateWrapper($wrapper, $converter), $latest);
	}
}
