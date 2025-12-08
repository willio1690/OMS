<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_warehouse extends openapi_api_params_abstract implements openapi_api_params_interface{
    
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
                'name'=>array('type'=>'string','required'=>'true','name'=>'入库单名称','desc'=>'必填'),
                'vendor'=>array('type'=>'string','required'=>'false','name'=>'供应商'),
                'extrabranch_name'=>array('type'=>'string','required'=>'true','name'=>'来源地仓库名称','desc'=>'必填'),
                'branch_bn'=>array('type'=>'string','required'=>'true','name'=>'仓库编号','desc'=>'必填'),
                'delivery_cost'=>array('type'=>'number','required'=>'false','name'=>'出入库费用'),
                'memo'=>array('type'=>'string','required'=>'false','name'=>'备注'),
                'operator'=>array('type'=>'string','required'=>'false','name'=>'经办人'),
                't_type'=>array('type'=>'string','required'=>'true','name'=>'出入库类型','desc'=>'必填
                                                                                                DC – 转仓入库'),
                'original_iso_bn'=>array('type'=>'string','required'=>'false','name'=>'外部原始单号'),
                'items'=>array('type'=>'string','required'=>'true','name'=>'明细','desc'=>'必填   格式为：bn:test1,name:测试1,price:10,nums:1;bn:test2,name:测试2,price:20,nums:2'),
            ),
            'getList'=>array(
                'start_time'=>array('type'=>'date','required'=>'true','name'=>'开始时间(完成状态)','desc'=>'例如2012-12-08 18:50:30'),
                'end_time'=>array('type'=>'date','required'=>'true','name'=>'结束时间(完成状态)','desc'=>'例如2012-12-08 18:50:30'),
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
            'add'=>array('name'=>'创建转仓单','description'=>'创建一个直接转仓入库的指令'),
            'getList'=>array('name'=>'查询转仓单明细','description'=>'根据转仓单创建时间来查询该时间段内的转仓明细')
        );
        return $desccription[$method];
    }
    
}