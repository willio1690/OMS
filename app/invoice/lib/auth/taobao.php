<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_auth_taobao implements invoice_auth_iconfig
{
    /**
     * 加载授权配置
     *
     * @return void
     * @author 
     */
    public function getAuthConfigs() 
    {
        $shopMdl = app::get('ome')->model('shop');

        $type = array();
        foreach ($shopMdl->getlist('shop_id,shop_type,name',array('shop_type'=>'taobao','filter_sql'=>' node_id IS NOT NULL ')) as $value) {
            $type[$value['shop_id']] = $value['name'];
        }
        $params = array(
            'shop_id' => array(
                'label'    => '关联店铺',
                'name'     => 'shop_id',
                'type'     => $type,
                'required' => true,
            ),
        );

        return $params;
    }
}