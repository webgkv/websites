<?php

// DB helpers
/*
 * v1.3.37 - created_at - mysql_fn
 * v1.4.20 - created_at fix - mysql_fn
 */

/**
 * DB connection
 * @param string $server
 * @param string $username
 * @param string $password
 * @param string $database
 * @return bool - connected or not
 * @version v1.2.52
 * v1.2.52 - mysqli migration
 */
function mysql_connect_db($server='',$username='',$password='',$database='') {
	global $config;
	if (@$config['mysql_connect']==false) {
		// Use $config when no params passed
		if ($server=='') {
			$server		= $config['mysql_server'];
			$username	= $config['mysql_username'];
			$password	= $config['mysql_password'];
			$database	= $config['mysql_database'];
		}
		if ($config['mysql_connect'] = @mysqli_connect($server,$username,$password)) {
			$connect = $config['mysql_connect'];
			if (mysqli_select_db($connect,$database)) {
				mysqli_query($connect,"SET NAMES '" . $config['mysql_charset'] . "'");
				mysqli_query($connect,"SET CHARACTER SET '" . $config['mysql_charset'] . "'");
				// Reset sql_mode so inserts/updates work (e.g. missing default)
				// mysqli_query($connect,"SET @@GLOBAL.sql_mode= ''");
				mysqli_query($connect,"SET @@SESSION.sql_mode= ''");
				return $config['mysql_connect'];
			}
			$config['mysql_error'] = 'cannot connect to database';
			trigger_error($config['mysql_error'], E_USER_DEPRECATED);
			return false;
		}
		$config['mysql_error'] = 'cannot connect to mysql server';
		trigger_error($config['mysql_error'], E_USER_DEPRECATED);
		return false;
	}
	$live = $config['mysql_connect'];
	if (!@mysqli_ping($live)) {
		@mysqli_close($live);
		$config['mysql_connect'] = false;
		return mysql_connect_db($server, $username, $password, $database);
	}
	return $live;
}

/**
 * Close DB connection
 * @version v1.2.52
 * v1.2.6 - added
 * v1.2.52 - mysqli migration
 */
function mysql_close_db() {
	global $config;
	if (@$config['mysql_connect']) {
		mysqli_close($config['mysql_connect']);
		$config['mysql_connect'] = false;
	}
}

/**
 * custom mysql_real_escape_string
 * @param string $str - string to escape
 * @return string - escaped string
 * @version v1.2.52
 * v1.2.52 - mysqli migration
 */
function mysql_res ($str) {
	if ($connect = mysql_connect_db()) return mysqli_real_escape_string($connect,$str);
	return false;
}


/**
 * DB select
 * @param string $query - SQL query
 * @param string $type - response data type [string,num_rows,row,rows,rows_id,array]
 * string - single cell value
 * num_rows - count of records
 * row - single row as associative array
 * rows - array of rows
 * rows_id - array of rows indexed by 'id'
 * array - key-value pair array ($id => $name)
 * @param int $cache - cache TTL in seconds
 * @return array|int|string - data from DB
 * @version v1.2.52
 * v1.1.13 - log timestamps for SQL queries
 * v1.1.28 - correct data types in mysql_select
 * v1.2.52 - mysqli migration
 */
function mysql_select($query,$type='rows',$cache=false) {
	global $config;
	$config['cache'] = isset($config['cache']) ? $config['cache'] : 0;
	$file	= ROOT_DIR.'cache/'.md5($query).'.php';
	// Use cache
	if ($config['cache'] && $cache && file_exists($file) && (time()-$cache)<filemtime($file)) {
		$config['queries'][] = array(md5($query).'.php',$query);
		$result = file_get_contents ($file);
		return json_decode($result,true);
	}
	else {
		if (($connect = mysql_connect_db()) !== false) {
			$time = microtime(true);
			$result = mysqli_query($connect,$query);
			// Return correct type when no results
			if (in_array($type,array('string','num_rows'))) $data = '';
			else $data = array();
			if ($error = mysqli_error($connect)) {
				trigger_error($error.' '.$query, E_USER_DEPRECATED);
				return $data;
			}
			if ($type=='string')		{
				$numrows = mysqli_num_rows($result);
				if ($numrows)
				{
					mysqli_data_seek($result, 0);
					$resrow = mysqli_fetch_row($result);
					if (isset($resrow[0]))
					{
						$data = $resrow[0];
					}
				} else {
					$data = false;
				}
			}
			elseif ($type=='num_rows')	$data = mysqli_num_rows($result);
			elseif ($type=='row')		$data = mysqli_fetch_assoc($result);
			elseif ($type=='rows')		while ($q = mysqli_fetch_assoc($result)) $data[] = $q;
			elseif ($type=='rows_id')	while ($q = mysqli_fetch_assoc($result)) $data[$q['id']] = $q;
			elseif ($type=='rows_field')	while ($q = mysqli_fetch_assoc($result)) $data[$q['Field']] = $q;
			elseif ($type=='array')		while ($q = mysqli_fetch_assoc($result)) $data[$q['id']] = $q['name'];
			// Cache result
			if (@$config['cache'] && $cache) {
				if (is_dir(ROOT_DIR.'cache') || mkdir(ROOT_DIR.'cache',0755,true)) {
					$f = fopen($file,'w');
					fwrite($f,json_encode($data));
					fclose($f);
				}
			}
			$time = microtime(true)-$time;
			$config['queries'][] = array($time,$query);
			return $data;
		}
	}
}

/**
 * @param $query - query without LIMIT
 * @param string $query_nr - query for total count
 * @param int $limit - items per page
 * @param int $n - current page number
 * @param bool $cache
 * @return array list - data array, limit, n, num_rows - total items
 * @version v1.2.110
 * v1.2.110 - added
 */
function mysql_data($query,$query_nr=false,$limit=10,$n=1,$cache=false,$offsetp=0) {
	global $config;
	$hash  = md5($query.$query_nr.$limit.$n);
	$file	= ROOT_DIR.'cache/'.$hash.'.php';
	// Use cache
	if (@$config['cache'] && $cache && file_exists($file) && (time()-$cache)<filemtime($file)) {
		$config['queries'][] = array($hash,$query);
		$result = file_get_contents ($file);
		return json_decode($result,true);
	}
	else {
		$data = array(
			'list'=>array(),
			'limit'=>$limit,
			'n'=>$n,
			'num_rows'=>0
		);
		if (mysql_connect_db() !== false) {
			if ($query_nr===false) {
				$query_nr = str_replace(PHP_EOL, ' ', $query);
				$query_nr1 = preg_replace('/SELECT DISTINCT\s+.*?\s+FROM/iu', 'SELECT DISTINCT COUNT(*) FROM', $query_nr);
				if($query_nr1!=$query_nr) {
					$query_nr=$query_nr1;
				} else {
					$query_nr = preg_replace('/SELECT\s+.*?\s+FROM/iu', 'SELECT COUNT(*) FROM', $query_nr);
				}
				$query_nr = explode('ORDER',$query_nr);
				$query_nr = $query_nr[0];
			}
			$data['num_rows'] = mysql_select($query_nr,'string')-$offsetp;
			if ($limit>0) {
				$n = abs(intval($n));
				if ($n==0) $n=1;
				$data['n'] = $n;
				$offset = $n * $limit - $limit + $offsetp;
				$query.= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
			}
			$data['list'] = mysql_select($query, 'rows');
			// Cache
			if (@$config['cache'] && $cache) {
				if (is_dir(ROOT_DIR.'cache') || mkdir(ROOT_DIR.'cache',0755,true)) {
					$f = fopen($file,'w');
					fwrite($f,json_encode($data));
					fclose($f);
				}
			}
		}
		return $data;
	}
}

/**
 * DB write operations
 * @param string $type - operation type [insert,update,delete,query]
 * @param string $tbl_name - table name or full query for type='query'
 * @param array $post - data array
 * @param string $where - WHERE clause
 * @return boolean|int|string - success/failure | Insert ID | Rows affected
 * @version v1.4.20
 * v1.1.10 - NULL handling
 * v1.1.13 - log timestamps
 * v1.1.26 - fix for delete (empty $post)
 * v1.2.52 - mysqli migration
 * v1.2.82 - $config['mysql_null'] configuration
 * v1.2.109 - info return mysqli_info()
 * v1.3.37 - created_at auto-field
 * v1.4.20 - created_at fix
 */
function mysql_fn($type, $tbl_name, $post = array(), $where = '', $ignore = false) {
	global $config;
	if (($connect = mysql_connect_db()) !== false) {
		$query = '';
		// Fourth param can be array (exceptions)
		if (!is_string($where)) {
			$exceptions = $where;
			$where = '';
		}
		else $exceptions = false;

		// 1.3.37 - created_at logic
		if ($type=='insert' OR $type=='insert values') {
			if (!isset($config['_created_at'][$tbl_name])) {
				$config['_created_at'][$tbl_name] = mysql_select("SHOW COLUMNS FROM ".$tbl_name." LIKE 'created_at'",'rows');
			}
			// Add created_at when column exists
			if ($config['_created_at'][$tbl_name]) {
				$post['created_at'] = $config['datetime'];
			}
		}

		// INSERT multiple rows
		if ($type == 'insert values') {
			$into = implode('`,`', array_keys(current($post)));
			foreach ($post as $q) {
				$values = array();
				foreach ($q as $v) $values[] = "'" . mysql_res($v) . "'";
				$sql[] = implode(',', $values);
			}
			$sql = implode('),(', $sql);
		}
		else {
			// Single INSERT or UPDATE body
			if (is_array($post)) {
				foreach ($post as $k => $v) {
					if ($exceptions == false OR !in_array($k, $exceptions)) {
						// v1.1.10 NULL handling
						if ($v === NULL AND @$config['mysql_null']==true) $sql[] = "`" . $k . "` = NULL";
						else $sql[] = "`" . $k . "` = '" . mysql_res($v) . "'";
					}
				}
				$sql = isset($sql) ? implode(', ', $sql) : '';
			}
		}


		$ignore = $ignore ? "IGNORE" : "";
		switch ($type) {
			case 'insert':
				$query = "
					INSERT " . $ignore . " INTO `" . $tbl_name . "`
					SET " . $sql . ";
				";
				break;
			case 'insert update':
				$query = "
					INSERT " . $ignore . " INTO `" . $tbl_name . "`
					SET " . $sql . "
					ON DUPLICATE KEY UPDATE " . $sql . ";
				";
				break;
			case 'insert values':
				$query = "
					INSERT " . $ignore . " INTO `" . $tbl_name . "` (`" . $into . "`)
					VALUES (" . $sql . ")
				";
				break;
			case 'update':
				if ($id = intval(@$post['id'])) $where .= " AND id = '" . $id . "' ";
				$query = "
					UPDATE `" . $tbl_name . "`
					SET " . $sql . "
					WHERE 1	" . $where;
				if ($where=='') {
					// Update without WHERE not allowed
					trigger_error('error_update ' . $query, E_USER_DEPRECATED);
					$query = '';
				}
				break;
			case 'delete':
				if (is_array($post)) $id = intval(@$post['id']);
				else $id = intval($post);
				if ($id) $where .= " AND id = '" . $id . "' ";
				$query = "
					DELETE
					FROM `" . $tbl_name . "`
					WHERE 1	" . $where;
				if ($where=='') {
					// Delete without WHERE not allowed
					trigger_error('error_delete ' . $query, E_USER_DEPRECATED);
					$query = '';
				}
				break;
			case 'query':
				$query = $tbl_name;
				break;
			default:
				// Return query body
				return $sql;
		}
		if ($query) {
			$time = microtime(true);
			mysqli_query($connect,$query);
			$time = microtime(true)-$time;
			$config['queries'][] = array($time,$query);

			if (($error = mysqli_error($connect)) == false) {
				switch ($type) {
					case 'insert':
					case 'insert update':
						return (mysqli_affected_rows($connect) > 0) ? mysqli_insert_id($connect) : false;
					case 'update':
					case 'delete':
					case 'insert values':
						return (($rows = mysqli_affected_rows($connect)) > 0) ? $rows : false;
					case 'query':
						if ($post=='affected_rows') return mysqli_affected_rows($connect);
						if ($post=='info') return mysqli_info($connect);
				}
				return false;
			} else {
				// Detailed MySQL debug output for development / admin use
				trigger_error($error . ' ' . $query, E_USER_DEPRECATED);
				if (function_exists('log_add')) {
					log_add('mysql_error.txt', $error . ' | ' . $query);
				}
				// If it's an admin request, wrap error in textarea to avoid JSON parser error on frontend
				if (isset($_GET['m'])) {
					echo '<textarea>' . json_encode(array('error' => 'MYSQL ERROR: ' . $error . ' | QUERY: ' . $query)) . '</textarea>';
					die();
				}
				die('MYSQL ERROR: ' . $error . ' | QUERY: ' . $query);
			}
		}
	}
}

/**
 * Run raw SQL on the active connection; return mysqli_affected_rows (0 if none).
 */
function mysql_query_affected_rows($sql) {
	$n = mysql_fn('query', $sql, 'affected_rows');
	if ($n === false || $n < 0) {
		return 0;
	}
	return (int)$n;
}

/**
 * Start transaction
 * @param $action - start, rollback or commit
 * @return bool - only for start action
 * @version v1.2.103
 * v1.2.103 - added - InnoDB and transactions
 */
function mysql_transaction($action) {
	global $config;
	if ($action=='start') {
		if (@$config['mysql_transaction']>0) {
			log_add('transactions.txt','transaction already started');
			return false;
		}
		else {
			$config['mysql_transaction'] = 1;
			mysql_fn('query', 'START TRANSACTION');
			return true;
		}
	}
	elseif ($action=='rollback') {
		mysql_fn('query', 'ROLLBACK');
	}
	elseif ($action=='commit') {
		mysql_fn('query', 'COMMIT');
	}
	else {
		// Invalid action
		log_add('transactions.txt','error action: '.$action);
		return false;
	}
}

/**
 * Generate query conditions
 * @param $type -  find_in_set
 * @param $field - field to search in
 * @param $value - values to search for
 * @return string - query condition
 * @version v1.3.23
 * v1.3.23 - added
 */
function mysql_where ($type,$field,$value) {
	if ($type == 'find_in_set') {
		if ($value) {
			$where = array();
			$array = explode(',', $value);
			foreach ($array as $k => $v) {
				$where[] = " FIND_IN_SET (" . $v . ",".$field.")";
			}
			return " AND (" . implode(' OR ', $where) . ")";
		}
	}
}