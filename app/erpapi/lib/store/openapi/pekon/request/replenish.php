<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 补货同步pos
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_openapi_pekon_request_replenish extends erpapi_store_request_replenish
{

    


    protected function _format_check_params($sdf)
    {

        $params = array(
           'docNo'          =>  $sdf['task_bn'],
           'auditAction'    =>  'APPROVE',

        );
            
       
        return $params;
    }

    protected function get_check_apiname()
    {

        return 'AuditInvOrderDocument';
    }

   
    protected function _format_cancel_params($sdf)
    {

        $params = array(
           'docNo'          =>  $sdf['task_bn'],
           'auditAction'    =>  'REJECT',
           'auditReason'    =>  '',
        );
            
       
        return $params;
    }

    protected function get_cancel_apiname()
    {

        return 'AuditInvOrderDocument';
    }
}
