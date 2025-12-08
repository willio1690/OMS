<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_brand extends openapi_api_params_abstract implements openapi_api_params_interface{

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

                'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码，默认1 第一页'),
                'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量，最大100'),
                'brand_code' => array('type'=>'string', 'required'=>'false', 'name'=>'品牌编号', 'desc'=>'品牌编号,多个编号用逗号分隔'),
            ),
            // 'decrypt'=>array(
            //     'order_bn'=>array('type'=>'string','name'=>'订单编号','desc'=>''),
            //     'shop_bn'=>array('type'=>'string','name'=>'店铺编码','desc'=>''),
            // ),
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
            'getList'=>array('name'=>'查询品牌信息','description'=>'批量获取品牌信息数据'),
            // 'decrypt'=>array('name'=>'敏感数据解密', 'description'=>'敏感数据解密'),
        );
        return $desccription[$method];
    }
}