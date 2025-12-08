<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 盘点单
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_openapi_pekon_request_inventory extends erpapi_store_request_inventory
{

   

    protected function _format_check_params($sdf)
    {

        $params = array(
           'docNo'          =>  $sdf['inventory_bn'],
           'auditAction'    =>  'APPROVE',

        );
            
       
        return $params;
    }

    protected function get_check_apiname()
    {

        return 'AuditCheckDocument';
    }

    
    protected function _format_cancel_params($sdf)
    {

        $params = array(
           'docNo'          =>  $sdf['inventory_bn'],
           'auditAction'    =>  'REJECT',
           'auditReason'    =>  'OMS发起取消',
        );
            
       
        return $params;
    }

    protected function get_cancel_apiname()
    {

        return 'AuditCheckDocument';
    }
}
