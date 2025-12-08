<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2017/9/14
 * @describe 物流信息请求
 */

class erpapi_wms_matrix_jd_request_logistics extends erpapi_wms_request_logistics {

    protected function _format_create_params($params)
    {
        $opUser = kernel::single('desktop_user')->get_name();
        $sdf = parent::_format_create_params($params);
        $sdf['useFlag'] = 1;
        $sdf['operateUser'] = $opUser ? $opUser : 'system';
        $sdf['operateTime'] = date('Y-m-d H:i:s');
        return $sdf;
    }
}