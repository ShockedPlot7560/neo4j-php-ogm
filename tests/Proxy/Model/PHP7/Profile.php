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

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Proxy\Model\PHP7;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Profile")
 */
class Profile
{
	/**
	 * @var int
	 *
	 * @OGM\GraphId()
	 */
	protected $id;

	/** @OGM\Property(type="string") */
	protected $email;

	/**
	 * @var User
	 *
	 * @OGM\Relationship(type="HAS_PROFILE", targetEntity="User", direction="INCOMING", mappedBy="profile")
	 */
	protected $user;

	/**
	 * Profile constructor.
	 *
	 * @param string $email
	 */
	public function __construct($email)
	{
		$this->email = $email;
	}

	public function getId() : int
	{
		return $this->id;
	}

	public function getEmail() : string
	{
		return $this->email;
	}

	public function getUser() : User
	{
		return $this->user;
	}
}
