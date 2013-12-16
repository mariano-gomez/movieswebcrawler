<?php

	/**********************************************************************
	*  Author: Justin Vincent (jv@jvmultimedia.com)
	*  Web...: http://twitter.com/justinvincent
	*  Name..: ezSQL_mysql
	*  Desc..: mySQL component (part of ezSQL databse abstraction library)
	*
	*/

	/**********************************************************************
	*  ezSQL error strings - mySQL
	*/

	$ezsql_mysql_str = array
	(
		1 => 'Require $dbuser and $dbpassword to connect to a database server',
		2 => 'Error establishing mySQL database connection. Correct user/password? Correct hostname? Database server running?',
		3 => 'Require $dbname to select a database',
		4 => 'mySQL database connection is not active',
		5 => 'Unexpected error while trying to select database'
	);

	/**********************************************************************
	*  ezSQL Database specific class - mySQL
	*/

	if ( ! function_exists ('mysql_connect') ) die('<b>Fatal Error:</b> ezSQL_mysql requires mySQL Lib to be compiled and or linked in to the PHP engine');
	if ( ! class_exists ('ezSQLcore') ) die('<b>Fatal Error:</b> ezSQL_mysql requires ezSQLcore (ez_sql_core.php) to be included/loaded before it can be used');

	class ezSQL_mysql extends ezSQLcore
	{

		var $dbuser = false;
		var $dbpassword = false;
		var $dbname = false;
		var $dbhost = false;

		/**********************************************************************
		*  Constructor - allow the user to perform a qucik connect at the
		*  same time as initialising the ezSQL_mysql class
		*/

		function ezSQL_mysql($dbuser='', $dbpassword='', $dbname='', $dbhost='localhost')
		{
			$this->dbuser = $dbuser;
			$this->dbpassword = $dbpassword;
			$this->dbname = $dbname;
			$this->dbhost = $dbhost;
		}

		/**********************************************************************
		*  Short hand way to connect to mySQL database server
		*  and select a mySQL database at the same time
		*/

		function quick_connect($dbuser='', $dbpassword='', $dbname='', $dbhost='localhost')
		{
			$return_val = false;
			if ( ! $this->connect($dbuser, $dbpassword, $dbhost,true) ) ;
			else if ( ! $this->select($dbname) ) ;
			else $return_val = true;
			return $return_val;
		}

		/**********************************************************************
		*  Try to connect to mySQL database server
		*/

		function connect($dbuser='', $dbpassword='', $dbhost='localhost')
		{
			global $ezsql_mysql_str; $return_val = false;

			// Must have a user and a password
			if ( ! $dbuser )
			{
				$this->register_error($ezsql_mysql_str[1].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[1],E_USER_WARNING) : null;
			}
			// Try to establish the server database handle
			else if ( ! $this->dbh = @mysql_connect($dbhost,$dbuser,$dbpassword,true) )
			{
				$this->register_error($ezsql_mysql_str[2].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[2],E_USER_WARNING) : null;
			}
			else
			{
				$this->dbuser = $dbuser;
				$this->dbpassword = $dbpassword;
				$this->dbhost = $dbhost;
				$return_val = true;
			}

			return $return_val;
		}

		/**********************************************************************
		*  Try to select a mySQL database
		*/

		function select($dbname='')
		{
			global $ezsql_mysql_str; $return_val = false;

			// Must have a database name
			if ( ! $dbname )
			{
				$this->register_error($ezsql_mysql_str[3].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[3],E_USER_WARNING) : null;
			}

			// Must have an active database connection
			else if ( ! $this->dbh )
			{
				$this->register_error($ezsql_mysql_str[4].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[4],E_USER_WARNING) : null;
			}

			// Try to connect to the database
			else if ( !@mysql_select_db($dbname,$this->dbh) )
			{
				// Try to get error supplied by mysql if not use our own
				if ( !$str = @mysql_error($this->dbh))
					  $str = $ezsql_mysql_str[5];

				$this->register_error($str.' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
			}
			else
			{
				$this->dbname = $dbname;
				$return_val = true;
			}

			return $return_val;
		}

		/**********************************************************************
		*  Format a mySQL string correctly for safe mySQL insert
		*  (no mater if magic quotes are on or not)
		*/

		function escape($str)
		{
			return mysql_escape_string(stripslashes($str));
		}

		/**********************************************************************
		*  Return mySQL specific system date syntax
		*  i.e. Oracle: SYSDATE Mysql: NOW()
		*/

		function sysdate()
		{
			return 'NOW()';
		}

		/**********************************************************************
		*  Perform mySQL query and try to detirmin result value
		*/

		function query($query)
		{

			// Initialise return
			$return_val = 0;

			// Flush cached values..
			$this->flush();

			// For reg expressions
			$query = trim($query);

			// Log how the function was called
			$this->func_call = "\$db->query(\"$query\")";

			// Keep track of the last query for debug..
			$this->last_query = $query;

			// Count how many queries there have been
			$this->num_queries++;

			// Use core file cache function
			if ( $cache = $this->get_cache($query) )
			{
				return $cache;
			}

			// If there is no existing database connection then try to connect
			if ( ! isset($this->dbh) || ! $this->dbh )
			{
				$this->connect($this->dbuser, $this->dbpassword, $this->dbhost);
				$this->select($this->dbname);
			}

			// Perform the query via std mysql_query function..
			$this->result = @mysql_query($query,$this->dbh);

			// If there is an error then take note of it..
			if ( $str = @mysql_error($this->dbh) )
			{
				$is_insert = true;
				$this->register_error($str);
				$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
				return false;
			}

			// Query was an insert, delete, update, replace
			$is_insert = false;
			if ( preg_match("/^(insert|delete|update|replace)\s+/i",$query) )
			{
				$this->rows_affected = @mysql_affected_rows();

				// Take note of the insert_id
				if ( preg_match("/^(insert|replace)\s+/i",$query) )
				{
					$this->insert_id = @mysql_insert_id($this->dbh);
				}

				// Return number fo rows affected
				$return_val = $this->rows_affected;
			}
			// Query was a select
			else
			{

				// Take note of column info
				$i=0;
				while ($i < @mysql_num_fields($this->result))
				{
					$this->col_info[$i] = @mysql_fetch_field($this->result);
					$i++;
				}

				// Store Query Results
				$num_rows=0;
				while ( $row = @mysql_fetch_object($this->result) )
				{
					// Store relults as an objects within main array
					$this->last_result[$num_rows] = $row;
					$num_rows++;
				}

				@mysql_free_result($this->result);

				// Log number of rows the query returned
				$this->num_rows = $num_rows;

				// Return number of rows selected
				$return_val = $this->num_rows;
			}

			// disk caching of queries
			$this->store_cache($query,$is_insert);

			// If debug ALL queries
			$this->trace || $this->debug_all ? $this->debug() : null ;

			return $return_val;

		}

	/***************************/
	/********* PROPIOS *********/
	/***************************/

	function getData($tipo, $campos, $tabla, $donde = "TRUE")  {
		if (is_array($donde)) {
			array_walk($donde, array($this, 'mergeKeyValue'));
			$donde = implode(" AND ", $donde);
		}
		if (is_array($campos)) $campos = implode(", ", $campos);   // Por si es un array con un solo campo
		if ($tipo == 'first') {
			if (!strpos($campos, ',') && $campos!="*") {
				return $this->get_var("SELECT ".$campos." FROM `".$tabla."` WHERE ".$donde);
			} else
				return $this->get_row("SELECT ".$campos." FROM `".$tabla."` WHERE ".$donde);
		} elseif ($tipo == 'count') {
			return $this->get_var("SELECT count(*) FROM `".$tabla."` WHERE ".$donde);
		} elseif ($tipo == 'all') {
			return $this->get_results("SELECT ".$campos." FROM `".$tabla."` WHERE ".$donde);
		}
	}

	/*  ALIAS de getData    */
	function find($tipo, $campos, $tabla, $donde = "TRUE")  {
		return $this->getData($tipo, $campos, $tabla, $donde);
	}

	/*  PROBADA  */
	function findAll($campos, $tabla, $donde = "TRUE") {
		return $this->getData('all', $campos, $tabla, $donde);
	}

	/*  PROBADA  */
	/*  Anda bien, pero si en usuarios le mando un array('id' => array('18', '>')) me devuelve el
	* de id mas grande (21, en lugar de 19). De todas formas, es algo de la tabla pq si hago la consulta en
	* phpMyAdmin me las devuelve en orden invertido */
	function first($campos, $tabla, $donde = "TRUE") {
		return $this->getData('first', $campos, $tabla, $donde);
	}

	/*  PROBADA  */
	function count($tabla, $donde = "TRUE") {
		return $this->getData('count', "", $tabla, $donde);
	}

	function deleteData($data, $table){
		if (!is_array($data)) $this->error('Data is not an array', __LINE__);
		//foreach ($data as $k => $v )
		//$data[$k] = "'".$this->escape($v)."'";
		//  NOTA: Quitar el for y analizar donde se van a analizar todos los inputs (para escaparlos)

		array_walk($data, array($this, 'mergeKeyValue')); //  devuelve los pares "atributo = valor"

		$this->query("DELETE FROM `$table` WHERE ".implode(", ", $data));
		//return (int)mysql_insert_id($this->dbConn);
		//  NOTA: La linea q comente arriba no se porque estaba antes (obtener el id de lo q acabo de borrar)
	}

	/*
	* Inserta datos del array $data en la tabla $tabla
	* @param array $data con los valores y campos
	* @param str $tabla con el nombre de la tabla
	* return int
	 * IMPORTANTE: Cuando especifico el valor de id (o tal vez cuando el pk no es un int autoincrementado),
	 *				el $this->insert_id queda en cero
	*/
	function insertData($data, $tabla){
		if (!is_array($data)) $this->error('Data is not an array', __LINE__);
		//foreach ($data as $k => $v ) $data[$k] = "'".$this->escape($v)."'";
		array_walk($data, array($this, 'addQuotes'));
		$this->query("INSERT INTO `".$tabla."` (`".implode('`, `', array_keys($data))."`) VALUES (".implode(", ", $data).")");
		return $this->insert_id;
	}


	/*
	* Updates a property. Data must be in the form 'property' => 'value'
	* @param array
	* @return bool
	*/
	function updateData($data, $table, $donde = "TRUE") {
		array_walk($data, array($this, 'mergeKeyValue'));
		if (is_array($donde)) {
			array_walk($donde, array($this, 'mergeKeyValue'));
			$donde = implode(" AND ", $donde);
		}
		return $this->query("UPDATE `".$table."` SET ".implode(", ",$data)." WHERE ".$donde);
	}

	private function mergeKeyValue(&$value, $key) {
		if (!is_array($value))
			$value = $key." = '".$value."'";
		else {
		if (!isset($value[1]))
			$value[1] = "=";
			$value = $key." ".$value[1]." '".$value[0]."'";
		}
	}

	private function addQuotes(&$value, $key) {
		$value = "'".$value."'";
	}

    
}
?>