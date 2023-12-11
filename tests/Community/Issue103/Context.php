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

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Context")
 */
class Context
{
	/**
	 * @OGM\GraphId()
	 */
	protected int $id;

	/**
	 * @OGM\Property(type="string")
	 */
	protected string $name;

	/**
	 * @OGM\Relationship(type="HAS_CONTEXT", direction="INCOMING", targetEntity="Entity", collection=false, mappedBy="contexts")
	 */
	protected Entity $entity;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function getId() : int
	{
		return $this->id;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function setName(string $name) : void
	{
		$this->name = $name;
	}

	public function getEntity() : Entity
	{
		return $this->entity;
	}

	public function setEntity(Entity $entity) : void
	{
		$this->entity = $entity;
	}
}
