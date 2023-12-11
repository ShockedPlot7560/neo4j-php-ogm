<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace GraphAware\Neo4j\OGM\Tests\Integration\Repository;

use GraphAware\Neo4j\OGM\Repository\BaseRepository;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Movie;

class MoviesCustomRepository extends BaseRepository
{
	public function findAllWithScore()
	{
		$query = $this->entityManager->createQuery('MATCH (n:Movie) RETURN n, size((n)<-[:ACTED_IN]-()) AS score ORDER BY score DESC');
		$query->addEntityMapping('n', Movie::class);

		return $query->getResult();
	}
}
