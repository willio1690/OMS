<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author
 * @describe 京东仓储入库单
 */
class erpapi_wms_matrix_jd_request_stockin extends erpapi_wms_request_stockin
{
    protected $_stockin_pagination = false;


    protected function _getNextObjType($ioType) {
        return 'search_in' . $ioType;
    }

    /**
     * _format_stockin_create_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function _format_stockin_create_params($sdf)
    {
        $params = parent::_format_stockin_create_params($sdf);
        $items = array('item'=>array());

        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                $material = kernel::single('material_basic_material')->getBasicMaterialBybn($v['bn']);
                $foreignsku = app::get('console')->model('foreign_sku')->dump(array('wms_id'=>$this->__channelObj->wms['channel_id'],'inner_sku'=>$v['bn']));
                // 获取外部商品sku
                $items['item'][] = array(
                    'item_code'       => $foreignsku['outer_sku'] ? $foreignsku['outer_sku'] : $v['bn'],
                    'item_name'       => $v['name'],
                    'item_quantity'   => (int)$v['num'],
                    'item_price'      => $v['price'] ? (float)$v['price'] : 0,// TODO: 商品价格
                    'item_line_num'   => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'      => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号)
                    'item_id'         => $v['bn'],// 商品ID
                    'is_gift'         => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'     => '',// TODO: 商品备注
                    'inventory_type'  => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'item_sale_price' => $material['retail_price'] ? (float)$material['retail_price'] : 0,
                );
            }
        }
        

        $params['items'] = json_encode($items);
        $params['department_no'] = app::get('wmsmgr')->getConf('department_no_'.$this->__channelObj->wms['channel_id']);
        $params['supplier_bn'] = $sdf['supplier_bn'];
        $params['warehouse_code'] = $this->get_warehouse_code($this->__channelObj->wms['channel_id'],$sdf['branch_bn']);
        return $params;
    }

    protected function _format_stockin_search_params($sdf)
    {
        $params = parent::_format_stockin_search_params($sdf);
        $params['order_type'] = $this->transfer_stockin_type($sdf['iso_type']);
        $params['warehouse_code'] = $this->get_warehouse_code($this->__channelObj->wms['channel_id'],$sdf['branch_bn']);
        return $params;
    }


    /**
     * 数据处理
     * @param $rs
     * @return array
     */
    protected function _deal_search_result($rs){

        $resultData = array();
        $data       = json_decode($rs['data'], true);

        if($data) {
            // 定义临时变量
            $items    = array();
            $outer_sku= array();
            $sku_list = array();
            $itemList = json_decode($data['item'],true);

            foreach($itemList as $val) {
                $outer_sku[] = $val['product_bn'];
                $sku_list[$val['product_bn']] = array(
                    'product_bn'    => $val['product_bn'],
                    'num'           => $val['normal_num'],
                    'normal_num'    => $val['normal_num'],
                    'defective_num' => $val['defective_num'],
                );
            }
            // 当物流类型为沧海物流时-查询erp内部货品编码
           // if($this->__channelObj->channel['addon']['wms_type'] == '1'){
                $res = app::get('console')->model('foreign_sku')->getList('inner_sku,outer_sku',array('outer_sku'=>$outer_sku));
                if($res){
                    foreach ($res as $val) {
                        if ($sku_list[$val['outer_sku']]) {
                            $sku_list[$val['outer_sku']]['product_bn'] = $val['inner_sku'];
                        }else{
                            $sku_list[$val['outer_sku']]['product_bn'] = '';
                        }
                    }
                }
           // }
            // 进行数据处理
            $items = array_values($sku_list);

            $resultData['type']         = $data['type'];
            $resultData['status']       = $data['status'];
            $resultData['remark']       = $data['remark'];
            $resultData['stockin_bn']   = $data['stock_bn'];
            $resultData['operate_time'] = $data['operate_time'];
            $resultData['item']         = json_encode($items);
        }
        $rs['data'] = $resultData;
        return $rs;
    }
}
