<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_appropriation extends openapi_api_params_abstract implements openapi_api_params_interface{

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
                        'appropriation_no'=>array('type'=>'string','require'=>'false','name'=>'调拨单编号'),
                        'start_time'=>array('type'=>'date','required'=>'true','name'=>'开始时间','desc'=>'例如2012-12-08 18:50:30'),
                        'end_time'=>array('type'=>'date','required'=>'true','name'=>'结束时间','desc'=>'例如2012-12-08 18:50:30'),
                        'page_no'=>array('type'=>'number','require'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
                        'page_size'=>array('type'=>'number','require'=>'false','name'=>'每页最大数量','desc'=>'最大100'),
                ),
                'add'=>array(
                    'appropriation_type'=>array('type'=>'string','require'=>'true','name'=>'调拨类型','desc'=>'必填   1：直接调拨／2：出入库调拨'),
                    'from_branch'=>array('type'=>'string','require'=>'true','name'=>'调出仓库编号','desc'=>'必填'),
                    'to_branch'=>array('type'=>'string','require'=>'true','name'=>'调入仓库编号','desc'=>'必填'),
                    'logi_name'=>array('type'=>'string','require'=>'false','name'=>'物流公司名称'),
                    'operator'=>array('type'=>'string','require'=>'false','name'=>'经办人'),
                    'is_check'=>array('type'=>'string','require'=>'false','name'=>'是否自动审核出库单','desc'=>'是/否 默认为否，出入库调拨类型时可用'),
                    'memo'=>array('type'=>'string','require'=>'false','name'=>'备注'),
                    'items'=>array('type'=>'string','required'=>'true','name'=>'明细','desc'=>'必填   格式为：bn:test1,nums:1;bn:test2,nums:2'),                 
                )
                    
        );
    
        return $params[$method];
    }
    
    /**
     * description
     * @param mixed $method method
     * @return mixed 返回值
     */
    public function description($method){
        $description = array(
                            'getList'=>array('name'=>'调拨单接口','description'=>'获取指定条件下的调拨单信息和出入库明细列表'),
                            'add'=>array('name'=>'调拨单接口','description'=>'新建调拨单'));
        return $description[$method];
    }
}  