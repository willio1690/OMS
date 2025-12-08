<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 促销插件
*
* @author chenping<chenping@shopex.cn>
* @version $Id: promotion.php 2013-3-12 17:23Z
*/
class erpapi_shop_response_plugins_order_promotion extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $pmtsdf = array();

        if ($platform->_ordersdf['pmt_detail']) {
            foreach ((array) $platform->_ordersdf['pmt_detail'] as $key => $value) {
                if (!is_array($value) || trim($value['pmt_amount']) == '' || trim($value['pmt_amount']) == 0) {
                    continue;
                }

                //TODO:兼容拍拍优惠描述
                $pmt_describe = '';
                if ($partpmtdesc = strstr($value['pmt_describe'],'@')){
                    $pmt_describe = ltrim($partpmtdesc,"@");
                }else{
                    $pmt_describe = $value['pmt_describe'];
                }

                $pmtsdf[] = array(
                    'order_id'     => $platform->_tgOrder['order_id'],
                    'pmt_amount'   =>  number_format(abs($value['pmt_amount']), 3, '.', ''),
                    'pmt_describe' => $pmt_describe,
                    'coupon_id' => $value['pmt_id'], //优惠券ID
                );
            }
        }

        // 更新
        if ($platform->_tgOrder) {
            $pmtObj = app::get('ome')->model('order_pmt');
            $oldpmts = $pmtObj->getList('order_id,pmt_amount,pmt_describe',array('order_id'=>$platform->_tgOrder['order_id']));

            usort($oldpmts,array($this,'_sort_pmt')); usort($pmtsdf,array($this,'_sort_pmt'));

            if ($oldpmts && !$pmtsdf) {
                 $pmtObj->delete(array('order_id'=>$platform->_tgOrder['order_id']));
            }

            // 无变化
            if ($oldpmts == $pmtsdf) return array();

        }

        return $pmtsdf;
    }

    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$pmts)
    {
        foreach ($pmts as $key => $value) {
            $pmts[$key]['order_id'] = $order_id;
        }

        $pmtObj = app::get('ome')->model('order_pmt');

        $sql = ome_func::get_insert_sql($pmtObj,$pmts);

        kernel::database()->exec($sql);
    }

    /**
     * 订单完成后处理
     *
     * @param Array $params
     * @return void
     * @author 
     **/
    public function postUpdate($order_id,$pmts)
    {
         $pmtObj = app::get('ome')->model('order_pmt');

        foreach ($pmts as $key => $value) {
            $pmts[$key]['order_id'] = $order_id;
        }

        $pmtObj->delete(array('order_id'=>$order_id));        

        $sql = ome_func::get_insert_sql($pmtObj,$pmts);
        kernel::database()->exec($sql);

        $logModel = app::get('ome')->model('operation_log');
        $logModel->write_log('order_edit@ome',$order_id,"修改订单优惠方案");
    }

    private function _sort_pmt($a,$b)
    {
        if ($a['pmt_describe'] == $b['pmt_describe']) return 0;

        return $a['pmt_describe'] < $b['pmt_describe'] ? -1 : 1;
    }
}