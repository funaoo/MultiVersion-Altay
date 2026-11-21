<?php

declare(strict_types=1);

namespace lol\network\utils;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use function gc_collect_cycles;
use function gc_mem_caches;

final class ReflectionUtils
{
	private static array $propertyCache = [];
	private static array $methodCache = [];

	private function __construct() {

	}

	public static function setProperty(string $className, object $instance, string $propertyName, mixed $value) : void
	{
		self::getCachedProperty($className, $propertyName)->setValue($instance, $value);
	}

	private static function getCachedProperty(string $className, string $propertyName) : ReflectionProperty
	{
		$key = "$className::$propertyName";
		if (!isset(self::$propertyCache[$key])) {
			$refClass = new ReflectionClass($className);
			$refProp = $refClass->getProperty($propertyName);
			$refProp->setAccessible(true);
			self::$propertyCache[$key] = $refProp;
		}
		return self::$propertyCache[$key];
	}

	public static function getProperty(string $className, object $instance, string $propertyName) : mixed
	{
		return self::getCachedProperty($className, $propertyName)->getValue($instance);
	}

	public static function invokeStatic(string $className, string $methodName, mixed ...$args) : mixed
	{
		return self::getCachedMethod($className, $methodName)->invoke(null, ...$args);
	}

	public static function invoke(string $className, object $instance, string $methodName, mixed ...$args) : mixed
	{
		$method = self::getCachedMethod($className, $methodName);
		return $method->invokeArgs($instance, $args);
	}

	private static function getCachedMethod(string $className, string $methodName) : ReflectionMethod
	{
		$key = "$className::$methodName";
		if (!isset(self::$methodCache[$key])) {
			$refClass = new ReflectionClass($className);
			$refMeth = $refClass->getMethod($methodName);
			$refMeth->setAccessible(true);
			self::$methodCache[$key] = $refMeth;
		}
		return self::$methodCache[$key];
	}

	static public function pruneCache() : void {
		self::$propertyCache = [];
		self::$methodCache = [];
		gc_collect_cycles();
		gc_mem_caches();
	}

}
