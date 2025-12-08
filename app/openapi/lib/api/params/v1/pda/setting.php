<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_pda_setting extends openapi_api_params_abstract{

    /**
     * 检查Params
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回验证结果
     */
    public function checkParams($method,$params,&$sub_msg){
        if(parent::checkParams($method,$params,$sub_msg)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取AppParams
     * @param mixed $method method
     * @return mixed 返回结果
     */
    public function getAppParams($method){
        $params = array(
            'check'=>array(
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'检查机器码','desc'=>'设备唯一编码(必填项)'),
                'check_secret'=>array('type'=>'string','required'=>'true','name'=>'检查秘钥','desc'=>'必填'),
            ),
        );
        return $params[$method];
    }

    /**
     * passwordMD5
     * @param mixed $key key
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function passwordMD5($key, &$params){
        $params[$key] = md5($params[$key]);
    }

    /**
     * description
     * @param mixed $method method
     * @return mixed 返回值
     */
    public function description($method){
        $desccription = array(
            'check'=>array('name'=>'连接测试接口 ','description'=>'测试用户配置是否正确'),
        );
        return $desccription[$method];
    }
}