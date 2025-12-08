<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_order_edit{
    /**
     * 获取订单编辑时每种objtype的显示内容定义
     * @access public
     * @param int $reship_id 退货单ID
     */
    public function get_config_list(){
        $conf_list = array(
            'goods' => kernel::single("ome_order_edit_goods")->get_config(),
            'pkg' => kernel::single("ome_order_edit_pkg")->get_config(),
            'gift' => kernel::single("ome_order_edit_gift")->get_config(),
            'giftpackage' => kernel::single("ome_order_edit_giftpackage")->get_config(),
            'lkb' => kernel::single("ome_order_edit_lkb")->get_config(),
            'pko' => kernel::single("ome_order_edit_pko")->get_config(),
        );
        return $conf_list;
    }
    
    /**
     * 处理订单编辑时提交的数据
     * @access public
     * @param array $objtype 如：array('goods','pkg');
     * @param array $data 订单编辑的数据 //POST
     */
    public function process_order_objtype($objtype,$data){
        $obj    = array();
        $new    = array();
        $total  = 0;
        $is_order_change = false;
        $is_goods_modify = false;
        
        if ($objtype && is_array($objtype))
        foreach ($objtype as $key =>$type){
            if ($service = kernel::service('ome.service.order.edit.'.$type)){
                if (method_exists($service,'process')){
                    $rs = $service->process($data);
                }
                
                if ($rs){
                    $obj = array_merge($obj,$rs['oobj'] ? $rs['oobj'] : array());
                    $new = array_merge($new,$rs['nobj'] ? $rs['nobj'] : array());
                    $total += is_numeric($rs['total']) ? $rs['total'] : 0;
                    $total_pmt_goods += is_numeric($rs['total_pmt_goods']) ? $rs['total_pmt_goods'] : 0;                    
                    if ($is_order_change == false) $is_order_change = $rs['is_order_change'] == true ? true : false;
                    if ($is_goods_modify == false) $is_goods_modify = $rs['is_goods_modify'] == true ? true : false;
                }
            }
        }
        $rs = array(
            'obj'   => $obj,
            'new'   => $new,
            'total' => $total,
            'is_order_change' => $is_order_change,
            'is_goods_modify' => $is_goods_modify,
            'total_pmt_goods' => $total_pmt_goods,
        );
        return $rs;
    }

    /**
     * 校验订单编辑时提交的数据
     * @access public
     * @param array $objtype 如：array('goods','pkg');
     * @param array $data 订单编辑的数据 //POST
     */
    public function valid_order_objtype($objtype,$data){
        $flag = true;
        $msg  = '';
        if ($objtype && is_array($objtype))
        foreach ($objtype as $key =>$type){
            if ($service = kernel::service('ome.service.order.edit.'.$type)){
                if (method_exists($service,'valid')){
                    $rs = $service->valid($data);
                    if ($rs !== true && $rs['flag'] == false){
                        $rs = array(
                            'flag' => $rs['flag'],
                            'msg' => $rs['msg'],
                        );
                        return $rs;
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * 判断是否有商品数据提交
     * @param array $objtype 如：array('goods','pkg');
     * @param array $data 订单编辑的数据 //POST
     */
    public function is_null($objtype,$data){
        $flag = true;
        $dt = array();
        if ($objtype && is_array($objtype))
        foreach ($objtype as $key =>$type){
            if ($service = kernel::service('ome.service.order.edit.'.$type)){
                if (method_exists($service,'is_null')){
                    $rs = $service->is_null($data);
                    if ($rs == false) return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * 判断订单上商品明细是否被修改
     *
     * @param Array $objtype
     * @param Array $data
     * @return bool
     */
    public function is_edit_order_item($objtype, $data){
        
        if ($objtype && is_array($objtype)){
            foreach ($objtype as $key => $type){
                if ($service = kernel::service('ome.service.order.edit.'.$type)){
                    if (method_exists($service,'is_edit_product')){
                        
                        //发现有商品被修改或者金额变化直接返回true
                        $result = $service->is_edit_product($data);
                        if($result){
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }
}