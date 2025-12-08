<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_ctl_admin_order extends desktop_controller
{
    function _views()
    {
        $orderObj = app::get('o2o')->model('performance_orders');
        
        //o2o门店物流公司
        $dlyCorpObj    = app::get('ome')->model('dly_corp');
        $corpList      = $dlyCorpObj->getList('corp_id', array('d_type'=>2));
        
        $logi_ids      = array();
        foreach ($corpList as $key => $val)
        {
            $logi_ids[]    = $val['corp_id'];
        }
        
        //filter
        $base_filter = array('logi_id'=>$logi_ids, 'disabled'=>'false');
        $sub_menu    = array(
                            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter, 'optional'=>false),
                            1 => array('label'=>app::get('base')->_('已分派未接单'),'filter'=>array_merge($base_filter, array('store_process_status'=>'1')), 'optional'=>false),
                            2 => array('label'=>app::get('base')->_('已拒绝'),'filter'=>array_merge($base_filter, array('store_process_status'=>'2')), 'optional'=>false),
                            3 => array('label'=>app::get('base')->_('已接单'),'filter'=>array_merge($base_filter, array('store_process_status'=>'3')), 'optional'=>false),
                            4 => array('label'=>app::get('base')->_('已发货未核销'),'filter'=>array_merge($base_filter, array('store_process_status'=>'4')),'optional'=>false),
                            5 => array('label'=>app::get('base')->_('已核销'),'filter'=>array_merge($base_filter, array('store_process_status'=>'5')),'optional'=>false),
        );

        //check shop permission
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        
        foreach ($sub_menu as $k => $v)
        {
            if($organization_permissions){
                $v['filter']['org_id'] = $organization_permissions;
            }

            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $orderObj->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=o2o&ctl='. $_GET['ctl'] .'&act='. $_GET['act'] .'&flt='. $_GET['flt'] .'&view='. $k;
        }
        
        return $sub_menu;
    }
    
    function index() {
        $op_id    = kernel::single('desktop_user')->get_id();
        $title = '门店履约订单';
        $base_filter      = array();
        
        $sub_menu    = $this->_views();
        foreach ($sub_menu as $key => $value)
        {
            if($_GET['view'] == $key)
            {
                $base_filter    = $value['filter'];
            }
        }
        

        //check shop permission
        $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        if($organization_permissions){
            $base_filter['org_id'] = $organization_permissions;
        }

        $params = array(
                    'title' => $title,
                    'use_buildin_new_dialog' => false,
                    'use_buildin_set_tag'=>false,
                    'use_buildin_recycle'=>false,
                    'use_buildin_export'=>false,
                    'use_buildin_import'=>false,
                    'use_buildin_filter'=>true,
                    'use_view_tab'=>true,
                    'finder_aliasname' => 'order_view'.$op_id,
                    'finder_cols' => 'order_bn,shop_id,total_amount,column_print_status,process_status,is_cod,pay_status,ship_status,payment,shipping,logi_id,logi_no,createtime,paytime,mark_type',
                    'base_filter' => $base_filter,
        );
        $this->finder('o2o_mdl_performance_orders', $params);
    }

    /**
     * 添加
     * @return mixed 返回值
     */
    public function add() {
        $shopFilter = array(
            'branch_id|than' => 0
        );
        if(!kernel::single('desktop_user')->is_super()) {
            // 使用新的权限继承服务获取门店权限
            $permissionService = kernel::single('organization_organization_permission');
            $branchIds = $permissionService->expandUserBranchIds(kernel::single('desktop_user')->get_id(), 'offline');
            $shopFilter['branch_id'] = $branchIds;
        }
        $paymentsCfg = app::get('ome')->model('payment_cfg')->getList('custom_name');
        $this->pagedata['payment_list'] = array_unique(array_map('current', $paymentsCfg));
        
        // 获取门店数据并转换为options格式
        $shopData = app::get('o2o')->model('store')->getList('shop_id,name', $shopFilter);
        $this->pagedata['shopData'] = array_column($shopData, 'name', 'shop_id');
        
        $this->pagedata['title'] = '新建门店订单';
        $this->page('admin/order/add.html');
    }

    /**
     * doAdd
     * @return mixed 返回值
     */
    public function doAdd() {
        $data = $_POST;
        $data['mobile'] = trim($data['mobile']);
        $url = 'index.php?app=o2o&ctl=admin_order&act=add';
        $this->begin($url);
        if(empty($data['price'])) {
            $this->end(false, '没有选择商品明细');
        }
        if (!$data['area'] || 'mainland://:' == substr($data['area'],0,12)) {
            $this->end(false, '请正确填写配送三级区域');
        }
        if ($data['payed'] < 0) {
            $this->end(false, '实收金额不能为负');
        }
        foreach ($data['sale_price'] as $k => $sale_price) {
            if ($sale_price<0) {
                $this->end(false, '销售物料销售价不能为负');
            }
        }

        $o2oOrder = kernel::single('o2o_order');
        list($rs, $msg) = $o2oOrder->createOrder($data);
        if(!$rs) {
            $this->end(false, $msg);
        }
        #提交事务，避免自动审单找不到订单
        $this->endonly(true);
        $o2oOrder->doPay($data);
        $o2oOrder->addConsignDelivery($data);
        $this->splash('success', $url, '操作完成');
    }

    /**
     * 获取Consignee
     * @return mixed 返回结果
     */
    public function getConsignee() {
        $mobile = $_POST['mobile'];
        $modelOrder = app::get('ome')->model('orders');
        $order = $modelOrder->db_dump(array('ship_mobile'=>$mobile));
        if(empty($order)) {
            $modelOrder = app::get('archive')->model('orders');
            $order = $modelOrder->db_dump(array('ship_mobile'=>$mobile));
        }
        $address = '';
        if($order) {
            $params = array(
                'app' => 'eccommon',
                'required' => 'true',
                'name' => 'area',
                'value' => $order['ship_area']
            );
            $address = kernel::single('eccommon_view_input')->input_region($params);
            $address .= ' <input type="text" name="addr"  vtype="required" value="'.$order['ship_addr'].'" />';
        }
        echo json_encode(array('rsp'=>'succ', 'data'=>$order, 'address'=>$address));
    }

    /**
     * 获取Product
     * @return mixed 返回结果
     */
    public function getProduct() {

        $sales_material_bn = $_POST['sales_material_bn'];
        if($sales_material_bn){
           $filter = array(
               'sales_material_bn'=>$sales_material_bn
           );
        } else {
            echo json_encode(array('rsp'=>'fail', 'msg'=>'销售物料编码为空'));
            exit();
        }

        $salesMaterialObj = app::get('material')->model('sales_material');
        $salesMaterialExtObj = app::get('material')->model('sales_material_ext');
        $salesMStockLib = kernel::single('material_sales_material_stock');

        $filter['use_like'] = 1;
        $data = $salesMaterialObj->getList('sm_id,sales_material_name,sales_material_bn,sales_material_type',$filter,0,1);

        if (!empty($data)){
            $item = $data[0];
            $store = $salesMStockLib->getSalesMStockById($item['sm_id']);
            $ExtInfo = $salesMaterialExtObj->db_dump($item['sm_id'],'retail_price');

            $item['store'] = $store;
            $item['num'] = 1;
            $item['price'] = $ExtInfo['retail_price'];
            if($item["sales_material_type"] == 4){ //手工新建订单 福袋类型不能修改售价price
                $item["tpl_price_readonly"] = "readonly";
            }
            $item['product_id'] = $item['sm_id'];
            $item['sales_material_bn'] = $item['sales_material_bn'];
            $item['name'] = $item['sales_material_name'];
            // $item['barcode'] = (string)$_code_list[$item['sm_id']];
        } else {
            echo json_encode(array('rsp'=>'fail', 'msg'=>'没有该物料'));
            exit();           
        }

        echo json_encode(array('rsp'=>'succ', 'data'=>$item));
    }

}