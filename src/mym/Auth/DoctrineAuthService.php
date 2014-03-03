<?php

/**
 * Auth service for Doctrine DBAL
 * @copyright 2014 Mikhail Yurasov <me@yurasov.me>
 */

namespace mym\Auth;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class DoctrineAuthService extends AbstractAuthService
{
  /**
   * @var Connection
   */
  protected $connection;

  /**
   * @var string
   */
  protected $tableName = 'Auth';

  public function getUserId($token)
  {
    $qb = $this->connection->createQueryBuilder();

    $qb->select('t.userId')
      ->from($this->tableName, 't')
      ->where('token = ?')
      ->andWhere('expires > ?')
      ->setParameter(0, $token)
      ->setParameter(1, time());

    if ($r = $qb->execute()->fetch()) {
      return $r['userId'];
    } else {
      return false;
    }
  }

  public function setUserId($token, $userId, $updateExpiration = false)
  {
    // try to update

    $data = array('userId' => $userId);

    if ($updateExpiration) {
      $data['expires'] = time() + $this->tokenLifetime;
    }

    $r = $this->connection->update($this->tableName, $data,
      array('token' => $token) // where
    );

    if (0 === $r) {
      // update failed - create new record
      $this->connection->insert($this->tableName, array(
          'userId' => $userId,
          'token' => $token,
          'expires' => time() + $this->tokenLifetime
        ));
    }
  }

  public function getExpiration($token)
  {
    $qb = $this->connection->createQueryBuilder();

    $qb->select('t.expires')
      ->from($this->tableName, 't')
      ->where('token = ?')
      ->andWhere('expires > ?')
      ->setParameter(0, $token)
      ->setParameter(1, time());

    if ($r = $qb->execute()->fetch()) {
      return (int) $r['expires'];
    } else {
      return false;
    }
  }

  public function removeToken($token)
  {
    $qb = $this->connection->createQueryBuilder();

    $qb->delete($this->tableName)
      ->where('token = ?')
      ->setParameter(0, $token, Type::STRING);

    $qb->execute();
  }

  public function cleanup()
  {
    $qb = $this->connection->createQueryBuilder();

    $qb->delete($this->tableName)
      ->where('expires <= ?')
      ->setParameter(0, time(), Type::BIGINT);

    $qb->execute();
  }

  public function install()
  {
    // create schema

    $schema = new Schema();
    $table = $schema->createTable($this->tableName);
    $table->addColumn('id', Type::INTEGER, array('autoincrement' => true));
    $table->addColumn('token', Type::STRING, array('length' => 256, 'unique' => true));
    $table->addColumn('userId', Type::INTEGER);
    $table->addColumn('expires', Type::BIGINT, array('unsigned' => true));
    $table->addIndex(array('token'));
    $table->addIndex(array('expires'));
    $table->setPrimaryKey(array('id'));

    // drop existing table

    if (in_array($this->tableName, $this->connection->getSchemaManager()->listTableNames())) {
      foreach ($schema->toDropSql($this->connection->getDatabasePlatform()) as $sql) {
        $this->connection->exec($sql);
      }
    }

    // create table

    foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
      $this->connection->exec($sql);
    }
  }

  //<editor-fold desc="accessors">

  public function setConnection(Connection $connection)
  {
    $this->connection = $connection;
  }

  public function getConnection()
  {
    return $this->connection;
  }

  public function setTableName($tableName)
  {
    $this->tableName = $tableName;
  }

  public function getTableName()
  {
    return $this->tableName;
  }

  //</editor-fold>
}