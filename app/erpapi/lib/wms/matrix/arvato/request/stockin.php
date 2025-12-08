<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 入库单推送
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_arvato_request_stockin extends erpapi_wms_request_stockin
{
    /**
     * _format_stockin_create_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function _format_stockin_create_params($sdf)
    {
        $params = parent::_format_stockin_create_params($sdf);

        $params['logical_code'] = $this->get_warehouse_code($this->__channelObj->wms['channel_id'],$sdf['branch_bn']);

        return $params;   
    }

    protected function _format_stockin_cancel_params($sdf)
    {
        $params = parent::_format_stockin_cancel_params($sdf);

        $params['logical_code'] = $this->get_warehouse_code($this->__channelObj->wms['channel_id'],$sdf['branch_bn']);
        
        return $params;
    }
}