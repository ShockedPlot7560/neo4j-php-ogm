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

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToOne;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Woman
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToOne
 *
 * @OGM\Node(label="Woman")
 */
class Woman
{
	/**
	 * @var int
	 *
	 * @OGM\GraphId()
	 */
	protected $id;

	/**
	 * @var string
	 *
	 * @OGM\Property()
	 */
	protected $name;

	/**
	 * @var Collection|Bag[]
	 *
	 * @OGM\Relationship(type="OWNS_BAG", direction="OUTGOING", collection=true, mappedBy="owner", targetEntity="Bag")
	 */
	protected $bags;

	public function __construct($name = null)
	{
		$this->bags = new Collection();
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return Collection
	 */
	public function getBags()
	{
		return $this->bags;
	}
}
