<?php

declare(strict_types=1);

namespace lol\network\proto\v84\scoreboard;

use Closure;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_slice;
use function implode;
use function usort;

final class v84SidebarState
{

	private ?string $sidebar = null;

	private array $objectives = [];

	private array $scores = [];

	private bool $visible = false;

	private int $sentAt = 0;

	public function display(SetDisplayObjectivePacket $packet) : bool
	{
		if ($packet->displaySlot !== SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR) {
			return false;
		}

		$this->objectives[$packet->objectiveName] = new v84SidebarObjective($packet->displayName, $packet->sortOrder);
		$this->sidebar = $packet->objectiveName;

		return true;
	}

	public function scores(SetScorePacket $packet, Closure $names) : bool
	{
		$changed = false;

		foreach ($packet->entries as $entry) {
			if ($entry->type === ScorePacketEntry::TYPE_REMOVE) {
				$changed = $this->release($entry->scoreboardId) || $changed;
				continue;
			}

			$label = $entry->type === ScorePacketEntry::TYPE_FAKE_PLAYER
				? $entry->customName
				: ($entry->actorUniqueId === null ? null : $names($entry->actorUniqueId));
			if ($label === null || $entry->objectiveName === null) {
				continue;
			}

			$this->scores[$entry->scoreboardId] = new v84SidebarScore($entry->objectiveName, $label, $entry->score);
			$changed = $changed || $entry->objectiveName === $this->sidebar;
		}

		return $changed;
	}

	public function forget(string $objective) : bool
	{
		unset($this->objectives[$objective]);
		$this->scores = array_filter(
			$this->scores,
			static fn(v84SidebarScore $score) : bool => $score->objective !== $objective
		);

		if ($objective !== $this->sidebar) {
			return false;
		}

		$this->sidebar = null;

		return true;
	}

	public function render(int $maxLines, bool $showNumbers = false) : ?string
	{
		$objective = $this->sidebar === null ? null : ($this->objectives[$this->sidebar] ?? null);
		if ($objective === null) {
			return null;
		}

		$rows = array_filter(
			$this->scores,
			fn(v84SidebarScore $score) : bool => $score->objective === $this->sidebar
		);
		$ascending = $objective->order === SetDisplayObjectivePacket::SORT_ORDER_ASCENDING;
		usort($rows, static fn(v84SidebarScore $a, v84SidebarScore $b) : int => $ascending ? $a->value <=> $b->value : $b->value <=> $a->value);

		$lines = [TextFormat::BOLD . $objective->title . TextFormat::RESET];
		foreach (array_slice($rows, 0, $maxLines) as $row) {
			$lines[] = $showNumbers
				? $row->label . TextFormat::RESET . ' ' . TextFormat::RED . $row->value
				: $row->label . TextFormat::RESET;
		}

		return implode("\n", $lines);
	}

	public function isVisible() : bool
	{
		return $this->visible;
	}

	public function sentAt() : int
	{
		return $this->sentAt;
	}

	public function markSent(int $tick, bool $visible) : void
	{
		$this->sentAt = $tick;
		$this->visible = $visible;
	}

	private function release(int $scoreboardId) : bool
	{
		$score = $this->scores[$scoreboardId] ?? null;
		unset($this->scores[$scoreboardId]);

		return $score !== null && $score->objective === $this->sidebar;
	}
}
