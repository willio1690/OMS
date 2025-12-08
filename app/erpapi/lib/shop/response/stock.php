<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_response_stock extends erpapi_shop_response_abstract {


    /**
     * 获取
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get($params){
        $this->__apilog['title'] = '门店实时查库存';
        $this->__apilog['original_bn'] = $params['warehouse'];
        $shop_id = $this->__channelObj->channel['shop_id'];
        $offId = app::get('ome')->model('shop_onoffline')->getList('off_id', ['on_id'=>$shop_id]);
        if(empty($offId)) {
            $this->__apilog['result']['msg'] = '该店铺下没有门店';
            return false;
        }
        $offline = app::get('ome')->model('shop')->db_dump([
            'shop_id'=>array_column($offId, 'off_id'), 
            "s_type"=>"2",
            'shop_bn'=>$params['warehouse']
        ], 'shop_bn');
        if(empty($offline['shop_bn'])) {
            $this->__apilog['result']['msg'] = '该店铺下没有编号为'.$params['warehouse'].'的门店';
            return false;
        }
        $branch = app::get('ome')->model('branch')->db_dump(['branch_bn' => $params['warehouse'], 'b_type' => 2,], 'branch_id');
        if(empty($branch['branch_id'])) {
            $this->__apilog['result']['msg'] = '该店铺下没有编号为'.$params['warehouse'].'的门店仓';
            return false;
        }
        $sdf = [
            'branch_id' => $branch['branch_id'],
            'shop' => $this->__channelObj->channel,
            'bn' => json_decode($params['bn'], 1)
        ];
        $sdf['shop_id'] = $this->__channelObj->channel['shop_id'];

        return $sdf;
    }
    
    /**
     * [翱象系统]实仓库存查询接口
     * method：ome.inventory.aoxiang.query
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_query($params)
    {
        $this->__apilog['title'] = '翱象实仓库存查询';
        $this->__apilog['original_bn'] = $params['erpWarehouseCode'];
        
        //shop
        $shopInfo = array(
            'shop_id' => $this->__channelObj->channel['shop_id'],
            'shop_bn' => $this->__channelObj->channel['shop_bn'],
            'name' => $this->__channelObj->channel['name'],
            'shop_type' => $this->__channelObj->channel['shop_type'],
        );
        
        //params
        $sdf = array(
            'shopInfo' => $shopInfo,
            'branch_bn' => $params['erpWarehouseCode'],
            'product_bn' => $params['scItemId'],
        );
        
        return $sdf;
    }

    /**
     * occupy
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function occupy($sdf){

        $this->__apilog['result']['data'] = array('tid'=>$sdf['orderNo']);
        $this->__apilog['original_bn']    = $sdf['orderNo'];
        $this->__apilog['title']          = '销售订单预占['.$sdf['orderNo'].']';

        $shop_id = $this->__channelObj->channel['shop_id'];
        $data = array(
            'order_bn'      =>  $sdf['orderNo'],
            'shop_id'       =>  $shop_id,
            'status'        =>  $sdf['status'],
            'type'          =>  $sdf['type'],
        );

        $orderLines = json_decode($sdf['orderLines'],true);

        $occupyLib = kernel::single('console_stock_occupy');

        $items = array();

        foreach($orderLines as $v){
            $warehouseno = $v['warehouseNo'];
            $branchs = $occupyLib->getBranchId($warehouseno);
            $items[] = array(
                'itemcode'      =>  $v['itemCode'],
                'itemname'      =>  $v['itemName'],
                'num'           =>  $v['qty'],
                'warehouseno'   =>  $v['warehouseNo'],
                'branch_id'     =>  $branchs['branch_id'],
                'shop_id'       =>  $shop_id,
            );
        }
        
        $data['items'] = $items;
        
        return $data;
    }
}