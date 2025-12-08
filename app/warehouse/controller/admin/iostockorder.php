<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 转仓单
 * 20180525 by wangjianjun
 */
class warehouse_ctl_admin_iostockorder extends desktop_controller{

    function index(){
        $params = array(
            'actions' => array(
                array(
                    'label'=>'新建',
                    'href'=>'index.php?app=warehouse&ctl=admin_iostockorder&act=iostock_add&p[0]=1',
                    'target'=>'_blank'
                ),
                array('label' => '重新推送',
                            'submit' => 'index.php?app=warehouse&ctl=admin_iostockorder&act=batch_sync',
                            'confirm' => '你确定要对勾选的单据重新推送吗？',
                            'target' => 'refresh'),
            ),
            'title'=>"转仓单",
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>false,
            'orderBy' => 'iso_id DESC',
        );
        /* 获取操作员管辖仓库 */
        $oBranch = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $oIso = app::get('warehouse')->model('iso');
                $iso_list = $oIso->getList('iso_id', array('branch_id'=>$branch_ids), 0,-1);
                if ($iso_list){
                    foreach ($iso_list as $p){
                        $isolist[] = $p['iso_id'];
                    }
                }
                if ($isolist){
                    $isolist = array_unique($isolist);
                    $params['base_filter']['iso_id'] = $isolist;
                }else{
                    $params['base_filter']['iso_id'] = 'false';
                }
            }else{
                $params['base_filter']['iso_id'] = 'false';
            }
        }
        
        $this->finder('warehouse_mdl_iso', $params);
    }
    
    //新建页面展示
    function iostock_add($io){
        $order_label = '转仓单';
        $suObj = app::get('purchase')->model('supplier');
        $brObj = app::get('ome')->model('branch');
        $row = $brObj->getList('branch_id, name',array('b_type'=>1),0,-1);
        
        $this->pagedata['io'] = $io; //按原有入库单业务逻辑 这里写死1 无需走上面的获取branch_list的代码
        $this->pagedata['is_super'] = 1; //按原有入库单业务逻辑 这里写死1 无需走上面的获取branch_list的代码
        $this->pagedata['supplier'] = $suObj->getList('supplier_id, name','',0,-1);
        $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['branch'] = $row;
        $this->pagedata['cur_date'] = date('Ymd',time()).$order_label;
        $iostock_types = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type($io);
        //这里目前只要转仓入库的类型
        foreach ($iostock_types as $key_it => $value_it){
            if($key_it == 1000){
                $this->pagedata['iostock_types'][$key_it] = $value_it;
            }
        }
        #外部仓库列表
        $oExtrabranch = app::get('ome')->model('extrabranch');
        $extrabranch = $oExtrabranch->getlist('branch_id,name','',0,-1);
        $this->pagedata['extrabranch'] = $extrabranch;
        $this->singlepage("admin/iostock/instock_add.html");
    }
    
    //执行新建
    function do_save_iostockorder(){
        $this->begin();

        $_POST['iso_price'] = $_POST['iso_price'] ? $_POST['iso_price'] : 0;
        if(!$_POST['bn']) {
            $this->end(false, '请先选择入库商品！.');
        }
        if(!$_POST['supplier_id']) {
            $this->end(false, '请先选择供应商！.');
        }
        //判断类型是否是残损
        $branch_id = $_POST['branch'];
        $branch_detail = kernel::single('console_iostockdata')->getBranchByid($branch_id);
        if ($branch_detail['type']=='damaged'){
            $this->end(false, '出入库类型不为残损出入库,不可以选择残仓!');
        }
        $products = array();
        foreach($_POST['bn'] as $product_id=>$bn){
            if($_POST['at'][$product_id] == 0) {
                $this->end(false, '库存数量不能为0.');
            }
            $products[$product_id] = array('bn'=>$bn,
                    'nums'=>$_POST['at'][$product_id],
                    'unit'=>$_POST['unit'][$product_id],
                    'name'=>$_POST['product_name'][$product_id],
                    'price'=>$_POST['pr'][$product_id],
            );
        }
        $_POST['products'] = $products;
        $iso_id = kernel::single('console_iostockorder')->save_warehouse_iostockorder($_POST,$msg);
        if ($iso_id){
            $this->end(true, '保存完成');
        }else {

            $this->end(false, '保存失败', '', array('msg'=>$msg));
        }
    }
    
    //取消展示页面
    function cancel($iso_id){
        $isoObj = app::get('warehouse')->model('iso');
        #库存状态判断
        $iso = $isoObj->dump($iso_id,'iso_bn,iso_id,type_id');
        $title = '转仓入库';
        $this->pagedata['iso'] = $iso;
        $this->pagedata['io'] = 1;
        $this->pagedata['title'] = $title;
        $this->display("admin/iostock/stock_cancel.html");
    }
    
    //执行取消
    function doCancel(){
        $this->begin();
        $iso_id = $_POST['iso_id'];
        $isoObj = app::get('warehouse')->model('iso');
        $iso = $isoObj->dump($iso_id,'iso_status');
        if ($iso['iso_status']>1){
            $this->end(false,'取消失败!');
        }else{
            $isoObj->update(array('iso_status'=>4),array('iso_id'=>$iso_id));
            $this->end(true,'成功');
        }
    }
    
    //确认是否可以取消
    function checkCancel($iso_id){
        $isoObj = app::get('warehouse')->model('iso');
        $iso = $isoObj->dump($iso_id,'iso_bn,iso_id,type_id,branch_id,check_status,iso_status,out_iso_bn');
        if ($iso['check_status'] == '2'){ //已审
            if ($iso['iso_status']>1) {
                $result = array('rsp'=>'fail','err_msg'=>'单据所在状态不允许此次操作');
            }else{
                $data = array(
                    'io_type'=>'DIRECT',
                    'io_bn'=>$iso['iso_bn'],
                    'out_iso_bn'=>$iso['out_iso_bn'],
                    'branch_id'=>$iso['branch_id']
                );
                $result = kernel::single('console_event_trigger_warehousestockin')->cancel($data, true);
            }
        }else{ //未审
            $result = array('rsp'=>'succ');
        }
        echo json_encode($result);
    }

    //审核展示页面
    /**
     * 检查
     * @param mixed $iso_id ID
     * @return mixed 返回验证结果
     */
    public function check($iso_id){
        $basicMaterialBarcode = kernel::single('material_basic_material_barcode');
        $isoObj = app::get('warehouse')->model('iso');
        $suObj = app::get('purchase')->model('supplier');
        $oExtrabranch = app::get('ome')->model('extrabranch');
        $brObj = app::get('ome')->model('branch');
        
        $iso = $isoObj->dump($iso_id,'*',array('iso_items'=>array('*')));
        $extrabranch = $oExtrabranch->dump($iso['extrabranch_id'],'name');
        $total_num=0;
        if ($iso['iso_items']){
            foreach($iso['iso_items'] as $k=>$v){
                $total_num+=$v['nums'];
                #查询关联的条形码
                $iso['iso_items'][$k]['barcode']    = $basicMaterialBarcode->getBarcodeById($v['product_id']);
            }
        }
        
        $su = $suObj->dump($iso['supplier_id'],'name');
        $br = $brObj->dump($iso['branch_id'], 'name');
        $iso['iso_id']   = $iso_id;
        $iso['branch_name']   = $br['name'];
        $iso['supplier_name'] = $su['name'];
        $iso['create_time'] = date("Y-m-d", $iso['create_time']);
        $iso['total_num'] = $total_num;
        $iso['memo'] = $iso['memo'];
        $iso['extrabranch_name'] = $extrabranch['name'];
        $this->pagedata['iso'] = $iso;
        $this->pagedata['io'] = 1;
        $this->pagedata['amount'] = $iso['product_cost'] + $iso['iso_price'];
        $this->singlepage('admin/iostock/stock_check.html');
    }
    
    //审核
    /**
     * doCheck
     * @return mixed 返回值
     */
    public function doCheck(){
        $this->begin();
        #更新单据审核状态
        $iso_id = intval( $_POST['iso_id'] );
        $isoObj = app::get('warehouse')->model('iso');
        #库存状态判断
        $iso = $isoObj->dump($iso_id,'check_status');
        if ($iso['check_status']!='1'){
            $this->end(false,'此单据已审核!');
        }
        $iso_data = array('check_status'=>'2');
        $result = $isoObj->update($iso_data,array('iso_id'=>$iso_id));
        if ($result){
            kernel::single('console_event_trigger_warehousestockin')->create(array('iso_id'=>$iso_id),false);
            $this->end(true,'审核成功');
        }else{
            $this->end(false, '审核失败');
        }
        
    }
    
    //出入库单残损确认
    function doDefective($iso_id){
        $iso_itemsObj = app::get('warehouse')->model('iso_items_simple');
        $iso_items = $iso_itemsObj->getlist('*',array('iso_id'=>$iso_id,'defective_num|than'=>'0'),0,-1);
        $iso = array();
        $iso['iso_id'] = $iso_id;
        $iso['iso_items'] = $iso_items;
        $this->pagedata['iso'] = $iso;
        $this->singlepage('admin/iostock/stock_defective.html');
    }
    
    //残损确认
    function doDefectiveconfirm(){
        $this->begin("index.php?app=warehouse&ctl=admin_iostockorder");

        $iso_id = intval($_POST['iso_id']);
        $oIso = app::get('warehouse')->model("iso");
        $oIsoItems = app::get('warehouse')->model("iso_items_simple");
        $iostockObj = kernel::single('console_iostockdata');
        $iso = $oIso->dump(array('iso_id'=>$iso_id),'branch_id,iso_bn,type_id,iso_id,supplier_id,supplier_name,cost_tax,oper,create_time,operator,defective_status');
        if ($iso['defective_status']!='1'){
            $this->end(false,'此单据已确认或无需确认!');
        }
        $damagedbranch = $iostockObj->getDamagedbranch($iso['branch_id']);
        if( empty($damagedbranch) ){
            $this->end(false,$item['bn'].'有不良品，但未设置主仓对应的残仓');
        }
        $branch_id = $damagedbranch['branch_id'];
        #查询是否有不良品
        $iostock_data = array(
            'type_id' => '50',
            'branch_id' => $branch_id,
            'iso_bn' => $iso['iso_bn'],
            'iso_id' => $iso['iso_id'],
            'supplier_id' => $iso['supplier_id'],
            'supplier_name' => $iso['supplier_name'],
            'cost_tax' => $iso['cost_tax'],
            'oper' => $iso['oper'],
            'create_time' => $iso['create_time'], 
            'original_bn' => $iso['iso_bn'],
            'original_id' => $iso['iso_id'],
            'orig_type_id' => $iso['type_id'],
        );
        $iso_items = $oIsoItems->getList('bn,price,defective_num,iso_items_simple_id',array('iso_id'=>$iso_id));
        $items_data = array();
        foreach($iso_items as $item){
            if($item['defective_num'] > 0 ){
                $items[] = array(
                    'bn' => $item['bn'],
                    'nums' => $item['defective_num'],
                    'price' => $item['price'],
                    'iso_items_id' => $item['iso_items_simple_id']
                );
            }
        }
        if (count($items)>0){
            $iostock_data['items'] = $items;
            $result = kernel::single('console_iostockorder')->confirm_iostockorder($iostock_data,'50',$msg);
            if($result){
                #更新确认状态
                $io_update_data = array(
                    'defective_status'=>'2',
                );
                $oIso->update($io_update_data,array('iso_id'=>$iso_id));
                $this->end(true,'成功');
            }else{

                $this->end(false,'残损确认失败!');
            }
        }else{
            $this->end(false,'没有可确认的货品');
        }
    }
    
    //差异查看确认
    function difference($iso_id){
        $isoObj = app::get('warehouse')->model('iso');
        $iso = $isoObj->dump($iso_id,'*');
        $stockObj = kernel::single('console_receipt_stock');
        //获取差异数据
        $sql = 'SELECT i.nums,i.normal_num,i.defective_num,i.bn, p.material_name AS name FROM sdb_warehouse_iso_items_simple as i
                LEFT JOIN sdb_material_basic_material as p ON i.bn=p.material_bn
                WHERE i.iso_id='.$iso_id.' AND (i.normal_num!=i.nums OR i.defective_num>0)';
        $iso['iso_items'] = $isoObj->db->select($sql);
        $this->pagedata['iso'] = $iso;
        $this->singlepage('admin/iostock/stock_difference.html');
    }
    
    //编辑展示页面（暂留）
    function iostock_edit($iso_id){
        $order_label = '转仓单';
        
        //获取出入库单信息
        $isoObj = app::get('warehouse')->model('iso');
        $data = $isoObj->dump($iso_id, '*', array('iso_items' => array('*')));
        
        echo "<pre>";
        var_dump($data);
        exit;
        
        $productIds = array();
        $product_cost = 0;
        foreach($data['iso_items'] as $k=>$v){
            $productIds[] = $v['product_id'];
            $total_num+=$v['nums'];
            $product_cost+=sprintf('%.3f',$v['nums']*$v['price']);
        }
        $data['total_num'] = $total_num;
        $data['items'] = implode('-',$productIds);
        $data['product_cost'] = $product_cost;
        //获取仓库信息
        $branchObj = app::get('ome')->model('branch');
        $branch   = $branchObj->dump(array('branch_id'=>$data['branch_id']),'branch_id, name');
        $data['branch_name'] = $branch['name'];
        
        //获取出入库类型信息
        $iostockTypeObj = app::get('ome')->model('iostock_type');
        $iotype = $iostockTypeObj->dump(array('type_id'=>$data['type_id']),'type_name');
        $data['type_name'] = $iotype['type_name'];
        
        $operator = kernel::single('desktop_user')->get_name();
        $data['oper'] = $data['oper'] ? $data['oper'] : $operator;
        #外部仓库列表
        $oExtrabranch = app::get('ome')->model('extrabranch');
        $extrabranch = $oExtrabranch->getlist('branch_id,name','',0,-1);
        $this->pagedata['extrabranch'] = $extrabranch;
        #
        $oDly_corp = app::get('ome')->model('dly_corp');
        $dly_corp = $oDly_corp->getlist('*',array('disabled'=>'false'));
        $this->pagedata['dly_corp'] = $dly_corp;
        $this->pagedata['io'] = $io;
        $this->pagedata['act'] = $act;
        $this->pagedata['iso'] = $data;
        $this->pagedata['order_label'] = $order_label;
        $this->pagedata['act_status'] = trim($_GET['act_status']);
        $this->singlepage("admin/iostock/instock_edit.html");
    }

    /**
     * batch_sync
     * @return mixed 返回值
     */
    public function batch_sync(){
        // $this->begin('');
        $ids = $_POST['iso_id'];
       
        $isoObj = app::get('warehouse')->model('iso');

               
        if (!empty($ids)) {
            foreach ($ids as  $iso_id) {
                $iso = $isoObj->dump(array('iso_id'=>$iso_id,'check_status'=>'2'),'iso_id');
                if ($iso){
                    kernel::single('console_event_trigger_warehousestockin')->create(array('iso_id'=>$iso_id),false);
                }
            }
        }
        $this->splash('success', null, '命令已经被成功发送！！');
    }
    
}
?>
