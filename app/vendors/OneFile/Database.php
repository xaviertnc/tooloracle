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
 * @update: C. Moller - 29 Feb 2020
 *   - Add db->insertInto()
 *   - Add db->updateOrInsertInto()
 *   - Add db->arrayIsSingleRow()
 *   - Add db->indexList()
 *   - Add db->query()->update()
 *   - Add db->query()->delete()
 *
 * @update: C. Moller - 04 Mar 2020
 *   - Add db->update()
 *
 *
 * Query Examples:
 * -----------------
 * $db->query->from('view_exhibitions')
 * $db->query->from('tbl_users')
 *
 * ->select('id,desc AS bio')
 * ->select('count(id) as TotalItems')
 * ->select('*,CONCAT(firstname," ",lastname) as name')
 *
 * ->where('refno IS NULL')
 * ->where('pakket_id=?', $pakket_id)
 * ->where('id>?', $id, ['ignore'=>null])
 * ->where('tag_id', $arrTagIDs, ['test'=>'IN'])
 * ->where('tag_id', ['one','two','three'], ['test'=>'NOT IN'])
 * ->where('tag_id NOT IN (?,?,?)' , ['one','two','three'], ['ignore'=>null])
 * ->where('tag_id IN (' . implode(',', $arTagIDs) . ')') // Unsafe
 * ->where(
 *   $db->subQuery()
 *     ->where('date1 BETWEEN (?,?)', [$minDate,$maxDate])         // Exclusive
 *     ->where('date2', [$fromDate,$toDate], ['test'=>'FROM TO'])  // Inclusive
 *     ->where('age', [$minAge,$maxAge], ['test'=>'FROM TO'])
 *     ->orWhere(is_weekend IS NOT NULL)
 * )
 *
 * ->orWhere('CONCAT(firstname," ",lastname) LIKE ?)', "%$nameTerm%")
 * ->orWhere('name LIKE ?', "%$nameTerm%", ['ignore'=>[null,'']])
 * ->orWhere("name LIKE '$nameTerm%'") // Unsafe
 *
 * ->orderBy('date desc')
 * ->orderBy(['amount desc', 'time asc'])
 * ->orderBy([$col1=>$col1dir, $col2=>$col2dir])

 * ->limit(100)
 * ->limit(100, 15)
 * ->limit($itemspp, $offset)
 *
 * ->indexBy('id')
 * ->indexBy(['type','color'])  // index == "{type}-{color}"
 *
 * ->getAll();
 * ->getAll('id,desc');
 * ->getAll('DISTINCT name, desc AS bio');
 *
 * ->getFirst();
 * ->getFirst('id,desc');
 *
 *
 * Insert Examples:
 * -----------------
 * $db->insertInto('tbl_users', $objUser)
 * $db->insertInto('tbl_users', [$objUser1, $objUser2, ...])
 * $db->insertInto('tbl_users', $arrUser)
 * $db->insertInto('tbl_users', [$arrUser1, $arrUser2, ...])
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
    return parent::commit();
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

  /**
   * Inserts a single or multiple data rows into a database table.
   * Auto detect multi-row insert.
   * @param string $tableName
   * @param array|object $row
   * @return boolean success
   */
  public function insertInto( $tableName, $row )
  {
    if( ! $row ) { return false; }
    if( is_array( $row ) and ! $this->arrayIsSingleRow( $row ) )
    {
      $this->log[] = 'Multi-row = TRUE';
      $rows = $row;
    }
    else
    {
      $rows = [ $row ];
    }
    $i = 0;
    $sql = '';
    $qMarks = [];
    $colNames = [];
    $affectedRows = [ 'insert' => 0, 'failed' => 0 ];
    try {
      $this->beginTransaction();
      foreach( $rows as $row )
      {
        if( is_object($row) )
        {
          $row = (array) $row;
        }
        if( $i == 0 )
        {
          foreach($row as $colName => $colValue)
          {
            $qMarks[] = '?';
            $colNames[] = $colName;
          }
          $qMarksSql = implode( ',', $qMarks );
          $colNamesSql = implode( ',', $colNames );
          $sql = "INSERT INTO {$tableName} ({$colNamesSql}) VALUES ({$qMarksSql})";
          $preparedPdoStatement = $this->prepare( $sql );
          $this->log[] = 'Batch stmt: ' . $sql;
        }
        // Execute the same prepared statement for each row provided!
        if( $preparedPdoStatement->execute( array_values( $row ) ) )
        {
          $affectedRows[ 'insert' ]++;
        }
        else {
          $affectedRows[ 'failed' ]++;
        }
        $i++;
      }
      $this->log[] = 'affectedRows: ' . print_r( $affectedRows, true );
      $this->commit();
    }
    catch( Exception $e )
    {
      $affectedRows[ 'insert' ] = 0;
      $this->rollback();
      throw $e;
    }
    return $affectedRows;
  } // end: batchInsert

  /**
   * Update OR Insert a single or multiple rows into a database table.
   * PS: The table MUST have UNIQUE primary key contraint
   *     for Insert OR Update to work!
   * @param string $tableName
   * @param array|stdClass $row
   * @param boolean $updateOnly
   * @return boolean success
   */
  public function updateOrInsertInto( $tableName, $row = null, $updateOnly = false )
  {
    if( ! $row ) { return false; }
    if( is_array( $row ) and ! $this->arrayIsSingleRow( $row ) )
    {
      $this->log[] = 'Multi-row = TRUE';
      $rows = $row;
    }
    else
    {
      $rows = [ $row ];
    }
    $sql = '';
    $qMarks = [];
    $setPairs = [];
    $colNames = [];
    $affectedRows = [ 'new' => 0, 'updated' => 0 ];
    try {
      $this->beginTransaction();
      // Extract column info from the first row!
      //  + Build SQL and prepare statements based on info
      $firstRow = reset( $rows );
      if( $rowsAreObjects = is_object( $firstRow ) )
      {
        $firstRow = (array) $firstRow;
      }
      // $this->log[] = 'updateOrInsert() firstRow: ' . print_r( $firstRow, true );
      if( $updateOnly )
      {
        foreach($firstRow as $colName => $colValue) { $updPairs[] = "$colName=?"; }
        $updPairsSql = implode( ',', $updPairs );
        $sql = "UPDATE {$tableName} SET {$updPairsSql};";
      }
      else
      {
        foreach($firstRow as $colName => $colValue)
        {
          $qMarks[]   = '?';
          $colNames[] = $colName;
          $updPairs[] = "$colName=VALUES($colName)";
        }
        $qMarksSql   = implode( ',', $qMarks   );
        $colNamesSql = implode( ',', $colNames );
        $updPairsSql = implode( ',', $updPairs );
        $sql = "INSERT INTO {$tableName} ({$colNamesSql}) VALUES ({$qMarksSql}) ";
        $sql.= "ON DUPLICATE KEY UPDATE {$updPairsSql};";
      }
      $preparedPdoStatement = $this->prepare( $sql );
      $this->log[] = 'Batch stmt: ' . $sql;
      // $this->log[] = 'updateOrInsert() rows: ' . print_r( $rows, true );
      // Insert or update rows...
      foreach( $rows as $i => $row )
      {
        $params = array_values( $rowsAreObjects ? (array) $row : $row );
        // $this->log[] = 'params: ' . print_r($params, true);
        $preparedPdoStatement->execute( $params );
        switch( $preparedPdoStatement->rowCount() )
        {
          case 1: $affectedRows[ 'new' ]++; break;
          case 2: $affectedRows[ 'updated' ]++; break;
        }
      } // end: Update rows loop
      $this->log[] = 'affectedRows: ' . print_r( $affectedRows, true );
      $this->commit();
    } // end: try
    catch( Exception $e )
    {
      $this->rollback();
      throw $e;
    } // end: catch
    return $affectedRows;
  } // end: updateOrInsert

  /**
   * Update a single or multiple database table rows in one call.
   * @param string $tableName
   * @param array|stdClass $row
   * @return boolean success
   */
  public function update( $tableName, $row = null )
  {
    return self::updateOrInsertInto( $tableName, $row, 'update-only' );
  }

  /**
   * Utillity
   * Detect if an ARRAY represents a
   * single DB row or a collection of rows.
   * @param array $array
   * @return boolean  yes/no
   */
  public function arrayIsSingleRow( array $array )
  {
    return is_scalar( reset( $array ) );
  }

  /**
   * Utility
   * Re-index a list of Objects or Arrays.
   * Use a SINGLE or MULTIPLE item properties as the new index.
   * @param array $list
   * @param string|array $propNames The name (str) or names (array)
   *   of item properties that should make up the new index.
   * @return boolean success
   */
  public function indexList( array $list, $propNames )
  {
    if ( ! $list ) { return $list; }

    $indexedList = [];
    if( ! is_array( $propNames ) )
    {
      $indexPropName = $propNames;
      foreach( $list as $listItem )
      {
        $li = (array) $listItem;
        $indexedList[ $li[ $indexPropName ] ] = $listItem;
      }
    }
    else
    {
      foreach( $list as $listItem )
      {
        $li = (array) $listItem;
        $indexPropValues = [];
        foreach( $propNames as $indexPropName )
        {
          $indexPropValues[] = $li[ $indexPropName ];
        }
        $index = implode( '-', $indexPropValues );
        $indexedList[ $index ] = $listItem;
      }
    }
    return $indexedList;
  } // end: indexList

} // end: Database class


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
  public function where( $paramsExpr, $params = [], $options = [] )
  {
    return $this->addExpression( $paramsExpr, $params, $options );
  }

  /**
   *
   */
  public function orWhere( $paramsExpr, $params = [], $options = [] )
  {
    return $this->addExpression(
      $paramsExpr, $params, array_merge( $options, [ 'glue' => 'OR' ] )
    );
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

  public function indexBy($columnNames)
  {
    $this->indexBy = $columnNames;
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

  public function getAll( $select = null )
  {
    $sql = 'SELECT ' . ( $select ?: $this->select?:'*' ) . ' FROM ' . $this->tableName;
    // NOTE: $params is passed to build() by ref. i.e. updated as we build()
    $where = $this->build( $params );
    if( $where ) { $sql .= ' ' . $where; }
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->prepare( $sql );
    if( $preparedPdoStatement->execute( $params ) )
    {
      return $this->indexBy
        ? $this->db->indexList( $preparedPdoStatement->fetchAll( PDO::FETCH_OBJ ), $this->indexBy )
        : $preparedPdoStatement->fetchAll( PDO::FETCH_OBJ );
    }
    return [];
  }

  public function getFirst( $select = null )
  {
    $sql = 'SELECT ' . ( $select ?: $this->select?:'*' ) . ' FROM ' . $this->tableName;
    $where = $this->build( $params );
    if( $where ) { $sql .= ' ' . $where; }
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->prepare( $sql );
    if( $preparedPdoStatement->execute( $params ) )
    {
      return $preparedPdoStatement->fetch( PDO::FETCH_OBJ );
    }
  }

  /**
   * Update a selection of rows with the same data.
   * @param  array|stdClass $data
   * @return integer Number of updated rows.
   */
  public function update( $data = null )
  {
    $values = [];
    $setPairs = [];
    if( is_object( $data ) ) { $data = (array) $data; }
    foreach( $data as $colName => $value )
    {
      $setPairs[] = "$colName=?";
      $values[] = $value;
    }
    $setPairsSql = implode( ',', $setPairs );
    $sql = "UPDATE {$this->tableName} SET {$setPairsSql}";
    $where = $this->build( $params );
    if( $where ) { $sql .= ' ' . $where; }
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->prepare( $sql );
    $preparedPdoStatement->execute( array_merge( $values, $params ) );
    return $preparedPdoStatement->rowCount();
  }

  public function delete()
  {
    $sql = "DELETE FROM {$this->tableName}";
    $where = $this->build( $params );
    if( $where ) { $sql .= ' ' . $where; }
    $this->db->log[] = $sql;
    $preparedPdoStatement = $this->db->prepare( $sql );
    $preparedPdoStatement->execute( $params );
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
   * @param string $paramsExpr  e.g. 'id=?', 'status=? AND age>=?', 'name LIKE ?', 'fieldname_only'
   * @param mixed  $params      e.g. 100, [100], [1,46], '%john%', ['john']
   * @param array  $options     e.g. ['ignore' => ['', 0, null], glue => 'OR', 'test' => 'IN']
   */
  public function __construct($paramsExpr, $params = null, $options = null)
  {
    $this->options = $options?:[];
    if( isset( $options['test'] ) )
    {
      $test = $options['test'];
      switch( $test )
      {
        case 'IN':
        case 'NOT IN':
          $qMarks = array_map( function() { return '?'; }, $params );
          $qMarksSql = implode( ',', $qMarks );
          $paramsExpr .= " $test ($qMarksSql)";
          break;
        case 'FROM TO':
          $paramsExpr = "$paramsExpr >= ? AND $paramsExpr <= ?";
          break;
      }
    }
    $this->paramsExpr = $paramsExpr;
    $this->params = $params?:[];
  }

  public function build(&$params)
  {
    if( ! $params ) { $params = []; }
    $glue = isset( $this->options[ 'glue' ] )
      ? ( ' ' . $this->options[ 'glue' ] . ' ' )
      : '';
    $params = array_merge( $params, $this->params );
    if( is_object( $this->paramsExpr ) and
      ( $this->paramsExpr instanceof QueryStatement ) )
    {
      return $glue . '(' . $this->paramsExpr->build( $params ) . ')';
    }
    return $glue . $this->paramsExpr;
  }
}
