<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_rpc_response_func_bill{

    /**
     * 批量添加交易数据
     * @access public
     * @param String $record_list 交易数据
     * @param String $node_id 节点ID
     * @return Array
     */
    function batch_trade_add($record_list,$node_id=''){
        $rs = array('rsp'=>'fail','msg'=>'');
        if (empty($record_list) || !isset($record_list[0])){
            $rs['msg'] = '交易数据不能为空或格式不正确';
            return $rs;
        }
        foreach ($record_list as $items){
            $rs = $this->trade_add($items,$node_id);
            if ($rs['rsp'] != 'succ'){
                break;
            }
        }
        return $rs;
    }

    /**
     * 单个添加交易数据
     * @access public
     * @param Array $record 单条交易数据
     * @param String $node_id 节点ID
     * @return Array
     */
    function trade_add($record,$node_id=''){
        $rs = array('rsp'=>'fail','msg'=>'');
        if (empty($record) || !isset($node_id)){
            $rs['msg'] = '交易数据或节点与不能为空';
            return $rs;
        }

        $shop_detail = kernel::single('finance_func')->getShopByNodeID($node_id);
        $node_type = $shop_detail['node_type'];
        $class_name = 'finance_rpc_response_func_bill'.$node_type;
        if (ome_func::class_exists($class_name) && $instance = kernel::single($class_name)){
            if (method_exists($instance,'trade_add')){
                $rs = $instance->trade_add($record,$shop_detail);
            }else{
                $rs['msg'] = 'method trade_add NOT FOUND';
            }
        }else{
            $rs['msg'] = 'class:'.$class_name.' NOT FOUND';
        }
        
        return $rs;
    }

}