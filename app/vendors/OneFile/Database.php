<?php namespace OneFile;

use PDO;
use PDOStatement;
use Exception;

/**
 *
 * Database Class
 *
 * @author: C. Moller
 * @date: 08 Jan 2017
 *
 * @update: C. Moller - 24 Jan 2017
 *   - Moved to OneFile
 *
 * @update: C. Moller - 19 Jan 2020
 *   - Simplify classes + Change query builder syntax!
 *   - Re-write build() methods
 *
 *
 * Usage Example(s):
 * -----------------
 * $db->query->from('view_uitstallings')
 * ->where('pakket_id=?', $pakket_id)
 * ->where('id>?', $id, ['ignore'=>null])
 * ->where('refno IS NULL')
 * ->where('tag_id IN (?)', implode(',', $tagIDs))
 *
 * ->where('CONCAT(firstname, " ", lastname) LIKE ?)', "%$search%")
 *
 * ->where($db->subQuery()
 *    ->where('date BETWEEN (?,?)', [$fromDate, $toDate])
 *    ->orWhere(is_weekend IS NOT NULL)
 *   )
 *
 * ->orWhere('name LIKE ?', "%$name%", ['ignore'=>null])
 *
 * ->orderBy([$colA => $colAdir, $colB => $colBdir])
 *
 * ->getResults();
 *
 * OR
 *
 * ->limit($itemspp, $offset)
 *
 * ->orderBy(['amount desc', 'time asc'])
 *
 * ->orderBy('date desc')
 *
 * ->indexBy('id')->getAll('DISTINCT name,description')
 *
 * ->getFirst('id,desc')
 *
 */


/**
 *
 *  @author C. Moller 24 May 2014 <xavier.tnc@gmail.com>
 *
 */
class Database extends PDO
{
  public $log = array();
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
   * @param string $sqlCmd SQL Command e.g. INSERT, UPDATE, DELETE ...
   * @return \QueryStatement
   */
  public function cmd($sqlCmd)
  {
    $affectedRows = $this->db->exec($sqlCmd);
    return $affectedRows;
  }

  /**
   *
   * @param string $sql
   * @return \QueryStatement
   */
  public function queryRaw($sql)
  {
    return parent::query($sql);
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
  protected $select;
  protected $limit;
  protected $orderBy;
  protected $indexBy;
  protected $expressions = [];

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
   * @param string $paramsExpr  e.g. 'id=?', 'status=? AND age>=?', 'name LIKE ?'
   * @param mixed  $params      e.g. 100, [100], [1,46], '%john%', ['john']
   * @param array  $options     e.g. ['ignore' => ['', 0, null], glue => 'OR']
   *
   */
  public function addExpression($paramsExpr, $params = null, $options = [])
  {
    if ( ! $params)
    {
      $params = [];
    }
    elseif ( ! is_array($params))
    {
      $params = [$params];
    }
    // Only do "ignore check" on single param expressions!
    if ( count($params) == 1 and isset($options['ignore']) )
    {
      $value = $params[0];
      $valuesToIgnore = $options['ignore'];
      // Ignore this expression if it has an "ignore me" param value!
      // Allow processing remaining expressions by returning $this.
      if ($value === $valuesToIgnore or is_array($valuesToIgnore) and in_array($value, $valuesToIgnore))
      {
        return $this;
      }
    }
    if ($this->expressions and empty($options['glue']))
    {
      $options['glue'] = 'AND';
    }
    $this->expressions[] = new QueryExpression($paramsExpr, $params, $options);
    return $this;
  }

  /**
   *
   * @param string $selectDef  e.g. 'id,name', 'DISTINCT firstname', 'COUNT(hasOption)', ...
   *
   */
  public function select($selectDef = '*')
  {
    $this->select = $selectDef;
    return $this;
  }

  /**
   *
   */
  public function where($paramsExpr, $params = [], $options = [])
  {
    return $this->addExpression($paramsExpr, $params, $options);
  }

  /**
   *
   */
  public function orWhere($paramsExpr, $params = [], $options = [])
  {
    return $this->addExpression($paramsExpr, $params, array_merge($options, [glue => 'OR']));
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

  public function limit($itemsPerPage, $offset = 0)
  {
    $this->limit = " LIMIT $offset,$itemsPerPage";
    return $this;
  }

  public function indexBy($columnName)
  {
    $this->indexBy = $columnName;
    return $this;
  }

  public function build(&$params)
  {
    $sql = '';
    foreach ( $this->expressions as $expression ) { $sql .= $expression->build($params); }
    if ( $sql ) { $sql  = 'WHERE ' . $sql; }
    if ( $this->orderBy ) { $sql .= $this->orderBy; }
    if ( $this->limit ) { $sql .= $this->limit; }
    return $this->expressions ? $sql : trim($sql);
  }

  public function count()
  {
    $sql = 'SELECT COUNT(*) FROM ' . $this->tableName;
    $where = $this->build($params);
    if ($where) { $sql .= ' ' . $where; }
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->queryRaw($sql);
    return $preparedPdoStatement->fetchColumn();
  }

  protected function indexResults($results, $columnName)
  {
    $indexedResult = [];
    foreach($results as $result)
    {
      $indexedResult[$result->{$columnName}] = $result;
    }
    return $indexedResult;
  }

  public function getAll($select = null, $indexBy = null)
  {
    $sql = 'SELECT ' . ($select ?: $this->select?:'*') . ' FROM ' . $this->tableName;
    // NOTE: $params is passed to build() by ref. i.e. updated as we build()
    $where = $this->build($params);
    if ($where) { $sql .= ' ' . $where; }
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->prepare($sql);
    if ($preparedPdoStatement->execute($params))
    {
      return $this->indexBy
        ? $this->indexResults($preparedPdoStatement->fetchAll(PDO::FETCH_OBJ), $this->indexBy)
        : $preparedPdoStatement->fetchAll(PDO::FETCH_OBJ);
    }
    return [];
  }

  public function getFirst($select = null)
  {
    $sql = 'SELECT ' . ($select ?: $this->select?:'*') . ' FROM ' . $this->tableName;
    $where = $this->build($params);
    if ($where) { $sql .= ' ' . $where; }
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->prepare($sql);
    if ($preparedPdoStatement->execute($params))
    {
      return $preparedPdoStatement->fetch(PDO::FETCH_OBJ);
    }
  }

  public function update($data = null)
  {
    $values = [];
    $setPairs = [];
    foreach($data as $colName => $value)
    {
      $setPairs[] = "$colName=?";
      $values[] = $value;
    }
    $setPairsSql = implode(',', $setPairs);
    $sql = "UPDATE {$this->tableName} SET {$setPairsSql}";
    $where = $this->build($params);
    if ($where) { $sql .= ' ' . $where; }
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->prepare($sql);
    $preparedPdoStatement->execute(array_merge($values, $params));
    return $preparedPdoStatement->rowCount();
  }

} //end: Query Statement Class


/**
 *
 * Query Expression Class
 *
 * @author: C. Moller
 * @date: 08 Jan 2017
 *
 * @update: C. Moller - 24 Jan 2017
 *   - Moved to OneFile
 *
 * @update: C. Moller - 19 Jan 2020
 *   - Simplyfy contructor. No more OPERATOR + GLUE params
 *   - Re-write build() method
 *
 */
class QueryExpression
{
  protected $paramsExpr;
  protected $params;
  protected $options;

  /*
   * @param string $paramsExpr  e.g. 'id=?', 'status=? AND age>=?', 'name LIKE ?'
   * @param mixed  $params      e.g. 100, [100], [1,46], '%john%', ['john']
   * @param array  $options     e.g. ['ignore' => ['', 0, null], glue => 'OR']
   */
  public function __construct($paramsExpr, $params = null, $options = null)
  {
    $this->paramsExpr = $paramsExpr;
    $this->params = $params?:[];
    $this->options = $options?:[];
  }

  public function build(&$params)
  {
    if ( ! $params) { $params = []; }
    $glue = isset($this->options['glue']) ? (' ' . $this->options['glue'] . ' ') : '';
    $params = $params + $this->params;
    if (is_object($this->paramsExpr) and ($this->paramsExpr instanceof QueryStatement))
    {
      return $glue . '(' . $this->paramsExpr->build($params) . ')';
    }
    return $glue . $this->paramsExpr;
  }
}
