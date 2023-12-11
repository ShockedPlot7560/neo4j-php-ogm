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

namespace GraphAware\Neo4j\OGM\Tests\Community\Export;

use GraphAware\Neo4j\OGM\Tests\Community\Issue21\TestUser;
use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;
use function json_decode;
use function json_encode;

/**
 * Class ExportEntityToJsonTest
 * @package GraphAware\Neo4j\OGM\Tests\Community
 *
 * @group export
 */
class ExportEntityToJsonTest extends IntegrationTestCase
{
	public function testBasicEntityCanBeSerializedToJson()
	{
		$this->clearDb();
		$user = new TestUser("me");
		$this->em->persist($user);
		$this->em->flush();

		$this->em->clear();
		$repository = $this->em->getRepository(TestUser::class);
		$all = $repository->findAll();

		$json = json_encode($all);
		$decoded = json_decode($json, true);
		$this->assertArrayHasKey('id', $decoded[0]);
		$this->assertArrayHasKey('name', $decoded[0]);
		$this->assertEquals('me', $decoded[0]['name']);
	}
}
