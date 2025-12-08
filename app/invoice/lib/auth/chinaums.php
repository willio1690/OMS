<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_auth_chinaums implements invoice_auth_iconfig
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
            'merchant_id' => array (
                'label'    => '商户号',
                'name'     => 'extend_data[merchant_id]',
                'required' => true,
                'size'     => 50,
            ),
            'terminal_id' => array (
                'label'    => '终端号',
                'name'     => 'extend_data[terminal_id]',
                'required' => true,
                'size'     => 50,
            ),
            'msg_src'     => array (
                'label'    => '消息来源',
                'name'     => 'extend_data[msg_src]',
                'required' => true,
                'size'     => 50,
            ),
            'secret'      => array (
                'label'    => '秘钥',
                'name'     => 'extend_data[secret]',
                'required' => true,
                'size'     => 50,
            ),
        );

        return $params;
    }
}
