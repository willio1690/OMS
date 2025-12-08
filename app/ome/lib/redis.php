<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_redis
{
    static protected $stockRedis = null;
    static protected function _connectRedis()
    {
        $redisConfig1 = $redisConfig2 = false;

        if (defined('KVSTORE_STORAGE') && constant('KVSTORE_STORAGE') == 'base_kvstore_redis') {
            // 共享KVSTORE的redis
            $redisConfig1 = true;
        }

        if (defined('STORE_REDIS_STORAGE') && constant('STORE_REDIS_STORAGE') === 'true') {
            // 新增的独享redis
            $redisConfig2 = true;
        }

        if (!$redisConfig1 && !$redisConfig2) {
            return false;
        }
        
        if (self::$stockRedis && is_object(self::$stockRedis)) {
            return true;
        }
        
        self::$stockRedis = new Redis();

        if ($redisConfig2) {
            $config = explode(':', constant('STORE_REDIS_CONFIG'));
            self::$stockRedis->connect($config[0], $config[1]);
            
            // 密码
            if (defined('STORE_REDIS_AUTH') && constant('STORE_REDIS_AUTH')){
                self::$stockRedis->auth(constant('STORE_REDIS_AUTH'));
            }
        } else {
            $config = explode(':', constant('KVSTORE_REDIS_CONFIG'));
            self::$stockRedis->connect($config[0], $config[1]);
            
            // 密码
            if (defined('KVSTORE_REDIS_AUTH') && constant('KVSTORE_REDIS_AUTH')){
                self::$stockRedis->auth(constant('KVSTORE_REDIS_AUTH'));
            }
        }
        
        //Specify a database
        if (isset($config[2]) && $config[2] >= 0){
            self::$stockRedis->select($config[2]);
        }
        
        return true;
    }

    static public $publicRedis = null;
    /**
     * publicConnectRedis
     * @return mixed 返回值
     */
    static public function publicConnectRedis()
    {
        $redisConfig1 = $redisConfig2 = false;

        if (defined('KVSTORE_STORAGE') && constant('KVSTORE_STORAGE') == 'base_kvstore_redis') {
            // 共享KVSTORE的redis
            $redisConfig1 = true;
        }

        if (defined('STORE_REDIS_STORAGE') && constant('STORE_REDIS_STORAGE') === 'true') {
            // 新增的独享redis
            $redisConfig2 = true;
        }

        if (!$redisConfig1 && !$redisConfig2) {
            return false;
        }
        
        if (self::$publicRedis && is_object(self::$publicRedis)) {
            return true;
        }
        
        self::$publicRedis = new Redis();

        if ($redisConfig2) {
            $config = explode(':', constant('STORE_REDIS_CONFIG'));
            self::$publicRedis->connect($config[0], $config[1]);
            
            // 密码
            if (defined('STORE_REDIS_AUTH') && constant('STORE_REDIS_AUTH')){
                self::$publicRedis->auth(constant('STORE_REDIS_AUTH'));
            }
        } else {
            $config = explode(':', constant('KVSTORE_REDIS_CONFIG'));
            self::$publicRedis->connect($config[0], $config[1]);
            
            // 密码
            if (defined('KVSTORE_REDIS_AUTH') && constant('KVSTORE_REDIS_AUTH')){
                self::$publicRedis->auth(constant('KVSTORE_REDIS_AUTH'));
            }
        }
        
        //Specify a database
        if (isset($config[2]) && $config[2] >= 0){
            self::$publicRedis->select($config[2]);
        }
        
        return true;
    }

    protected function getFlowHash(){}

    // 查看redis《冻结流水》和redis《库存流水》
    /**
     * 获取RedisFlow
     * @param mixed $howLongAgo howLongAgo
     * @return mixed 返回结果
     */
    public function getRedisFlow($howLongAgo = 0)
    {
        $return = ['freeze' => [], 'store' => []];

        $isRedis = self::_connectRedis();
        if (!$isRedis) {
            return $return;
        }

        $checkTime = time()-$howLongAgo;

        // 冻结流水
        $freezeHash = $this->getFlowHash('freeze');
        // 初始化游标为字符串'0'（或者null），int0会取不到，经测大于512条数据以后，count才生效，且实际返回数据条数会在count上下浮动
        $cursor = null;
        $count = 100;
        do {
            $freezeList = self::$stockRedis->hscan($freezeHash, $cursor, null, $count);
            if (!$freezeList) {
                break;
            }
            foreach (array_keys($freezeList) as $_field) {
                $inTime = '';
                list(,,,,$inTime) = explode('#', $_field);
                if (!$inTime || $inTime<=$checkTime) {
                    $return['freeze'][$_field] = $freezeList[$_field];
                }
            }
            /*
            foreach ($freezeList as $_field => $_v) {
                $_vs = explode(';', $_v);
                foreach ($_vs as $info) {
                    $inTime = '';
                    list(,,,$inTime) = explode(':', $info);
                    if (!$inTime || $inTime<=$checkTime) {
                        $return['freeze'][$_field] = $_v;
                        break 2;
                    }
                }
            }
            */
        } while ($cursor != 0); // 当游标变为0时，表示迭代完成

        // 库存流水
        $storeHash = $this->getFlowHash('store');
        // 初始化游标为字符串'0'（或者null），int0会取不到，经测大于512条数据以后，count才生效，且实际返回数据条数会在count上下浮动
        $cursor = null;
        $count = 100;
        do {
            $storeList = self::$stockRedis->hscan($storeHash, $cursor, null, $count);
            if (!$storeList) {
                break;
            }
            foreach (array_keys($storeList) as $_field) {
                $inTime = '';
                list(,,$inTime) = explode('#', $_field);
                if (!$inTime || $inTime<=$checkTime) {
                    $return['store'][$_field] = $storeList[$_field];
                }
            }
            /*
            foreach ($storeList as $_field => $_v) {
                $_vs = explode(';', $_v);
                foreach ($_vs as $info) {
                    $inTime = '';
                    list(,,,$inTime) = explode(':', $info);
                    if (!$inTime || $inTime<=$checkTime) {
                        $return['store'][$_field] = $_v;
                        break 2;
                    }
                }
            }
            */
        } while ($cursor != 0); // 当游标变为0时，表示迭代完成

        return $return;
    }

    /**
     * 检测ome/lua目录下所有Lua脚本的语法
     * 
     * @param string $lua_dir 可选，指定Lua脚本目录，默认为app/ome/lua
     * @return array 检测结果
     */

    static public function validateLuaScripts($lua_dir = null)
    {
        if ($lua_dir === null) {
            $lua_dir = app::get('ome')->app_dir . '/lua';
        }

        $results = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'errors' => [],
            'details' => []
        ];

        // 检查目录是否存在
        if (!is_dir($lua_dir)) {
            $results['errors'][] = "Lua脚本目录不存在: {$lua_dir}";
            return $results;
        }

        // 连接Redis
        $isRedis = self::_connectRedis();
        if (!$isRedis) {
            $results['errors'][] = "Redis连接失败，无法验证Lua脚本语法";
            return $results;
        }

        // 获取所有.lua文件
        $lua_files = glob($lua_dir . '/*.lua');
        $results['total'] = count($lua_files);

        if ($results['total'] == 0) {
            $results['errors'][] = "在目录 {$lua_dir} 中未找到.lua文件";
            return $results;
        }

        foreach ($lua_files as $lua_file) {
            $filename = basename($lua_file);
            $script_name = pathinfo($filename, PATHINFO_FILENAME);
            
            try {
                $lua_content = file_get_contents($lua_file);
                if ($lua_content === false) {
                    $results['invalid']++;
                    $results['errors'][] = "无法读取文件: {$filename}";
                    $results['details'][$filename] = [
                        'valid' => false,
                        'error' => '无法读取文件'
                    ];
                    continue;
                }

                // 使用Redis的SCRIPT LOAD命令验证Lua脚本语法
                $sha = self::$stockRedis->script('LOAD', $lua_content);
                
                if ($sha === false) {
                    $results['invalid']++;
                    $results['errors'][] = "语法错误: {$filename}";
                    $results['details'][$filename] = [
                        'valid' => false,
                        'error' => 'Lua脚本语法错误'
                    ];
                } else {
                    $results['valid']++;
                    $results['details'][$filename] = [
                        'valid' => true,
                        'sha' => $sha,
                        'size' => strlen($lua_content)
                    ];
                }
            } catch (Exception $e) {
                $results['invalid']++;
                $results['errors'][] = "验证失败: {$filename} - " . $e->getMessage();
                $results['details'][$filename] = [
                    'valid' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * 检测单个Lua脚本文件的语法
     * 
     * @param string $lua_file Lua脚本文件路径
     * @return array 检测结果
     */

    static public function validateSingleLuaScript($lua_file)
    {
        $result = [
            'valid' => false,
            'error' => '',
            'sha' => '',
            'size' => 0
        ];

        // 检查文件是否存在
        if (!file_exists($lua_file)) {
            $result['error'] = "文件不存在: {$lua_file}";
            return $result;
        }

        // 连接Redis
        $isRedis = self::_connectRedis();
        if (!$isRedis) {
            $result['error'] = "Redis连接失败，无法验证Lua脚本语法";
            return $result;
        }

        try {
            $lua_content = file_get_contents($lua_file);
            if ($lua_content === false) {
                $result['error'] = "无法读取文件";
                return $result;
            }

            $result['size'] = strlen($lua_content);

            // 使用Redis的SCRIPT LOAD命令验证Lua脚本语法
            $sha = self::$stockRedis->script('LOAD', $lua_content);
            
            if ($sha === false) {
                $result['error'] = 'Lua脚本语法错误';
                return $result;
            } else {
                $result['valid'] = true;
                $result['sha'] = $sha;
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

}