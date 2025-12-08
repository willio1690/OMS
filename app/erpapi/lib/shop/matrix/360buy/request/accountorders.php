<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_360buy_request_accountorders extends erpapi_shop_request_accountorders
{


    /**
     * 获取list
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getlist($params){

        $sdf = $this->format_getlist_params($params);

        $title = '实销实结明细列表';

       
        $result = $this->__caller->call(SHOP_BILL_PAYABLE_SETTLEMENT_QUERY, $sdf, null, $title, 30, $params['original_bn']);
       
        if($result['rsp']=='succ' && $result['data']){
            $result['data'] = json_decode($result['data'],true);
        }
        unset($result['response']);
        return $result;
    }

    /**
     * [自定义]请求参数
     * 
     * @param array $params
     * @return array
     */
    public function format_getlist_params($params)
    {
        //api同步日志单据号
        $this->_original_bn = $params['original_bn'];
        
        //params
        $query_params = array(
            'lastId'    =>  $params['lastid'],
          
            'page_size' => 10, //分页大小
        );
        

        if($params['start_time'] && $params['end_time']){
            $query_params['refDateStart'] = date('Y-m-d H:i:s',$params['start_time']);
            $query_params['refDateEnd'] = date('Y-m-d H:i:s',$params['end_time']);
        }
        return $query_params;
    } 
    
}