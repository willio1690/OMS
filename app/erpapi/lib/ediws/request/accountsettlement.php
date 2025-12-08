<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * [京东结算单分页查询]请求接口Lib类
 *
 * @author
 * @version
 */
class erpapi_ediws_request_accountsettlement extends erpapi_ediws_request_abstract
{
   


    /**
     * 获取list
     * @param mixed $params 参数
     * @return mixed 返回结果
     */

    public function getlist($params){

        $sdf = $this->format_getlist_params($params);

        $title = '京东结算单分页查询列表';

        $result = $this->call('edi.request.accountsettlement.getlist', $sdf, null, $title, 30, $sdf['original_bn']);

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
        
        //必传参数
        $query_params = array(
            'original_bn' => $this->_original_bn, //原始单据号
            'vendorCode' => $params['vendorCode'], //供应商编码
            'pageNo' => ($params['page_no'] ? $params['page_no'] : 1), //页数
            'pageSize' => ($params['page_size'] ? $params['page_size'] : 100), //分页大小
        );
        
        //可选参数
        //审核状态：101待审核:103审核通过;
        if($params['approveStatus']){
            $query_params['approveStatus'] = $params['approveStatus'];
        }
        
        //结算单创建时间(开始)yyyy-MM-dd HH:mm:ss
        if($params['start_time']){
            $query_params['createDateBegin'] = $params['start_time'];
        }
        
        //结算单创建时间(结束)yyyy-MM-dd HH:mm:ss
        if($params['end_time']){
            $query_params['createDateEnd'] = $params['end_time'];
        }
        
        return $query_params;
    }
}
