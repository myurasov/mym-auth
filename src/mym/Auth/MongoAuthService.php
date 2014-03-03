<?php

/**
 * @copyright 2013, Mikhail Yurasov <me@yurasov.me>
 */

namespace mym\Auth;

class MongoAuthService extends AbstractAuthService
{
  /**
   * @var \MongoClient
   */
  protected $mongoClient;

  protected $database;
  protected $collection;

  private $mongoCollection;

  public function __construct()
  {
    $this->mongoCollection = $this->mongoClient->selectCollection(
      $this->database, $this->collection
    );

    $this->mongoCollection->ensureIndex(
      array('expires' => 1),
      array('expireAfterSeconds' => 0)
    );
  }

  public function removeToken($token)
  {
    $this->mongoCollection->remove(array(
        '_id' => $token
      ));
  }

  public function setUserId($token, $userId, $updateExpiration = false)
  {
    $data = array(
      '_id' => $token,
      'userId' => $userId
    );

    if ($updateExpiration) {
      $data['expires'] = new \MongoDate(time() + $this->tokenLifetime);
    }

    $this->mongoCollection->save($data);
  }

  public function getUserId($token)
  {
    $res = $this->mongoCollection->findOne(array('_id' => $token));
    return isset($res['userId']) ? $res['userId'] : false;
  }

  public function getExpiration($token)
  {
    $res = $this->mongoCollection->findOne(array('_id' => $token));
    return isset($res['expires']) ? \DateTime::createFromFormat('U', $res['expires']->sec) : false;
  }

  public function cleanup()
  {
    // cleanup is done automatically
  }

  public function install()
  {
    // database table is created automatically
  }

  //

  //<editor-fold desc="accessors">

  public function getMongoClient()
  {
    return $this->mongoClient;
  }

  public function setMongoClient(\MongoClient $mongoClient)
  {
    $this->mongoClient = $mongoClient;
  }

  public function getDatabase()
  {
    return $this->database;
  }

  public function setDatabase($database)
  {
    $this->database = $database;
  }

  public function getCollection()
  {
    return $this->collection;
  }

  public function setCollection($collection)
  {
    $this->collection = $collection;
  }

  //</editor-fold>
}
