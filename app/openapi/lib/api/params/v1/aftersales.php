<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_aftersales extends openapi_api_params_abstract implements openapi_api_params_interface{

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
            	'start_time'=>array('type'=>'date','required'=>'false','name'=>'开始时间','desc'=>'(售后单创建时间),例如2012-12-08 18:50:30'),
            	'end_time'=>array('type'=>'date','required'=>'false','name'=>'结束时间','desc'=>'(售后单创建时间),例如2012-12-08 18:50:30'),
                'modified_start'=>array('type'=>'date','name'=>'更新开始时间','desc'=>'(售后单更新时间),例如2012-12-08 18:50:30'),
                'modified_end'=>array('type'=>'date','name'=>'更新结束时间','desc'=>'(售后单更新时间),例如2012-12-08 18:50:30'),
                'page_no'    => array('type' => 'number', 'required' => 'false', 'name' => '页码，默认1 第一页'),
                'page_size'  => array('type' => 'number', 'required' => 'false', 'name' => '每页数量，最大1000'),
            ),
            'getGxList' => array(
                'start_time'  => array('type' => 'date', 'required' => 'true', 'name' => '查询修改开始时间', 'desc' => '格式：2012-12-08 18:50:30'),
                'end_time'    => array('type' => 'date', 'required' => 'true', 'name' => '查询修改结束时间', 'desc' => '格式：2012-12-08 18:50:30'),
                'page_no'     => array('type' => 'number', 'required' => 'false', 'name' => '页码', 'desc' => '默认1,第一页'),
                'page_size'   => array('type' => 'number', 'required' => 'false', 'name' => '每页数量', 'desc' => '最大1000'),
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
        $desccription = array('getList'=>array('name'=>'查询售后单信息(根据售后单创建时间)','description'=>'批量获取一个时间段内的售后单信息数据'));
        return $desccription[$method];
    }
}