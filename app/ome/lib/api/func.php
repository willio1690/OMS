<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_api_func{
    
    /**
     * 错误代码关系表
     * @access public
     * @param string $code 编码
     * @param bool $log_id 日志主键ID
     * @param bool $node_type 店铺类型
     * @return 提示信息
     */
    public function api_code2msg($code=null,$log_id='',$node_type=''){
        
        if (empty($log_id) && empty($node_type)) return $code;
        if (empty($code)) return null;
        $msg = '';
        $api_lang = require_once 'lang.php';
        $oApi_log = app::get('ome')->model('api_log');
        if (empty($node_type)){
            if ($log_id){
                $log_info = $oApi_log->dump($log_id, 'params');
                if ($log_info){
                    $log_params = unserialize($log_info['params']);
                    if (is_array($log_params)){
                        $node_type = $log_params[1]['node_type'];
                    }
                }
            }
        }
        if ($node_type){
            $msg = $api_lang[$node_type][$code];
        }
        if (!$msg){
            $msg = $api_lang['public'][$code];
        }
        if (!empty($msg)){
            return $msg;
        }else{
            return $code;
        }
    }

    /**
     * 重试前扩展逻辑处理
     * @access public
     * @param String $log_id 日志ID
     * @param String $log_type 日志类型
     * @param String $original_bn 原始单据号
     * @param mixed $params 日志参数
     * @return boolean true终止重试,false继续重试
     */
    public function retry_before($log_id,$log_type='',$original_bn='',$params=array()){
        if (empty($log_id) || empty($log_type) || empty($original_bn)) return false;

        #过滤：非同步至WMS的重试日志无需判断单据状态是否取消或已发货
        $method = isset($params[0]) ? $params[0] : '';
        if(!self::retry_filter($method)){
            return false;
        }
        
        $method = substr($log_type,strrpos($log_type,'.')+1);

        // middleware 功能已移除
        // if ( $iostockObj = kernel::single('middleware_iostock') ){
        //     if ( $iostockObj->iscancel($original_bn,$method) ){
        //         $logObj = app::get('ome')->model('api_log');
        //         $api_status = 'success';
        //         $msg = '单据已取消或已发货,不发起同步';
        //         $logObj->update_log($log_id,$msg,$api_status);
        //         return true;
        //     }
        // }

        return false;
    }

    /**
     * 过滤：非同步至WMS的重试日志无需判断单据状态是否取消或已发货
     * @param String $method 接口名
     * @return boid
     */
    public static function retry_filter($method){
        $api_list = array(
            'store.wms.saleorder.create',
            'store.wms.returnorder.create',
            'store.wms.transferorder.create',
            'store.wms.inorder.create',
            'store.wms.outorder.create'
        );
        if (in_array($method,$api_list)){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 递归解析嵌套的JSON字符串
     * @param mixed $data 待解析的数据
     * @return mixed 解析后的数据
     */
    public static function parseNestedJson($data) {
        if (is_array($data)) {
            // 如果是数组，递归处理每个元素
            foreach ($data as $key => $value) {
                $data[$key] = self::parseNestedJson($value);
            }
            return $data; // 返回数组，不重新编码
        } elseif (is_string($data)) {
            // 如果是字符串，尝试解析为JSON
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // 成功解析为数组，递归处理
                return self::parseNestedJson($decoded);
            } else {
                // 不是有效的JSON，返回原字符串
                return $data;
            }
        } else {
            // 其他类型直接返回
            return $data;
        }
    }
    
    /**
     * 处理API日志数据，递归解析JSON并格式化输出
     * @param array $apilog API日志数据
     * @return array 处理后的API日志数据
     */
    public static function processApiLogData($apilog) {
        // 递归解析JSON字符串
        $apilog['params'] = self::parseNestedJson($apilog['params']);
        $apilog['transfer'] = self::parseNestedJson($apilog['transfer']);
        $apilog['response'] = self::parseNestedJson($apilog['response']);
        
        // 将解析后的数据编码为JSON字符串用于前端显示
        if (is_array($apilog['params'])) {
            $apilog['params'] = json_encode($apilog['params'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        if (is_array($apilog['transfer'])) {
            $apilog['transfer'] = json_encode($apilog['transfer'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        if (is_array($apilog['response'])) {
            $apilog['response'] = json_encode($apilog['response'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        
        // HTML转义
        $apilog['msg'] = htmlentities($apilog['msg']);
        $apilog['response'] = htmlentities($apilog['response']);
        
        return $apilog;
    }
    
}