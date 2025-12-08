<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * CONFIG
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_hqepay_config extends erpapi_config
{
    /**
     * 应用级参数
     *
     * @param String $method 请求方法
     * @param Array $params 业务级请求参数
     * @return void
     * @author
     **/

    public function get_query_params($method, $params)
    {
        if ($method == SHOP_LOGISTICS_BIND) {
            return array();
        }

        $query_params = array(
            'app_id'       => 'ecos.ome',
            'method'       => $method,
            'date'         => date('Y-m-d H:i:s'),
            'format'       => 'json',
            'certi_id'     => base_certificate::certi_id(),
            'v'            => '1',
            'from_node_id' => base_shopnode::node_id('ome'),
            'node_type'    => 'kdn',
            'to_node_id'   => $this->__channelObj->channel['node_id'],
        );

        $app_xml                    = app::get('ome')->define();
        $query_params['from_api_v'] = $app_xml['api_ver'];

        return $query_params;
    }

    

    /**
     * 签名
     *
     * @param Array $params 参数
     * @return void
     * @author
     **/
    public function gen_sign($params, $method = '')
    {
        if ($method == SHOP_LOGISTICS_BIND) {return '';}

        if (!base_shopnode::token('ome')) {
            $sign = base_certificate::gen_sign($params);
        } else {
            $sign = base_shopnode::gen_sign($params, 'ome');
        }

        return $sign;
    }
}
