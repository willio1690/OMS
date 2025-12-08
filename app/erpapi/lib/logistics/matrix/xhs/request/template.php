<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2021/7/15 14:42:57
 * @describe 模板数据获取
 */
class erpapi_logistics_matrix_xhs_request_template extends erpapi_logistics_request_template
{

    public function syncUserTpl()
    {
        if ($this->__channelObj->channel['ver'] == '2') {

            $this->title = '获取小红书用户模板';
            $params = [
                'type'        =>  'ark', // 类型，不填返回标准模板列表，ark-返回小红书商家配置的模板列表
                'billVersion' =>  '2',
            ];
            $rs = $this->requestCall(STORE_WAYBILL_STANDARD_TEMPLATE, $params);

            if ($rs['rsp'] == 'succ' && $rs['data']) {
                $resData      = json_decode($rs['data'], 1);
                $rs['data']   = [];
                $templateList = $resData['data']['templateList'];
                foreach ($templateList as $k => $v) {
                    $rs['data'][] = array(
                        'tpl_index'       => 'user' . '-' . $v['id'],
                        'cp_code'         => $v['cpCode'] == 'shunfeng' ? $v['brandCode'] : $v['cpCode'],
                        'out_template_id' => $v['id'],
                        'template_name'   => $v['templateName'] . '(小红书-新)',
                        'template_type'   => 'xhs_user',
                        'template_data'   => json_encode($v),
                        'template_width'  => explode('*', $v['templateType'])[0],
                        'template_height' => explode('*', $v['templateType'])[1],
                    );
                }
            } else {
                $rs['data'] = [];
            }
            return $rs;
        }
        return ['rsp' => 'succ'];
    }

    public function syncStandardTpl()
    {
        $this->title = '获取小红书标准模板';

        $params = [];
        if ($this->__channelObj->channel['ver'] == '2') {
            $params['billVersion'] = '2';
        }

        $rs = $this->requestCall(STORE_STANDARD_XHS_TEMPLATE, $params);

        if ($rs['rsp'] == 'succ' && $rs['data']) {
            $resData      = json_decode($rs['data'], 1);
            $rs['data']   = [];
            $templateList = $resData['data']['templateList'];
            foreach ($templateList as $k => $v) {
                $rs['data'][] = array(
                    'tpl_index'       => 'standard' . '-' . $v['id'],
                    'cp_code'         => $v['cpCode'] == 'shunfeng' ? $v['brandCode'] : $v['cpCode'],
                    'out_template_id' => $v['id'],
                    'template_name'   => $v['templateName'] . '(小红书' . ($params['billVersion']=='2' ? '-新' : '').')',
                    'template_type'   => 'xhs_standard',
                    'template_data'   => json_encode($v),
                    'template_width'  => explode('*', $v['templateType'])[0],
                    'template_height' => explode('*', $v['templateType'])[1],
                );
            }
        } else {
            $rs['data'] = [];
        }
        return $rs;
    }

    /*
    public function getUserDefinedTpl($params)
    {
        return ['rsp' => 'succ'];
    }

    // 查询电子面单订购关系
    public function getUserSubscribesTpl($value = '')
    {
        $this->title = '获取小红书已开通的快递服务';

        $rs = $this->requestCall(STORE_STANDARD_XHS_SEARCH, []);

        return $rs;
    }
    */
}
