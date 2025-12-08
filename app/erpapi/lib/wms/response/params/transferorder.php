<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * WMS 转储参数验证
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_params_transferorder extends erpapi_wms_response_params_abstract
{
    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/

    public function update()
    {
        $params = array(
            'stockdump_bn' => array('required'=>'true','type'=>'string','errmsg'=>'转储单号必填'),
        );


        return $params;
    }
}