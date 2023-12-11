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

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Tests\Integration\Models\Base\User;

/**
 * @group entity-lifecycle
 */
class EntityLifecycleTest extends IntegrationTestCase
{
	public function setUp() : void
	{
		parent::setUp();
		$this->clearDb();
	}

	public function testEntityCanBeRefreshed()
	{
		$user = new User('M');
		$this->em->persist($user);
		$this->em->flush();
		$this->client->run('MATCH (n:User) SET n.login = "Z"');

		$this->em->refresh($user);
		$this->assertEquals("Z", $user->getLogin());
	}
}
