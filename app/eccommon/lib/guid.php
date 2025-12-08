<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯一ID生成
 */
class eccommon_guid {

    /**
     * 单例对像
     */
    static $_instance = null;

    /**
     * instance
     * @return mixed 返回值
     */


    static public function instance() {

        if (!is_object(self::$_instance)) {

            self::$_instance = new eccommon_guid();
        }

        return self::$_instance;
    }

     /**
      * 生成自增ID
      * 
      * @param $type  String  单据号类型 如 delvery, iostock-1, iostock-2 等等，用于区别某一类别的单据
      * @param $prefix  String  返回单据号的前缀，如 R， E, R20150807 , E20150807 , 150817 等等
      * @param $length  Integer  用于组成后缀的长度，如 5，6
      * @param $fix  Boolean  用于否需使用1位长度来标识GUID的的产生来源，如 DB =>1, Memcache => 2, Redis => 3 
      */
     public function incId($type, $prefix ,$length, $fixed=false) {

        //获取可能存在的可用于产生自增量的系统，如 redis, memcache 等
        $cacheObj = $incObj = $this->_getIncGenerater();
        $key = $prefix;

        if (is_object($incObj)) {
            //根据 fixed 传入值，产生用于组成唯一GUID的前半部分内容
            if ($fixed) {
                $key = $prefix . $incObj->getUUIDFix();
            } 
            //如系统有可用配置，则使用可用的对像来产生对应类型的自增
            $cachekey = $this->_createKey($key, $type);
            $step = $incObj->increment($cachekey);

            //判断如果自增id，验证是否是异常情况链接不上导致的id重置为1，检查数据库是否已存在
            if ($step == 1) {
                $numid = $key . str_pad($step, $length,'0',STR_PAD_LEFT);
                if ($this->_existGUID($type, $numid)) {
                    $step = false;
                }
            }
        } else {
            //不存在，赋值为false，让系统后续使用数据库方式获取
            $step = false;
        }

        //如获取失败，使用数据库获取
        if ($step === false) {
            
            $incObj = $this->_getIncDbGUID();
            if ($fixed) {
                $key = $prefix . $incObj->getUUIDFix();
            }
            $step = $incObj->increment($type, $prefix ,$length, $fixed);

            //根据数据库计算的自增id重置缓存中的值
            if (is_object($cacheObj)) {
                $cacheObj->store($cachekey, $step);
            }
        }

        //根据传入的参数来生成可用的GUID
        $guid = $key . str_pad($step, $length,'0',STR_PAD_LEFT);

        //在数据库中进行保存，进行可用性校验，并保证在缓存类产生对像不可用的情况下，能使用数据库产生合适的可用GUID
        if ($this->_vaildGUID($type, $guid)) {

            return $guid;
        } else {
            //重新生成一次
            return $this->incId($type, $prefix ,$length, $fixed);
        }
    }

    /**
     * 保存数据库，并检查是否有重复
     * 
     * @return Boolean
     */
    private function _vaildGUID($type, $guid) {

        return $this->_getIncDbGUID()->_vaildGUID($type, $guid);
    }

    /**
     * ID是否存在
     * 
     * @return void
     * @author 
     * */
    private function _existGUID($type, $guid)
    {
        return $this->_getIncDbGUID()->_existGUID($type, $guid);
    }

    /**
     * 获取实际KEY值
     * 
     * @return String
     */
    private function _createKey($key, $type) {

        return sprintf("%s_%s_%s", 'S_GUID', strtoupper($type), strtoupper($key));
    }

    /**
     * 获取用于产生唯一自增ID的对像
     * 
     * @return Object
     */
    private function _getIncGenerater() {

        if (base_kvstore::instance('uuid')->supportUUID()) {

            //先检查是否KV支持自增型操作
            return base_kvstore::instance('uuid');
        } elseif(cachecore::instance()->supportUUID()) {

            //再检查是否缓存模块是否支持自增型操作
            return cachecore::instance();
        } else {

            return false;//$this->_getIncDbGUID();
        }
    }

    /**
     * 返回使用数据库产生唯一自增ID的对像
     * 
     * @return Object
     */
    private function _getIncDbGUID() {

        return ome_concurrent_guid::instance();
    }
}
