<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_dewu_request_template extends erpapi_logistics_request_template
{
    /**
     * syncStandardTpl
     * @return mixed 返回值
     */
    public function syncStandardTpl()
    {
        return ['rsp' => 'succ'];
    }

    /**
     * syncUserTpl
     * @return mixed 返回值
     */
    public function syncUserTpl()
    {
        // 品牌直发不允许自己绘制面单，为了兼容oms系统，返回一条默认数据
        $rs = [
            'rsp'  => 'succ',
            'data' => [[
                'out_template_id' => '0',
                'tpl_index'       => 'DEWU',
                'template_name'   => '得物默认模板',
                'template_type'   => 'dewu_ppzf',
                'status'          => 'true',
                'template_width'  => '70',
                'template_height' => '170',
                'file_id'         => '0',
                'is_logo'         => 'true',
                'template_data'   => '',
                'is_default'      => 'true',
                'page_type'       => '1',
                'aloneBtn'        => false,
                'btnName'         => '',
                'source'          => 'dewu',
                'cp_code'         => '',
            ],
            [
                'out_template_id' => '0',
                'tpl_index'       => 'DEWU-ZY',
                'template_name'   => '得物自研控件默认模板',
                'template_type'   => 'dewu_ppzf_zy',
                'status'          => 'true',
                'template_width'  => '70',
                'template_height' => '170',
                'file_id'         => '0',
                'is_logo'         => 'true',
                'template_data'   => '',
                'is_default'      => 'true',
                'page_type'       => '1',
                'aloneBtn'        => false,
                'btnName'         => '',
                'source'          => 'dewu',
                'cp_code'         => '',
            ]],
        ];
        return $rs;
    }

    /**
     * 获取UserDefinedTpl
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getUserDefinedTpl($params)
    {
        return ['rsp' => 'succ'];
    }
}
