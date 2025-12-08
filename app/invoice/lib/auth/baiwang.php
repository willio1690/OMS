<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_auth_baiwang implements invoice_auth_iconfig
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
            'username'  => array (
                'label'    => '用户名',
                'name'     => 'extend_data[username]',
                'required' => true,
            ),
            'password'  => array (
                'label'    => '密码',
                'name'     => 'extend_data[password]',
                'required' => true,
            ),
            'appKey'    => array (
                'label'    => 'appKey',
                'name'     => 'extend_data[appKey]',
                'required' => true,
            ),
            'appSecret' => array (
                'label'    => 'appSecret',
                'name'     => 'extend_data[appSecret]',
                'required' => true,
            ),
            'salt'      => array (
                'label'    => '用户盐值',
                'name'     => 'extend_data[salt]',
                'required' => true,
            ),
            'tax_no'    => array (
                'label'    => '机构税号',
                'name'     => 'extend_data[tax_no]',
                'required' => true,
            ),
        );

        return $params;
    }
}
