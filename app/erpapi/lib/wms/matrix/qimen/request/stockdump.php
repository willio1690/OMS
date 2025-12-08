<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 转储单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_qimen_request_stockdump extends erpapi_wms_request_stockdump
{
    protected function _format_stockdump_create_params($sdf)
    {
        $params = parent::_format_stockdump_create_params($sdf);
    
        if (isset($sdf['owner_code'])) {
            $params['ownerCode'] = $sdf['owner_code'];
        }
        
        return $params;
    }
}