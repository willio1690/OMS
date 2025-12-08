<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_stock extends openapi_api_params_abstract implements openapi_api_params_interface{

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
				'getAll'=>array(
                        'product_bn' => array('type'=>'string', 'required'=>'false','name'=>'基础物料编码'),
						'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
						'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量','desc'=>'最大100'),
				),
				'getDetailList'=>array(
						'product_bn'=>array('type'=>'string','required'=>'false','name'=>'基础物料编码'),
						'branch_bn'=>array('type'=>'string','required'=>'false','name'=>'仓库编码'),
						'page_no'=>array('type'=>'number','required'=>'false','name'=>'页码','desc'=>'默认1,第一页'),
						'page_size'=>array('type'=>'number','required'=>'false','name'=>'每页数量','desc'=>'最大100'),
						'modified_start' => array('type' => 'date', 'required' => 'false', 'name' => '开始时间(更新时间),例如2012-12-08 18:50:30'),
						'modified_end'   => array('type' => 'date', 'required' => 'false', 'name' => '结束时间(同上)'),
				),
			    'getBarcodeStock'=>array(
		                'barcodes'=>array('type'=>'string', 'required'=>'false', 'name'=>'条形码', 'desc'=>'多个条形码用,英文逗号分隔'),
		        ),
		        'getBnStock'=>array(
		                'product_bns'=>array('type'=>'string', 'required'=>'false', 'name'=>'货号', 'desc'=>'多个货号用,英文逗号分隔'),
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
                            'getAll'=>array('name'=>'库存接口','description'=>'返回总数量带仓库明细'),
                            'getDetailList'=>array('name'=>'仓库数量接口','description'=>'返回仓库数量'),
                            'getList'=>array('name'=>'货品仓库接口','description'),
                            'getBarcodeStock' => array('name'=>'获取货品对应的库存接口', 'description'=>'返回货品的总库存、冻结库存'),
                            'getBnStock' => array('name'=>'获取货品对应的库存接口', 'description'=>'返回货品的总库存、冻结库存'),
			);
        return $description[$method];
	}
}