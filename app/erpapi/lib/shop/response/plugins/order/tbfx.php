<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
*
* @author chenping<chenping@shopex.cn>
* @version $Id: 2013-3-12 17:23Z
*/
class erpapi_shop_response_plugins_order_tbfx extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        // 创建
        $ordersdf = $platform->_ordersdf;

        $tbfx = array(
            'object_comp_key'  => $platform->object_comp_key,
            'item_comp_key'    => $platform->item_comp_key,
            'order_id'         => null,
            'fenxiao_order_id' => $ordersdf['fx_order_id'],
            'tc_order_id'      => $ordersdf['tc_order_id'],
            'shop_id'               => $platform->__channelObj->channel['shop_id'],
        );
        $salesMLib = kernel::single('material_sales_material');
        foreach($ordersdf['order_objects'] as $object){
            
            $object_pmt = 0;$order_items = array();
             $salesMInfo = $salesMLib->getSalesMByBn($tbfx['shop_id'],$object['bn']);
             $obj_type = 'goods';

              if($salesMInfo){
                  if($salesMInfo['sales_material_type'] == 4){ //福袋类型
                      $basicMInfos = $salesMLib->get_order_luckybag_bminfo($salesMInfo['sm_id']);
                      if($basicMInfos){
                          $obj_amount = $object['amount'] ? $object['amount'] : bcmul($object['quantity'], $object['price'],3);
                          $obj_type = 'lkb';
                          foreach($basicMInfos as $k => $basicMInfo){
                              $item['bn'] = $basicMInfo['material_bn'];
                              $item['quantity'] = $basicMInfo['number']*$object['quantity'];
                              $item['item_type'] = 'lkb';
                              $item['amount'] = $basicMInfo['price']*$object['quantity']*$basicMInfo['number'];
                              $item['sale_price'] =$basicMInfo['price']*$object['quantity']*$basicMInfo['number'];
                              $itemkey = $this->_get_item_key($tbfx['item_comp_key'],$item);
                              $order_items[$itemkey] = array(
                                      'order_id'      => null,
                                      'obj_id'        => null,
                                      'item_id'       => null,
                                      'buyer_payment' => $object['buyer_payment'],
                                      'cost_tax'      => $item['cost_tax'],
                                      'lbr_id' => $basicMInfo['lbr_id'],
                              );
                          }
                      }
                  }elseif($salesMInfo['sales_material_type'] == 5){ //多选一
                      $basicMInfos = $salesMLib->get_order_pickone_bminfo($salesMInfo['sm_id'],$object['quantity'],$tbfx['shop_id']);
                      if($basicMInfos){
                          $obj_amount = $object['amount'] ? $object['amount'] : bcmul($object['quantity'], $object['price'],3);
                          $obj_sale_price = (isset($object['sale_price']) && is_numeric($object['sale_price']) && -1 != bccomp($object['sale_price'], 0, 3) ) ? $object['sale_price'] :  bcsub($obj_amount,$object['pmt_price'],3);
                          $obj_type = 'pko';
                          foreach($basicMInfos as $k => $basicMInfo){
                              $item['bn'] = $basicMInfo['material_bn'];
                              $item['quantity'] = $basicMInfo['number'];
                              $item['item_type'] = 'pko';
                              $item['amount'] = bcmul($obj_sale_price/$object['quantity'], $basicMInfo['number'], 3);
                              $item['sale_price'] = $item['amount'];
                              $itemkey = $this->_get_item_key($tbfx['item_comp_key'],$item);
                              $order_items[$itemkey] = array(
                                  'order_id'      => null,
                                  'obj_id'        => null,
                                  'item_id'       => null,
                                  'buyer_payment' => $object['buyer_payment'],
                                  'cost_tax'      => $item['cost_tax'],
                              );
                          }
                      }
                  }else{
                      $basicMInfos = $salesMLib->getBasicMBySalesMId($salesMInfo['sm_id']);
                      if($basicMInfos){
                          $obj_amount = $object['amount'] ? $object['amount'] : bcmul($object['quantity'], $object['price'],3);
                          $obj_sale_price = (isset($object['sale_price']) && is_numeric($object['sale_price']) && -1 != bccomp($object['sale_price'], 0, 3) ) ? $object['sale_price'] :  bcsub($obj_amount,$object['pmt_price'],3);
                          if($salesMInfo['sales_material_type'] == 2){
                              $obj_type = 'pkg';
                          }
                          if ($salesMInfo['sales_material_type'] == 3){
                              $obj_type = 'gift';
                              $obj_amount = 0;
                          }
                          $buyerRat = $salesMLib->calProbuyerMPriceByRate($tbfx['shop_id'],$object['buyer_payment'],$object['bn']);
                          foreach($basicMInfos as $k => $basicMInfo){
                              $item['bn']   = $basicMInfo['material_bn'];
                              $item['quantity']   = $basicMInfo['number']*$object['quantity'];
                              $sale_price = (isset($object['sale_price']) && is_numeric($object['sale_price']) && -1 != bccomp($object['sale_price'], 0, 3) ) ? $object['sale_price'] : bcsub($amount, (float)$object['pmt_price'],3);
                              if( $obj_type == 'pkg' ) {
                                  $salesMLib->calProSaleMPriceByRate($obj_sale_price, $basicMInfos);
                                  $buyer_payment = $buyerRat[$basicMInfo['material_bn']]['rate_price'] ? $buyerRat[$basicMInfo['material_bn']]['rate_price'] : 0.00;
                                  $item_type  = 'pkg';
                                  $amount     = $basicMInfo['rate_price'];
                                  $sale_price = $basicMInfo['rate_price'];
                              }else if ($obj_type == 'gift'){
                                  $buyer_payment = 0;
                                  $amount  = 0;
                                  $item_type  ='gift';
                                  $sale_price = 0;
                              }else{
                                  $amount  = $obj_amount;
                                  $buyer_payment = $object['buyer_payment'];
                              }
                              $item['item_type'] = $item_type ? $item_type : 'product';
                              $item['amount'] = $amount;
                              $item['sale_price'] =$sale_price;
                              $itemkey = $this->_get_item_key($tbfx['item_comp_key'],$item);
                              $order_items[$itemkey] = array(
                                      'order_id'      => null,
                                      'obj_id'        => null,
                                      'item_id'       => null,
                                      'buyer_payment' => $buyer_payment,
                                      'cost_tax'      => $item['cost_tax'],
                              );
                          }
                      }
                  }
              }
            
            $object['obj_type'] = $obj_type;

            $object['amount'] = $obj_amount;

            if (1 > bccomp((float) $object['sale_price'], 0, 3) ) {
                $object['sale_price'] = bcsub($object['amount'],bcadd($object['pmt_price'], $object_pmt,3),3);
            }

            $objkey = $this->_get_obj_key($tbfx['object_comp_key'],$object);
            $tbfx['order_objects'][$objkey] = array(
                'order_id'      => null,
                'obj_id'        => null,
                'fx_oid'        => $object['fx_oid'],
                'tc_order_id'   => $object['tc_order_id'],
                'buyer_payment' => $object['buyer_payment'],
                'cost_tax'      => $object['cost_tax'],
            );
            $tbfx['order_objects'][$objkey]['order_items'] = $order_items;

        }

        // 编辑
        if ($platform->_tgOrder) {
            $tg_objects = array();

            foreach ($platform->_tgOrder['order_objects'] as $object) {
                $order_items = array();
                foreach ($object['order_items'] as $item) {
                    $itemkey = $this->_get_item_key($tbfx['item_comp_key'],$item);
                    $order_items[$itemkey] = $item;
                }

                $objkey = $this->_get_obj_key($tbfx['object_comp_key'],$object);
                $tg_objects[$objkey] = $object;
                $tg_objects[$objkey]['order_items'] = $order_items;
            }

            $tbfxObjectModel = app::get('ome')->model('tbfx_order_objects');
            $tbfxItemsModel  = app::get('ome')->model('tbfx_order_items');

            $old_tbfx_objects = array();
            foreach ($tbfxObjectModel->getList('*',array('order_id'=>$platform->_tgOrder['order_id'])) as $value) {
                $old_tbfx_objects[$value['obj_id']] = $value;
            }

            $old_tbfx_items = array();
            foreach ($tbfxItemsModel->getList('*',array('order_id'=>$platform->_tgOrder['order_id'])) as $value) {
                $old_tbfx_items[$value['item_id']] = $value;
            }

            $updatetbfx = array();

            // 比较
            foreach ($tbfx['order_objects'] as $objkey=>$object) {
                $order_items = $object['order_items']; unset($object['order_items']);


                $obj_id = $tg_objects[$objkey]['obj_id'];
                $object['order_id'] = $platform->_tgOrder['order_id'];
                $object['obj_id']   = $obj_id;

                $object = array_filter($object,array($this,'filter_null'));
                $diff_obj = array_udiff_assoc((array)$object, (array)$old_tbfx_objects[$obj_id],array($this,'comp_array_value'));
                if ($diff_obj) {
                    $diff_obj['obj_id']   = $obj_id;
                    $diff_obj['order_id'] = $platform->_tgOrder['order_id'];

                    $updatetbfx['order_objects'][$objkey] = $diff_obj;
                }

                foreach ($order_items as $itemkey=>$item) {
                    $item_id = $tg_objects[$objkey]['order_items'][$itemkey]['item_id'];

                    $item['order_id'] = $platform->_tgOrder['order_id'];
                    $item['obj_id']   = $obj_id;
                    $item['item_id']  = $item_id;

                    $diff_item = array_udiff_assoc((array)$item, (array)$old_tbfx_items[$item_id],array($this,'comp_array_value'));

                    if ($diff_item) {
                        $diff_item['order_id'] = $platform->_tgOrder['order_id'];
                        $diff_item['obj_id']   = $obj_id;
                        $diff_item['item_id']  = $item_id;

                        $updatetbfx['order_objects'][$objkey]['order_items'][$itemkey] = $diff_item; 
                    }
                }

            }

            if ($updatetbfx) {
                $updatetbfx['object_comp_key'] = $platform->object_comp_key;
                $updatetbfx['item_comp_key'] = $platform->item_comp_key;
            }

            return $updatetbfx;
        }


        return $tbfx;
    }

    /**
     * 订单保存之后处理
     *
     * @param Array $tbfx [object_comp_key:;item_comp_key:;order:;objects:;items:;]
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$tbfx)
    {
        $objectsdf = array();
        foreach ($this->getOrderObjects($order_id) as $value) {
            $objectsdf[$value['obj_id']] = $value;
        }
        foreach ($this->getOrderItems($order_id) as $value) {
            $value['quantity'] = $value['nums'];
            $objectsdf[$value['obj_id']]['order_items'][$value['item_id']] = $value;
        }

        $tg_objects = array();
        foreach ($objectsdf as $object) {
            $order_items = array();
            foreach ($object['order_items'] as $item) {
                $itemkey = $this->_get_item_key($tbfx['item_comp_key'],$item);

                $order_items[$itemkey] = $item;
            }

            $objkey = $this->_get_obj_key($tbfx['object_comp_key'],$object);

            $tg_objects[$objkey] = $object;
            $tg_objects[$objkey]['order_items'] = $order_items;
        }

        // 保存主表
        $tbfx_orders = array(
            'order_id'         => $order_id,
            'fenxiao_order_id' => $tbfx['fenxiao_order_id'],
            'tc_order_id'      => $tbfx['tc_order_id'],
        );
        app::get('ome')->model('tbfx_orders')->save($tbfx_orders);

        
        // 保存OBJ
        $tbfx_objects = array();$tbfx_items = array();
        foreach ($tbfx['order_objects'] as $objkey => $object) {
            $tbfx_objects[] = array(
                'order_id'      => $order_id,
                'obj_id'        => $tg_objects[$objkey]['obj_id'],
                'fx_oid'        => $object['fx_oid'],
                'tc_order_id'   => $object['tc_order_id'],
                'buyer_payment' => $object['buyer_payment'],
                'cost_tax'      => $object['cost_tax'],
            );
            
            foreach ($object['order_items'] as $itemkey => $item) {
                $tbfx_items[] = array(
                    'order_id'      => $order_id,
                    'obj_id'        => $tg_objects[$objkey]['order_items'][$itemkey]['obj_id'],
                    'item_id'       => $tg_objects[$objkey]['order_items'][$itemkey]['item_id'],
                    'buyer_payment' => $item['buyer_payment'],
                    'cost_tax'      => $item['cost_tax'],
                );
            }
        }

        if ($tbfx_objects) {
            $tbfxObjectModel = app::get('ome')->model('tbfx_order_objects');

            $sql = ome_func::get_insert_sql($tbfxObjectModel,$tbfx_objects);

            kernel::database()->exec($sql);
        }

        if ($tbfx_items) {
            $tbfxItemsModel = app::get('ome')->model('tbfx_order_items');

            $sql = ome_func::get_insert_sql($tbfxItemsModel,$tbfx_items);

            kernel::database()->exec($sql);
        }
    }

    /**
     * 订单更新之后处理
     *
     * @return void
     * @author 
     **/
    public function postUpdate($order_id,$tbfx)
    {
        $objectsdf = array();
        foreach ($this->getOrderObjects($order_id) as $value) {
            $objectsdf[$value['obj_id']] = $value;
        }
        foreach ($this->getOrderItems($order_id) as $value) {
            $value['quantity'] = $value['nums'];
            $objectsdf[$value['obj_id']]['order_items'][$value['item_id']] = $value;
        }

        $tg_objects = array();
        foreach ($objectsdf as $object) {
            $order_items = array();
            foreach ($object['order_items'] as $item) {
                $itemkey = $this->_get_item_key($tbfx['item_comp_key'],$item);

                $order_items[$itemkey] = $item;
            }

            $objkey = $this->_get_obj_key($tbfx['object_comp_key'],$object);

            $tg_objects[$objkey] = $object;
            $tg_objects[$objkey]['order_items'] = $order_items;
        }

        $tborder = app::get('ome')->model('tbfx_orders');
        $tbobj   = app::get('ome')->model('tbfx_order_objects');
        $tbitem  = app::get('ome')->model('tbfx_order_items');
        foreach ((array) $tbfx['order_objects'] as $objkey => $object) {
            if ($object['order_id']  && $object['obj_id']) {
                $tbobj->update($object,array('order_id'=>$object['order_id'],'obj_id'=>$object['obj_id']));
            } elseif ($object['order_id'] && !$object['obj_id'] && $tg_objects[$objkey]['obj_id']) {
                $object['obj_id'] = $tg_objects[$objkey]['obj_id'];

                $tbobj->insert($object);
            }
            foreach ((array)$object['order_items'] as $itemkey => $item) {
                if ($item['order_id'] && $item['obj_id'] && $item['item_id']) {
                   $tbitem->update($item,array('order_id'=>$item['order_id'],'obj_id'=>$item['obj_id'],'item_id'=>$item['item_id'])); 
                } elseif ($item['order_id'] && !$item['item_id'] && $tg_objects[$objkey]['obj_id'] && $tg_objects[$objkey]['order_items'][$itemkey]['item_id']) {
                    $item['obj_id'] = $tg_objects[$objkey]['obj_id'];
                    $item['item_id'] = $tg_objects[$objkey]['order_items'][$itemkey]['item_id'];

                    $tbitem->insert($item);
                }
            }
        }
    }

    private function _get_obj_key($object_comp_key,$object)
    {
        $objkey = '';
        
        foreach (explode('-', $object_comp_key) as $field) {
            $objkey .= ($object[$field] ? trim($object[$field]) : '').'-';
        }
        return sprintf('%u',crc32(ltrim($objkey,'-')));
    }

    private function _get_item_key($item_comp_key,$item)
    {
        $itemkey = '';
        foreach (explode('-',$item_comp_key) as $field) {
            if ($field == 'unit_sale_price') {
                $itemkey .= bcdiv((float)$item['sale_price'], $item['quantity'],3).'-';
            } else {
                $itemkey .= ($item[$field] ? $item[$field] : '').'-'; 
            }
        }
        return sprintf('%u',crc32(ltrim($itemkey,'-')));
    }
}