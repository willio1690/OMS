<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 使用mysqli函数库，兼容已经废弃的mysql函数库
 */

if (function_exists('mysqli_connect') && !function_exists('mysql_connect')) {

    //Columns are returned into the array having the fieldname as the array index.
    define('MYSQL_ASSOC', MYSQLI_ASSOC);
    //Columns are returned into the array having a numerical index to the fields. This index starts with 0, the first field in the result.
    define('MYSQL_NUM', MYSQLI_NUM);
    //Columns are returned into the array having both a numerical index and the fieldname as the array index.
    define('MYSQL_BOTH', MYSQLI_BOTH);
    //Use compression protocol
    define('MYSQL_CLIENT_COMPRESS', MYSQLI_CLIENT_COMPRESS);
    //Use SSL encryption
    define('MYSQL_CLIENT_SSL', MYSQLI_CLIENT_SSL);
    //Allow interactive_timeout seconds (instead of wait_timeout ) of inactivity before closing the connection.
    define('MYSQL_CLIENT_INTERACTIVE', MYSQLI_CLIENT_INTERACTIVE);
    //Allow space after function names
    define('MYSQL_CLIENT_IGNORE_SPACE', MYSQLI_CLIENT_IGNORE_SPACE);

    /**
     * Class mysqli_tool
     */
    class mysqli_tool {

        /**
         * @var array
         */
        static public $_links = array();
        /**
         * @var null
         */
        static public $_lastLink = null;

        /**
         * 跟据输入的连接资源，如为空，返回最近使用的，反之，原样返回
         *
         * @param $link
         * @return mysqli
         */
        static public function linkProcess($link) {

            if ($link === null) {

                return self::$_lastLink;
            } else {

                self::$_lastLink = $link;

                return $link;
            }
        }
    }

    /**
     * Open a connection to a MySQL Server
     *
     * @param String $server
     * @param String $username
     * @param String $password
     * @param bool $new_link
     * @param int $client_flags
     * @return mysqli
     */
    function mysql_connect($server = '', $username = '', $password = '', $new_link = false, $client_flags = 0) {

        if (empty($server) || empty($username)) {

            die('Please give me server config');
        }

        //localhost 的处理
        $server = trim($server);
        if (preg_match('/^localhost/is', $server)) {

            $server = preg_replace('/^localhost/is', '127.0.0.1',$server);
        }

        $link = mysqli_init();

        if (mysqli_real_connect($link, $server, $username, $password, null, null, null, $client_flags)) {
            mysqli_tool::linkProcess($link);
            return $link;
        } else {

            return false;
        }
    }

    /**
     * Open a connection to a MySQL Server
     *
     * @param String $server
     * @param String $username
     * @param String $password
     * @param int $client_flags
     * @return mysqli
     */
    function mysql_pconnect($server = '', $username = '', $password = '', $client_flags = 0) {

        return mysql_connect($server, $username, $password, false, $client_flags);
    }

    /**
     * Close a MySQL Server connection
     *
     * @param mysqli $link
     * @return bool
     */
    function mysql_close($link = null) {

        return mysqli_close(mysqli_tool::linkProcess($link));
    }

    /**
     * Select a database to default use
     *
     * @param String $database_name
     * @param mysqli $link
     * @return bool
     */
    function mysql_select_db($database_name, $link = null) {

        return mysqli_select_db(mysqli_tool::linkProcess($link), $database_name);
    }

    /**
     * Select a database to default use
     *
     * @param String $database_name
     * @param mysqli $link
     * @return bool
     */
    function mysql_selectdb($database_name, $link) {

        return mysql_select_db($database_name, $link);
    }

    /**
     * Execute sql
     *
     * @param String $query
     * @param mysql $link
     * @return bool|mysqli_result
     */
    function mysql_query($query, $link = null) {

        return mysqli_query(mysqli_tool::linkProcess($link), $query);
    }

    /**
     * Execute sql
     *
     * @param String $query
     * @param mysqli $link
     * @return bool|mysqli_result
     */
    function mysql_unbuffered_query($query, $link = null) {

        return mysqli_query(mysqli_tool::linkProcess($link), $query);
    }

    /**
     * Execute SQL, used database will changed
     *
     * @param String $database
     * @param String $query
     * @param mysqli $link
     * @return bool|mysqli_result
     */
    function mysql_db_query($database, $query, $link = null) {

        mysqli_select_db(mysqli_tool::linkProcess($link), $database);

        return mysqli_query($link, $query);
    }

    /**
     * List databases available on a MySQL server
     *
     * @param mysqli $link
     * @return bool|mysqli_result
     */
    function mysql_list_dbs($link = null) {

        return mysqli_query(mysqli_tool::linkProcess($link), 'SHOW DATABASES');
    }

    /**
     * List databases available on a MySQL server
     *
     * @param mysqli $link
     * @return bool|mysqli_result
     */
    function mysql_listdbs($link) {

        return mysql_list_dbs($link);
    }

    function mysql_db_name($result, $row, $field = null) {


    }

    function mysql_dbname($result, $row, $field) {


    }

    /**
     * List tables available on a MySQL server by a database
     *
     * @param string $database
     * @param mysqli $link
     * @return bool|mysqli_result
     */
    function mysql_list_tables($database, $link = null) {

        return mysqli_query(mysqli_tool::linkProcess($link), sprintf('SHOW TABLES FROM %s'), $database);
    }

    /**
     * List tables available on a MySQL server by a database
     *
     * @param string $database
     * @param mysqli $link
     * @return bool|mysqli_result
     */
    function mysql_listtables($database, $link) {

        return mysql_list_tables($database, $link);
    }

    function mysql_table_name($result, $row, $field = null) {


    }

    function mysql_tablename($result, $i) {


    }

    /**
     *  List MySQL table fields
     *
     * @param $database_name
     * @param $table_name
     * @param Mysqli $link
     * @return bool|mysqli_result
     */
    function mysql_list_fields($database_name, $table_name, $link = null) {

        return mysqli_query(mysqli_tool::linkProcess($link), sprintf('SHOW COLUMNS FROM %s.%s', $database_name, $table_name));
    }

    /**
     *  List MySQL table fields
     *
     * @param $database_name
     * @param $table_name
     * @param Mysqli $link
     * @return bool|mysqli_result
     */
    function mysql_listfields($database_name, $table_name, $link) {

        return mysql_list_fields($database_name, $table_name, $link);
    }

    /**
     * Not support
     *
     * @param Mysqli $link
     * @return bool
     */
    function mysql_list_processes($link = null) {

        return false;
    }

    /**
     * Get mysql error string
     *
     * @param Mysqli $link
     * @return string
     */
    function mysql_error($link = null) {

        return mysqli_error(mysqli_tool::linkProcess($link));
    }

    /**
     * Get mysql server error no
     *
     * @param mysqli $link
     * @return int
     */
    function mysql_errno($link = null) {

        return mysqli_errno(mysqli_tool::linkProcess($link));
    }

    /**
     * Get number of affected rows in previous MySQL operation
     *
     * @param mysql $link
     * @return int
     */
    function mysql_affected_rows($link = null) {

        return mysqli_affected_rows(mysqli_tool::linkProcess($link));
    }

    /**
     * Get the ID generated in the last query
     *
     * @param mysqli $link
     * @return int|string
     */
    function mysql_insert_id($link = null) {

        return mysqli_insert_id(mysqli_tool::linkProcess($link));
    }

    /**
     * Get result data
     *
     * @param mysqli_result $result
     * @param int $offset
     * @param int $field
     * @return false | string
     */
    function mysql_result($result, $offset, $field = 0) {

        if (mysqli_data_seek($result, $offset)) {

            $row = mysqli_fetch_array($result);
            if ($row) {

                return $row[$field];
            }
        }

        return false;
    }

    /**
     * Get number of rows in result
     *
     * @param mysqli_result $result
     * @return int
     */
    function mysql_num_rows($result) {

        return mysqli_num_rows($result);
    }

    /**
     * Get number of rows in result
     *
     * @param mysqli_result $result
     * @return int
     */
    function mysql_numrows($result) {

        return mysqli_num_rows($result);
    }

    /**
     * Get number of fields in result
     *
     * @param mysqli_result $result
     * @return int
     */
    function mysql_num_fields($result) {

        return mysqli_num_fields($result);
    }

    /**
     * Get number of fields in result
     *
     * @param mysqli_result $result
     * @return int
     */
    function mysql_numfields($result) {

        return mysqli_num_fields($result);
    }

    /**
     * Get a result row as an enumerated array
     *
     * @param mysqli_result $result
     * @return array|null
     */
    function mysql_fetch_row($result) {

        return mysqli_fetch_row($result);
    }

    /**
     * Fetch a result row as an associative array, a numeric array, or both
     *
     * @param mysqli_result $result
     * @param int $result_type
     * @return array|null
     */
    function mysql_fetch_array($result, $result_type = MYSQL_BOTH) {

        return mysqli_fetch_array($result, $result_type);
    }

    /**
     * Fetch a result row as an associative array
     *
     * @param mysqli_result $result
     * @return array|null
     */
    function mysql_fetch_assoc($result) {

        return mysqli_fetch_assoc($result);
    }

    /**
     * Fetch a result row as an object
     *
     * @param mysqli_result $result
     * @param string $class_name
     * @param array|null $params
     * @return null|object
     */
    function mysql_fetch_object($result, $class_name = 'stdClass', array $params = null) {

        return mysqli_fetch_object($result, $class_name, $params);
    }

    /**
     * Move internal result pointer
     *
     * @param mysqli_result $result
     * @param $row_number
     * @return bool
     */
    function mysql_data_seek($result, $row_number) {

        return mysqli_data_seek($result, $row_number);
    }

    /**
     * Get the length of each output in a result
     *
     * @param mysqli_result $result
     * @return array|bool
     */
    function mysql_fetch_lengths($result) {

        return mysqli_fetch_lengths($result);
    }

    /**
     * Get column information from a result and return as an object
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|object
     */
    function mysql_fetch_field($result, $field_offset = 0) {

        return mysqli_fetch_field($result);
    }

    /**
     * Set result pointer to a specified field offset
     *
     * @param mysqli_result $result
     * @param $field_offset
     * @return bool
     */
    function mysql_field_seek($result, $field_offset) {

        return mysqli_field_seek($result, $field_offset);
    }

    /**
     * Free result memory
     *
     * @param mysqli_result $result
     * @return bool
     */
    function mysql_free_result($result) {

        return mysqli_free_result($result);
    }

    /**
     * Free result memory
     *
     * @param mysqli_result $result
     * @return bool
     */
    function mysql_freeresult($result) {

        return mysqli_free_result($result);
    }

    /**
     * Get the name of the specified field in a result
     *
     * @param mysqli_result $result
     * @param $field_offset
     * @return bool|string
     */
    function mysql_field_name($result, $field_offset) {

        $fieldInfo = mysqli_fetch_field_direct($result, $field_offset);

        if (is_object($fieldInfo)) {

            return $fieldInfo->name;
        } else {

            return false;
        }
    }

    /**
     * Get the name of the specified field in a result
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|string
     */
    function mysql_fieldname($result, $field_offset) {

        return mysql_field_name($result, $field_offset);
    }

    /**
     * Get name of the table the specified field is in
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|string
     */
    function mysql_field_table($result, $field_offset) {

        $fieldInfo = mysqli_fetch_field_direct($result, $field_offset);

        if (is_object($fieldInfo)) {

            return $fieldInfo->table;
        } else {

            return false;
        }
    }

    /**
     * Get name of the table the specified field is in
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|string
     */
    function mysql_fieldtable($result, $field_offset) {

        return mysql_field_table($result, $field_offset);
    }

    /**
     *  Returns the length of the specified field
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|int
     */
    function mysql_field_len($result, $field_offset) {

        $fieldInfo = mysqli_fetch_field_direct($result, $field_offset);

        if (is_object($fieldInfo)) {

            return $fieldInfo->length;
        } else {

            return false;
        }
    }

    /**
     *  Returns the length of the specified field
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|int
     */
    function mysql_fieldlen($result, $field_offset) {

        return mysql_field_len($result, $field_offset);
    }

    /**
     * Get the type of the specified field in a result
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|int
     */
    function mysql_field_type($result, $field_offset) {

        $fieldInfo = mysqli_fetch_field_direct($result, $field_offset);

        if (is_object($fieldInfo)) {

            return $fieldInfo->type;
        } else {

            return false;
        }
    }

    /**
     * Get the type of the specified field in a result
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|int
     */
    function mysql_fieldtype($result, $field_offset) {

        return mysql_field_type($result, $field_offset);
    }

    /**
     * Get the flags associated with the specified field in a result
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|string
     */
    function mysql_field_flags($result, $field_offset) {

        $fieldInfo = mysqli_fetch_field_direct($result, $field_offset);

        if (is_object($fieldInfo)) {

            return $fieldInfo->flags;
        } else {

            return false;
        }
    }

    /**
     * Get the flags associated with the specified field in a result
     *
     * @param mysqli_result $result
     * @param int $field_offset
     * @return bool|string
     */
    function mysql_fieldflags($result, $field_offset) {

        return mysql_field_flags($result, $field_offset);
    }

    /**
     * Escapes a string for use in a mysql_query
     *
     * @param string $unescaped_string
     * @return string
     */
    function mysql_escape_string($unescaped_string) {

        return mysql_real_escape_string($unescaped_string);
    }

    /**
     * Escapes a string for use in a mysql_query
     *
     * @param string $unescaped_string
     * @param mysqli $link
     * @return string
     */
    function mysql_real_escape_string($unescaped_string, $link = null) {
        //todo
        return mysqli_real_escape_string(mysqli_tool::linkProcess($link), $unescaped_string);
    }

    /**
     * Get current system status
     *
     * @param mysqli $link
     * @return bool|string
     */
    function mysql_stat($link = null) {

        return mysqli_stat(mysqli_tool::linkProcess($link));
    }

    /**
     * Return the current thread ID
     *
     * @param mysqli $link
     * @return int
     */
    function mysql_thread_id($link = null) {

        return mysqli_thread_id(mysqli_tool::linkProcess($link));
    }

    /**
     * Returns the name of the character set
     *
     * @param mysqli $link
     * @return string
     */
    function mysql_client_encoding($link = null) {

        return mysqli_character_set_name(mysqli_tool::linkProcess($link));
    }

    /**
     * Ping a server connection or reconnect if there is no connection
     *
     * @param mysqli $link
     * @return bool
     */
    function mysql_ping($link = null) {

        return mysqli_ping(mysqli_tool::linkProcess($link));
    }

    /**
     * Get MySQL client info
     *
     * @return string
     */
    function mysql_get_client_info() {

        return mysqli_get_client_info();
    }

    /**
     * Get MySQL host info
     *
     * @param mysqli $link
     * @return string
     */
    function mysql_get_host_info($link = null) {

        return mysqli_get_host_info(mysqli_tool::linkProcess($link));
    }

    /**
     * Get MySQL protocol info
     *
     * @param mysqli $link
     * @return bool|int
     */
    function mysql_get_proto_info($link = null) {

        return mysqli_get_proto_info(mysqli_tool::linkProcess($link));
    }

    /**
     * Get MySQL server info
     *
     * @param mysqli $link
     * @return string
     */
    function mysql_get_server_info($link = null) {

        return mysqli_get_server_info(mysqli_tool::linkProcess($link));
    }

    /**
     * Get information about the most recent query
     *
     * @param mysqli $link
     * @return bool|string
     */
    function mysql_info($link = null) {

        return mysqli_info(mysqli_tool::linkProcess($link));
    }

    /**
     * Sets the client character set
     *
     * @param string $charset
     * @param mysqli $link
     * @return bool
     */
    function mysql_set_charset($charset, $link = null) {

        return mysqli_set_charset(mysqli_tool::linkProcess($link), $charset);
    }

    /**
     * Execute SQL, used database will changed
     *
     * @param $database_name
     * @param $query
     * @param $link
     * @return bool|mysqli_result
     */
    function mysql($database_name, $query, $link) {

        return mysql_db_query($database_name, $query, $link);
    }
}