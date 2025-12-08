<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author
 * @describe 京东仓储出库单
 */
class erpapi_wms_matrix_jd_request_stockout extends erpapi_wms_request_stockout
{
    protected $_stockout_pagination = false;
    protected $outSysProductField = 'item_code';


    protected function _getNextObjType($ioType) {
        return 'search_out' . $ioType;
    }

    /**
     * _format_stockout_create_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function _format_stockout_create_params($sdf)
    {
        $params = parent::_format_stockout_create_params($sdf);
        $params['isv_source'] = 'shopex';
        $params['order_source'] = 'OTHER';
        $params['shop_code'] = 'noshopcode';
        $params['department_no'] = app::get('wmsmgr')->getConf('department_no_'.$this->__channelObj->wms['channel_id']);
        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ($sdf['items'] as $k => $v){
                $foreignsku = app::get('console')->model('foreign_sku')->dump(array('wms_id'=>$this->__channelObj->wms['channel_id'],'inner_sku'=>$v['bn']));
                $items['item'][] = array(
                    'item_code'      => $foreignsku['outer_sku'] ? $foreignsku['outer_sku'] : $v['bn'],
                    'item_name'      => $v['name'],
                    'item_quantity'  => (int)$v['num'],
                    'item_price'     => $v['price'] ? (float)$v['price'] : 0,// TODO: 商品价格
                    'item_line_num'  => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号)
                    'item_id'        => $foreignsku['outer_sku'] ? $foreignsku['outer_sku'] : $v['bn'],// 商品ID
                    'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '',// TODO: 商品备注
                    'inventory_type' => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                );
            }
        }

        $params['items']            = json_encode($items);
        if ($sdf['logi_code']) {
            $corp = app::get('ome')->model('dly_corp')->dump($sdf['corp_id'],'type,corp_id');
            $params['logistics_code'] = $this->get_wmslogi_code($this->__channelObj->wms['channel_id'],$sdf['logi_code']);

        } else {
            $params['logistics_code'] = 'other';
        }
        $params['supplier_bn'] = $sdf['supplier_id'] ? $sdf['supplier_bn'] : 'nosuppliercode';
        $params['warehouse_code'] = $this->get_warehouse_code($this->__channelObj->wms['channel_id'],$sdf['branch_bn']);
        return $params;
    }

    /**
     * @param $rs
     * @return array
     * currentStatus :  10014, 已下发库房
                        10010, 订单初始化
                        10015, 任务已分配
                        10016, 拣货下架
                        10017, 复核
                        10018, 货品已打包
                        10019, 交接发货
                        10028, 取消成功
     */
    public function _deal_search_result($rs){

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
                    'product_bn'    => $val['product_bn'],  // 事业部商品编号
                    'normal_num'    => $val['normal_num'],  // 实际数量
                    'num'           => $val['normal_num'],  // 实际数量
                    'defective_num' => $val['defective_num'], // 残损数量
                );
            }
            // 当物流类型为沧海物流时-查询erp内部货品编码
            //if($this->__channelObj->channel['addon']['wms_type'] == '1'){
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
            //}
            // 进行数据处理
            $items = array_values($sku_list);

            $resultData['type']            = $data['type'];            // 类型
            $resultData['status']          = $data['status'];          // 状态 CREATING、CANCELING、CLOSE、FINISH
            $resultData['remark']          = $data['remark'];          // 失败消息
            $resultData['stockout_bn']     = $data['stock_bn'];        // 采购单号-ERP
            $resultData['out_delivery_bn'] = $data['eclp_bn'];         // 采购单号-平台
            $resultData['operate_time']    = $data['operate_time'];    // 采购单生成时间
            $resultData['logi_no']         = $data['logi_no'];         // 物流号
            $resultData['logistics']       = $data['logistics'];       // 承运商编号
            $resultData['warehouse']       = $data['warehouseNo'];     // 仓库编码
            $resultData['item']            = json_encode($items);      // 商品信息
        }
        $rs['data'] = $resultData;
        return $rs;
    }

}
