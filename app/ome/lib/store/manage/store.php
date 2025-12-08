<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_store_manage_store extends ome_store_manage_abstract implements ome_store_manage_interface
{

    /**
     * __construct
     * @param mixed $is_ctrl_store is_ctrl_store
     * @return mixed 返回值
     */
    public function __construct($is_ctrl_store)
    {
        
        $this->_basicMaterialStock   = kernel::single('material_basic_material_stock');
        $this->_basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
    }

    /**
     * 添加发货单节点的库存处理方法
     * 
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     * @return boolean true/false
     */
    public function addDly($params, &$err_msg)
    {
        //门店管控库存
        if ($this->_is_ctrl_store) {
            $storeBranchObj       = app::get('o2o')->model("product_store");
            $branchPrdLib         = kernel::single('o2o_branch_product');
            $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
            $theDly = app::get('ome')->model('delivery')->db_dump(['delivery_id'=>$params['delivery_id']],'delivery_bn');
            $obj_bn = $theDly['delivery_bn'];
            foreach ($params['delivery_items'] as $key => $dly_item) {
                //识别门店货品是否管控库存，不管控直接处理下一个货品
                $is_bm_ctrl_store = $branchPrdLib->isCtrlBmStore($params['branch_id'], $dly_item['product_id']);
                if ($is_bm_ctrl_store) {
                    $sql = "SELECT bm_id, branch_id, store FROM sdb_o2o_product_store
                            WHERE bm_id=" . $dly_item['product_id'] . " AND branch_id =" . $params['branch_id'];
                    $store_p = $storeBranchObj->db->selectrow($sql);

                    //根据仓库ID、基础物料ID获取该物料仓库级的预占
                    $store_freeze   = $basicMStockFreezeLib->getO2oBranchFreeze($store_p['bm_id'], $store_p['branch_id']);
                    $store_p['has'] = ($store_p['store'] < $store_freeze) ? 0 : ($store_p['store'] - $store_freeze);

                    if (!is_numeric($dly_item['number'])) {
                        $err_msg .= $dly_item['product_name'] . ":请输入正确数量";
                        return false;
                    }

                    if (empty($store_p['has']) || $store_p['has'] == 0 || $store_p['has'] < $dly_item['number']) {
                        $err_msg .= $dly_item['product_name'] . ":商品库存不足";
                        return false;
                    }
                    //门店货品预占冻结
                    $freezeData = [];
                    $freezeData['bm_id'] = $dly_item['product_id'];
                    $freezeData['obj_type'] = material_basic_material_stock_freeze::__BRANCH;
                    $freezeData['bill_type'] = material_basic_material_stock_freeze::__DELIVERY;
                    $freezeData['obj_id'] = $params['delivery_id'];
                    $freezeData['shop_id'] = $params['shop_id'];
                    $freezeData['branch_id'] = $params['branch_id'];
                    $freezeData['bmsq_id'] = material_basic_material_stock_freeze::__STORE_CONFIRM;
                    $freezeData['num'] = $dly_item['number'];
                    $freezeData['obj_bn'] = $obj_bn;
                    $rs = $basicMStockFreezeLib->freeze($freezeData);
                    if ($rs == false) {
                        $err_msg .= '门店货品冻结预占失败!';
                        return false;
                    }
                    //订单货品预占释放
                    $rs = $basicMStockFreezeLib->unfreeze($dly_item['product_id'], material_basic_material_stock_freeze::__ORDER, 0, $params['order_id'], '', material_basic_material_stock_freeze::__SHARE_STORE, $dly_item['number']);
                    if ($rs == false) {
                        $err_msg .= '订单货品冻结释放失败!';
                        return false;
                    }
                } else {
                    continue;
                }
            }
        } else {
            //不管控库存情况下，发货单生成不做任何库存预占释放增加的处理
        }

        return true;
    }

    /**
     * 取消发货单节点的库存处理方法
     * 
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     * @return boolean true/false
     */
    public function cancelDly($params, &$err_msg)
    {
        //门店管控库存
        if ($this->_is_ctrl_store) {
            $branchPrdLib         = kernel::single('o2o_branch_product');
            $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
            $theOrder = app::get('ome')->model('orders')->db_dump(['order_id'=>$params['order_id']], 'order_bn');
            $obj_bn = $theOrder['order_bn'];
            foreach ($params['delivery_items'] as $key => $dly_item) {
                //识别门店供货货品是否管控库存，管控的：释放门店货品冻结，增加订单货品冻结，不管控的：不做任何处理
                //识别门店货品是否管控库存，不管控直接处理下一个货品
                $is_bm_ctrl_store = $branchPrdLib->isCtrlBmStore($params['branch_id'], $dly_item['product_id']);
                if ($is_bm_ctrl_store) {
                    $rs = $basicMStockFreezeLib->unfreeze($dly_item['product_id'], material_basic_material_stock_freeze::__BRANCH, material_basic_material_stock_freeze::__DELIVERY, $params['delivery_id'], $params['branch_id'], material_basic_material_stock_freeze::__STORE_CONFIRM, $dly_item['number']);
                    if ($rs == false) {
                        $err_msg = '门店货品冻结释放失败!';
                        return false;
                    }

                    $freezeData = [];
                    $freezeData['bm_id'] = $dly_item['product_id'];
                    $freezeData['obj_type'] = material_basic_material_stock_freeze::__ORDER;
                    $freezeData['bill_type'] = 0;
                    $freezeData['obj_id'] = $params['order_id'];
                    $freezeData['shop_id'] = $params['shop_id'];
                    $freezeData['branch_id'] = 0;
                    $freezeData['bmsq_id'] = material_basic_material_stock_freeze::__SHARE_STORE;
                    $freezeData['num'] = $dly_item['number'];
                    $freezeData['obj_bn'] = $obj_bn;
                    $rs = $basicMStockFreezeLib->freeze($freezeData);
                    if ($rs == false) {
                        $err_msg = '订单货品冻结预占失败!';
                        return false;
                    }
                } else {
                    continue;
                }
            }

            //发货单取消，门店仓预占流水删除
            $basicMStockFreezeLib->delDeliveryFreeze($params['delivery_id']);
        } else {
            //不管控库存情况下，发货单取消不做任何库存预占释放增加的处理
        }

        return true;
    }

    /**
     * 发货单发货节点的库存处理方法
     * 
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     * @return boolean true/false
     */
    public function consignDly($params, &$err_msg)
    {
        $branchPrdLib         = kernel::single('o2o_branch_product');
        $basicMStockLib       = kernel::single('material_basic_material_stock');
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');

        uasort($params['delivery_items'], [kernel::single('console_iostockorder'), 'cmp_productid']);
        //门店管控库存
        if ($this->_is_ctrl_store) {

            //是否需要删除仓库预占记录
            $is_delDlyFreeze = false;

            foreach ($params['delivery_items'] as $key => $dly_item) {
                //识别门店供货货品是否管控库存，管控的：释放门店货品冻结，扣减门店货品实际库存，不管控的：释放订单货品冻结
                $is_bm_ctrl_store = $branchPrdLib->isCtrlBmStore($params['branch_id'], $dly_item['product_id']);
                if ($is_bm_ctrl_store) {
                    $is_delDlyFreeze = true;

                    $rs = $basicMStockFreezeLib->unfreeze($dly_item['product_id'], material_basic_material_stock_freeze::__BRANCH, material_basic_material_stock_freeze::__DELIVERY, $params['delivery_id'], $params['branch_id'], material_basic_material_stock_freeze::__STORE_CONFIRM, $dly_item['number']);
                    if ($rs == false) {
                        $err_msg = '门店货品冻结释放失败!';
                        return false;
                    }

                    $rs = $basicMStockLib->unfreeze($dly_item['product_id'], $dly_item['number']);
                    if ($rs == false) {
                        $err_msg = '货品冻结释放失败!';
                        return false;
                    }

                    //门店货品实际库存扣减
                    // $rs = $branchPrdLib->changeStoreConfirmStore($params['branch_id'], $dly_item['product_id'], $dly_item['number'], '-');
                    // if ($rs == false) {
                    //     $err_msg = '门店货品库存扣减失败!';
                    //     return false;
                    // }
                } else {
                    $rs = $basicMStockFreezeLib->unfreeze($dly_item['product_id'], material_basic_material_stock_freeze::__ORDER, 0, $params['order_id'], '', material_basic_material_stock_freeze::__SHARE_STORE, $dly_item['number']);
                    if ($rs == false) {
                        $err_msg = '订单货品冻结释放失败!';
                        return false;
                    }

                    $rs = $basicMStockLib->unfreeze($dly_item['product_id'], $dly_item['number']);
                    if ($rs == false) {
                        $err_msg = '货品冻结释放失败!';
                        return false;
                    }
                }
            }

            //发货单发货，门店仓预占流水删除
            if ($is_delDlyFreeze) {
                $basicMStockFreezeLib->delDeliveryFreeze($params['delivery_id']);
            }

        } else {
            //门店仓不管控库存，释放订单货品冻结，释放货品冻结
            foreach ($params['delivery_items'] as $key => $dly_item) {
                $rs = $basicMStockFreezeLib->unfreeze($dly_item['product_id'], material_basic_material_stock_freeze::__ORDER, 0, $params['order_id'], '', material_basic_material_stock_freeze::__SHARE_STORE, $dly_item['number']);
                if ($rs == false) {
                    $err_msg = '订单货品冻结释放失败!';
                    return false;
                }

                $rs = $basicMStockLib->unfreeze($dly_item['product_id'], $dly_item['number']);
                if ($rs == false) {
                    $err_msg = '货品冻结释放失败!';
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 订单暂停发货单取消节点的库存处理方法
     * 
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     * @return boolean true/false
     */
    public function pauseOrd($params, &$err_msg)
    {
        //门店管控库存
        if ($this->_is_ctrl_store) {
            $branchPrdLib         = kernel::single('o2o_branch_product');
            $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');

            $theOrder = app::get('ome')->model('orders')->db_dump(['order_id'=>$params['order_id']], 'order_bn');
            $obj_bn = $theOrder['order_bn'];
            foreach ($params['delivery_items'] as $key => $dly_item) {
                //识别门店供货货品是否管控库存，管控的：释放门店货品冻结，增加订单货品冻结，不管控的：不做任何处理
                //识别门店货品是否管控库存，不管控直接处理下一个货品
                $is_bm_ctrl_store = $branchPrdLib->isCtrlBmStore($params['branch_id'], $dly_item['product_id']);
                if ($is_bm_ctrl_store) {
                    $rs = $basicMStockFreezeLib->unfreeze($dly_item['product_id'], material_basic_material_stock_freeze::__BRANCH, material_basic_material_stock_freeze::__DELIVERY, $params['delivery_id'], $params['branch_id'], material_basic_material_stock_freeze::__STORE_CONFIRM, $dly_item['number']);
                    if ($rs == false) {
                        $err_msg = '门店货品冻结释放失败!';
                        return false;
                    }

                    $freezeData = [];
                    $freezeData['bm_id'] = $dly_item['product_id'];
                    $freezeData['obj_type'] = material_basic_material_stock_freeze::__ORDER;
                    $freezeData['bill_type'] = 0;
                    $freezeData['obj_id'] = $params['order_id'];
                    $freezeData['shop_id'] = $params['shop_id'];
                    $freezeData['branch_id'] = 0;
                    $freezeData['bmsq_id'] = material_basic_material_stock_freeze::__SHARE_STORE;
                    $freezeData['num'] = $dly_item['number'];
                    $freezeData['obj_bn'] = $obj_bn;
                    $rs = $basicMStockFreezeLib->freeze($freezeData);
                    if ($rs == false) {
                        $err_msg = '订单货品冻结预占失败!';
                        return false;
                    }
                } else {
                    continue;
                }
            }

            //订单暂停发货单取消，门店仓预占流水删除
            $basicMStockFreezeLib->delDeliveryFreeze($params['delivery_id']);
        } else {
            //不管控库存情况下，发货单取消不做任何库存预占释放增加的处理
        }

        return true;
    }

    /**
     * 订单恢复节点的库存处理方法
     * 
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     * @return boolean true/false
     */
    public function renewOrd($params, &$err_msg)
    {
        //和电商仓一样，nothing need to do
    }

    /*
     * 售后：审核换货单库存处理方法
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     */
    public function checkChangeReship($params, &$err_msg)
    {

        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        $branchPrdLib         = kernel::single('o2o_branch_product');
        $basicMaterialStock   = kernel::single('material_basic_material_stock');
        $branch_id            = $params['changebranch_id'];
        //换货单数据
        $mdl_ome_reship = app::get('ome')->model('reship');
        $rs_reship      = $mdl_ome_reship->dump(array("reship_id" => $params["reship_id"]), "shop_id,reship_bn,changebranch_id");

        //换货明细数据
        $change_item = $params['reship_item'];
        if ($change_item) {
            uasort($change_item, [kernel::single('console_iostockorder'), 'cmp_productid']);
            //增加基础物料冻结数
            foreach ($change_item as $item) {
                $rs = $basicMaterialStock->freeze($item['product_id'], $item['num']);
                if ($rs == false) {
                    $err_msg = '货品冻结预占失败!';
                    return false;
                }
            }
        }

        //门店管控库存的做相应处理
        if ($this->_is_ctrl_store) {
            //判断售后换货的信息是否存在
            if (!empty($rs_reship) && !empty($change_item)) {
                //循环换货明细进行冻结预占
                $obj_bn = $rs_reship['reship_bn'];
                foreach ($change_item as $item) {
                    $is_bm_ctrl_store = $branchPrdLib->isCtrlBmStore($branch_id, $item['product_id']);
                    if ($is_bm_ctrl_store) {
                        //门店货品预占冻结
                        $freezeData = [];
                        $freezeData['bm_id'] = $item['product_id'];
                        $freezeData['obj_type'] = material_basic_material_stock_freeze::__BRANCH;
                        $freezeData['bill_type'] = material_basic_material_stock_freeze::__RESHIP;
                        $freezeData['obj_id'] = $params['reship_id'];
                        $freezeData['shop_id'] = $rs_reship['shop_id'];
                        $freezeData['branch_id'] = $branch_id;
                        $freezeData['bmsq_id'] = material_basic_material_stock_freeze::__STORE_CONFIRM;
                        $freezeData['num'] = $item['num'];
                        $freezeData['obj_bn'] = $obj_bn;
                        $rs = $basicMStockFreezeLib->freeze($freezeData);
                        if ($rs == false) {
                            $err_msg = '门店货品冻结预占失败!';
                            return false;
                        }
                    }
                }
            }
        }
    }

    /*
     * 售后：拒绝换货单库存处理方法（质检拒绝质检/wap换货单确认拒绝）
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     */

    public function refuseChangeReship($params, &$err_msg)
    {

        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        $branchPrdLib         = kernel::single('o2o_branch_product');
        $basicMaterialStock   = kernel::single('material_basic_material_stock');

        //换货单数据
        $mdl_ome_reship = app::get('ome')->model('reship');
        $rs_reship      = $mdl_ome_reship->dump(array("reship_id" => $params["reship_id"]), "shop_id,changebranch_id");
        $branch_id      = $params['changebranch_id'];
        //换货明细数据
        $change_item = $params['reship_item'];
        if ($change_item) {
            uasort($change_item, [kernel::single('console_iostockorder'), 'cmp_productid']);
            //释放基础物料冻结数
            foreach ($change_item as $item) {
                $rs = $basicMaterialStock->unfreeze($item['product_id'], $item['num']);
                if ($rs == false) {
                    $err_msg = '货品冻结释放失败!';
                    return false;
                }
            }
        }

        //门店管控库存的做相应处理
        if ($this->_is_ctrl_store) {
            //判断售后换货的信息是否存在
            if (!empty($rs_reship) && !empty($change_item)) {
                //循环换货明细进行冻结释放
                foreach ($change_item as $item) {
                    $is_bm_ctrl_store = $branchPrdLib->isCtrlBmStore($branch_id, $item['product_id']);
                    if ($is_bm_ctrl_store) {
                        //门店货品预占冻结释放
                        $rs = $basicMStockFreezeLib->unfreeze($item['product_id'], material_basic_material_stock_freeze::__BRANCH, material_basic_material_stock_freeze::__RESHIP, $params["reship_id"], $branch_id, material_basic_material_stock_freeze::__STORE_CONFIRM, $item['num']);
                        if ($rs == false) {
                            $err_msg = '门店货品冻结释放失败!';
                            return false;
                        }
                    }
                }
            }
        }

        //删除预占流水
        $basicMStockFreezeLib->delOtherFreeze($params["reship_id"], material_basic_material_stock_freeze::__RESHIP);
    }

    /*
     * 售后：确认退换货单库存处理方法（质检确认收货的退入/wap退换货单确认的退入）
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     */

    public function confirmReshipReturn($params, &$err_msg)
    {

        $branchPrdLib = kernel::single('o2o_branch_product');

        //是否管控库存
        if ($this->_is_ctrl_store) {
            //获取明细数据
            $oReship_item = app::get('ome')->model('reship_items');
            $reship_item  = $oReship_item->getList('num,product_id', array("reship_id" => $params["reship_id"], "return_type" => "return"), 0, -1);
            if (!empty($reship_item)) {
                //获取数据 处理
                foreach ($reship_item as $item) {
                    $is_bm_ctrl_store = $branchPrdLib->isCtrlBmStore($params['branch_id'], $item['product_id']);
                    if ($is_bm_ctrl_store) {
                        //门店货品实际库存增加
                        $rs = $branchPrdLib->changeStoreConfirmStore($params['branch_id'], $item['product_id'], $item['num'], '+');
                        if ($rs == false) {
                            $err_msg = '门店货品库存增加失败!';
                            return false;
                        }
                    }
                }
            }
        }
    }

    /*
     * 售后：确认换货单库存处理方法（质检确认收货的换出/wap换货单确认的换出）
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     */

    public function confirmReshipChange($params, &$err_msg)
    {

        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        $branchPrdLib         = kernel::single('o2o_branch_product');
        $basicMaterialStock   = kernel::single('material_basic_material_stock');

        //换货明细数据
        $change_item = kernel::single('console_reship')->change_items($params["reship_id"]);
        if ($change_item) {
            uasort($change_item, [kernel::single('console_iostockorder'), 'cmp_productid']);
            //释放基础物料冻结数
            foreach ($change_item as $item) {
                $rs = $basicMaterialStock->unfreeze($item['product_id'], $item['num']);
                if ($rs == false) {
                    $err_msg = '货品冻结释放更新失败!';
                    return false;
                }
            }
        }

        //门店管控库存的做相应处理
        if ($this->_is_ctrl_store) {
            //判断售后换货的信息是否存在
            if (!empty($change_item)) {
                //循环换货明细进行冻结释放
                foreach ($change_item as $item) {
                    $is_bm_ctrl_store = $branchPrdLib->isCtrlBmStore($params['changebranch_id'], $item['product_id']);
                    if ($is_bm_ctrl_store) {
                        //门店货品预占冻结释放
                        $rs = $basicMStockFreezeLib->unfreeze($item['product_id'], material_basic_material_stock_freeze::__BRANCH, material_basic_material_stock_freeze::__RESHIP, $params["reship_id"], $params['changebranch_id'], material_basic_material_stock_freeze::__STORE_CONFIRM, $item['num']);
                        if ($rs == false) {
                            $err_msg = '门店货品冻结释放失败!';
                            return false;
                        }
                    }
                }
            }
        }

        //删除预占流水
        $basicMStockFreezeLib->delOtherFreeze($params["reship_id"], material_basic_material_stock_freeze::__RESHIP);
    }

    /*
     * 退换货单回传拒绝换货库存处理方法（门店仓无入口）
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     */
    /**
     * reshipReturnRefuseChange
     * @param mixed $params 参数
     * @param mixed $err_msg err_msg
     * @return mixed 返回值
     */
    public function reshipReturnRefuseChange($params, &$err_msg)
    {}

    /*
     * 最终收货确认由换货变为退货库存处理方法（门店仓无入口）
     * @param array $params 传入参数
     * @param string $err_msg 错误信息
     */

    public function editChangeToReturn($params, &$err_msg)
    {}

    /**
     * 审核采购退货
     */
    public function checkReturned($params, &$err_msg)
    {
        return true;
    }

    /**
     * 完成采购退货
     */
    public function finishReturned($params, &$err_msg)
    {
        return true;
    }

    /**
     * 取消采购退货
     */
    public function cancelReturned($params, &$err_msg)
    {
        return true;
    }

    /**
     * 审核调拨出库单
     */
    public function checkStockout($params, &$err_msg)
    {
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        $basicMaterialStock   = kernel::single('material_basic_material_stock');
        $iso_id               = $params['iso_id'];
        $branch_id            = $params['branch_id'];
        $log_type             = 'other';

        if (empty($branch_id) || empty($params['items'])) {
            $err_msg = '无效操作!';
            return false;
        }
        $nitems = array();

        foreach ($params['items'] as $item) {
            if (isset($nitems[$item['product_id']])) {
                $nitems[$item['product_id']]['nums'] += $item['nums'];
            } else {
                $nitems[$item['product_id']] = array(
                    'product_id' => $item['product_id'],
                    'nums'       => $item['nums'],
                );
            }

        }

        ksort($nitems);
        $isoObj    = app::get('taoguaniostockorder')->model("iso");
        $isoInfo   = $isoObj->dump(array('iso_id'=>$iso_id), 'iso_bn');
        $obj_bn    = $isoInfo['iso_bn'];
        foreach ($nitems as $key => $item) {
            $rs = $basicMaterialStock->freeze($item['product_id'], $item['nums']);
            if ($rs == false) {
                $err_msg = '货品冻结预占失败!';
                return false;
            }
            $freezeData = [];
            $freezeData['bm_id'] = $item['product_id'];
            $freezeData['obj_type'] = material_basic_material_stock_freeze::__BRANCH;
            $freezeData['bill_type'] = material_basic_material_stock_freeze::__STOCKOUT;
            $freezeData['obj_id'] = $iso_id;
            $freezeData['shop_id'] = '';
            $freezeData['branch_id'] = $branch_id;
            $freezeData['bmsq_id'] = material_basic_material_stock_freeze::__STORE_CONFIRM;
            $freezeData['num'] = $item['nums'];
            $freezeData['obj_bn'] = $obj_bn;
            $rs = $basicMStockFreezeLib->freeze($freezeData);
            if ($rs == false) {
                $err_msg = '门店货品冻结预占失败!';
                return false;
            }

        }

        return true;
    }

    /**
     * 最终处理调拨出库单
     */
    public function finishStockout($params, &$err_msg)
    {
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        $basicMaterialStock   = kernel::single('material_basic_material_stock');

        $iso_id     = $params['iso_id'];
        $branch_id  = $params['branch_id'];
        $log_type   = 'other';
        $product_id = $params['product_id'];
        $num        = $params['num'];

        $rs = $basicMaterialStock->unfreeze($product_id, $num);
        if ($rs == false) {
            $err_msg = '货品冻结释放失败!';
            return false;
        }

        $rs = $basicMStockFreezeLib->unfreeze($product_id, material_basic_material_stock_freeze::__BRANCH, material_basic_material_stock_freeze::__STOCKOUT, $iso_id,$branch_id, material_basic_material_stock_freeze::__STORE_CONFIRM, $num);
        if ($rs == false) {
            $err_msg = '仓库货品冻结释放失败!';
            return false;
        }

        return true;
    }

    /**
     * 审核库内转储单
     */
    public function saveStockdump($params, &$err_msg)
    {
        return true;
    }

    /**
     * 最终处理库内转储单
     */
    public function finishStockdump($params, &$err_msg)
    {
        return true;
    }

    /**
     * 审核唯品会出库单
     */
    public function checkVopstockout($params, &$err_msg)
    {
        return true;
    }

    /**
     * 最终处理唯品会出库单
     */
    public function finishVopstockout($params, &$err_msg)
    {
        return true;
    }

    //人工库存预占（暂不支持门店仓）
    public function artificialFreeze($params, &$err_msg)
    {}

    //人工库存预占释放（暂不支持门店仓）
    public function artificialUnfreeze($params, &$err_msg)
    {}

    /**
     * 更新仓库库存
     * 
     * @param array $params (branch_id="仓库ID" product_id="商品ID" nums="100" #数量 operator="+"#增减操作 update_material=true #是否更新物料)
     * @return boolean
     * @author
     * */
    public function changeStore($params, &$err_msg)
    {
        if (!$this->_is_ctrl_store) {
            return true;
        }

        $branchPrdLib = kernel::single('o2o_branch_product');
        if (!$branchPrdLib->isCtrlBmStore($params['branch_id'], $params['product_id'])) {
            return true;
        }

        $o2oProStoreMdl = app::get('o2o')->model('product_store');
        if (!$o2oProStoreMdl->count(array('branch_id' => $params['branch_id'], 'bm_id' => $params['product_id']))) {
            $data = array(
                'branch_id' => $params['branch_id'],
                'bm_id'     => $params['product_id'],
            );

            $o2oProStoreMdl->insert($data);
        }

        $rs = $branchPrdLib->changeStoreConfirmStore($params['branch_id'], $params['product_id'], $params['nums'], $params['operator']);

        if ($rs == false) {
            $err_msg = '门店货品库存扣减失败';
        }

        return $rs;
    }

    /**
     *  获取可用库存
     *  @param  branch_id="仓库id" product_id="商品id"
     */
    public function getAvailableStore($params, &$err_msg)
    {

        $productStoreObj = app::get('o2o')->model('product_store');

        $storeList = $productStoreObj->getList('bm_id, branch_id, store, store_freeze, share_store, share_freeze', array('branch_id' => $params['branch_id'], 'bm_id' => $params['product_id']));

        return $storeList[0]['store'] - $storeList[0]['store_freeze'];
    }

    /**
     * 新增冻结库存
     * 
     */
    public function changeArriveStore($params, &$err_msg)
    {
        return true;
    }
    #释放在途
    public function deleteArriveStore($params, &$err_msg)
    {
        return true;
    }

    /**
     *  获取库存
     *  @param  branch_id="仓库id" product_id="商品id"
     */
    public function getStoreByBranch($params, &$err_msg)
    {

        $bpModel = app::get('o2o')->model('product_store');

        $branch = $bpModel->dump(array('bm_id' => $params['product_id'], 'branch_id' => $params['branch_id']), 'store');

        return $branch['store'];
    }

    public function cmp_by_bm_id($a, $b) {
        if($a['bm_id'] == $b['bm_id']) {
            return 0;
        }
        return $a['bm_id'] < $b['bm_id'] ? -1 : 1;
    }
    /**
     * 生成差异单
     */
    public function addDifference($params, &$err_msg)
    {
      
        $difference_id = $params['difference']['id'];
        $branch_id = $params['difference']['branch_id'];
        $obj_bn    = $params['difference']['diff_bn'];
        $sub_bill_type = '';

        if (empty($branch_id) || empty($params['items'])) {
            $err_msg = '无效操作!';
            return false;
        }
        $nitems = $params['items'];
        uasort($nitems, [$this, 'cmp_by_bm_id']);

        foreach ($nitems as $key => $item) {
           
            $rs = $this->_basicMaterialStock->freeze($item['bm_id'], $item['freeze_num']);

         
            if ($rs == false) {
                $err_msg = $item['material_bn'].':货品冻结预占失败!';
                return false;
            }

            $freezeData = [];
            $freezeData['bm_id'] = $item['bm_id'];
            $freezeData['obj_type'] = material_basic_material_stock_freeze::__BRANCH;
            $freezeData['bill_type'] = material_basic_material_stock_freeze::__DIFFERENCEOUT;
            $freezeData['obj_id'] = $difference_id;
            $freezeData['shop_id'] = '';
            $freezeData['branch_id'] = $item['branch_id'];
            $freezeData['bmsq_id'] = material_basic_material_stock_freeze::__STORE_CONFIRM;
            $freezeData['num'] = $item['freeze_num'];
            $freezeData['log_type'] = 'difference';
            $freezeData['obj_bn'] = $obj_bn;
            $freezeData['sub_bill_type'] = $sub_bill_type;
            $rs = $this->_basicMStockFreezeLib->freeze($freezeData);
            if ($rs == false) {
                $err_msg = $item['material_bn'].':仓库货品冻结预占失败!';
                return false;
            }
        }

        return true;
    }
    /**
     * 确定差异单
     */
    public function confirmDifference($params, &$err_msg)
    {
      
        $difference_id = $params['diff_id'];
        $branch_id = $params['branch_id'];

        if (empty($branch_id) || empty($params['items'])) {
            $err_msg = '无效操作!';
            return false;
        }
        $nitems = $params['items'];
        uasort($nitems, [$this, 'cmp_by_bm_id']);
        foreach ($nitems as $key => $item) {
           
            $rs = $this->_basicMaterialStock->unfreeze($item['bm_id'], $item['freeze_num']);
            if ($rs == false) {
                $err_msg = $item['material_bn'].':货品冻结释放失败!';
                return false;
            }
            $rs = $this->_basicMStockFreezeLib->unfreeze($item['bm_id'], material_basic_material_stock_freeze::__BRANCH, material_basic_material_stock_freeze::__DIFFERENCEOUT, $difference_id, $item['branch_id'], material_basic_material_stock_freeze::__STORE_CONFIRM, $item['freeze_num'], 'difference');
            if ($rs == false) {
                $err_msg = $item['material_bn'].':仓库货品冻结释放失败!';
                return false;
            }
        }

        return true;
    }
    /**
     * 取消差异单
     */
    public function cancelDifference($params, &$err_msg)
    {
        
        $difference_id = $params['id'];
        $branch_id = $params['branch_id'];

        if (empty($branch_id) || empty($params['items'])) {
            $err_msg = '无效操作!';
            return false;
        }
        $nitems = $params['items'];
        uasort($nitems, [$this, 'cmp_by_bm_id']);
        foreach ($nitems as $key => $item) {

            $rs = $this->_basicMaterialStock->unfreeze($item['bm_id'], $item['freeze_num']);
            if ($rs == false) {
                $err_msg = $item['material_bn'].':货品冻结释放失败!';
                return false;
            }
            $rs = $this->_basicMStockFreezeLib->unfreeze($item['bm_id'], material_basic_material_stock_freeze::__BRANCH, material_basic_material_stock_freeze::__DIFFERENCEOUT, $difference_id, $item['branch_id'], material_basic_material_stock_freeze::__STORE_CONFIRM, $item['freeze_num'], 'difference');
            if ($rs == false) {
                $err_msg = $item['material_bn'].':仓库货品冻结释放失败!';
                return false;
            }
        }
        $this->_basicMStockFreezeLib->delOtherFreeze($difference_id, material_basic_material_stock_freeze::__DIFFERENCEOUT);

        return true;
    }

    /**
     * 获取StoreByBranchId
     * @param mixed $branch_id ID
     * @return mixed 返回结果
     */
    public function getStoreByBranchId($branch_id){


        $storeMdl = app::get('o2o')->model('store');

        $branchs = $storeMdl->db->selectrow("SELECT store_id FROM sdb_ome_branch WHERE branch_id=".$branch_id."");
        $store_id = $branchs['store_id'];
        if($store_id){
            $stores = $storeMdl->db_dump(array('store_id'=>$store_id),'store_bn,store_id');
        }else{
            $stores = $storeMdl->db_dump(array('branch_id'=>$branch_id),'store_bn,store_id');
        }
        
        return $stores;
    }
}
