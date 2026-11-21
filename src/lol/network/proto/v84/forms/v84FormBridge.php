<?php

declare(strict_types=1);

namespace lol\network\proto\v84\forms;

use lol\network\NetworkSession;
use lol\network\proto\v84\inventory\v84ServerTicks;
use lol\network\proto\v84\packets\outgoing\v84OutgoingTextPacket;
use lol\network\proto\v84\text\v84TextFormat;
use lol\network\proto\v84\text\v84TextType;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use WeakMap;
use function count;
use function ctype_digit;
use function explode;
use function floor;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function mb_strtolower;
use function microtime;
use function str_replace;
use function str_starts_with;
use function trim;

final class v84FormBridge
{

	private const TIMEOUT_SECONDS = 120;

	private static ?self $instance = null;

	private WeakMap $states;

	public static function shared() : self
	{
		return self::$instance ??= new self();
	}

	private function __construct()
	{
		$this->states = new WeakMap();
	}

	public function open(NetworkSession $session, ModalFormRequestPacket $packet) : ?v84OutgoingTextPacket
	{
		$player = $session->getPlayer();
		if ($player === null) {
			return null;
		}

		$form = json_decode($packet->formData, true);
		$kind = is_array($form) ? ($form['type'] ?? null) : null;
		if (!in_array($kind, [v84FormState::MODAL, v84FormState::SIMPLE, v84FormState::CUSTOM], true)) {
			$this->cancelLater($player, $packet->formId);

			return null;
		}

		$previous = $this->states[$player] ?? null;
		if ($previous !== null) {
			$this->cancelLater($player, $previous->formId);
		}

		$state = new v84FormState($packet->formId, $kind, $form, microtime(true) + self::TIMEOUT_SECONDS);
		$this->states[$player] = $state;

		$lines = [TextFormat::GOLD . TextFormat::BOLD . self::text($form['title'] ?? '') . TextFormat::RESET];
		$done = false;
		switch ($kind) {
			case v84FormState::MODAL:
				self::addContent($lines, $form['content'] ?? '');
				$lines[] = TextFormat::GREEN . '[1] ' . TextFormat::WHITE . self::text($form['button1'] ?? 'Sí');
				$lines[] = TextFormat::RED . '[2] ' . TextFormat::WHITE . self::text($form['button2'] ?? 'No');
				$lines[] = TextFormat::GRAY . 'Responde con el número en el chat. ' . TextFormat::RED . '0' . TextFormat::GRAY . ' = cancelar';
				break;
			case v84FormState::SIMPLE:
				self::addContent($lines, $form['content'] ?? '');
				foreach (self::buttons($form) as $index => $button) {
					$lines[] = TextFormat::GREEN . '[' . ($index + 1) . '] ' . TextFormat::WHITE . $button;
				}
				$lines[] = TextFormat::GRAY . 'Responde con el número en el chat. ' . TextFormat::RED . '0' . TextFormat::GRAY . ' = cancelar';
				break;
			default:
				$lines[] = TextFormat::GRAY . 'Responde cada campo en el chat. ' . TextFormat::WHITE . '-' . TextFormat::GRAY . ' = valor por defecto, ' . TextFormat::RED . 'cancelar' . TextFormat::GRAY . ' = salir';
				$done = $this->customLines($state, $lines);
				break;
		}

		if ($done) {
			unset($this->states[$player]);
			$answers = $state->answers;
			$formId = $state->formId;
			v84ServerTicks::after(static function () use ($player, $formId, $answers) : void {
				if ($player->isConnected()) {
					$player->onFormSubmit($formId, $answers);
				}
			}, 1);
		}

		return v84OutgoingTextPacket::fromParts(v84TextType::RAW, '', v84TextFormat::normalize(implode("\n", $lines)));
	}

	public function consume(Player $player, string $message) : bool
	{
		$state = $this->states[$player] ?? null;
		if ($state === null) {
			return false;
		}

		$input = trim($message);
		if (str_starts_with($input, '/')) {
			$this->finish($player, $state, null);

			return false;
		}

		$lower = mb_strtolower($input);
		if ($lower === 'cancelar' || $lower === 'cancel' || ($input === '0' && $state->kind !== v84FormState::CUSTOM)) {
			$player->sendMessage(TextFormat::GRAY . 'Formulario cancelado.');
			$this->finish($player, $state, null);

			return true;
		}

		switch ($state->kind) {
			case v84FormState::MODAL:
				if ($input === '1' || $input === '2') {
					$this->finish($player, $state, $input === '1');
				} else {
					$player->sendMessage(TextFormat::RED . 'Escribe 1 o 2 (0 = cancelar).');
				}
				break;
			case v84FormState::SIMPLE:
				$count = count(self::buttons($state->form));
				if (ctype_digit($input) && (int) $input >= 1 && (int) $input <= $count) {
					$this->finish($player, $state, (int) $input - 1);
				} else {
					$player->sendMessage(TextFormat::RED . "Escribe un número del 1 al $count (0 = cancelar).");
				}
				break;
			default:
				$this->answerCustom($player, $state, $input);
				break;
		}

		return true;
	}

	public function tick(NetworkSession $session) : void
	{
		$player = $session->getPlayer();
		if ($player === null) {
			return;
		}

		$state = $this->states[$player] ?? null;
		if ($state !== null && microtime(true) > $state->expiresAt) {
			$player->sendMessage(TextFormat::GRAY . 'Formulario cerrado por inactividad.');
			$this->finish($player, $state, null);
		}
	}

	public function closeAll(NetworkSession $session) : void
	{
		$player = $session->getPlayer();
		$state = $player === null ? null : ($this->states[$player] ?? null);
		if ($player !== null && $state !== null) {
			$this->finish($player, $state, null);
		}
	}

	private function finish(Player $player, v84FormState $state, mixed $data) : void
	{
		if (($this->states[$player] ?? null) === $state) {
			unset($this->states[$player]);
		}

		if ($player->isConnected()) {
			$player->onFormSubmit($state->formId, $data);
		}
	}

	private function cancelLater(Player $player, int $formId) : void
	{
		v84ServerTicks::after(static function () use ($player, $formId) : void {
			if ($player->isConnected()) {
				$player->onFormSubmit($formId, null);
			}
		}, 1);
	}

	private function answerCustom(Player $player, v84FormState $state, string $input) : void
	{
		$elements = $state->form['content'] ?? [];
		$element = $elements[$state->stage] ?? null;
		if (!is_array($element)) {
			$this->finish($player, $state, $state->answers);

			return;
		}

		$value = null;
		$error = self::parse($element, $input, $value);
		if ($error !== null) {
			$lines = [TextFormat::RED . $error];
			self::addPrompt($lines, $element, $state->stage, count($elements));
			$player->sendMessage(implode("\n", $lines));

			return;
		}

		$state->answers[] = $value;
		++$state->stage;

		$lines = [];
		$done = $this->customLines($state, $lines);
		if ($lines !== []) {
			$player->sendMessage(implode("\n", $lines));
		}
		if ($done) {
			$this->finish($player, $state, $state->answers);
		}
	}

	private function customLines(v84FormState $state, array &$lines) : bool
	{
		$elements = $state->form['content'] ?? [];
		if (!is_array($elements)) {
			return true;
		}

		$total = count($elements);
		while (isset($elements[$state->stage])) {
			$element = $elements[$state->stage];
			$type = is_array($element) ? ($element['type'] ?? '') : '';
			if ($type === 'label' || $type === 'header') {
				$lines[] = TextFormat::GRAY . self::text($element['text'] ?? '');
			} elseif (!in_array($type, ['input', 'toggle', 'slider', 'step_slider', 'dropdown'], true)) {
			} else {
				self::addPrompt($lines, $element, $state->stage, $total);

				return false;
			}

			$state->answers[] = null;
			++$state->stage;
		}

		return true;
	}

	private static function addPrompt(array &$lines, array $element, int $stage, int $total) : void
	{
		$type = $element['type'] ?? '';
		$head = TextFormat::YELLOW . '[' . ($stage + 1) . '/' . $total . '] ' . TextFormat::WHITE . self::text($element['text'] ?? '');
		$lines[] = $head;

		switch ($type) {
			case 'input':
				if (($element['placeholder'] ?? '') !== '') {
					$lines[] = TextFormat::GRAY . 'Ej.: ' . self::text($element['placeholder']);
				}
				$default = self::text($element['default'] ?? '');
				$lines[] = TextFormat::GRAY . 'Escribe tu respuesta.' . ($default !== '' ? " (- = \"$default\")" : '');
				break;
			case 'toggle':
				$on = (bool) ($element['default'] ?? false);
				$lines[] = TextFormat::GREEN . '[1] Sí' . TextFormat::GRAY . ($on ? ' (por defecto)' : '') . '  ' . TextFormat::RED . '[2] No' . TextFormat::GRAY . ($on ? '' : ' (por defecto)');
				break;
			case 'slider':
				$min = $element['min'] ?? 0;
				$max = $element['max'] ?? 0;
				$default = $element['default'] ?? $min;
				$step = $element['step'] ?? 1;
				$lines[] = TextFormat::GRAY . "Escribe un número de $min a $max (paso $step). - = $default";
				break;
			case 'dropdown':
			case 'step_slider':
				$options = $type === 'dropdown' ? ($element['options'] ?? []) : ($element['steps'] ?? []);
				$default = (int) ($element['default'] ?? 0);
				foreach ($options as $index => $option) {
					$lines[] = TextFormat::GREEN . '[' . ($index + 1) . '] ' . TextFormat::WHITE . self::text($option) . ($index === $default ? TextFormat::GRAY . ' (por defecto)' : '');
				}
				break;
		}
	}

	private static function parse(array $element, string $input, mixed &$value) : ?string
	{
		$type = $element['type'] ?? '';
		if ($input === '-') {
			$value = match ($type) {
				'input' => self::text($element['default'] ?? ''),
				'toggle' => (bool) ($element['default'] ?? false),
				'slider' => $element['default'] ?? $element['min'] ?? 0,
				default => (int) ($element['default'] ?? 0)
			};

			return null;
		}

		switch ($type) {
			case 'input':
				$value = $input;

				return null;
			case 'toggle':
				$lower = mb_strtolower($input);
				if (in_array($lower, ['1', 'si', 'sí', 's', 'on', 'true', 'yes', 'y'], true)) {
					$value = true;

					return null;
				}
				if (in_array($lower, ['2', 'no', 'n', 'off', 'false'], true)) {
					$value = false;

					return null;
				}

				return 'Escribe 1 (Sí) o 2 (No).';
			case 'slider':
				$min = $element['min'] ?? 0;
				$max = $element['max'] ?? 0;
				if (!is_numeric($input) || (float) $input < $min || (float) $input > $max) {
					return "Escribe un número entre $min y $max.";
				}
				$number = (float) $input;
				$value = $number === floor($number) ? (int) $number : $number;

				return null;
			case 'dropdown':
			case 'step_slider':
				$options = $type === 'dropdown' ? ($element['options'] ?? []) : ($element['steps'] ?? []);
				$count = count($options);
				if (!ctype_digit($input) || (int) $input < 1 || (int) $input > $count) {
					return "Escribe un número del 1 al $count.";
				}
				$value = (int) $input - 1;

				return null;
		}

		$value = null;

		return null;
	}

	private static function buttons(array $form) : array
	{
		$buttons = [];
		foreach (($form['buttons'] ?? []) as $button) {
			$buttons[] = str_replace("\n", TextFormat::GRAY . ' - ' . TextFormat::WHITE, self::text(is_array($button) ? ($button['text'] ?? '') : $button));
		}

		return $buttons;
	}

	private static function addContent(array &$lines, mixed $content) : void
	{
		$content = self::text($content);
		if ($content === '') {
			return;
		}
		foreach (explode("\n", $content) as $line) {
			$lines[] = TextFormat::GRAY . $line;
		}
	}

	private static function text(mixed $value) : string
	{
		return is_string($value) ? $value : (is_numeric($value) ? (string) $value : '');
	}
}
