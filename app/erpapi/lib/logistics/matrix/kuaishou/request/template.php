<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author Joe 2021/12/13 14:42:57
 * @describe 模板数据获取
 */
class erpapi_logistics_matrix_kuaishou_request_template extends erpapi_logistics_request_template
{
    protected $standardTemplateUrl = [];

    /**
     * syncStandardTpl
     * @return mixed 返回值
     */

    public function syncStandardTpl()
    {
        $this->title = '获取快手标准模板';
        $this->primaryBn = 'kuaishou';
        $rs = $this->requestCall(STORE_STANDARD_DY_TEMPLATE, []);

        if($rs['rsp'] == 'succ' && $rs['data']) {
            $standardResult = json_decode($rs['data'], true);
            $rs['data'] = array();
            if($standardResult) {
                foreach ($standardResult as $val) {
                        $template_data = ['template_code'=>$val['templateCode'], 'template_url'=>$val['templateUrl']];
                        $this->standardTemplateUrl[$val['templateCode']] = $val['templateUrl'];
                        $rs['data'][] = array(
                            'tpl_index' => 'standard' . '-' . $val['templateCode'],
                            'cp_code' => $val['expressCompanyCode'],
                            'out_template_id' => $val['templateCode'],
                            'template_name' => $val['templateName'] . '(快手)',
                            'template_type' => 'kuaishou_standard',
                            'template_data' => json_encode($template_data)
                        );

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
        $this->title = '获取快手用户模板';
        $this->primaryBn = 'kuaishou';
        $rs = $this->requestCall(STORE_KS_USER_TEMPLATE, ['type'=>'3']);

        if($rs['rsp'] == 'succ' && $rs['data']) {
            $userResult = json_decode($rs['data'], true);
            $rs['data'] = array();
            if($userResult) {
                foreach ($userResult as $val) {
                        $template_data = ['template_code'=>$val['standardTemplateCode'], 'custom_template_url'=>$val['customTemplateUrl']];
                        $template_data['template_url'] = $this->standardTemplateUrl[$val['standardTemplateCode']];
                        $rs['data'][] = array(
                            'tpl_index' => 'user' . '-' . $val['customTemplateCode'],
                            'cp_code' => $val['expressCompanyCode'],
                            'out_template_id' => $val['customTemplateCode'],
                            'template_name' => $val['customTemplateName'] . '(快手)',
                            'template_type' => 'kuaishou_user',
                            'template_data' => json_encode($template_data),
                            'template_select' => $val['placeholderKeys']
                        );

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
        return ['rsp'=>'succ'];
    }

}