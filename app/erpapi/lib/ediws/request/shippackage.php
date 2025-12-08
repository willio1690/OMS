<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 运单列表
 *
 * @categoryclassName
 * @package
 * @version $Id: Z
 */
class erpapi_ediws_request_shippackage extends erpapi_ediws_request_abstract
{

   
    /**
     * 备件库运单列表
     * @param $appid
     * @param $secret
     * @return mixed
     */

    public function getlist($params)
    {
        
        $sdf = $this->getlist_format_params($params);
        $sdf['original_bn']='shippackage_getlist';
        $title = '查询中小件备件库发运详情-时尚';

        $result = $this->call('edi.request.shippackage.getlist', $sdf, null, $title, 30, $sdf['original_bn']);

       
        unset($result['response']);

        
        return $result;
    }

   
    public function getlist_format_params($params){

        

        return $params;
    }
   

    /**
     * 查询中小件备件库发运详情-时尚
     * @param $appid
     * @param $secret
     * @return mixed
     */

    public function detail($params){
        $sdf = $this->detail_format_params($params);
        $sdf['original_bn']=$sdf['packageId'];
        $title = '查询中小件备件库发运详情-时尚';

        $result = $this->call('edi.request.shippackage.detail', $sdf, null, $title, 30, $sdf['original_bn']);


        unset($result['response']);


        return $result;
    }

    public function detail_format_params($params){

        
        return $params;
    }
}
