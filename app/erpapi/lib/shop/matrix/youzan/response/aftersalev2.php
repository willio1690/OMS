<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2016/6/3
 * @describe 淘宝售后数据转换
 */

class erpapi_shop_matrix_youzan_response_aftersalev2 extends erpapi_shop_response_aftersalev2 {

    protected function _getAddType($sdf) {
        if(in_array($sdf['refund_type'],array('refund','apply'))) { #退款
            return 'refund';
        } else { #退货
            return 'returnProduct';
        }
    }

    protected function _formatAddItemList($sdf, $convert=array()) {

        $oid = array();
        foreach($sdf['refund_item_list']['return_item'] as $val) {
            if ($val['oid']) $oid[] = (string)$val['oid'];
        }

        if (!$oid) return array();

        $filter = array(
            'oid'      => $oid,
            'order_id' => $sdf['order']['order_id'],
        );

        $objMdl = app::get('ome')->model('order_objects');

        $order_objects = array();
        foreach($objMdl->getList('oid,bn,quantity', $filter) as $value) {
            $order_objects[$value['oid']] = $value;
        }

        if (!$order_objects) return array();

        $arrItem = array();
        foreach ($sdf['refund_item_list']['return_item'] as $item) {
            if (!$order_objects[$item['oid']]) continue;

            $item['bn']  = (string) $order_objects[$item['oid']]['bn'];
            $item['num'] = $order_objects[$item['oid']]['quantity'];

            if($arrItem[$item['bn']]) {
                $arrItem[$item['bn']]['num'] += $item['num'];
            } else {
                $arrItem[$item['bn']] = $item;
            }
        }

        return $arrItem;
    }

    protected function _refundApplyAdditional($sdf) {
        $ret = array(
            'model' => 'refund_apply_youzan',
            'data' => array(
                'shop_id'          => $sdf['shop_id'],
                'oid'              => $sdf['oid'],
                'refund_version'   => $sdf['refund_version'],
                'bill_type'        => $sdf['bill_type'],
                'outer_lastmodify' => $sdf['modified'],
                'refund_fee'       => $sdf['refund_fee'],
            )
        );
        return $ret;
    }

    protected function _formatAddParams($params) {

        $sdf = parent::_formatAddParams($params);

        $item = current($sdf['refund_item_list']['return_item']);
        $youzanSdf = array(
            'oid'                   => $params['oid'] ? $params['oid'] : $item['oid'],
            'refund_type'           => $params['refund_type'],
            'refund_version'        => $params['refund_version'],
            'trade_status'          => $params['trade_status'],
            'bill_type'             => $params['bill_type'],
        );

        return array_merge($sdf, $youzanSdf);
    }

    protected function _refundAddSdf($sdf){
        $sdf = parent::_refundAddSdf($sdf);

        if($sdf['refund_apply']) {
            $refundData = app::get('ome')->model('refund_apply_youzan')->db_dump(array('apply_id'=>$sdf['refund_apply']['apply_id'],'shop_id'=>$sdf['shop_id']),'refund_version');

            if ($sdf['refund_version'] > $refundData['refund_version']) {
                $sdf['refund_version_change'] = true;
                $sdf['table_additional'] = $this->_refundApplyAdditional($sdf);
            } else {
                $sdf['refund_version_change'] = false;
            }
        }

        return $sdf;
    }

    protected function _returnProductAdditional($sdf) {
        $ret = array(
            'model' => 'return_product_youzan',
            'data' => array(
                'shop_id'               => $sdf['shop_id'],
                'refund_type'           => $sdf['refund_type'] == 'reship' ? 'return' : $sdf['refund_type'],
                'refund_version'        => $sdf['refund_version'],
                'oid'                   => $sdf['oid'],
                'bill_type'             => $sdf['bill_type'],
                'refund_fee'            => $sdf['refund_fee'],
            )
        );
        return $ret;
    }

    protected function _returnProductAddSdf($sdf) {
        $sdf = parent::_returnProductAddSdf($sdf);
        if(!$sdf) return false;

        $sdf['choose_type_flag'] = 0;
        if($sdf['return_product']) {
            $ref = app::get('ome')->model('return_product_youzan')->db_dump(array('return_id'=>$sdf['return_product']['return_id'],'shop_id'=>$sdf['shop_id']), 'refund_version');

            if ($sdf['refund_version'] > $ref['refund_version']) {
                $sdf['refund_version_change'] = true;
            } else {
                $sdf['refund_version_change'] = false;
            }
        }

        $sdf['table_additional'] = $this->_returnProductAdditional($sdf);

        return $sdf;
    }
}