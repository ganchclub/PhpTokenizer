<?php

  namespace Test\Funivan\PhpTokenizer\Tokenizer\Strategy;

  use Funivan\PhpTokenizer\Collection;
  use Funivan\PhpTokenizer\Strategy\Search;
  use Funivan\PhpTokenizer\TokenStream;
  use Test\Funivan\PhpTokenizer\MainTestCase;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 4/17/15
   */
  class SearchTest extends MainTestCase {

    public function testSearchDefault() {

      $code = '<?php 
      
      echo $a;
      echo 1 . $a;
      
      
      ';

      $collection = Collection::initFromString($code);
      $finder = new TokenStream($collection);

      $linesWithEcho = array();

      while ($q = $finder->iterate()) {
        $start = $q->strict('echo');
        $end = $q->search(';');

        if ($q->isValid()) {
          $linesWithEcho[] = $collection->extractByTokens($start, $end);
        }
      }

      $this->assertCount(2, $linesWithEcho);
      $this->assertEquals('echo $a;', $linesWithEcho[0]);
      $this->assertEquals('echo 1 . $a;', $linesWithEcho[1]);

    }


    /**
     *
     */
    public function testBackwardSearch() {

      $code = '<?php 
      
      
      echo $name;
      echo $userName;
      
      
      ';

      $collection = Collection::initFromString($code);
      $finder = new TokenStream($collection);

      $linesWithEcho = array();

      while ($q = $finder->iterate()) {

        $q->strict('echo');
        $q->search(';');
        $variable = $q->search(T_VARIABLE, Search::BACKWARD);
        if ($q->isValid()) {
          $linesWithEcho[] = $variable;
        }
      }

      $this->assertCount(2, $linesWithEcho);
      $this->assertEquals('$name', (string)$linesWithEcho[0]);
      $this->assertEquals('$userName', (string)$linesWithEcho[1]);

    }

    /**
     * @expectedException \Funivan\PhpTokenizer\Exception\InvalidArgumentException
     */
    public function testInvalidDirection() {
      Search::create()->setDirection(null);
    }


    public function testSearchBackwardWithInvalidFailureCondition() {

      $code = '<?php 
      
      echo $name;
      echo $userName;
      
      
      ';

      $collection = Collection::initFromString($code);
      $finder = new TokenStream($collection);

      $linesWithEcho = array();

      while ($q = $finder->iterate()) {

        $q->strict('echo');
        $q->search(';');
        $variable = $q->search('(string)', Search::BACKWARD);
        if ($q->isValid()) {
          $linesWithEcho[] = $variable;
        }
      }

      $this->assertCount(0, $linesWithEcho);
      
    }

  }
