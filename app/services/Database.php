<?php //NOTE:  NO NAMESPACE!  I.e. This class is in global scope and static.

require $app->env->vendorsPath . '/Xap/Engine.php';
require $app->env->vendorsPath . '/OneFile/DbQuery.php';

use Xap\Engine as Xap;
use Xap\Pagination;
use Xap\Cache;

use OneFile\DbQuery;


/**
 * DB Class using XAP lib
 *
 * @author C. Moller <xavier.tnc@gmail.com> - 25 Nov 2014
 *
 * @updated 21 Feb 2016
 *    - Added "paginate" and "first" methods.
 *    - Added and updated comments.
 *
 * @updated 25 Nov 2016
 *    - Added DB::hasError()
 *    - Added options and comments to DB::connect()
 *    - Added more comments at end
 *
 *
 * NOTE: See /vendors/Xap/README for detailed usage instructions.
 *
 */

class DB
{
  // Switch query sql LOGGING ON/OFF
  const DEBUG = true;

  // Switch XAP exceptions on errors + error logging ON/OFF
  const HANDLE_ERRORS = true;

  public static $connections = [];

  // Get Debug Log Array example:
  // $log = xap(':log'); ... returns array of debug log messages
  // Note: Debug mode (XAP_DEBUG) must be enabled for this example
  //
  // 'errors' => TRUE: Throws exceptions, FALSE: No exceptions
  //  With 'errors' = true, use DB::xap('error') to check if an error has occurred
  //  or use DB::xap('error_last') to get last error
  public static function connect($connection)
  {
    $alias = null;
    if (isset($connection['alias']))
    {
      $alias = $connection['alias'];
      unset($connection['alias']);
    }

    $xap_config = [
      'host'          => $connection['DBHOST'],
      'database'      => $connection['DBNAME'],
      'user'          => $connection['DBUSER'],
      'password'      => $connection['DBPASS'],
      'objects'       => true,                  // return records as objects or arrays (no effect?)
      'debug'         => self::DEBUG,           // turn Xap QUERY LOGGING on/off (DB CONST value.. see top)
      'errors'        => self::HANDLE_ERRORS,   // turn Xap ERROR HANDLING and EXCEPTIONS on/off
      'error_handler' => self::getErrorHandler()
    ];

    $id = Xap::exec([$xap_config]);

    if (isset($connection['id']))
    {
      self::$connections[$alias ?: "conn_$id"] = $id;
    }

    return $id;
  }

  private static function getErrorHandler()
  {
    return function($message) {
      $message = 'DB::error(), ' . $message ;
      $html = '<div class="server-error" style="margin-top: 12px;">'. $message;
      if (self::DEBUG and ! __ENV_PROD__)
      {
        $dblog = print_r(self::queryLog(), true);
        $html .= "<br><pre>$dblog</pre>";
        $message .= PHP_EOL . $dblog;
      }
      $html .= '</div>';
      die($html);
    };
  }

  // Sets the query result classname for the nex query. (PDO queries only!)
  //
  // This setting only lasts for ONE query! If you have multiple queries,
  // you have to run this command before each new query.
  //
  // Example:
  // DB::objClassName('UserModel')
  public static function objClassName($classname = null)
  {
    return Xap::exec([':classname', [$classname]]);
  }

  public static function setPagination($page, $itemspp = null, $prevStr = 'Prev', $nextStr = 'Next')
  {
    $arguments = ['page' => $page, 'prev_string' => $prevStr, 'next_string' => $nextStr];
    if ($itemspp) { $arguments['rpp'] = $itemspp; }
    return Xap::exec([':pagination', $arguments]);
  }

  // The most basic XAP DB:: function.
  // Uses stock XAP short-command syntax and params layout. (See XAP Docs)
  //
  // NB: Only use this function if there is no custom DB:: function available
  // or the custom function doesn't accommodate your specific implementation.
  //
  // Examples:
  // DB::xap('users.12'); NB!! .dotID has been discontinued! - NM 5 Jan 2017
  // DB::xap('users LIMIT 30');
  // DB::xap('users:del WHERE id = ?', [2])
  // DB::xap(':classname', ['UserModel'])
  public static function xap($cmd, $arguments = null)
  {
    return Xap::exec([$cmd, $arguments]);
  }

  // A subset of DB::xap focussing on use of RAW Query Syntax with optional pagination support.
  //
  // Use this function if you want to execute advanced queries not covered by XAP syntax or
  // an existing DB:: function
  //
  // If we require auto pagination, first do:
  // DB::setPagination($page, $itemspp);
  //
  // Then:
  // DB::exec('SELECT * FROM users', null, true);
  // DB::exec('SELECT * FROM users WHERE name=?', [$name], 'paginate');
  //
  // Complex Query:
  // DB::exec('SELECT (((complex field defs))).. FROM (((complex sources))).. WHERE (((complex conditions)))..', $args);
  public static function exec($sql, $arguments = null, $pagination = false)
  {
    $command = $pagination ? ':query/pagination ' : ':query ';
    return Xap::exec([$command . $sql, $arguments]);
  }

  // DB::tempTable('temptablename', 'SELECT * FROM sourcetable WHERE <<some conditions>>')
  public static function tempTable($table, $sql, $arguments = null)
  {
    return Xap::exec(["$table:temp $sql", $arguments]);
  }

  // DB::dropTempTable('temptablename')
  public static function dropTempTable($table)
  {
    return Xap::exec(["$table:droptemp"]);
  }

  // XAP syntax select command
  //
  // Examples:
  // DB::select('users.12');
  // DB::select('users LIMIT 30');
  // DB::select('users WHERE name=?', [$name]);
  // DB::select('users(id, name) WHERE name=?', [$name]);
  public static function select($query, $arguments = null)
  {
    return Xap::exec([$query, $arguments]);
  }

  // XAP syntax select command with AUTO pagination
  // NB: It goes without saying.. DON'T add a LIMIT clause to an auto paginated query!
  //
  // Examples:
  // DB::paginate('users.12', null, null, $page, $itemspp);
  // DB::paginate('users(id, name)', 'WHERE name=?', [$name], $page, $itemspp, 'Before', 'After');
  public static function paginate($table, $conditions, $arguments = null, $page = 1, $rpp = 7, $prevStr = 'Prev', $nextStr = 'Next')
  {
    Pagination::$conf_page_get_var = 'page';
    static::setPagination($page, $rpp, $prevStr, $nextStr);
    // print_r($arguments);
    // die();
    return Xap::exec(["$table/pagination $conditions", $arguments]);
  }

  // DB::first('users', 'WHERE name=?', [$name]);
  // DB::first('users(id, name)', 'WHERE name=?', [$name]);
  public static function first($table, $conditions = '', $arguments = null)
  {
    return Xap::exec(["$table/first $conditions", $arguments]);
  }

  // $conditions == Conditions statement without values. e.g. 'WHERE id=?' - NB: Remember to put WHERE infront of your statement!
  // $arguments == Conditions statement values array. e.g. [12, 'Hello', 'World'] NOT DATA field-value pairs!
  public static function count($table, $conditions = '', $arguments = null)
  {
    return Xap::exec(["$table:count $conditions", $arguments]);
  }

  // $conditions == Conditions statement without values. e.g. 'WHERE id=?' - NB: Remember to put WHERE infront of your statement!
  // DB::exists('users'); check if ANY records exist
  // DB::exists('users', 'WHERE user_id = ? AND is_active = 1', [2]); check if SPECIFIC record/s exists
  // @return: boolean
  public static function exists($table, $conditions, $arguments = null)
  {
    return Xap::exec(["$table:exists $conditions", $arguments]);
  }

  // $conditions == Conditions statement without values. e.g. 'WHERE id=:id' - NB: Remember to put WHERE infront of your statement!
  // $data == field-value pairs array. e.g.  ['name'=>Johnny, 'age'=>29]
  // $arguments == Condition statement values array. e.g. [12, 'Hello', 'World'] NOT DATA field-value pairs!
  // DB::update('mytable', 'WHERE id=:id', $_POST_ARRAY, ['id' => $id]); NOTE: Named parameter(s) notation in conditional is required instead of positional notation!
  // @return: boolean ?
  public static function update($table, $conditions, $data = null, $arguments = null, $ignore_errors = false)
  {
    $ignore = $ignore_errors ? '/ignore' : '';
    return Xap::exec(["$table:mod$ignore $conditions", $data, $arguments]);
  }

  // $data == field-value pairs array OR object. e.g.  ['name'=>'Johnny', 'age'=>29] OR $data->name == 'Johnny' etc.
  // @return == affected rows
  public static function insertInto($table, $data, $ignore_errors = false)
  {
    return Xap::exec(["$table:add" . ( $ignore_errors ? '/ignore' : '' ), $data]);
  }

  public static function lastInsertId()
  {
    return Xap::exec([':id']);
  }

  // See $conditions notes for DB::count
  public static function deleteFrom($table, $conditions, $arguments = null)
  {
    return Xap::exec(["$table:del $conditions", $arguments]);
  }

  public static function beginTransaction()
  {
    return Xap::exec([':transaction']);
  }

  public static function rollBack()
  {
    return Xap::exec([':rollback']);
  }

  public static function commit()
  {
    return Xap::exec([':commit']);
  }

  public static function hasError()
  {
    return Xap::exec([':error']);
  }

  public static function lastError()
  {
    return Xap::exec([':error_last']);
  }

  // Needs debug option to be TRUE. See connect() above.
  // $log = DB::queryLog()
  // echo $log;
  public static function queryLog()
  {
    return Xap::exec([':log']);
  }

  public static function tables()
  {
    return Xap::exec([':tables']);
  }

  public static function columns($tableName)
  {
    return Xap::exec([$tableName . ':columns']);
  }

  // DB::query('invoices')
  //  ->where('invoice_no', 'LIKE', '%123%', ['ignore' => [null, 0]])
  //  ->get();                                  // Get all with all fields
  //  ->get('id,invoice_no,description');       // Get all with only selected fields
  //  ->getBy('id', 'invoice_no,description');  // Get all indexed by id and with only selected fields
  //  ->getBy('id');                            // Get all indexed by id with all fields
  //  ->count();                                // Get count only (For pagination)
  public static function query($tableName)
  {
    $q = new DbQuery(
      // The function to execute the generated WHERE SQL and PARAMS with the DB engine of choice.
      function($queryType, $tableName, $whereSql, $whereParams, $extraArgs)
      {
        switch($queryType)
        {
          case 'count':
            return DB::count($tableName, $whereSql, $whereParams);

          default:
            return DB::select("$tableName $whereSql", $whereParams);
        }
      }
    );
    return $q->from($tableName);
  }

}