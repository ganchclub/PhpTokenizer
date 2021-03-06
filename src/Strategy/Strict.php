<?php

  namespace Funivan\PhpTokenizer\Strategy;


  /**
   *
   * @package Funivan\PhpTokenizer\Query\Strategy
   */
  class Strict extends QueryStrategy {

    /**
     * @inheritdoc
     */
    public function process(\Funivan\PhpTokenizer\Collection $collection, $currentIndex) {

      $result = new StrategyResult();
      $result->setValid(true);

      $token = $collection->offsetGet($currentIndex);

      if ($token === null or $this->isValid($token) === false) {
        $result->setValid(false);
        return $result;
      }

      $result->setNexTokenIndex(++$currentIndex);
      $result->setToken($token);

      return $result;
    }

  }