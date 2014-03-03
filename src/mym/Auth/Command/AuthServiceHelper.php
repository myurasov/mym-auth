<?php

/**
 * @copyright 2014 Mikhail Yurasov <me@yurasov.me>
 */

namespace mym\Auth\Command;

use mym\Auth\AbstractAuthService;
use Symfony\Component\Console\Helper\Helper;

class AuthServiceHelper extends Helper
{
  /**
   * @var AbstractAuthService
   */
  protected $authService;

  public function __construct(AbstractAuthService $authService)
  {
    $this->authService = $authService;
  }

  public function getAuthService()
  {
    return $this->authService;
  }

  /**
   * {@inheritdoc}
   */
  public function getName()
  {
    return 'mymAuthService';
  }
}