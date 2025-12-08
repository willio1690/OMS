<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_supplier extends openapi_api_params_abstract implements openapi_api_params_interface{
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
            'add'=>array(
                'supplier_name' => array('type'=>'string', 'required'=>'true', 'name'=>'供应商名称'),
                'supplier_bn' => array('type'=>'string', 'required'=>'true', 'name'=>'供应商编码'),
            ),
            'edit'=>array(
                'supplier_name' => array('type'=>'string', 'required'=>'true', 'name'=>'供应商名称'),
                'supplier_bn' => array('type'=>'string', 'required'=>'true', 'name'=>'供应商编码'),
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
            'add'=>array('name'=>'新建供应商','description'=>'新建供应商'),
            'edit'=>array('name'=>'编辑供应商','description'=>'编辑供应商'),
        );
        return $desccription[$method];
    }
}