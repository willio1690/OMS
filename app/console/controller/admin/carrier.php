<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会承运商管理
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 */
class console_ctl_admin_carrier extends desktop_controller{
    
    var $workground = "console_purchasecenter";
    
    function index()
    {
        $this->title = '承运商列表';
        $filter      = array();
        
        $params = array(
                'title'=>$this->title,
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'base_filter' => $filter,
                'actions'=>array(
                        array('label'=>'同步承运商','href'=>'index.php?app=console&ctl=admin_carrier&act=sync&finder_id='.$_GET['finder_id']),
                ),
        );
        
        $this->finder('console_mdl_carrier', $params);
    }
    
    //同步
    /**
     * sync
     * @return mixed 返回值
     */

    public function sync()
    {
        $this->begin('index.php?app=console&ctl=admin_carrier&act=index');
        
        //唯品会店铺
        $purchaseLib  = kernel::single('purchase_purchase_order');
        $shopList     = $purchaseLib->get_vop_shop_list();
        
        if($shopList)
        {
            foreach ($shopList as $key => $val)
            {
                $shop_id    = $val['shop_id'];
                $param      = array('page'=>1, 'limit'=>100);
                
                $multi_param    = array('carrierRequest'=>$param);
                
                $rsp      = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getCarrierList($multi_param);
            }
        }
        
        $this->end(true, app::get('base')->_('发送成功'));
    }
}