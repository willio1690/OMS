<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_delivery extends openapi_api_params_abstract implements openapi_api_params_interface{

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
                        'create_starttime'=>array('type'=>'date','required'=>'true','name'=>'创建时间(开始)','desc'=>'例如2012-12-08 18:50:30'),
                        'create_endtime'=>array('type'=>'date','required'=>'false','name'=>'创建时间(结束)','desc'=>'例如2012-12-08 18:50:30'),
                        'ship_starttime'=>array('type'=>'date','required'=>'false','name'=>'发货时间(开始)','desc'=>'例如2012-12-08 18:50:30'),
                        'ship_endtime'=>array('type'=>'date','required'=>'false','name'=>'发货时间(结束)','desc'=>'例如2012-12-08 18:50:30'),
                        'branch_name'=>array('type'=>'string','required'=>'false','name'=>'仓库'),
                        'shop_name'=>array('type'=>'string','required'=>'false','name'=>'来源店铺'),
                        'receive_area'=>array('type'=>'string','required'=>'false','name'=>'收货地区'),
                        'corp_name'=>array('type'=>'string','required'=>'false','name'=>'快递公司'),
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
                            'getList'=>array('name'=>'发货单接口','description'=>'获取指定条件下的发货单列表'));
        return $description[$method];
    }
}  