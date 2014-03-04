<?php

/**
 * @copyright 2013, Mikhail Yurasov <me@yurasov.me>
 */

namespace mym\Auth;

use Doctrine\Common\Persistence\ObjectRepository;
use mym\Util\Strings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

abstract class AbstractAuthService
{
  protected $tokenName = 'authToken';
  protected $tokenLifetime = 604800; // [s] 1 week
  protected $cookieDomain = null;

  /**
   * @param $token string
   * @return mixed
   */
  abstract public function getUserId($token);

  /**
   * @param $token string
   * @param $userId mixed
   * @param bool $updateExpiration boolean
   */
  abstract public function setUserId($token, $userId, $updateExpiration = false);

  /**
   * @param $token string
   * @return int
   */
  abstract public function getExpiration($token);

  abstract public function removeToken($token);

  /**
   * Remove old sessions
   */
  abstract public function cleanup();

  /**
   * Initialize storage
   */
  abstract public function install();

  public function createToken($userId)
  {
    $token = Strings::createRandomString(null, Strings::ALPHABET_ALPHANUMERICAL, 512);
    $this->setUserId($token, $userId, true);
    return $token;
  }

  public function getTokenFromRequest(Request $request)
  {
    // get from cookie
    $token = $request->cookies->get($this->tokenName);

    // try GET
    if (!$token) {
      $token = $request->get($this->tokenName);
    }

    return $token;
  }

  /**
   * @param Request $request
   * @return string|bool
   */
  public function getUserIdFromRequest(Request $request)
  {
    $token = $this->getTokenFromRequest($request);

    if ($token) {
      return $this->getUserId($token);
    }

    return false;
  }

  public function saveTokenToCookie(Response $response, $token)
  {
    $cookie = new Cookie(
      $this->tokenName,
      $token,
      time() + $this->tokenLifetime,
      '/',
      $this->cookieDomain,
      false,
      true
    );

    $response->headers->setCookie($cookie);
  }

  public function clearTokenCookie(Response $response)
  {
    $cookie = new Cookie($this->tokenName, '', 0, '/', $this->cookieDomain, false, true);
    $response->headers->setCookie($cookie);
  }

  // <editor-fold defaultstate="collapsed" desc="accessors">

  public function getTokenLifetime()
  {
    return $this->tokenLifetime;
  }

  public function setTokenLifetime($tokenLifetime)
  {
    $this->tokenLifetime = $tokenLifetime;
  }

  public function getTokenName()
  {
    return $this->tokenName;
  }

  public function setTokenName($tokenName)
  {
    $this->tokenName = $tokenName;
  }

  public function getCookieDomain()
  {
    return $this->cookieDomain;
  }

  public function setCookieDomain($cookieDomain)
  {
    $this->cookieDomain = $cookieDomain;
  }

  // </editor-fold>

  //<editor-fold desc="Singleton implementation">

  protected static $instance;

  /**
   * @return static
   */
  public static function getInstance() {
    if (is_null(static::$instance)) {
      static::$instance = new static();
    }

    return static::$instance;
  }

  /**
   * @return static
   */
  public static function get()
  {
    return static::getInstance();
  }

  public function __construct() {
    if (static::$instance) {
      throw new \Exception('Class ' . get_called_class() .' is a singleton');
    } else {
      static::$instance = $this;
    }
  }

  //</editor-fold>
}