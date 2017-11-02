<?php

/**
 *  DB - A simple database class
 *
 * @author		Author: Vivek Wicky Aswal. (https://twitter.com/#!/VivekWickyAswal)
 * @git 		https://github.com/indieteq/PHP-MySQL-PDO-Database-Class
 * @version      0.2ab
 *
 */
require("Log.class.php");
require("log.php");
require("timer.class.php");
function debug($arr,$stop = ''){
       echo '<pre>'. print_r($arr,true).'</pre>';
       if ($stop == ''){
         die(); 
       }
    }
class DB {
    # @object, The PDO object

    private $pdo;

    # @object, PDO statement object
    private $sQuery;

    # @array,  The database settings
    private $settings;

    # @bool ,  Connected to the database
    private $bConnected = false;

    # @object, Object for logging exceptions
    private $log;

    # @array, The parameters of the SQL query
    private $parameters;

    /**
     *   Default Constructor
     *
     * 	1. Instantiate Log class.
     * 	2. Connect to database.
     * 	3. Creates the parameter array.
     */
    public function __construct() {
        $this->log = new Log();
        $this->Connect();
        $this->parameters = array();
    }

    /**
     * 	This method makes connection to the database.
     *
     * 	1. Reads the database settings from a ini file.
     * 	2. Puts  the ini content into the settings array.
     * 	3. Tries to connect to the database.
     * 	4. If connection failed, exception is displayed and a log file gets created.
     */
    private function Connect() {
        //$this->settings = parse_ini_file("settings.ini.php");
        if ($_SERVER['HTTP_HOST'] == 'testtask.loc') {
            $dsn = 'mysql:dbname=test_task;host=localhost;charset=UTF8';
            $pass='';
            $user='root';
        } else {
            $dsn = 'mysql:dbname=interatl_brg;host=interatl.mysql.ukraine.com.ua;charset=UTF8';//remmen_shop
            $pass='';
            $user='';
        }

        try {
            # Read settings from INI file, set UTF8
            $this->pdo = new PDO($dsn, $user, $pass/*, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")*/);
            # We can now log any exceptions on Fatal error.
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


            //$this->pdo->query('SET NAMES utf8 COLLATE utf8_general_ci');


            # Disable emulation of prepared statements, use REAL prepared statements instead.
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            # Connection succeeded, set the boolean to true.
            $this->bConnected = true;
        } catch (PDOException $e) {
            # Write into log
            echo $this->ExceptionLog($e->getMessage());
            die();
        }
    }

    /*
     *   You can use this little method if you want to close the PDO connection
     *
     */

    public function CloseConnection() {
        # Set the PDO object to null to close the connection
        # http://www.php.net/manual/en/pdo.connections.php
        $this->pdo = null;
    }

    /**
     * 	Every method which needs to execute a SQL query uses this method.
     *
     * 	1. If not connected, connect to the database.
     * 	2. Prepare Query.
     * 	3. Parameterize Query.
     * 	4. Execute Query.
     * 	5. On exception : Write Exception into the log + SQL query.
     * 	6. Reset the Parameters.
     */
    private function Init($query, $parameters = "") {
        # Connect to database
        if (!$this->bConnected) {
            $this->Connect();
        }
        try {
            # Prepare query
            $this->sQuery = $this->pdo->prepare($query);

            # Add parameters to the parameter array
            $this->bindMore($parameters);

            # Bind parameters
            if (!empty($this->parameters)) {
               
                foreach ($this->parameters as $param) {
		    //debug ($param);
                   $parameters = explode("\x7F", $param);
		   //
                    $this->sQuery->bindParam($parameters[0], $parameters[1]);
                }
            }

            # Execute SQL
            $this->succes = $this->sQuery->execute();
        } catch (PDOException $e) {
            # Write into log and display Exception
            echo $this->ExceptionLog($e->getMessage(), $query);
            die();
        }

        # Reset the parameters
        $this->parameters = array();
    }

    /**
     * 	@void
     *
     * 	Add the parameter to the parameter array
     * 	@param string $para
     * 	@param string $value
     */
    public function bind($para, $value) {
        $this->parameters[sizeof($this->parameters)] = ":" . $para . "\x7F" . //utf8_encode
		//(
		$value
		//)
		;
	//debug ($value);
    }

    /**
     * 	@void
     *
     * 	Add more parameters to the parameter array
     * 	@param array $parray
     */
    public function bindMore($parray) {
        if (empty($this->parameters) && is_array($parray)) {
            $columns = array_keys($parray);
            foreach ($columns as $i => &$column) {
                $this->bind($column, $parray[$column]);
            }
        }
    }

    public function clearData($input) {
        foreach ($input as $k => $v) {
            $out[$k] = addslashes($v);
        }
        return $out;
    }

    public function getCount($table, $cond, $sql = false) {
        $sql = "SELECT COUNT(*) as cnt FROM $table $cond";
        //echo $sql;
        $res = self :: query($sql);
        //print_r($res);
        return $res[0]['cnt'];
        //exit();
        //$result->Rows=$this->sQuery->rowCount();
    }

    public function setData($fields, $table, $cond = null, $skipped = array()) {
        $fields = self :: clearData($fields);
        foreach ($fields as $k => $v) {
            if ($k == 'id' || in_array($k, $skipped)) {
                continue;
            }
            if (is_array($v)) {
                $fields[$k] = serialize($v);
            }
            $params.='`' . $k . '`=\'' . $v . '\',';
        }
        // self :: test ($fields,'s');
        $sql = "INSERT INTO $table (
  		`" . implode('`,`', array_keys($fields)) . "`
   )
		VALUES (
	'" . implode("','", array_values($fields)) . "'
		)
		ON DUPLICATE KEY UPDATE
		" . substr($params, 0, -1) . "";
        //echo $sql;
        self :: query($sql);
        $result = new stdClass();
        $result->insId = $this->pdo->lastInsertId();
        $result->Rows = $this->sQuery->rowCount();
        return $result;
        //echo $this->pdo->lastInsertId();
        //return $this->sQuery->rowCount();
    }

    /**
     *   	If the SQL query  contains a SELECT or SHOW statement it returns an array containing all of the result set row
     * 	If the SQL statement is a DELETE, INSERT, or UPDATE statement it returns the number of affected rows
     *
     *   	@param  string $query
     * 	@param  array  $params
     * 	@param  int    $fetchmode
     * 	@return mixed
     */
    public function query($query, $params = null, $fetchmode = 'accos', $test = false) {

        $query = trim($query);
	
        //$query = str_replace("\r\n", '', $query);
        $this->Init($query, $params);
        $rawStatement = explode(" ", $query);
	//debug ($rawStatement);
        /* echo '<pre>';
          print_r($rawStatement);
          echo '</pre>'; */
        # Which SQL statement is used
        $statement = strtolower($rawStatement[0]);

        if ($statement === 'select' || $statement === 'show') {
            /* echo $query . '<br><br>';
              if ($test) {
              echo $query;
              exit();
              } */
            if ($fetchmode == 'unique') {
                return $this->sQuery->fetchAll($fetchmode = PDO::FETCH_UNIQUE);
            }elseif($fetchmode == 'object'){ 
                return $this->sQuery->fetchAll($fetchmode = PDO::FETCH_OBJ);
	    }elseif($fetchmode == 'one'){ 
                return $this->sQuery->fetch();
	    }else {
                return $this->sQuery->fetchAll($fetchmode = PDO::FETCH_ASSOC);
            }
        } elseif ($statement === 'insert' || $statement === 'update' || $statement === 'delete') {
            return $this->sQuery->rowCount();
        } else {
            return NULL;
        }
    }

    /**
     *  Returns the last inserted id.
     *  @return string
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     *  Insert array field_name => $value
     *  @return string
     */
    public function insertArray($table, $values_arr = array(), $params = null) {
        if (count($values_arr) > 0) {

            $fields = implode(',', array_keys($values_arr));

            $end_element = array_pop($values_arr);
            $insert_values_string = "(";
            foreach ($values_arr as $k => $val) {
                $insert_values_string .= "'" . $val . "', ";
            }
            $insert_values_string .= "'" . $end_element . "')";
            //			test($insert_values_string);

            $this->Init("INSERT INTO $table ($fields) VALUES $insert_values_string", $params);
        }

        return $this->sQuery->rowCount();
    }

    /**
     *  Insert arrays
     * 	[0][field_name => $value]
     * 	[1][field_name => $value]
     *  ...
     *  @return string
     */
    public function insertArrays($table, $data_arr = array(), $params = null) {
        if (count($data_arr) > 0) {

            $fields = implode(',', array_keys($data_arr[0]));
            foreach ($data_arr as $set) {
                $insert_values_string = "(";
                $end_element = array_pop($set);
                foreach ($set as $val) {
                    $insert_values_string .= "'" . $val . "', ";
                }
                $insert_values_string .= "'" . $end_element . "')";
                $ins_array[] = $insert_values_string;
            }

//			test($ins_array);

            $this->Init("INSERT IGNORE INTO $table ($fields) VALUES " . implode(',', $ins_array), $params);
        }

        return $this->sQuery->rowCount();
    }

    /**
     * 	Returns an array which represents a column from the result set
     *
     * 	@param  string $query
     * 	@param  array  $params
     * 	@return array
     */
    public function column($query, $params = null) {
        $this->Init($query, $params);
        $Columns = $this->sQuery->fetchAll(PDO::FETCH_NUM);

        $column = null;

        foreach ($Columns as $cells) {
            $column[] = $cells[0];
        }

        return $column;
    }

    /**
     * 	Returns an array which represents a row from the result set
     *
     * 	@param  string $query
     * 	@param  array  $params
     *   	@param  int    $fetchmode
     * 	@return array
     */
    public function row($query, $params = null, $fetchmode = PDO::FETCH_ASSOC) {
        $this->Init($query, $params);
        return $this->sQuery->fetch($fetchmode);
    }

    /**
     * 	Returns the value of one single field/column
     *
     * 	@param  string $query
     * 	@param  array  $params
     * 	@return string
     */
    public function single($query, $params = null) {
        $this->Init($query, $params);
        return $this->sQuery->fetchColumn();
    }

    /**
     * Writes the log and returns the exception
     *
     * @param  string $message
     * @param  string $sql
     * @return string
     */
    private function ExceptionLog($message, $sql = "") {
        $exception = 'Unhandled Exception. <br />';
        $exception .= $message;
        $exception .= "<br /> You can find the error back in the log.";

        if (!empty($sql)) {
            # Add the Raw SQL to the Log
            $message .= "\r\nRaw SQL : " . $sql;
        }
        # Write into log
        $this->log->write($message);

        return $exception;
    }

}

function d($data, $stop = false) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    if (!$stop)
        exit();
}

function get_fcontent($url, $javascript_loop = 0, $timeout = 10) {
    $url = str_replace("&amp;", "&", urldecode(trim($url)));

    $cookie = tempnam("/tmp", "CURLCOOKIE");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    # required for https urls
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    $content = curl_exec($ch);
    $response = curl_getinfo($ch);
    curl_close($ch);

    if ($response['http_code'] == 301 || $response['http_code'] == 302) {
        ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");

        if ($headers = get_headers($response['url'])) {
            foreach ($headers as $value) {
                if (substr(strtolower($value), 0, 9) == "location:")
                    return get_url(trim(substr($value, 9, strlen($value))));
            }
        }
    }

    if (( preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value) ) && $javascript_loop < 5) {
        return get_url($value[1], $javascript_loop + 1);
    } else {
        return array($content, $response);
    }
}

?>
