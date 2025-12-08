<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 采购退货出库事件节点
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class flowctrl_node_purchasereturn_stockout extends flowctrl_node_abstract implements flowctrl_node_interface {

    protected $__Config = array(
        'html' => 'admin/node/conf/purchasereturn/stockout.html',
    );

    public function processModeToString($cnf){
        if($cnf['purchasereturn']['stockout'] == 'normal'){
            $string = "传统出库(货号、条码)";
        }elseif($cnf['purchasereturn']['stockout'] == 'batch'){
            $string = "批次出库";
        }

        return $string;
    }
}