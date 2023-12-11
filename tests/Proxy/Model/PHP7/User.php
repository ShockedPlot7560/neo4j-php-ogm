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
 * @OGM\Node(label="User")
 */
class User
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
	 * @OGM\Property(type="string")
	 */
	protected $login;

	/**
	 * @var Profile
	 *
	 * @OGM\Relationship(type="HAS_PROFILE", direction="OUTGOING", targetEntity="Profile", mappedBy="user")
	 */
	protected $profile;

	/**
	 * @var Profile
	 * An entity without getter
	 *
	 * @OGM\Relationship(type="HAS_PUBLIC_PROFILE", direction="OUTGOING", targetEntity="Profile")
	 */
	protected $publicProfile;

	/**
	 * User constructor.
	 */
	public function __construct(string $login)
	{
		$this->login = $login;
		$this->profile = new Profile($login . '@graphaware.com');
	}

	public function getId() : int
	{
		return $this->id;
	}

	public function getLogin() : string
	{
		return $this->login;
	}

	/**
	 * @return User
	 */
	public function setLogin(string $login)
	{
		$this->login = $login;
	}

	public function getProfile() : Profile
	{
		return $this->profile;
	}
}
