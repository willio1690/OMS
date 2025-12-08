<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 财务费用抽象类
*
* @category finance
* @package finance/lib/rpc/request/bill
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-10-11 14:23Z
*/
class finance_rpc_request_bill_abstract
{
    const _APP_NAME = 'ome';
    
    protected $shop = array();

    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct()
    {
        $this->_caller = kernel::single('ome_rpc_request');
    }

    /**
     * 设置Shop
     * @param mixed $shop shop
     * @return mixed 返回操作结果
     */
    public function setShop($shop)
    {
        $this->shop = $shop;

        return $this;
    }

    /**
     * RPC同步返回数据接收
     * @access public
     * @param json array $res RPC响应结果
     * @param array $params 同步日志ID
     */
    public function response_log($res, $params){
        $response = json_decode($res, true);
        if (!is_array($response)){
            $response = array(
                'rsp' => 'running',
                'res' => $res,
            );
        }
        $status = $response['rsp'];
        $result = $response['res'];

        if($status == 'running'){
            $api_status = 'running';
        }elseif ($result == 'rx002'){
            //将解除绑定的重试设置为成功
            $api_status = 'success';
        }else{
            $api_status = 'fail';
        }

        $log_id = $params['log_id'];
        $oApi_log = app::get('ome')->model('api_log');

        //更新日志数据
        $oApi_log->update_log($log_id, $result, $api_status);

        if ($response['msg_id']){
            $update_data = array(
                'msg_id' => $response['msg_id'],
            );
            $update_filter = array('log_id'=>$log_id);
            $oApi_log->update($update_data, $update_filter);
        }
    }
}