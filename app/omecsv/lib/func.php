<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 公共函数类
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class omecsv_func
{
    /**
     * 添加队列
     * @param String $title 队列标题
     * @param String $worker 队列执行类方法
     * @param mixed $params 队列参数
     * @param String $type 队列类型
     * @return bool
     */
    public function addTask($title, $worker, $params, $type = 'slow')
    {
        if (empty($params)) {
            return false;
        }
        
        $oQueue    = app::get('base')->model('queue');
        $queueData = array(
            'queue_title' => $title,
            'start_time'  => time(),
            'params'      => $params,
            'worker'      => $worker,
        );
        
        $result = $oQueue->save($queueData);
        
        return $result;
    }
    
    /**
     * db
     * 写入日志
     * @param String $log_title 日志标题
     * @param String $log_type  日志类型
     * @param mixed   $params 参数
     */
    public function writelog($log_title, $log_type, $params)
    {
        if (empty($params)) {
            return null;
        }
        
        if (empty($log_type)) {
            return null;
        }
        
        if (app::get('omecsv')->getConf('bill.logs.is_open') == 'false') {
            return null;
        }
        
        $log_dir = DATA_DIR . '/omecsv/logs/' . date('Ym');
        if (!is_dir($log_dir)) {
            utils::mkdir_p($log_dir);
        }
        
        $log_file = $log_dir . '/omecsv_' . $log_type . '.log';
        
        if (is_array($params)) {
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        }
        
        $log_msg = sprintf("%s [ %s ] : %s \n", date('c'), $log_title, $params);
        
        error_log($log_msg, 3, $log_file);
    }
    
    /**
     * 获取csv文件总行数
     * @param String $file    文件地址
     * @param Int    $length  读取长度
     * @param Int    $start   开始行数
     */
    public function getCsvData($file = '', $length = 0, $start = 0)
    {
        if (!file_exists($file)) {
            return array();
        }
        
        $splObject = new SplFileObject($file, 'rb');
        $length    = $length ? $length : $this->getCsvTotalLines($file) - $start;
        $start     = ($start < 0) ? 0 : $start;
        $data      = array();
        $splObject->seek($start);
        while ($length-- && !$splObject->eof()) {
            $current = $splObject->current(); //current方法不会跳行
            
            $encode = mb_detect_encoding($current, array("ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5'));
            if ('UTF-8' != $encode) {
                $current = mb_convert_encoding($current, 'UTF-8', $encode);
            }
            
            $data[] = str_getcsv($current); //再转成数组
            $splObject->next();
        }
        return $data;
    }
    
    /**
     * 获取csv文件总行数
     * @param String $file 文件地址
     */
    public function getCsvTotalLines($file = '')
    {
        if (!file_exists($file)) {
            return 0;
        }
        
        $splObject = new SplFileObject($file, 'rb');
        $splObject->seek(filesize($file));
        return $splObject->key() + 1;
    }
    
    public function strIconv($str, $from = 'gbk', $to = 'utf-8')
    {
        return iconv("$from", "$to//TRANSLIT", $str);
    }
    
    /**
     * 下载
     * @return bool
     */
    public function download($url, $write_file)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $fp = fopen($write_file, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        
        return true;
    }
    
    /**
     * 解压
     * @return bool
     */
    public function unZip($zip_file, $unzip_dir, $is_delete = 0)
    {
        if (!is_dir($unzip_dir)) {
            utils::mkdir_p($unzip_dir);
        }
        
        $zip = new ZipArchive;
        if ($zip->open($zip_file) === true) {
            $zip->extractTo($unzip_dir);
            $zip->close();
        }
        if ($is_delete) {
            unlink($zip_file);
        }
        
        return true;
    }
    
    /**
     * 接口请求
     * @return bool
     */
    public function request($method, $params, $callback, $log_title, $shop_id, $time_out = 5, $queue = false, $addon = '', $write_log = '', $mode = 'sync')
    {
        $rpcObj = kernel::single('ome_rpc_request');
        
        if ($mode == 'sync') {
            $rs = $rpcObj->rpc_request($method, $params, null, $time_out);
            $rs = (array) $rs;
            if (isset($rs['data'])) {
                $rs['data'] = json_decode($rs['data'], true);
            }
        } elseif ($mode == 'async') {
            
            $rs = $rpcObj->request($method, $params, $callback, $log_title, $shop_id, $time_out, $queue, $addon, $write_log);
        } else {
            $rs = array('rsp' => 'fail', 'err_msg' => '请求类型错误！');
        }
        
        return $rs;
    }
    
    /**
     * 获取配置项
     * @param string $key
     * @return array|mixed
     * @author db
     * @date 2024-01-03 6:22 下午
     */
    public function getConfig($key = '')
    {
        $config = array(
            'page_size' => 500,//500
        );
        return $key ? $config[$key] : $config;
    }
    
    
    /**
     *  数据保存到存储空间 返回远程数据
     *
     * @param      string   $file_name  文件名
     * @param      array    $data       数据
     *
     * @return     Mix
     */
    public static function storeStorageData($file_name, $data = array())
    {
        $storageLib = kernel::single('taskmgr_interface_storage');
        $local_file = DATA_DIR . '/omecsv/tmp_local/' . $file_name;
        file_put_contents($local_file, json_encode($data, JSON_UNESCAPED_UNICODE));
        
        $move_res = $storageLib->save($local_file, $file_name, $remote_url);
        unlink($local_file);
        
        if ($move_res) {
            return $remote_url;
        }
        
        return false;
    }
    
    public static function getStorageData($remote_url, $local_file)
    {
        $storageLib  = kernel::single('taskmgr_interface_storage');
        $getfile_res = $storageLib->get($remote_url, $local_file);
        if ($getfile_res) {
            return json_decode(file_get_contents($local_file), 1);
        }
        return array();
    }
    
    public static function deleteStorageData($remote_url)
    {
        $storageLib = kernel::single('taskmgr_interface_storage');
        $storageLib->delete($remote_url);
    }
    
    public static function addTaskQueue($params, $task_type = 'verificationprocess')
    {
        $params['task_type'] = $task_type;
        $push_params         = array(
            'data' => $params,
            'url'  => kernel::openapi_url('openapi.autotask', 'service'),
        );
        return kernel::single('taskmgr_interface_connecter')->push($push_params);
    }
    
    public static function dd($data)
    {
        echo '<pre>';
        print_r($data);exit;
    }
    
    /**
     * 队列号
     * @return mixed
     * @author db
     * @date 2024-01-05 3:19 下午
     */
    public static function gen_id()
    {
        $prefix = date("Ymd") . '-';
        
        $queue_no = kernel::single('eccommon_guid')->incId('omecsv_split', $prefix, 3, false);
        
        return $queue_no;
    }
}
