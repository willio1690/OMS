<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 预定义的场景和元素
 * Class ome_reship_setting
 */
class ome_reship_setting
{
    /**
     * 获取_pagecols_setting
     * @param mixed $type type
     * @return mixed 返回结果
     */

    public function get_pagecols_setting($type = '')
    {
        $predefinedScenes = array(
            'ome_reship' => array(
                'name'     => '新建退换货单',
                'elements' => array(
                    'flag_type' => array(
                        'name' => '售后类型',
                        'options' => array(
                            'kt' => '客退',
                            'ydt' => '原单退',
                        ),
                        'default' => 'kt'
                    ),
                    'return_logi_name' => array(
                        'name' => '退回物流公司',
                        'options' => array(),
                        'default' => ''
                    ),
                    'return_logi_no' => array(
                        'name' => '退货物流单号',
                        'options' => array(),
                        'default' => ''
                    ),
                )
            ),
        );
        return $predefinedScenes[$type] ?? $predefinedScenes;
    }
}