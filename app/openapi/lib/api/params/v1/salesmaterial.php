<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_salesmaterial extends openapi_api_params_abstract implements openapi_api_params_interface{

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
                'start_time'=>array('type'=>'string','require'=>'false','name'=>'开始时间','desc'=>'例如2012-12-08 18:50:30'),
                'end_time'=>array('type'=>'string','require'=>'false','name'=>'结束时间','desc'=>'例如2012-12-08 18:50:30'),
                'sales_material_bn'   => array('type' => 'string','name' => '销售物料编码'),
                'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量','desc'=>'最大100'),
            ),
            'add'=>array(
                'sales_material_name'  => array('type'=>'string','required'=>'true','name'=>'销售物料名称','desc'=>'必填'),
                'sales_material_bn'   => array('type' => 'string','required'=>'true','name' => '销售物料编号','desc'=>'必填'),
                'sales_material_type'   => array('type' => 'number','required'=>'true','name' => '销售物料类型','desc'=>'必填，1代表普通类型、2代表组合类型、3代表赠品类型、4代表福袋类型、5代表多选一类型'),
                'unit'   => array('type' => 'string','name' => '计量单位'),
                'retail_price'   => array('type' => 'string','name' => '销售价'),
                //'cost'   => array('type' => 'string','name' => '成本价'),
                'bind_info'   => array('type' => 'string','name'=>'促销类关联物料信息','desc' => '单物料关联，例：test001；多物料关联，例子：test001:1x40|test002:2x60'),
                'luckybag_bind_info' => array('type' => 'string','name'=>'福袋类关联物料信息','desc' => '请确认销售物料类型需填写4、例子：组合A:a1|a2|a3|a4-2|2|50.00#组合B:b1|b2-1|1|100.00（基础物料编码|基础物料编码-选sku数|件数|单价、#组分割符）'),
                'pickone_bind_info' => array('type' => 'string','name'=>'多选一类关联物料信息','desc' => '请确认销售物料类型需填写5、例子：随机#a1:0|a2:0 或者 排序#a1:1|a2:2（选择方式名称#基础物料编码1:排序值|物料编码2:排序值）'),
            ),
            'edit'=>array(
                'sales_material_name'  => array('type'=>'string','required'=>'true','name'=>'销售物料名称','desc'=>'必填'),
                'sales_material_bn'   => array('type' => 'string','required'=>'true','name' => '销售物料编号','desc'=>'必填'),
                'sales_material_type'   => array('type' => 'number','required'=>'true','name' => '销售物料类型','desc'=>'必填，1代表普通类型、2代表促销类型、3代表赠品类型、4代表福袋类型、5代表多选一类型'),
                'unit'   => array('type' => 'string','name' => '计量单位'),
                'retail_price'   => array('type' => 'string','name' => '销售价'),
                //'cost'   => array('type' => 'string','name' => '成本价'),
                'bind_info'   => array('type' => 'string','name'=>'促销类关联物料信息','desc' => '单物料关联，例：test001；多物料关联，例子：test001:1x40|test002:2x60'),
                'luckybag_bind_info' => array('type' => 'string','name'=>'福袋类关联物料信息','desc' => '请确认销售物料类型需填写4、例子：组合A:a1|a2|a3|a4-2|2|50.00#组合B:b1|b2-1|1|100.00（基础物料编码|基础物料编码-选sku数|件数|单价、#组分割符）'),
                'pickone_bind_info' => array('type' => 'string','name'=>'多选一类关联物料信息','desc' => '请确认销售物料类型需填写5、例子：随机#a1:0|a2:0 或者 排序#a1:1|a2:2（选择方式名称#基础物料编码1:排序值|物料编码2:排序值）'),
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
            'getList'=>array(
                'name'        =>'销售物料查询接口',
                'description' =>'实时批量获取特定条件下的销售物料',
            ),
            'add'=>array(
                'name'        =>'销售物料添加接口',
                'description' =>'添加销售物料',
            ),
            'edit'=>array(
                'name'        =>'销售物料修改接口',
                'description' =>'修改销售物料',
            ),
        );
        return $desccription[$method];
    }
}