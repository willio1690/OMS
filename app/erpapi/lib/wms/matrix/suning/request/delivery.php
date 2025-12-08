<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/6/11 14:41:31
 * @describe: 类
 * ============================
 */
class erpapi_wms_matrix_suning_request_delivery extends erpapi_wms_request_delivery
{
    protected $outSysProductField = '';

    private $_shop_type_mapping = array(
        'taobao'    => array('code' => '201', 'name' => '淘宝'),
        'paipai'    => array('code' => '210', 'name' => '拍拍'),
        '360buy'    => array('code' => '214', 'name' => '京东'),
        'yihaodian' => array('code' => '208', 'name' => '1号店'),
        'dangdang'  => array('code' => '205', 'name' => '当当'),
        'amazon'    => array('code' => '204', 'name' => '亚马逊'),
        'alibaba'   => array('code' => '202', 'name' => '阿里巴巴'),
        'suning'    => array('code' => '203', 'name' => '苏宁'),
        'gome'      => array('code' => '209', 'name' => '国美'),
        'guomei'    => array('code' => '209', 'name' => '国美'),
        'vop'       => array('code' => '207', 'name' => '唯品会'),
    );

    protected function _format_delivery_create_params($sdf)
    {
        $params = parent::_format_delivery_create_params($sdf);

        $shopType = $sdf['shop_type'];

        $params['order_source'] = $this->_shop_type_mapping[$shopType] ? $this->_shop_type_mapping[$shopType]['code'] : '301';

        return $params;
    }

}
