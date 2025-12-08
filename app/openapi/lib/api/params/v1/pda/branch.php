<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#货品处理
class openapi_api_params_v1_pda_branch extends openapi_api_params_abstract implements openapi_api_params_interface{
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
                'pda_token'=>array('type'=>'string','required'=>'true','name'=>'pda_token','desc'=>'用户登录后的凭证(必填项)'),
                'branch_bn' => array('type'=>'string','name'=>'仓库编号'),
                'page_no'=>array('type'=>'number','name'=>'页码','desc'=>'默认1,第一页'),
                'page_size'=>array('type'=>'number','name'=>'每页数量','desc'=>'默认100'),
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
        $desccription = array('getList'=>array('name'=>'仓库查询','description'=>'pda获取仓库列表'));
        return $desccription[$method];
    }
}