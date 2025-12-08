<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2021/7/15 14:42:57
 * @describe 模板数据获取
 */
class erpapi_logistics_matrix_wxshipin_request_template extends erpapi_logistics_request_template
{

    /**
     * syncStandardTpl
     * @return mixed 返回值
     */

    public function syncStandardTpl()
    {
        $this->title = '获取微信视频号标准模板';

        $rs = $this->requestCall(STORE_WAYBILL_STANDARD_TEMPLATE, []);

        if ($rs['rsp'] == 'succ' && $rs['data']) {
            $resData      = json_decode($rs['data'], 1);
            $rs['data']   = [];
            $templateList = $resData['config'];
            foreach ($templateList as $code => $v) {
                $rs['data'][] = array(
                    'tpl_index'       => 'standard' . '-' . $v['single']['type'] . '_' . $code,
                    'cp_code'         => $code,
                    'out_template_id' => $v['single']['type'] . '_' . $code,
                    'template_name'   => $code . $v['single']['desc'] . '(微信视频号)',
                    'template_type'   => 'wxshipin_standard',
                    'template_data'   => json_encode([$code => $v]),
                    'template_width'  => $v['single']['width'],
                    'template_height' => $v['single']['height'],
                );
            }
        } else {
            $rs['data'] = [];
        }
        return $rs;
    }

    /**
     * syncUserTpl
     * @return mixed 返回值
     */
    public function syncUserTpl()
    {
        $this->title = '获取微信视频号用户模板';

        $rs = $this->requestCall(STORE_WAYBILL_USER_TEMPLATE, []);

        if ($rs['rsp'] == 'succ' && $rs['data']) {
            $resData    = json_decode($rs['data'], 1);
            $rs['data'] = [];
            $totalList  = $resData['total_template'];
            foreach ($totalList as $k => $tList) {
                $templateList = $tList['template_list'];
                foreach ($templateList as $k => $v) {

                    $v['delivery_id'] = $tList['delivery_id'];

                    $rs['data'][] = array(
                        'tpl_index'       => 'standard' . '-' . $v['template_id'],
                        'cp_code'         => $tList['delivery_id'],
                        'out_template_id' => $v['template_id'],
                        'template_name'   => $v['template_name'] . '(微信视频号)',
                        'template_type'   => 'wxshipin_user',
                        'template_data'   => json_encode($v),
                        'template_width'  => '',
                        'template_height' => '',
                        'is_default'      => $v['is_default'],
                    );
                }
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

    // 查询电子面单订购关系
    /**
     * 获取UserSubscribesTpl
     * @param mixed $value value
     * @return mixed 返回结果
     */
    public function getUserSubscribesTpl($value = '')
    {
        $this->title = '获取微信视频号已开通的快递服务';

        $rs = $this->requestCall(STORE_STANDARD_XHS_SEARCH, []);

        return $rs;
    }
}
