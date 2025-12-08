<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016/6/17
 * @describe 模板数据获取
 */
class erpapi_logistics_matrix_taobao_request_template extends erpapi_logistics_request_template {
    protected $waybillType = array(
        1 => '快递标准面单' ,2 => '快递三联面单', 3 => '快递便携式三联单', 4 => '快运标准面单', 5 => '快运三联面单'
    );

    /**
     * syncStandardTpl
     * @return mixed 返回值
     */

    public function syncStandardTpl() {
        $this->title = '获取菜鸟标准模板';
        $rs = $this->requestCall(STORE_STANDARD_TEMPLATE, array());
        if($rs['rsp'] == 'succ' && $rs['data']) {
            $data = json_decode($rs['data'], true);
            $standardResult = $data['datas']['standard_template_result'];
            $rs['data'] = array();
            if($standardResult) {
                foreach ($standardResult as $val) {
                    foreach ($val['standard_templates']['standard_template_do'] as $sVal) {
                        $rs['data'][] = array(
                            'tpl_index' => 'standard' . '-' . $sVal['standard_template_id'],
                            'cp_code' => $val['cp_code'],
                            'out_template_id' => $sVal['standard_template_id'],
                            'template_name' => $sVal['standard_template_name'] . '-' . $this->waybillType[$sVal['standard_waybill_type']] . '(菜鸟)',
                            'template_type' => 'cainiao_standard',
                            'template_data' => 'url:' . $sVal['standard_template_url']
                        );
                    }
                }
            }
        } else {
            $rs['data'] = array();
        }
        return $rs;
    }

    /**
     * syncUserTpl
     * @return mixed 返回值
     */
    public function syncUserTpl() {
        $this->title = '店铺(' . $this->__channelObj->channel['shop_id'] . ')' . $this->__channelObj->channel['channel_type'] . '获取用户模板';
        $rs = $this->requestCall(STORE_USER_TEMPLATE, array());
        if($rs['rsp'] == 'succ' && $rs['data']) {
            $data = json_decode($rs['data'], true);
            $userResult = $data['datas']['user_template_result'];
            $rs['data'] = array();
            if($userResult) {
                foreach ($userResult as $val) {
                    foreach ($val['user_std_templates']['user_template_do'] as $sVal) {
                        $tplSelect = array();
                        foreach ($sVal['keys']['key_result'] as $kVal) {
                            $tplSelect[] = str_replace('_data.', '', $kVal['key_name']);
                        }
                        $rs['data'][] = array(
                            'tpl_index' => 'user' . '-' . $sVal['user_std_template_id'],
                            'cp_code' => $val['cp_code'],
                            'out_template_id' => $sVal['user_std_template_id'],
                            'template_name' => $sVal['user_std_template_name'],
                            'template_type' => 'cainiao_user',
                            'template_select' => $tplSelect,
                            'template_data' => 'url:' . $sVal['user_std_template_url'],
                        );
                    }
                }
            }
        } else {
            $rs['data'] = array();
        }
        return $rs;
    }

    /**
     * 获取UserDefinedTpl
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getUserDefinedTpl($params) {
        $this->title = '获取模板(' . $params['template_id'] . ')自定义区域内容';
        $sdf = array(
            'template_id' => $params['template_id']
        );
        $rs = $this->requestCall(STORE_USER_DEFINE_TEMPLATE, $sdf);
        if($rs['rsp'] == 'succ' && $rs['data']) {
            $data = json_decode($rs['data'], true);
            $customResult = $data['datas']['custom_area_result'];
            $rs['data'] = array();
            if($customResult) {
                foreach ($customResult as $val) {
                    $tplSelect = array();
                    foreach ($val['keys']['key_result'] as $kVal) {
                        $tplSelect[] = str_replace('_data.', '', $kVal['key_name']);
                    }
                    $rs['data'] = array(
                        'custom_area_id' => $val['custom_area_id'],
                        'custom_area_url' => $val['custom_area_url'],
                        'template_select' => $tplSelect
                    );
                }
            }
        } else {
            $rs['data'] = array();
        }
        return $rs;
    }
}