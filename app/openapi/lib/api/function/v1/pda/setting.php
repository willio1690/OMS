<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_function_v1_pda_setting extends openapi_api_function_v1_pda_abstract {
    #检查用户填写的配置
    /**
     * 检查
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回验证结果
     */
    public function check($params,&$code,&$sub_msg){
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $check_secret = $params['check_secret'];
        $check_flag = $params['check_flag'];
        $rs = app::get('openapi')->model('setting')->getList('*',array('code'=>$check_flag,'interfacekey'=>$check_secret));
        if(empty($rs)){
            $result['message'] = 'fail';
        }else{
            $result['message'] = 'success';
        }
        return $result;
    }
}