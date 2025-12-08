<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * WMS 发货单
 *
 * @category
 * @package
 * @author yaokangming<yaokangming@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_sku360_response_delivery  extends erpapi_wms_response_delivery
{
    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params){
        $sys_logistics = kernel::single('wmsmgr_func')->getlogiCode($this->__channelObj->wms['channel_id'],$params['logistics']);
        $params['logistics'] = $sys_logistics ? $sys_logistics : $params['logistics'];

        return parent::status_update($params);
    }
}
