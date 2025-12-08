<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_po extends openapi_api_params_abstract implements openapi_api_params_interface{

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
                'name'=>array('type'=>'string','required'=>'true','name'=>'采购单名称','desc'=>'必填'),
                'vendor'=>array('type'=>'string','required'=>'false','name'=>'供应商','desc'=>''),
                'po_type'=>array('type'=>'string','required'=>'true','name'=>'采购方式','desc'=>'必填  1 现购    2 赊购'),
                'branch_bn'=>array('type'=>'string','required'=>'true','name'=>'到货仓库编号','desc'=>'必填'),
                'delivery_cost'=>array('type'=>'number','required'=>'false','name'=>'物流费用','desc'=>'必填'),
                'deposit_balance'=>array('type'=>'number','required'=>'false','name'=>'预付款金额'),
                'arrive_time'=>array('type'=>'number','required'=>'false','name'=>'预计到货天数','desc'=>'必填'),
                'operator'=>array('type'=>'string','required'=>'false','name'=>'采购员'),
                'po_bn'=>array('type'=>'string','required'=>'false','name'=>'采购单号'),
                'memo'=>array('type'=>'string','required'=>'false','name'=>'备注'),
                'items'=>array('type'=>'string','required'=>'true','name'=>'明细','desc'=>'必填   格式为：bn:test1,name:测试1,price:10,nums:1;bn:test2,name:测试2,price:20,nums:2'),
            ),
            'getList'=>array(
                'po_bn'=>array('type'=>'string','require'=>'false','name'=>'采购单编号'),
                'supplier'=>array('type'=>'string','require'=>'false','name'=>'供应商名称'),
                'start_time'=>array('type'=>'string','require'=>'false','name'=>'开始时间','desc'=>'例如2012-12-08 18:50:30'),
                'end_time'=>array('type'=>'string','require'=>'false','name'=>'结束时间','desc'=>'例如2012-12-08 18:50:30'),
                'last_modify_start_time'=>array('type'=>'string','require'=>'false','name'=>'最后更新开始时间','desc'=>'例如2012-12-08 18:50:30'),
                'last_modify_end_time'=>array('type'=>'string','require'=>'false','name'=>'最后更新结束时间','desc'=>'例如2012-12-08 18:50:30'),
                'eo_status'=>array('type'=>'string','require'=>'false','name'=>'入库状态'),
                'check_status'=>array('type'=>'string','require'=>'false','name'=>'审核状态'),
                'po_status'=>array('type'=>'string','require'=>'false','name'=>'采购状态'),
                'statement_status'=>array('type'=>'string','require'=>'false','name'=>'结算状态'),
                'page_no'=>array('type'=>'number','require'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'=>array('type'=>'number','require'=>'false','name'=>'每页最大数量','desc'=>'最大100'),
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
        $desccription = array('add'=>array('name'=>'新建采购单','description'=>'创建一个采购指令'),
                              'getList'=>array('name'=>'返回采购单信息','description'=>'创建一个采购指令'));
        return $desccription[$method];
    }
}