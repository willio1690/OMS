<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * @package base
 * @copyright Copyright (c) 2021, shopex. inc
 * @author edwin.lzh@gmail.com
 * @license 
 * php7.0之后支持mongodb扩展， 本类使用
 */
class base_kvstore_mongo extends base_kvstore_abstract implements base_interface_kvstore_base {

    static protected $_dbCollection = null;

    static protected $_writeConcern = null;

    static protected $_mongodb = null;

    static protected $_rd = 0;
    static protected $_wd = 0;
    static protected $_cd = 0;
    const __DEBUG = false;

    function __construct($prefix) 
    {
        $this->prefix = $prefix.'/mongo';
    }//End Function

    protected function _connectMongoDB() {
	
	if (!is_object(self::$_mongodb)) {
              
             $this->_init();
	}
    }

    protected function _init() {

        $server = defined('MONGODB_SERVER_CONFIG')?MONGODB_SERVER_CONFIG:"mongodb://localhost:27017";
        $option = defined('MONGODB_OPTION_CONFIG')?eval(MONGODB_OPTION_CONFIG):array("connect" => TRUE);

        self::$_mongodb = self::getMongoClient($server, $option);

        self::$_dbCollection = 'erp.'.base_kvstore::kvprefix();

        self::$_writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);

    }

    static protected function getMongoClient($seeds = "", $options = array(), $retry = 3) {
        try {
            return new MongoDB\Driver\Manager($seeds, $options);
        } catch(Exception $e) {

        }

        if ($retry > 0) {
            return self::getMongoClient($seeds, $options, --$retry);
        }

        throw new Exception("I've tried several times getting MongoClient.. Is mongod really running?");
    }

    /**
     * fetch
     * @param mixed $key key
     * @param mixed $value value
     * @param mixed $timeout_version timeout_version
     * @return mixed 返回值
     */
    public function fetch($key, &$value, $timeout_version=null) 
    {
        $_interKey = $this->create_key($key);
        $store = cachecore::fetch($_interKey);
        if ($store===false) {

            if (self::__DEBUG) {

                self::$_rd ++ ;
                echo sprintf('Read %3d : %s _ %s<hr>', self::$_rd, $this->prefix, $key);
            }
            $this->_connectMongoDB();
            $query = new MongoDB\Driver\Query(['key'=>$_interKey], []);
            $documents = self::$_mongodb->executeQuery(self::$_dbCollection, $query);
            foreach($documents as $document){
                $store = json_decode(json_encode($document),true);
            }
            //更新内容到缓存服务器
            if ($store) {

                cachecore::store($_interKey, serialize($store), 864000);
            } else {

                cachecore::store($_interKey, '', 3600);
            }
        } else {
            //兼容原有TT存储的序列化数据
            if (!is_array($store)) {

                $store = unserialize($store);
            }
        }

        if(!empty($store) && $timeout_version < $store['dateline']){
            if($store['ttl'] > 0 && ($store['dateline']+$store['ttl']) < time()){
                return false;
            }
            $value = $store['value'];
            return true;
        }
        return false;
    }//End Function

    /**
     * store
     * @param mixed $key key
     * @param mixed $value value
     * @param mixed $ttl ttl
     * @return mixed 返回值
     */
    public function store($key, $value, $ttl=0) 
    {
        $_interKey = $this->create_key($key);
        $store['value'] = $this->_encoding($value);
        $store['dateline'] = time();
        $store['ttl'] = $ttl;
        $store['key'] = $_interKey;
        $store['o_key'] = $key;
        $store['kvfix'] = base_kvstore::kvprefix();
        $store['prefix'] = $this->prefix;

        //先在Memcache中缓存一份
        cachecore::store($_interKey, $store, 864000);
        if (self::__DEBUG) {

            self::$_wd ++ ;
            echo sprintf('Write %3d : %s _ %s<hr>', self::$_wd, $this->prefix, $key);
        }
        $this->_connectMongoDB();
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(['key'=>$_interKey], ['$set' => $store], ['upsert' => true]);
        $res = self::$_mongodb->executeBulkWrite(self::$_dbCollection, $bulk, self::$_writeConcern);
        return $res;
    }//End Function

    /**
     * 删除
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function delete($key) 
    {
        $_interKey = $this->create_key($key);
        cachecore::store($_interKey, '',1);
        $this->_connectMongoDB();
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete(['key'=>$_interKey]);
        $res = self::$_mongodb->executeBulkWrite(self::$_dbCollection, $bulk, self::$_writeConcern);
        return $res;
    }//End Function

    /**
     * recovery
     * @param mixed $record record
     * @return mixed 返回值
     */
    public function recovery($record) 
    {
        if (isset($record['o_key']) && !empty($record['o_key'])) {
            $_interKey = $this->create_key($record['o_key']);
        } else {
            $_interKey = $this->create_key($record['key']);
        }

        $store['key'] = $_interKey;
        $store['o_key'] = $record['o_key'] ? $record['o_key'] : $record['key'];
        $store['value'] = $this->_encoding($record['value']);
        $store['dateline'] = $record['dateline'];
        $store['ttl'] = $record['ttl'];
        $store['kvfix'] = $record['kvfix'] ? $record['kvfix'] : base_kvstore::kvprefix();
        $store['prefix'] = $record['prefix'] ? $record['prefix'] : $this->prefix;
        if (self::__DEBUG) {

            self::$_cd ++ ;
            echo sprintf('Recovery %3d : %s _ %s<hr>', self::$_cd, $this->prefix, $store['o_key']);
        }
        $this->_connectMongoDB();
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(['key'=>$_interKey], ['$set' => $store], ['upsert' => true]);
        $res = self::$_mongodb->executeBulkWrite(self::$_dbCollection, $bulk, self::$_writeConcern);
        return $res;
    }//End Function

    /**
     * persistence
     * @param mixed $dateline dateline
     * @return mixed 返回值
     */
    public function persistence($dateline = 0) {

        $db = kernel::database();
        $this->_connectMongoDB();
        $cursors = self::$_mongodb->find();

        foreach ($cursors as $doc) {

            $isNew = $this->_getUpdateType($doc);
            $db->exec($this->_getKvSql($doc, $isNew));
        }
    }

    private function _getKvSql($doc, $isnew) {

        $fields = array();
        $fields['prefix']   = $doc['prefix'];
        $fields['key']      = $doc['o_key'];
        $fields['value']    = serialize($doc['value']);
        $fields['dateline'] = $doc['dateline'];
        $fields['ttl']      = $doc['ttl'];


        foreach($fields as $field => $value) {

            $_tmp[] = sprintf("`%s` = '%s'", $field, mysql_escape_string($value)); 
        }

        if ($isnew) {

            return sprintf("INSERT INTO sdb_base_kvstore SET %s", join(' ,', $_tmp));
        } else {

            return sprintf("UPDATE sdb_base_kvstore SET %s WHERE `prefix`='%s' AND `key`='%s'",
                join(' ,', $_tmp), mysql_escape_string($doc['prefix']), mysql_escape_string($doc['o_key']));
        }
    }

    private function _getUpdateType($doc) {

        $sqlStr = sprintf("SELECT count(*) as c from sdb_base_kvstore WHERE `prefix`='%s' AND `key`='%s'",
                        mysql_escape_string($doc['prefix']), mysql_escape_string($doc['o_key']));
        $count = kernel::database()->count($sqlStr);
        return ($count > 0 ) ? false : true ;
    }

    private function _encoding($val) {

        if (!empty($val)) {

            if (is_array($val)) {
                foreach ($val as $k => $v) {

                    $val[$k] = $this->_encoding($v);
                }
                return $val;
            } else {

                return mb_convert_encoding($val,'UTF-8','auto');
            }
        } else {
                return $val;
        
        }
    }
}//End Class

