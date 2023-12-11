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

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Entity")
 */
class Entity
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
	 * @OGM\Relationship(type="HAS_CONTEXT", direction="OUTGOING", targetEntity="Context", collection=true, mappedBy="entity")
	 * @var ArrayCollection|Context[];
	 */
	protected array|ArrayCollection $contexts;

	public function __construct($name)
	{
		$this->name = $name;

		$this->contexts = new ArrayCollection();
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

	/**
	 * @return ArrayCollection|Context[];
	 */
	public function getContexts() : ArrayCollection|array
	{
		return $this->contexts;
	}

	public function addContext(Context $context) : void
	{
		if (!$this->contexts->contains($context)) {
			$this->contexts->add($context);
		}
	}

	public function removeContext(Context $context) : void
	{
		if ($this->contexts->contains($context)) {
			$this->contexts->removeElement($context);
		}
	}
}
