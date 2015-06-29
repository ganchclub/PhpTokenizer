<?php

  namespace Funivan\PhpTokenizer\QuerySequence;

  use Funivan\PhpTokenizer\Collection;
  use Funivan\PhpTokenizer\Exception\InvalidArgumentException;
  use Funivan\PhpTokenizer\Query\QueryInterface;
  use Funivan\PhpTokenizer\Strategy\Move;
  use Funivan\PhpTokenizer\Strategy\Possible;
  use Funivan\PhpTokenizer\Strategy\Search;
  use Funivan\PhpTokenizer\Strategy\StrategyInterface;
  use Funivan\PhpTokenizer\Strategy\Strict;
  use Funivan\PhpTokenizer\Token;

  /**
   * Start from specific position and check token from this position according to strategies
   */
  class QuerySequence implements QuerySequenceInterface {

    /**
     * @var bool
     */
    private $valid = true;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var
     */
    private $skipWhitespaces = false;

    /**
     * @inheritdoc
     */
    public function __construct(Collection $collection, $initialPosition = 0) {
      $this->collection = $collection;
      $this->position = $initialPosition;
    }

    /**
     * @return Collection
     */
    public function getCollection() {
      return $this->collection;
    }


    /**
     * @param int $position
     * @return QuerySequence
     */
    public function setPosition($position) {
      $this->position = $position;
      return $this;
    }

    /**
     * @return int
     */
    public function getPosition() {
      return $this->position;
    }

    /**
     * Strict validation of condition
     *
     * @param int|string $condition
     * @return Token
     */
    public function strict($condition) {
      $query = $this->buildQuery($condition, Strict::create());
      return $this->process($query);
    }

    /**
     * Check if token possible valid for our condition
     *
     * @param int|string $condition
     * @return Token
     */
    public function possible($condition) {
      $query = $this->buildQuery($condition, Possible::create());
      return $this->process($query);
    }

    /**
     * @param string $start
     * @param string $end
     * @return Collection
     */
    public function section($start, $end) {

      $token = $this->search($start);
      if (!$token->isValid()) {
        # cant find start position
        return new Collection();
      }

      $this->moveTo($token->getIndex());

      $section = new \Funivan\PhpTokenizer\Strategy\Section();
      $section->setDelimiters($start, $end);
      $lastToken = $this->process($section);
      if (!$lastToken->isValid()) {
        return new Collection();
      }

      return $this->collection->extractByTokens($token, $lastToken);
    }

    /**
     * By default we search forward
     *
     * @param int|string $condition
     * @param null $direction
     * @return Token
     */
    public function search($condition, $direction = null) {
      $strategy = Search::create();
      if ($direction !== null) {
        $strategy->setDirection($direction);
      }
      $query = $this->buildQuery($condition, $strategy);
      return $this->process($query);
    }

    /**
     * @param int $steps
     * @return Token
     */
    public function move($steps) {
      return $this->process(Move::create($steps));
    }

    /**
     * Move to specific position
     *
     * @param int $tokenIndex
     * @return Token|null
     */
    public function moveTo($tokenIndex) {

      foreach ($this->collection as $index => $token) {
        if ($token->getIndex() == $tokenIndex) {
          $this->setPosition($index);
          return $token;
        }
      }

      return new Token();
    }


    /**
     * @param array $conditions
     * @return Collection
     */
    public function sequence(array $conditions) {
      $range = new Collection();
      foreach ($conditions as $value) {
        $range[] = $this->check($value);
      }

      return $range;
    }

    /**
     * @param string|int|StrategyInterface $value
     * @return Token
     */
    private function check($value) {
      if ($value instanceof StrategyInterface) {
        $query = $value;
      } else {
        $query = $this->buildQuery($value, Strict::create());
      }

      $token = $this->process($query);
      return $token;
    }

    /**
     * @inheritdoc
     */
    public function process(StrategyInterface $strategy) {

      if ($this->isValid() === false) {
        return new Token();
      }

      $result = $strategy->process($this->collection, $this->getPosition());

      if ($result->isValid() === false) {
        $this->setValid(false);
        return new Token();
      }

      $position = $result->getNexTokenIndex();
      $this->setPosition($position);

      $token = $result->getToken();
      if ($token === null) {
        $token = new Token();
      }

      if ($this->skipWhitespaces and isset($this->collection[$position]) and $this->collection[$position]->getType() === T_WHITESPACE) {
        # skip whitespaces in next check
        $this->setPosition(($position + 1));
      }

      return $token;
    }

    /**
     * @todo change queryInterface
     *
     * @param StrategyInterface|string|int $value
     * @param QueryInterface $defaultStrategy
     * @return StrategyInterface
     */
    private function buildQuery($value, QueryInterface $defaultStrategy) {
      if (is_string($value) or $value === null) {
        $query = $defaultStrategy;
        $query->valueIs($value);
      } elseif (is_int($value)) {
        $query = $defaultStrategy;
        $query->typeIs($value);
      } else {
        throw new InvalidArgumentException("Invalid token condition. Expect string or int or StrategyInterface");
      }

      return $query;
    }


    /**
     * @inheritdoc
     */
    public function valid() {
      $position = $this->getPosition();
      return isset($this->collection[$position]);
    }


    /**
     * @param boolean $valid
     * @return $this
     */
    public function setValid($valid) {
      if (!is_bool($valid)) {
        throw new InvalidArgumentException("Invalid flag. Expect boolean. Given:" . gettype($valid));
      }
      $this->valid = $valid;
      return $this;
    }

    /**
     * Indicate state of all conditions
     *
     * @return bool
     */
    public function isValid() {
      return ($this->valid === true);
    }

    /**
     * @param boolean $skipWhitespaces
     * @return $this
     */
    public function setSkipWhitespaces($skipWhitespaces) {
      $this->skipWhitespaces = $skipWhitespaces;
      return $this;
    }
    
  }