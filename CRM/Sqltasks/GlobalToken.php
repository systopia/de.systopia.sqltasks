
<?php

/**
 * This class is adapter which safely works with sqltasks global tokens
 */
class CRM_Sqltasks_GlobalToken {

  /**
   * Setting name in CiviCRM
   * There located sqltasks global tokens
   */
  const SETTING_NAME = 'sqltasks_global_tokens';

  /**
   * Max length of token name
   */
  const MAX_LENGTH_OF_TOKEN_NAME = 60;

  /**
   * Instance
   *
   * @var CRM_Sqltasks_GlobalToken
   */
  private static $instance = null;

  /**
   * All tokens
   *
   * @var array
   */
  private $tokens;

  /**
   * Gets the instance
   *
   * @return CRM_Sqltasks_GlobalToken
   */
  public static function singleton() {
    if (static::$instance === null) {
      static::$instance = new static();
    }

    return static::$instance;
  }

  private function __construct() {
    $this->tokens = $this->getTokensFromSettings();
  }

  /**
   * Gets all tokens from CiviCRM Settings
   *
   * @return array
   */
  private function getTokensFromSettings() {
    $tokens = Civi::settings()->get(self::SETTING_NAME);

    return is_array($tokens) ? $tokens : [];
  }

  /**
   * Gets token value
   *
   * @param $tokenName
   * @return string
   */
  public function getValue($tokenName) {
    $tokenName = (string) $tokenName;

    if (!empty($tokenName) && array_key_exists($tokenName, $this->tokens)) {
      return $this->tokens[$tokenName];
    }

    return '';
  }

  /**
   * Sets value to token
   * If token not exist it creates new one
   * else it updates token's value
   *
   * @param $tokenName
   * @param $tokenValue
   */
  public function setValue($tokenName, $tokenValue) {
    if (empty($tokenName)) {
      return;
    }

    $tokenName = (string) $tokenName;
    $this->tokens[$tokenName] = $tokenValue;
    $this->save();
  }

  /**
   * Saves tokens into CiviCRM Settings
   */
  private function save() {
    Civi::settings()->set(self::SETTING_NAME, $this->tokens);
  }

  /**
   * Gets token data
   *
   * @param $tokenName
   * @return array
   */
  public function getTokenData($tokenName) {
    return [
      'name' => (string) $tokenName,
      'value' => $this->getValue($tokenName),
    ];
  }

  /**
   * Gets all tokens data
   *
   * @return array
   */
  public function getAllTokenData() {
    $tokensData = [];
    foreach ($this->tokens as $tokenName => $tokenValue) {
      $tokensData[] = $this->getTokenData($tokenName);
    }

    return $tokensData;
  }

  /**
   * Is token exist?
   *
   * @param $tokenName
   * @return mixed|string
   */
  public function isTokenExist($tokenName) {
    $tokenName = (string) $tokenName;

    return !empty($tokenName) && array_key_exists($tokenName, $this->tokens);
  }

  /**
   * Delete token
   *
   * @param $tokenName
   */
  public function delete($tokenName) {
    if (empty($tokenName)) {
      return;
    }

    $tokenName = (string) $tokenName;

    if ($this->isTokenExist($tokenName)) {
      unset($this->tokens[$tokenName]);
      $this->save();
    }
  }

  private function __clone() {}
  private function __wakeup() {}

}
