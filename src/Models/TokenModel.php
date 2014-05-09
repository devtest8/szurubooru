<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

final class TokenModel extends AbstractCrudModel
{
	public static function getTableName()
	{
		return 'user_token';
	}

	public static function save($token)
	{
		$token->validate();

		Database::transaction(function() use ($token)
		{
			self::forgeId($token);

			$bindings = [
				'user_id' => $token->getUserId(),
				'token' => $token->getText(),
				'used' => $token->isUsed(),
				'expires' => $token->getExpirationTime(),
				];

			$stmt = new Sql\UpdateStatement();
			$stmt->setTable('user_token');
			$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($token->getId())));

			foreach ($bindings as $key => $val)
				$stmt->setColumn($key, new Sql\Binding($val));

			Database::exec($stmt);
		});

		return $token;
	}

	public static function getByToken($key)
	{
		$ret = self::tryGetByToken($key);
		if (!$ret)
			throw new SimpleNotFoundException('No user with such security token');
		return $ret;
	}

	public static function tryGetByToken($key)
	{
		if (empty($key))
			throw new SimpleNotFoundException('Invalid security token');

		$stmt = new Sql\SelectStatement();
		$stmt->setTable('user_token');
		$stmt->setColumn('*');
		$stmt->setCriterion(new Sql\EqualsFunctor('token', new Sql\Binding($key)));

		$row = Database::fetchOne($stmt);
		return $row
			? self::spawnFromDatabaseRow($row)
			: null;
	}

	public static function checkValidity($token)
	{
		if (empty($token))
			throw new SimpleException('Invalid security token');

		if ($token->isUsed())
			throw new SimpleException('This token was already used');

		if ($token->getExpirationTime() !== null and time() > $token->getExpirationTime())
			throw new SimpleException('This token has expired');
	}

	public static function forgeUnusedToken()
	{
		$tokenText = '';
		while (true)
		{
			$tokenText =  md5(mt_rand() . uniqid());
			$token = self::tryGetByToken($tokenText);
			if (!$token)
				return $tokenText;
		}
	}
}
