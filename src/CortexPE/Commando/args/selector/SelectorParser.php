<?php

/***
 *    ___                                          _
 *   / __\___  _ __ ___  _ __ ___   __ _ _ __   __| | ___
 *  / /  / _ \| '_ ` _ \| '_ ` _ \ / _` | '_ \ / _` |/ _ \
 * / /__| (_) | | | | | | | | | | | (_| | | | | (_| | (_) |
 * \____/\___/|_| |_| |_|_| |_| |_|\__,_|_| |_|\__,_|\___/
 *
 * Commando - A Command Framework virion for PocketMine-MP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Written by @CortexPE <https://CortexPE.xyz>
 *
 */
declare(strict_types=1);

namespace CortexPE\Commando\args\selector;

use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use function array_keys;
use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_match_all;
use function strtolower;
use function trim;

class SelectorParser {
	/** @var ISelector[] $selectors */
	private array $selectors = [];
	private string $selRegex = "";

	public function registerSelector(ISelector $selector) : void {
		$c = strtolower($selector->getChar()[0]);
		if(!isset($this->selectors[$c])){
			$this->selectors[$c] = $selector;
		}
		$this->selRegex = "/(?:@([" . implode("", array_keys($this->selectors)) . "])(?:\[(.+)\])?)/";
	}

	public function parse(CommandSender $sender, string $arg) : array {
		preg_match_all($this->selRegex, $arg, $matches);
		$args = [];
		if(isset($matches[2]) && $matches[2] !== []){
			foreach(explode(",", $matches[2][0]) as $matchArg){
				$matchArg = explode("=", trim($matchArg));
				if(count($matchArg) === 2){
					$args[$matchArg[0]] = $matchArg[1];
				}else{
					throw new InvalidCommandSyntaxException("Invalid selector syntax");
				}
			}
		}
		return $this->selectors[$matches[1][0]]->getTargets($sender, $args);
	}

	public function isValid(CommandSender $sender, string $arg) : bool{
		return (bool) preg_match($this->selRegex, $arg);
	}
}
