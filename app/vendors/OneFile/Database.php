<?php namespace OneFile;

use PDO;
use PDOStatement;
use Exception;

/**
 *
 * Query Statement Builder Class(es)
 *
 * @author: C. Moller
 * @date: 08 Jan 2017
 *
 * @update: C. Moller
 *   - Moved to OneFile 24 Jan 2017
 *
 *
 * Usage Example(s):
 * -----------------
 * DB::query->from('view_uitstallings')
 * ->where('pakket_id', '=', $pakket_id)
 * ->where('id', '>', $id, ['ignore'=>[null]])
 * ->where('refno', 'IS NULL')
 * ->where('tag_id', 'IN', (array) $tagIDs)
 *
 * ->where('CONCAT(firstname, ' ', lastname)', 'LIKE%', $search)
 *
 * ->where(QueryBuilder::subQuery()
 *    ->where('date', 'BETWEEN', [$fromDate, $toDate], ['ignore'=>[0, null]])
 *    ->orWhere())
 *
 * ->orWhere('name', 'LIKE', $name ? "%$name%" : null, ['ignore'=>null])
 *
 * ->orderBy([$colA => $colAdir, $colB => $colBdir])
 *
 * ->getResults();
 *
 * OR
 *
 * ->limit($offset, $itemspp)
 * ->getBy('id', 'name,description');
 * ->get('id,desc');
 *
 */


/**
 *
 *  @author C. Moller 24 May 2014 <xavier.tnc@gmail.com>
 *
 */
class Database extends PDO
{
  protected $log = array();
  protected $connection = array();

  /**
   *
   * @param array|string $connection
   *   Examples:
   *   ---------
   *   $connection = [
   *    'DBHOST'=>'...',
   *    'DBNAME'=>'...',
   *    'DBUSER'=>'...',
   *    'DBPASS'=>'...'
   *   ];
   *   - OR -
   *   $connection = __DIR__ . '/dbconfig.php;
   */
  public function __construct($connection = null)
  {
    if (is_string($connection) and file_exists($connection))
    {
      $this->connection = include($connection);
    }
    else
    {
      $this->connection = $connection;
    }
    parent::__construct(
      'mysql:host=' . $this->connection['DBHOST'] . ';dbname=' . $this->connection['DBNAME'],
      $this->connection['DBUSER'],
      $this->connection['DBPASS'],
      array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"'
      )
    );
    $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  // public function execute($querySql, $queryParams = null)
  // {
  //   $preparedQuery = $this->prepare($querySql);
  //   $queryResult = $preparedQuery->execute($queryParams);
  //   return $queryResult;
  // }

  public function beginTransction()
  {
    return parent::beginTransaction();
  }

  public function commit()
  {
    return parent::comit();
  }

  public function rollback()
  {
    return parent::rollback();
  }

  /**
   *
   * @param string $tableName
   * @return \QueryStatement
   */
  public function query($tableName = null)
  {
    return new QueryStatement($this, $tableName);
  }

  /**
   *
   * @return \QueryStatement
   */
  public function subQuery()
  {
    return new QueryStatement($this);
  }
}


/**
 *
 * Query Statement Class
 *
 */
class QueryStatement
{
  protected $limit;
  protected $orderBy;
  protected $expressions = array();

  public $db;
  public $tableName;
  public $error;

  /**
   *
   * @param string $tableName Database table name.
   *   e.g. $q = new QueryStatement('tblusers');
   *
   */
  public function __construct($db, $tableName = null)
  {
    $this->db = $db;
    $this->tableName = $tableName;
  }

  /**
   *
   * @param string $leftArg
   * @param string $operator        e.g. '=', '<>', '>', '<', 'IN', ...
   * @param string|array $rightArg  e.g. 'SomeStringVal' or '1,4,12,34' or [1,4,12,34]
   * @param array $options          e.g. ['ignore' => ['', 0, null]]
   * @param string $glue            e.g. 'AND', 'OR'
   *
   */
  public function addExpression($leftArg, $operator = null, $rightArg = null,
    $options = [], $glue = null)
  {
    if ( isset($options['ignore']) )
    {
      $value = $rightArg;
      $valuesToIgnore = $options['ignore'];
      if ( in_array($value, $valuesToIgnore) )
      {
        return $this; // Ignore this test, but allow for subsequent tests via $this.
      }
    }
    if ( ! $this->expressions )
    {
      $glue = null;
    }
    $this->expressions[] = new QueryExpression(
      $leftArg,
      $operator,
      $rightArg,
      $options,
      $glue
    );
    return $this;
  }

  /**
   *
   */
  public function where($leftArg, $expression_operator = null, $rightArg = null, $options = null)
  {
    return $this->addExpression($leftArg, $expression_operator, $rightArg, $options, 'AND');
  }

  /**
   *
   */
  public function orWhere($leftArg, $expression_operator = null, $rightArg = null, $options = null)
  {
    return $this->addExpression($leftArg, $expression_operator, $rightArg, $options, 'OR');
  }

  /**
   * Build an order statement based on the value(s) in $orderBy.
   *
   * If $orderBy is an array or multi-array, assume that it contains multiple order statements.
   * The addition of order statements should be handled outside the scope of this class.
   *
   * @param mixed $orderBy String / Array / Multi-Array.
   *   E.g. $orderBy = 'amount desc' or
   *        $orderBy = ['amount desc', 'time asc'] or
   *        $orderBy = [['amount'=>'desc'],['time'=>'asc']]
   *
   * @return Statement
   */
  public function orderBy($orderBy)
  {
    if ( is_array($orderBy) )
    {
      if ( is_array($orderBy[0]) )
      {
        $orderStatements = array();
        foreach ($orderBy as $orderSpec) { $orderStatements[] = implode(' ', $orderSpec); }
        $orderBy = implode(',', $orderStatements);
      }
      else
      {
        $orderBy = implode(',', $orderBy);
      }
    }
    $this->orderBy = $orderBy ? ' ORDER BY ' . $orderBy : null;
    return $this;
  }

  public function limit($offset, $itemsPerPage)
  {
    $this->limit = " LIMIT $offset,$itemsPerPage";
    return $this;
  }

  public function build(&$params)
  {
    if ( empty($this->expressions) ) { return ''; }
    $sql = '';
    foreach ( $this->expressions as $expression ) { $sql .= $expression->build($params); }
    if ( $sql ) { $sql  = 'WHERE ' . $sql; }
    if ( $this->orderBy ) { $sql .= $this->orderBy; }
    if ( $this->limit ) { $sql .= $this->limit; }
    return $sql;
  }

  public function fetch()
  {
    $sql = $this->build($params);
    $pdoStatement = $this->db->prepeare($sql);
    return $pdoStatement->fetch($params)
  }

  public function fetchAll()
  {

  }

  public function execute()
  {

  }

} //end: Query Statement Class


/**
 *
 * Query Expression Class
 *
 * @author: C. Moller
 * @date: 08 Jan 2017
 *
 * @update: C. Moller
 *   - Moved to OneFile 24 Jan 2017
 *
 */
class QueryExpression
{
  protected $leftArg;
  protected $operator;
  protected $rightArg;
  protected $options;
  protected $glue;

  /*
   * @param string $leftArg
   * @param string $operator         e.g. '=', '<>', '>', '<', 'IN', ...
   * @param string|array $rightArg   e.g. 'SomeStringVal' or '1,4,12,34' or [1,4,12,34]
   * @param array $options           e.g. ['ignore' => ['', 0, null]]
   * @param string $glue             e.g. 'AND', 'OR'
   */
  public function __construct($leftArg, $operator = null, $rightArg = null,
    $options = null, $glue = null)
  {
    $this->leftArg = $leftArg;
    $this->operator = $operator;
    $this->rightArg = $rightArg;
    $this->options = $options ?: array();
    $this->glue = $glue;
  }

  public function build(&$params)
  {
    $glue = $this->glue ? (' ' . $this->glue . ' ') : '';
    if (is_object($this->leftArg) and ($this->leftArg instanceof QueryStatement))
    {
      return $glue . '(' . $this->leftArg->build($params) . ')';
    }
    switch (strtoupper($this->operator))
    {
      case 'BETWEEN':
        $params = array_merge($params, $this->rightArg);
        return "$glue ({$this->leftArg} BETWEEN ? AND ?)";
      default:
        if (isset($this->rightArg))
        {
          $params[] = $this->rightArg;
          return $glue . $this->leftArg . ' ' . $this->operator . ' ?';
        }
        return $glue . $this->leftArg . ' ' . $this->operator;
    }
  }
}
