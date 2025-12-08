<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc
 * @author: sunjing@shopex.cn
 * @since:
 */
class erpapi_shop_matrix_pos_response_aftersalev2 extends erpapi_shop_response_aftersalev2
{

    protected function _formatAddParams($params)
    {

        $sdf                = parent::_formatAddParams($params);
        $sdf['refund_list'] = $params['refund_list'] ? json_decode($params['refund_list'], true) : '';

        #custom 新增强制自动审核标记
        $sdf['force_approve'] = true;

        #custom 校验退回仓库,补充门店指定退货仓库
        if ($params['shop_type'] != 'pekon' && !$params['warehouse_code']) {
            $this->__apilog['result']['msg'] = '仓库编码必填';
            return false;
        }
        if ($params['warehouse_code']) {
            $filter = [
                'branch_bn' => $params['warehouse_code'],
            ];
            $branchMdl = app::get('ome')->model('branch');
            $branch    = $branchMdl->dump($filter, 'branch_id');
            if (!$branch) {
                $this->__apilog['result']['msg'] = sprintf('[%s]退仓门店不存在', $params['warehouse_code']);
                return false;
            }

            # 指定门店标记
            $sdf['store_appoint_branch'] = $branch['branch_id'];
        }

        # 付款方式创建
        return $sdf;
    }

    protected function _getAddType($sdf)
    {

        return 'returnProduct';
    }

    protected function _formatAddItemList($sdf, $convert = array())
    {
        $convert = array(
            'sdf_field'     => 'oid',
            'order_field'   => 'oid',
            'default_field' => 'outer_id',
        );

        $itemList = $sdf['refund_item_list']['return_item'];

        $sdfField     = $convert['sdf_field'];
        $orderField   = $convert['order_field'];
        $defaultField = $convert['default_field'];

        // 取oid
        $arrOrderField = array();
        foreach ($itemList as $val) {
            if ($val[$sdfField]) {
                $arrOrderField[] = $val[$sdfField];
            }
        }

        if ($sdf['order']['tran_type'] == 'archive') {
            $objMdl = app::get('archive')->model('order_objects');
        } else {
            $objMdl = app::get('ome')->model('order_objects');
        }

        // oid查询
        $object = $objMdl->getList('bn, quantity,obj_id,oid', [
            $orderField => $arrOrderField,
            'order_id'  => $sdf['order']['order_id'],
        ]);

        $arrBn       = [];
        $objId       = [];
        $arrQuantity = [];
        $oid         = [];
        foreach ($object as $oVal) {
            $arrBn[$oVal[$orderField]]       = $oVal['bn'];
            $objId[$oVal[$orderField]]       = $oVal['obj_id'];
            $oid[$oVal[$orderField]]         = $oVal['oid'];
            $arrQuantity[$oVal[$orderField]] = $oVal['quantity'];
        }

        $arrItem = array();
        foreach ($itemList as $item) {
            $item['bn']     = $arrBn[(string) $item[$sdfField]] ?: $item[$defaultField];
            $item['obj_id'] = $objId[(string) $item[$sdfField]] ?: $item[$defaultField];
            $item['oid']    = $oid[(string) $item[$sdfField]] ?: $item[$defaultField];
            $item['bn']     = (string) $item['bn'];

            if ($item['nums'] && !$item['num']) {
                $item['num'] = $item['nums'];
                unset($item['nums']);
            }

            if ($this->refund_item_all) {
                $item['num'] = $arrQuantity[(string) $item[$sdfField]];
            }

            $ik = $item['oid'];

            // 相同的KEY，数量累加
            if ($arrItem[$ik]) {
                $arrItem[$ik]['num'] += $item['num'];
            } else {
                $arrItem[$ik] = $item;
            }
        }

        return $arrItem;
    }

    #重新计算单价($refundItems 以bn作主键的数组 捆绑商品使用捆绑商品的bn)
    #custom 由于父类使用子订单sale_price 进行计算,故复制一份用以适配互道实际退货金额
    protected function _calculateAddPrice($refundItems, $sdf)
    {
        if (empty($refundItems)) {
            return [];
        }

        $order = $sdf['order'];

        $orderNumPrice = kernel::single('ome_order_object_item')->getNumPrice([$order['order_id']]);
      
        $return = $productSend = [];

        foreach ($orderNumPrice[$order['order_id']] as $obj) {
            $ik = $obj['oid'] ?: $obj['bn'];

            // 退组合商品
            if ($obj['obj_type'] == 'pkg') {
                if ($refundItems[$ik]) {
                    $radio = $refundItems[$ik]['num'] / $obj['quantity'];

                    // 退组合对应明细
                    foreach ($obj['order_items'] as $item) {
                        $num = (int) ($radio * $item['nums']);

                        // 按实付进行退
                        $price = $item['divide_order_fee'] / $item['nums'];

                        $tmpReturn = array(
                            'bn'            => $item['bn'],
                            'price'         => sprintf('%.2f', $price),
                            'num'           => $num,
                            'order_item_id' => $item['item_id'],
                            'sendNum'       => $item['sendnum'],
                            'product_id'    => $item['product_id'],
                            'name'          => str_replace(["\r", "\n"], "", $item['name']),
                        );

                        $tmpReturn['amount'] = $tmpReturn['price'] * $tmpReturn['num'];

                        $return[] = array_merge((array) $refundItems[$ik], $tmpReturn);
                    }

                    // 清除组合
                    unset($refundItems[$ik]);

                    continue;
                }

            }

            foreach ($obj['order_items'] as $item) {
                // 普通商品下单
                if ($refundItems[$ik]) {

                    $tmpReturn = array(
                        'bn'            => $item['bn'],
                        'price'         => sprintf('%.2f', $refundItems[$ik]['price']),
                        'num'           => $refundItems[$ik]['num'],
                        'order_item_id' => $item['item_id'],
                        'sendNum'       => $item['sendnum'],
                        'product_id'    => $item['product_id'],
                        'name'          => str_replace(["\r", "\n"], "", $item['name']),
                    );

                    $tmpReturn['amount'] = $tmpReturn['price'] * $tmpReturn['num'];

                    $return[] = array_merge((array) $refundItems[$ik], $tmpReturn);

                    unset($refundItems[$ik]);
                }
            }
        }

        if ($refundItems && is_array($refundItems)) {
            $return = array_merge(array_values($refundItems), $return);
        }

        $items = array();
        foreach ($return as $value) {
            $ik = $value['oid'] ?: $value['bn'];

            if ($items[$ik]) {
                $items[$ik]['num'] += $value['num'];
                $items[$ik]['amount'] += $value['amount'];

                $items[$ik]['price'] = sprintf('%.2f', $items[$ik]['amount'] / $items[$ik]['num']);
            } else {
                $items[$ik] = $value;
            }
        }

        return array_values($items);
    }

}
