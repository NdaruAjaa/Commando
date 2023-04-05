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
use pocketmine\entity\Entity;
use pocketmine\player\Player;
use pocketmine\utils\Limits;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use function method_exists;

class PlayerSelector implements ISelector {
	public function getChar() : string {
		return "p";
	}

	public function getTargets(CommandSender $sender, array $args) : array {
		if(method_exists($sender, 'getPosition')) {
			$position = $sender->getPosition();
			if($position instanceof Position) {
				return [
					$this->getNearestPlayer($position)
				];
			}
		}

		return [];
	}

	/**
	 * Returns the closest Entity to the specified position, within the given radius.
	 *
	 * @return Player|null an entity of type $entityType, or null if not found
	 */
	private function getNearestPlayer(Position $pos) : ?Entity{
		assert($pos->isValid());

		$minX = ((int) floor($pos->x - Limits::INT32_MAX)) >> Chunk::COORD_BIT_SIZE;
		$maxX = ((int) floor($pos->x + Limits::INT32_MAX)) >> Chunk::COORD_BIT_SIZE;
		$minZ = ((int) floor($pos->z - Limits::INT32_MAX)) >> Chunk::COORD_BIT_SIZE;
		$maxZ = ((int) floor($pos->z + Limits::INT32_MAX)) >> Chunk::COORD_BIT_SIZE;

		$currentTargetDistSq = Limits::INT32_MAX ** 2;

		/** @var Player|null $currentTarget */
		$currentTarget = null;

		for($x = $minX; $x <= $maxX; ++$x){
			for($z = $minZ; $z <= $maxZ; ++$z){
				if(!$pos->getWorld()->isChunkLoaded($x, $z)){
					continue;
				}
				foreach($pos->getWorld()->getChunkEntities($x, $z) as $entity){
					if(!$entity instanceof Player || $entity->isFlaggedForDespawn()){
						continue;
					}
					$distSq = $entity->getPosition()->distanceSquared($pos);
					if($distSq < $currentTargetDistSq){
						$currentTargetDistSq = $distSq;
						$currentTarget = $entity;
					}
				}
			}
		}

		return $currentTarget;
	}
}
