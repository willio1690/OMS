<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 获取京东平台基础信息
 *
 * @author wangbiao@shopex.cn
 * @version 2024.03.07
 */
class erpapi_shop_matrix_360buy_request_base extends erpapi_shop_request_base
{
    /**
     * 获取京东Token信息
     * 
     * @param $params
     * @return array
     */

    public function getNpsToken($params=null)
    {
        $title = '获取京东Token信息';
        $original_bn = 'jd_token';
        $params = array();
        $callback = array();
        
        //request
        $result = $this->__caller->call(STORE_JD_NPS_TOKEN, $params, $callback, $title, 10, $original_bn);
        $msg_id = $result['msg_id'];
        if ($result['rsp'] != 'succ') {
            $error_msg = '获取京东Token失败：';
            $error_msg .= ($result['msg'] ? $result['msg'] : $result['err_msg']);
            
            return $this->error($error_msg, $msg_id);
        }
        
        //data
        $tempData = json_decode($result['data'], true);
        $data = array(
            'requestId' => $tempData['requestId'],
            'token' => $tempData['data'],
            'msg' => $tempData['msg'],
        );
        
        //check
        if(empty($data['token'])){
            $error_msg = '获取京东Token为空';
            
            return $this->error($error_msg, $msg_id, $data);
        }
        
        return $this->succ('获取京东Token成功', $msg_id, $data);
    }
}
