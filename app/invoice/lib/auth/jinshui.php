<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_auth_jinshui implements invoice_auth_iconfig
{
    /**
     * 加载授权配置
     *
     * @return void
     * @author 
     */
    public function getAuthConfigs() 
    {
        $params = array(
            'jinshui_api_key' => array(
                'label'    => '身份认证',
                'name'     => 'extend_data[jinshui_api_key]',
                'required' => true,
            ),
        );

        return $params;
    }
}