<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 主库退货单查询
 *
 * @categoryclassName
 * @package
 * @version $Id: Z
 */
class erpapi_ediws_request_reship extends erpapi_ediws_request_abstract
{

   
    /**
     * 主库退货单查询
     * @param $appid
     * @param $secret
     * @return mixed
     */

    public function query($params)
    {
        
        $sdf = $this->query_format_params($params);

        $title = '主库退货单查询';

        $result = $this->call('edi.request.reship.query', $sdf, null, $title, 30, $sdf['original_bn']);

        
        unset($result['response']);

        
        return $result;
    }

   
    public function query_format_params($params){

        

        return $params;
    }
   

}
