<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_conf_server
{

    private static $__server = array(
        'wap'     => array(
            'label' => '系统自带移动端',
            'desc'  => '',
        ),
        'openapi' => array(
            'label' => '商派POS',
            'desc'  => '',
        ),
    );

    public static function getTypeList($type = '')
    {
        //判断是否配置阿里全渠道的主店铺和绑定奇门
        if (app::get('tbo2o')->is_installed()){
            $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
            if (!empty($tbo2o_shop) && $tbo2o_shop["shop_id"]) {
                self::$__server["taobao"] = array(
                    'label' => '阿里全渠道',
                    'desc'  => '',
                );
            }
        }

        foreach (self::$__server as $key => $value) {
            $types[$key] = array('type' => $key, 'label' => $value['label'], 'desc' => $value['desc']);
        }
        return isset($types[$type]) ? $types[$type] : $types;
    }
}
