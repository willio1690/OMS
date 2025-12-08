<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#货品处理
class openapi_api_params_v1_pda_product extends openapi_api_params_abstract implements openapi_api_params_interface{
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
            'position'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'branch_bn' => array('type'=>'string','required'=>'true','name'=>'仓库编号','desc'=>'必填'),
                'items'=>array('type'=>'string','required'=>'true','name'=>'货品明细','desc'=>'必填'),
            ),
            'getList'=>array(
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'device_code'=>array('type'=>'string','required'=>'true','name'=>'机器码','desc'=>'设备唯一编码(必填项)'),
                'brand_name' => array('type'=>'string','name'=>'品牌名称'),
                'type_name'  => array('type'=>'string','name'=>'商品类型'),
                'goods_bn'   => array('type' => 'string','name' => '商品编号'),
                'product_bn'   => array('type' => 'string','name' => '货品货号'),
                'product_barcode'   => array('type' => 'string','name' => '货品条码'),
                'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量','desc'=>'最大1000'),
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
            'position'=>array('name'=>'货位整理','description'=>'创建货品和货位关联关系'),
            'getList'=>array('name'=>'商品查询接口','description'=>'pda获取商品查询')
        );
        return $desccription[$method];
    }
}