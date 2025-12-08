<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_idaas_request_organization extends erpapi_idaas_request_abstract
{
    /**
     * 开票
     * @param  [type] $sdf [description]
     * @return [type]      [description]
     */
    public function create($sdf)
    {
        $title = 'IDAAS同步组织架构';

        $params = array(
            'org_name'     => base_shopnode::node_id('ome'),
            'external_id'  => base_shopnode::node_id('ome'),
            'external_pid' => '7769495049577975511',
            'source'         => $sdf['source'],
            'domain'         => base_request::get_host(),
        );

        $rsp = $this->__caller->call(IDAAS_ORGANIZATION_CREATE, $params, array(), $title, 10, $sdf['org_name']);

        return $rsp;

    }

    /**
     * 查询组织架构
     * @param  [type] $sdf [description]
     * @return [type]      [description]
     */
    public function get($sdf)
    {
        $title = 'IDAAS查询组织架构';

        $params = array(
            'external_id' => base_shopnode::node_id('ome'),
            'source'         => $sdf['source'],
            'domain'         => base_request::get_host(),
        );

        $rsp = $this->__caller->call(IDAAS_ORGANIZATION_GET, $params, array(), $title, 10);

        return $rsp;

    }

}
