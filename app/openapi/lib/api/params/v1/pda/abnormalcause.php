<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @describe pda 获取异常原因
 * @author pangxp
 */
class openapi_api_params_v1_pda_abnormalcause extends openapi_api_params_abstract implements openapi_api_params_interface{

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
            'getList'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量','desc'=>'最大100'),
            ),
            
        );

        return $params[$method];
    }
    
    /**
     * description
     * @param mixed $method method
     * @return mixed 返回值
     */
    public function description($method){
        $desccription = array(
                'getList'=>array('name'=>'异常原因列表','description'=>'异常原因列表'),
        );
        return $desccription[$method];
    }
}