<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2021/7/15 14:42:57
 * @describe 模板数据获取
 */
class erpapi_logistics_matrix_meituan4bulkpurchasing_request_template extends erpapi_logistics_request_template
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
        $this->title = '获取美团电商模板';

        $rs = $this->requestCall(STORE_STANDARD_DY_TEMPLATE, ['company_code' => $this->__channelObj->channel['logistics_code']]);
        $resData      = json_decode($rs['data'], 1);
        if ($rs['rsp'] == 'succ' && is_array($resData) && is_array($resData['data']) && $resData['data']['template_list']) {
            $rs['data']   = [];
            $templateList = $resData['data']['template_list'];
            foreach ($templateList as $k => $v) {
                $rs['data'][] = array(
                    'tpl_index'       => 'user' . '-' . $v['id'],
                    'cp_code'         => $v['express_code'],
                    'out_template_id' => $v['id'],
                    'template_name'   => $v['template_name'] . '(美团电商)',
                    'template_type'   => 'meituan4bulkpurchasing_user',
                    'template_data'   => json_encode(['template_url'=>$v['template_url'], 'custom_url'=>$v['custom_url']]),
                    'template_select' => $v['custom_zone_field_list'],
                    'template_width'  => 0,
                    'template_height' => 0,
                );
            }
        } else {
            $rs['data'] = [];
        }
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
