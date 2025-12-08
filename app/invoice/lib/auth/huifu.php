<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_auth_huifu implements invoice_auth_iconfig
{
    /**
     * 加载授权配置
     *
     * @return void
     * @author
     */
    public function getAuthConfigs()
    {
        $params = array (
            'sys_id'  => array (
                'label'    => '系统号',
                'name'     => 'extend_data[sys_id]',
                'required' => true,
            ),
            'product_id'  => array (
                'label'    => '产品号',
                'name'     => 'extend_data[product_id]',
                'required' => true,
            ),
            'private_key'    => array (
                'label'    => '商户私钥',
                'name'     => 'extend_data[private_key]',
                'required' => true,
            ),
        );

        return $params;
    }
}
