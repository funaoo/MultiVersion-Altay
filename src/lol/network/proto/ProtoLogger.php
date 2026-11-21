<?php

declare(strict_types=1);

namespace lol\network\proto;

use lol\network\proto\v844\structure\v844Debug;
use pocketmine\utils\Terminal;
use SimpleLogger;
use function date;
use function strtoupper;
use function time;
use const PHP_EOL;

final class ProtoLogger extends SimpleLogger {

	protected string $prefix;
	protected string $postfix;
	protected bool $debug;

	public function __construct(TProtocol $protocol, ?string $label = null) {
		$this->prefix = "Protocol#{$protocol->getProtocolId()}: ";
		$this->postfix = $label ?? (string) $protocol;
		$this->debug = $protocol->hasDebug();
	}

	public function log($level, $message) {
		echo Terminal::$COLOR_MINECOIN_GOLD . "[" . date("Y-m-d", time()) . "] [MultiVersion: " . strtoupper($level) . "] $this->prefix" . $message . " ($this->postfix)" . PHP_EOL;
	}

	public function debug($message) {
		if($this->debug && v844Debug::isEnabled()) {
			echo Terminal::$COLOR_GRAY . "[" . date("Y-m-d", time()) . "] [MultiVersion: " . strtoupper(\LogLevel::DEBUG) . "] $this->prefix" . $message . " ($this->postfix)" . PHP_EOL;
		}
	}

	public function isDebug() : bool {
		return $this->debug && v844Debug::isEnabled();
	}

	public function undoDebug() : void {
		$this->debug = !$this->debug;
	}
}
