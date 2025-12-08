<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 采购入库事件节点
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class flowctrl_node_purchase_stockin extends flowctrl_node_abstract implements flowctrl_node_interface {

    protected $__Config = array(
        'html' => 'admin/node/conf/purchase/stockin.html',
    );

    public function processModeToString($cnf){
        if($cnf['purchase']['stockin'] == 'normal'){
            $string = "传统入库(货号、条码)";
        }elseif($cnf['purchase']['stockin'] == 'batch'){
            $string = "批次入库";
        }

        return $string;
    }
}