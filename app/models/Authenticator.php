<?php

use Nette\Security as NS;
use Nette\Security\Identity;


/**
 * Users authenticator.
 *
 * @author     John Doe
 * @package    MyApplication
 */
class Authenticator extends Nette\Object implements NS\IAuthenticator
{
	/** @var Nette\Database\Table\Selection */
	private $users;



	public function __construct(Nette\Database\Context $database)
	{
		$this->users = $database->table('users');
	}



	/**
	 * Performs an authentication
	 * @param  array
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials) {
		list($identity, $password) = $credentials;
		
		$row = $this->users->where('email', $identity)->where('role != ?', '')->fetch();

		if (!$row) {
			throw new NS\AuthenticationException("E-mail '$identity' not found.", self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->calculateHash($password)) {
			throw new NS\AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		}

// 		unset($row->password);
		return new NS\Identity($row->id, $row->role, $row->toArray());
	}



	/**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	public function calculateHash($password)
	{
		return hash('sha512', $password);
	}

	function authenticateFb(array $credentials) {
		if ($user = $this->users->where('email', $credentials['email'])->fetch()) {
		}
		else {
			$data['name'] = $credentials['name'];
			$data['surname'] = $credentials['surname'];
			$data['email'] = $credentials['email'];
	
			$user = $this->users->insert($data);
		}
			
		return new Identity($user->id, null, $user->toArray());
	}
}
