<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_stockdump extends openapi_api_params_abstract implements openapi_api_params_interface{

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
                        'modified_start'    =>array('type'=>'date','required'=>'false','name'=>'更新时间开始','desc'=>'例如2012-12-08 18:50:30'),
                        'modified_end'      =>array('type'=>'date','required'=>'false','name'=>'更新时间结束','desc'=>'例如2012-12-08 18:50:30'),
                        'confirm_time_start'=>array('type'=>'date','required'=>'false','name'=>'确认时间开始','desc'=>'例如2012-12-08 18:50:30'),
                        'confirm_time_end'  =>array('type'=>'date','required'=>'false','name'=>'确认时间结束','desc'=>'例如2012-12-08 18:50:30'),
                        'stockdump_bn'      =>array('type'=>'string','required'=>'false','name'=>'转储单编号'),
                        'in_status'         =>array('type'=>'string','required'=>'false','name'=>'入库状态'),
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
        $description = array(
                            'getList'=>array('name'=>'转储单接口','description'=>'获取指定条件下的转储单列表'));
        return $description[$method];
    }
}  