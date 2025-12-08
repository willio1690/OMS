<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 是否能到判断
 *
 * 
 * 
 */
class omeauto_auto_plugin_arrived extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {


    public $address = '';
    public $area = '';
    public static $corpList ='';
    public $corp = '';
    public $apiurl = '';
    /**
     * 状态码
     * 
     * @var Integer
     */
    protected $__STATE_CODE = omeauto_auto_const::_LOGIST_ARRIVED;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles=null) {

        $corp = $group->getDlyCorp();

        //如果门店配送方式，不检查是否到不到
        if (app::get('o2o')->is_installed()) {
            if($corp['type'] == 'o2o_ship'){
                return true;
            }
        }

        $arrived_conf = app::get('ome')->getConf('ome.logi.arrived');
        $arrived_auto_conf = app::get('ome')->getConf('ome.logi.arrived.auto');
        $allow = true;
        $msg = '';
        if ($arrived_conf=='1' && $arrived_auto_conf=='true') {
            $arrOrder = [];
            foreach ($group->getOrders() as $order ) {
                if(in_array($order['shop_type'], $this->getShopType()) && $order['createway'] == 'matrix') {
                    $arrOrder[] = $order;
                }
            }
            if($arrOrder) {
                $shopId = array_unique(array_column($arrOrder, 'shop_id'));
                if(count($shopId) > 1) {
                    #多店铺无法使用
                    return true;
                }
                $branch = app::get('ome')->model('branch')->db_dump(['branch_id'=>$group->getBranchId(), 'check_permission'=>'false']);
                $data = [
                    'orders'=>$arrOrder,
                    'branch'=>$branch,
                ];
                $result = kernel::single('erpapi_router_request')->set('shop', $shopId[0])->logistics_addressReachable($data);

                if ($result['rsp']!='succ' || !$result['data'][$corp['type']]['is_deliverable']) {
                    $allow = false;
                    $msg = '订单快递不到';
                    foreach ($group->getOrders() as $order ) {
                        $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
                    }
                }
            }
        }

        if ( !$allow ) {
            $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName(), $msg);
        }
    }

    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {

        return '物流到不到';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {
        return array('color' => 'orange', 'flag' => '到', 'msg' => '物流公司不到');
    }

     /**
      *目前支持到不到物流查询列表（弃用）
      * @param 
      * @return  
      * @access  public
      * @author sunjing@shopex.cn
      */
     public function getCheckCorp()
     {
         $corp = array('POSTB','POST','EYB','EMS','GTO','HTKY','FAST','QFKD','UC','YTO','ZJS','ZTO','SF','TTKDEX','YUNDA','BEST','STO','DBKD');
         return $corp;
     }

     public function getShopType() {
        $shopType = ['luban'];
        return $shopType;
     }
}