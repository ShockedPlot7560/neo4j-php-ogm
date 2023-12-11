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

namespace GraphAware\Neo4j\OGM\Tests\Community\Issue103;

use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;
use function spl_object_hash;

/**
 * @group issue-103
 */
class Issue103ReportTest extends IntegrationTestCase
{
	public function setUp() : void
	{
		parent::setUp();
		$this->clearDb();
	}

	public function testIssueReport()
	{
		$manager = $this->em;

		// CREATE ENTITY
		$test_entityUuid = 1234;
		$entity = new Entity($test_entityUuid);

		// CREATE CONTEXT
		$test_contextUuid = '456';
		$context = new Context($test_contextUuid);
		$context->setEntity($entity);

		// ADD CONTEXT TO ENTITY
		$entity->addContext($context);

		// SAVE EVERYTHING
		$manager->persist($entity);
		$manager->persist($context);
		$manager->flush();

		// LOOK UP CONTEXT
		$context2 = $manager->getRepository(Context::class)->findOneBy(['name' => $test_contextUuid]);
		$this->assertEquals(spl_object_hash($context), spl_object_hash($context2));
		$this->assertEquals($test_entityUuid, $context2->getEntity()->getName());
	}
}
