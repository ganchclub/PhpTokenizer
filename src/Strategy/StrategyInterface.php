<?php

  namespace Funivan\PhpTokenizer\Strategy;

  use Funivan\PhpTokenizer\Collection;

  /**
   *
   * @package Funivan\PhpTokenizer\Query\Strategy
   */
  interface StrategyInterface {

    /**
     * Find next token for check
     * If this method return null we should stop check next tokens
     *
     * @param Collection $collection
     * @param int $currentIndex
     * @return StrategyResult
     */
    public function process(Collection $collection, $currentIndex);

  }