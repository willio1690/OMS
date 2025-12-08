<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_order_remain_gift {
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function diff_money($obj){
        return 0;
    }
    
    /*
     * 余单撤销处理
     */
    public function remain_cancel($obj){
        if ($obj){
            $sql = "UPDATE `sdb_ome_order_items` SET `nums`=`sendnum` WHERE `obj_id`='".$obj['obj_id']."' AND `sendnum`<`nums` AND `sendnum` <> '0' ";
            kernel::database()->exec($sql);
            $sql = "UPDATE `sdb_ome_order_items` SET `delete`='true' WHERE `obj_id`='".$obj['obj_id']."' AND `sendnum`<`nums` AND `sendnum` = '0' ";
            kernel::database()->exec($sql);
            
            //更新order_objects订单对象表上的quantity购买数量
            if($obj['quantity'] && $obj['order_items'])
            {
                $num    = 0;
                $delete = false;
                foreach ($obj['order_items'] as $item)
                {
                    if ($item['sendnum'] == 0)
                    {
                        $num    = $obj['quantity'];
                        $delete = true;
                        break;
                    }
                    else
                    {
                        $num    = $obj['quantity'] / $item['quantity'] * $item['sendnum'];
                    }
                }
                
                if(!$delete)
                {
                    $sql    = "UPDATE `sdb_ome_order_objects` SET `quantity`=". intval($num) ." WHERE `obj_id`='". $obj['obj_id'] ."'";
                    kernel::database()->exec($sql);
                }
            }
        }
        return true;
    }
    
}