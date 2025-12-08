<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_youzan_request_template extends erpapi_logistics_request_template
{

    /**
     * syncStandardTpl
     * @return mixed 返回值
     */
    public function syncStandardTpl()
    {
        $this->title = '获取标准模板';

        $rs = $this->requestCall(STORE_WAYBILL_STANDARD_TEMPLATE, []);

        if ($rs['rsp'] == 'succ' && $rs['data']) {
            $data = @json_decode($rs['data'], 1);

            $rs['data']   = [];
            foreach ($data['data'] as $k => $v) {
                $rs['data'][] = array(
                    'tpl_index'       => 'standard' . '-' . $v['template_name'],
                    'cp_code'         => $v['express_id'],
                    'out_template_id' => $v['template_name'],
                    'template_name'   => $v['template_name'] . '(有赞)',
                    'template_type'   => 'youzan_standard',
                    'template_data'   => $v['template_url'],
                );
            }
        } else {
            $rs['data'] = [];
        }
        
        return $rs;
    }

    // 查询电子面单订购关系
    /**
     * syncUserTpl
     * @param mixed $value value
     * @return mixed 返回值
     */
    public function syncUserTpl($value = '')
    {
        return $this->succ();
    }
}
