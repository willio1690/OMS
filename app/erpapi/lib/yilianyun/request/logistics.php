<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_yilianyun_request_logistics extends erpapi_yilianyun_request_abstract
{
    protected $error_list = [
        '999' => '云打印服务器内部错误，请联系管理员、技术处理',
        '9999' => '未知错误，请联系管理员、技术处理',
        '6002' => '打印设备权限异常错误，请联系管理员、技术处理',
        '6001' => '打印设备异常错误，如离线、未绑定',
        '3002' => '绑定打印设备异常，请检查小镇账号设置',
    ];

    


    /**
     * 打印
     * @param $sdf
     * @return mixed
     */
    public function printText($sdf)
    {

        $this->__original_bn = $sdf['outer_delivery_bn'];
        $iscancel = kernel::single('ome_interface_delivery')->iscancel($this->__original_bn);
        if ($iscancel) {
            return $this->error('发货单已取消，终止打印');
        }
        $cloudprint = $sdf['cloudprint'];

        $machine_code = $cloudprint['machine_code'];
       
        $appInfo = [
            'delivery_bn' => $sdf['outer_delivery_bn'],
            'org_bn' => $sdf['stores']['store_bn'],
            'app_key' => $cloudprint['app_key'],
            'app_secret' => $cloudprint['secret_key'],
        ];
      
      
        # 获取accessToken
        $accessToken = kernel::single('erpapi_router_request')->set($this->default_channel, true)->comm_oauth($appInfo);

        if ($accessToken['rsp'] == 'fail') {
           
            $appInfo['force_again'] = true;
            $accessToken = kernel::single('erpapi_router_request')->set($this->default_channel, true)->comm_oauth($appInfo);
            if($accessToken['rsp'] == 'fail'){
                $msg = empty($accessToken['msg']) ? '获取AccessToken失败' : $accessToken['msg'];
                return $this->error($msg);
            }
            
        }

        $title = '易联云打印电子面单（发货单：' . $sdf['outer_delivery_bn'] . '）';
        $callback = array();

        $sdf['machine_code'] = $machine_code;
        # accesstoken
        $access_token = $accessToken['res']['access_token'] ?? $accessToken['data']['access_token'];
        # 获取打印参数
        $params = $this->_format_print_image_params($access_token, $sdf, $error_msg);


        if (empty($params)) {
            return $this->error('数据本地验证失败,(' . $error_msg . ')');
        }
        # 合并打印机参数
        $params['temp'] = [
            'client_id' => $cloudprint['app_key'],
            'client_secret' => $cloudprint['secret_key'],
        ];
        # 打印面单
        $count = 0;
        do {
            $response = $this->__caller->call(YLY_PICTUREPRINT_INDEX, $params, $callback, $title, 30, $this->__original_bn);

            if (in_array($response['rsp'], array('succ', 'success'))) {
                break;
            } elseif ($response['rsp'] == 'fail' && !empty($response['response'])) {
                break;
            }
            $count++;
        } while ($count < 3);

        if ($response['rsp'] == 'fail') {
            $response['msg'] = $this->error_mapping($response['res'], $response['msg']);
        }
        
        return $response;
    }

    /**
     * 错误码映射关系
     * @param $code
     * @param $msg
     * @return void
     */
    private function error_mapping($code, $msg)
    {
        if (empty($code) || empty($this->error_list[$code])) {
            return $msg;
        }
        return $this->error_list[$code];
    }

    
    /**
     * 打印图片参数
     * @param $token
     * @param $params
     * @param $error_msg
     * @return mixed
     */
    protected function _format_print_image_params($token, $params, &$error_msg)
    {
        
        if (empty($params['img_url'])) {
            $error_msg = '打印图片的url不能为空';
            return false;
        }

        $result = [
            'access_token' => $token,
            'machine_code' => $params['machine_code'],
            'origin_id' => $params['outer_delivery_bn'] . '-' . date('YmdHis'),
            'picture_url' => $params['img_url'],
            'idempotence' => 1,
        ];
        return $result;
    }



}
