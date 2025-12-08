<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 超卖
*
* @author chenping<chenping@shopex.cn>
* @version $Id: items.php 2013-3-12 17:23Z
*/
class erpapi_shop_response_components_order_oversold extends erpapi_shop_response_components_order_abstract
{
    /**
     * 数据格式转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {   
        $is_oversold = false;
        foreach ($this->_platform->_ordersdf['order_objects'] as $object) {
            

            if ($object['is_oversold'] == true) {
                $is_oversold = true;
            }
        }

        if ($is_oversold) {
            $this->_platform->_newOrder['auto_status'] = omeauto_auto_const::__OVERSOLD_CODE;
        }
    }

    /**
     * 更新订单明细
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        // 后期修改
        if ($this->_platform->_tgOrder['ship_status'] == '0') {
            // 原单处理
            $tgOrder_object = array();
            $salesMLib = kernel::single('material_sales_material');
            foreach ((array)$this->_platform->_tgOrder['order_objects'] as $object) {
                $objkey = $this->_get_obj_key($object);

                $tgOrder_object[$objkey] = array(
                    'is_oversold' => $object['is_oversold'],
                    'order_id'    => $object['order_id'],
                    'obj_id'      => $object['obj_id'],
                    'obj_type'    => $object['obj_type'],
                    'oid'         => $object['oid'],
                    'bn'          => $object['bn'],
                );
            }


            $ordersdf = $this->_platform->_ordersdf;

            // 接收的参数
            $ordersdf_object = array();
            foreach ((array)$ordersdf['order_objects'] as $object) {
                
                $obj_type = 'goods';

                $goods = array();
                if ($object['bn']) {
                    $salesMInfo = $salesMLib->getSalesMByBn($this->_platform->_ordersdf['shop_id'],$object['bn']);
                    if($salesMInfo){
                        switch($salesMInfo['sales_material_type']){
                            case "2": //促销
                                $obj_type = 'pkg'; break;
                            case "7": //福袋
                                $obj_type = 'lkb'; break;
                            case "5": //多选一
                                $obj_type = 'pko'; break;
                        }
                    } 
                }

                $objecttmp = array(
                    'is_oversold' => ($object['is_oversold'] == true) ? 1 : 0,
                    'oid'         => $object['oid'],
                    'obj_type'    => $obj_type,
                    'bn'          => $object['bn'],
                );
                
                $objkey = $this->_get_obj_key($objecttmp);
                $ordersdf_object[$objkey] = $objecttmp;
            }

   

            $is_oversold = false;
            // 字段比较
            foreach ($ordersdf_object as $objkey => $object) {
                $obj_id = $tgOrder_object[$objkey]['obj_id'];

                $object = array_filter($object,array($this,'filter_null'));
                // OBJECT比较

                $diff_obj = array();
                if ($object['is_oversold'] != $tgOrder_object[$objkey]['is_oversold'] && $obj_id) {
                    $diff_obj['obj_id']      = $obj_id;
                    $diff_obj['is_oversold'] = $object['is_oversold'];

                    $this->_platform->_newOrder['order_objects'][$objkey] = array_merge((array)$this->_platform->_newOrder['order_objects'][$objkey],(array)$diff_obj);

                    if ($diff_obj['is_oversold'] == 1) {
                        $is_oversold = true;
                    }
                }
            }


            if ($is_oversold) {
                $this->_platform->_newOrder['auto_status'] = $this->_platform->_tgOrder['auto_status'] | omeauto_auto_const::__OVERSOLD_CODE;
            } else {
                if ($this->_platform->_tgOrder['auto_status'] & omeauto_auto_const::__OVERSOLD_CODE) {
                    $this->_platform->_newOrder['auto_status'] = $this->_platform->_tgOrder['auto_status'] ^ omeauto_auto_const::__OVERSOLD_CODE;
                }
            }
        }
    }

    private function _get_obj_key($object)
    {
        $objkey = '';
        foreach (explode('-', $this->_platform->object_comp_key) as $field) {
            $objkey .= ($object[$field] ? trim($object[$field]) : '').'-';
        }
        return sprintf('%u',crc32(ltrim($objkey,'-')));
    }
}