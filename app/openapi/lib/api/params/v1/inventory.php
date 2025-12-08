<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_api_params_v1_inventory extends openapi_api_params_abstract implements openapi_api_params_interface
{
    
    /**
     * 检查Params
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回验证结果
     */
    public function checkParams($method, $params, &$sub_msg)
    {
        if (parent::checkParams($method, $params, $sub_msg)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 获取AppParams
     * @param mixed $method method
     * @return mixed 返回结果
     */
    public function getAppParams($method)
    {
        $params = array(
            'getList' => array(
                'start_time'   => array('type' => 'date', 'required' => 'true', 'name'     => '盘点开始时间,例如2012-12-08 18:50:30' ),
                'end_time'     => array('type' => 'date', 'required' => 'true', 'name' => '盘点结束时间(同上)'),
                'page_no'      => array('type' => 'number', 'required' => 'false', 'name' => '页码，默认1 第一页'),
                'page_size'    => array('type' => 'number', 'required' => 'false', 'name' => '每页数量，最大100'),
                'inventory_bn' => array('type' => 'string', 'name' => '盘点单号'),
                'branch_bn'    => array('type' => 'string', 'name' => '仓库编码'),
            ),
            'getApplyDetail' => array(
                'page_no'      => array('type' => 'number', 'required' => 'false', 'name' => '页码，默认1 第一页'),
                'page_size'    => array('type' => 'number', 'required' => 'false', 'name' => '每页数量，最大100'),
                'inventory_apply_bn' => array('type' => 'string', 'name' => '盘点申请单号'),
                'status'        => array('type' => 'string', 'name' => '盘点申请单状态'),
            ),
            'getApplyList' => array(
                'page_no'      => array('type' => 'number', 'required' => 'false', 'name' => '页码，默认1 第一页'),
                'page_size'    => array('type' => 'number', 'required' => 'false', 'name' => '每页数量，最大100'),
                'inventory_apply_bn'    => array('type' => 'string','required' => 'false', 'name' => '盘点申请单号'),
            ),
            'getShopSkuList' => array(
                'start_time'   => array('type' => 'date', 'required' => 'true', 'name'     => '单据更新时间开始时间,例如2012-12-08 18:50:30' ),
                'end_time'     => array('type' => 'date', 'required' => 'true', 'name' => '单据更新时间结束时间(同上)'),
                'page_no'      => array('type' => 'number', 'required' => 'false', 'name' => '页码，默认1 第一页'),
                'page_size'    => array('type' => 'number', 'required' => 'false', 'name' => '每页数量，最大100'),
                'shop_product_bn'    => array('type' => 'number', 'required' => 'false', 'name' => '店铺商品编码'),
            ),
            'getShopStockList' => array(
                'start_time'   => array('type' => 'date', 'required' => 'true', 'name'     => '单据更新时间开始时间,例如2012-12-08 18:50:30' ),
                'end_time'     => array('type' => 'date', 'required' => 'true', 'name' => '单据更新时间结束时间(同上)'),
                'page_no'      => array('type' => 'number', 'required' => 'false', 'name' => '页码，默认1 第一页'),
                'page_size'    => array('type' => 'number', 'required' => 'false', 'name' => '每页数量，最大100'),
            ),
        );
        
        return $params[$method];
    }
    
    /**
     * description
     * @param mixed $method method
     * @return mixed 返回值
     */
    public function description($method)
    {
        $desccription = array(
            'getList' => array('name' => '查询盘点单列表(根据盘点时间)', 'description' => '批量获取一个时间段内的盘点单信息数据'),
            'getApplyDetail' => array('name' => '查询盘点单申请单详情', 'description' => '查询单个盘点单申请单详情带明细'),
            'getApplyList' => array('name' => '查询盘点单申请单列表', 'description' => '查询盘点单申请单列表不带明细'),
            'getShopSkuList' => array('name' => '查询店铺商品列表', 'description' => '查询店铺商品列表'),
            'getShopStockList' => array('name' => '查询店铺商品回写库存列表', 'description' => '查询店铺商品回写库存列表'),
        );
        return $desccription[$method];
    }
}
