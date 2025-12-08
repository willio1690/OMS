<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

//pda出入库查询接口
class openapi_api_function_v1_pda_iostock extends openapi_api_function_v1_pda_abstract {
    private  $__event_trigger = array( 
        'in'=> array('purchase' => 'purchase_event_receive_purchase','other'=> 'taoguaniostockorder_event_receive_iostock'),
        'out'=>array('purchase_return' => 'purchase_event_receive_purchasereturn','other'=>'taoguaniostockorder_event_receive_iostock')
    );
    private  $_type_relation = array(
        1=>array('type'=>'in','io_type'=>'purchase'),#采购入库
        10=>array('type'=>'out','io_type'=>'purchase_return'),#采购退货
        4=>array('type'=>'in','io_type'=>'other'),#调拨入库
        40=>array('type'=>'out','io_type'=>'other'),#调拨出库
        7=>array('type'=>'out','io_type'=>'other'),#直接出库
        70=>array('type'=>'in','io_type'=>'other'),#直接入库
        300=>array('type'=>'out','io_type'=>'other'),#样品出库
        400=>array('type'=>'in','io_type'=>'other'), #样品入库
        700=>array('type'=>'out','io_type'=>'other'), #分销出库
        800=>array('type'=>'in','io_type'=>'other'), #分销入库
    );
    
    /**
     * 获取List
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回结果
     */
    public function getList($params,&$code,&$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $page_no = intval($params['page_no']) > 0 ? intval($params['page_no']) : 1;
        $limit = (intval($params['page_size']) > 100 || intval($params['page_size']) <= 0) ? 100 : intval($params['page_size']);
        if($page_no == 1){
            $offset = 0;
        }else{
            $offset = ($page_no-1)*$limit;
        }
        if(empty($params['io_bn'])){
            if(empty($params['start_time']) || empty($params['end_time'])){
                $sub_msg ='请填写查询时间段';
                return false;
            }
        }
        $support_type_id = $this->get_support_type_id();
        if(!in_array($params['io_type'], $support_type_id)){
            $sub_msg = '类型不正确！请填数字：1：采购入库；10：采购退货;4：调拨入库;40：调拨出库;7：直接出库;70：直接入库;300：样品出库;400：样品入库;700：分销出库;800：分销入库';
            return false;
        }
        if(in_array($params['io_type'],array('1','10')) ){
            #采购入库
            $data = $this->get_purchase_data($params,$code,$sub_msg,$offset, $limit);
        }else{
            #其他入库和调拨入库
            $data = $this->get_taoguaniostockorder_data($params,$code,$sub_msg,$offset, $limit);
        }
        return $data;
    }
    #获取支持的出入库类型
    function get_support_type_id($type){
        $support_type_id  = array('purchase'=>array('1','10'),'taoguaniostockorder'=>array('4','40','7','70','300','400','700','800'));
        if($type){
            return  $support_type_id[$type];
        }
        return array_merge($support_type_id['purchase'],$support_type_id['taoguaniostockorder']);
    }
    #获取采购单(采购入库和采购退货)
    function get_purchase_data($params,&$code,&$sub_msg,$offset, $limit){
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $branchInfos = array();
        #获取操作员管辖所有自建仓库
        $all_owner_branch_arr = $this->get_all_branchs('1');
        if(empty($all_owner_branch_arr)){
            $sub_msg = '操作员无仓库管辖权限,请到OMS设置';
            return false;
        }
        foreach ($all_owner_branch_arr as $k => $branch){
            $branchInfos[$branch['branch_id']] = array('branch_bn'=>$branch['branch_bn'],'name'=>$branch['name']);
        }
        $all_branch_id = array_keys($branchInfos);
        $where = '';
        if($params['io_type'] == '1'){
            if(!empty($params['io_bn'])){
                $where = " po_bn='".$params['io_bn']."' and ";
            }else{
                $where = " purchase_time>=".$start_time.' and purchase_time<'.$end_time.' and ';
            }
            #采购入库
            $sql = "select  po_id iso_id ,po_bn iso_bn,name,operator,branch_id,purchase_time create_time from sdb_purchase_po  where  ".$where." branch_id in( ".implode(',', $all_branch_id)." ) "."  and check_status='2' and po_status='1' and eo_status in( '0','1','2','4') ".' limit '.$offset.",".$limit;
        }else{
            #有出入库单，就不要用时间段了
            if(!empty($params['io_bn'])){
                $where = " rp_bn ='".$params['io_bn']."' and ";
            }else{
                $where = " returned_time>=".$start_time.' and returned_time<'.$end_time.' and ';
            }
            #采购退货
            $sql = "select  rp_id iso_id,rp_bn iso_bn,name,operator,branch_id,returned_time create_time from sdb_purchase_returned_purchase where   ".$where." branch_id in( ".implode(',', $all_branch_id)." )   and rp_type='eo' and check_status='2' and return_status in('1','4')  limit ".$offset.",".$limit;
        }
        $_lists = kernel::database()->select($sql);
        if(empty($_lists)){
            return array(
                'lists' => array(),
                'count' => 0,
            );
        }

        $lists = array();
        $iso_ids = array();
        foreach($_lists as $k=>$v){
            $iso_id = $v['iso_id'];
            $iso_ids[] = $iso_id;
            $lists[$iso_id]['iso_id'] = $v['iso_id'];
            $lists[$iso_id]['iso_bn'] = $v['iso_bn'];
            $lists[$iso_id]['name'] = $v['name'];
            $lists[$iso_id]['create_time'] = date('Y-m-d',$v['create_time']);
            $lists[$iso_id]['branch_bn'] = $branchInfos[$v['branch_id']]['branch_bn'];
            $lists[$iso_id]['branch_name'] = $branchInfos[$v['branch_id']]['name'];
            $lists[$iso_id]['operator'] = $v['operator'];
        }
        if($params['io_type'] == '1'){
            #采购入库明细
            $obj_items = app::get('purchase')->model('po_items');
            $_items = $obj_items->getList('po_id iso_id,bn,name,barcode,num,in_num as io_num',array('po_id'=>$iso_ids));
        }else{
            #采购退货明细
            $obj_items = app::get('purchase')->model('returned_purchase_items');
            $_items = $obj_items->getList('rp_id iso_id,bn,name,barcode,num,out_num as io_num',array('rp_id'=>$iso_ids));
        }
        $purchase_list = array();
        foreach($_items as $val){
            $iso_id = $val['iso_id'];
            $lists[$iso_id]['items'][] = array('bn'=>$val['bn'],'name'=>$this->charFilter($val['name']),'num'=>$val['num'],'barcode'=>$val['barcode'],'io_num'=>$val['io_num']);
        }
        $purchase_list = array_values($lists);
        unset($lists);
        return array('lists' => $purchase_list,'count' => count($purchase_list));
    }
    #获取其他入库和调拨入库待入库单据
    function get_taoguaniostockorder_data($params,&$code,&$sub_msg,$offset, $limit){
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        
        $branchInfos = array();
        #获取操作员管辖所有自建仓库
        $all_owner_branch_arr = $this->get_all_branchs('1');
        if(empty($all_owner_branch_arr)){
            $sub_msg = '操作员无仓库管辖权限,请到OMS设置';
            return false;
        }
        foreach ($all_owner_branch_arr as $k => $branch){
            $branchInfos[$branch['branch_id']] = array('branch_bn'=>$branch['branch_bn'],'name'=>$branch['name']);
        }
        $all_branch_id = array_keys($branchInfos);
        
        if(!empty($params['io_bn'])){
            $where = " iso_bn='".$params['io_bn']."' and ";
        }else{
            $where = " create_time>=".$start_time.' and create_time<'.$end_time.' and ';
        }
        
        $sql = "select iso_id,iso_bn,name,operator,branch_id,create_time
            from sdb_taoguaniostockorder_iso
            where ".$where."   branch_id in( ".implode(',', $all_branch_id)." )  and type_id=".$params['io_type']." and check_status='2'  and confirm='N'  and iso_status in('1','2')".' limit '.$offset.",".$limit;
        $_lists = kernel::database()->select($sql);
        if(empty($_lists)){
            return array(
                'lists' => array(),
                'count' => 0,
            );
        }
        $lists = array();
        $iso_ids = array();
        foreach($_lists as $k=>$v){
            $iso_id = $v['iso_id'];
            $iso_ids[] = $iso_id;
            $lists[$iso_id]['iso_id'] = $v['iso_id'];
            $lists[$iso_id]['iso_bn'] = $v['iso_bn'];
            $lists[$iso_id]['name'] = $v['name'];
            $lists[$iso_id]['create_time'] = date('Y-m-d',$v['create_time']);
            $lists[$iso_id]['branch_bn'] = $branchInfos[$v['branch_id']]['branch_bn'];
            $lists[$iso_id]['branch_name'] = $branchInfos[$v['branch_id']]['name'];
            $lists[$iso_id]['operator'] = $v['operator'];
        }
        #获取明细
        $obj_iso_items = app::get('taoguaniostockorder')->model('iso_items');
        $productsObj = app::get('ome')->model('products');
        $_items = $obj_iso_items->getList('iso_id,bn,product_name,nums,normal_num as io_num',array('iso_id'=>$iso_ids));
        // 获取物料条码
        $bns = $barcodes = array();
        foreach ($_items as $_item) {
            $bns[] = $_item['bn'];
        }
        $basicMaterialLib = kernel::single('material_basic_material');
        $barcode_data = $basicMaterialLib->getBasicMaterialByBns(array_unique($bns));
        foreach ($barcode_data as $bd) {
            $barcodes[$bd['material_bn']] = $bd['code'];
        }
        $iso_list = array();
        foreach($_items as $val){
            $iso_id = $val['iso_id'];
            $lists[$iso_id]['items'][] = array('bn'=>$val['bn'],'name'=>$this->charFilter($val['product_name']),'num'=>$val['nums'],'barcode'=>$barcodes[$val['bn']],'io_num'=>$val['io_num']);
        }
        $iso_list = array_values($lists);
        unset($lists);
        return array('lists' => $iso_list,'count' => count($iso_list));        
    }
    
    
    /**
     * confirm
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function confirm($params,&$code,&$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $support_type_id  = $this->get_support_type_id();
        if(!in_array($params['io_type'], $support_type_id)){
            $sub_msg = '类型不正确！请填数字：1：采购入库；10：采购退货;4：调拨入库;40：调拨出库;7：直接出库;70：直接入库;300：样品出库;400：样品入库;700：分销出库;800：分销入库';
            return false;
        }
        #判断是做出入库还是取消
        switch ($params['io_status']){
            case 'FINISH':
            case 'PARTIN':
                $rs = $this->save_iso($params, $sub_msg);
                break;
            case 'CANCEL';
                $rs = $this->cancel_iso($params, $sub_msg);
            break;
        }
        return $rs;
    }
    function cancel_iso($params,&$sub_msg){
        $relation = $this->_type_relation[$params['io_type']];
        $method = $relation['type'].'Storage';
        $trx_status = kernel::database()->beginTransaction();

        $_re = kernel::single('openapi_data_original_pda_iostock')->$method($params);
       
        if($_re['rsp'] == 'fail'){
            $sub_msg = $rs['msg'] ? $rs['msg'] : '取消失败';

            kernel::database()->rollBack();
            return false;
        }
        $result['msg'] = "取消成功";
        $result['message'] = 'success';
        $result['io_bn'] = $params['io_bn'];
        kernel::database()->commit($trx_status);
        return  $result;
    }
    function save_iso($params,&$sub_msg){
        if($params['io_type'] == '1'){
            #采购入库
            $rs = $this->save_purchase($params,$sub_msg);
        }elseif($params['io_type'] == '10'){
            #采购退货
            $rs = $this->save_purchase_return_confirm($params,$sub_msg);
        }else{
            #其他出入库和调拨出入库
            $rs = $this->save_taoguaniostockorder_confirm($params,$sub_msg);
        }
        return $rs;
    }
    private function save_purchase($params,&$sub_msg){
        $io_bn = trim($params['io_bn']);
        $obj_purchase_po = app::get('purchase')->model('po');
        $obj_purchase_po_items = app::get('purchase')->model('po_items');
        $obj_eo       =  app::get('purchase')->model("eo");
        
        $op_info = kernel::single('ome_func')->getDesktopUser();
        
        $result = $obj_purchase_po->getList('po_id,branch_id,po_bn,eo_status',array('po_bn'=>$io_bn));
        if(empty($result)){
            $sub_msg = 'OMS系统找不到单据'.$io_bn;
            return false;
        }
        $po_ids = array();
        #未入库1、部分入库2、未入库4
        $support_status = array('1','2','4');
        $eo_data = array();
        #检查单据的状态,状态只能是未入库、部分入库、未入库
        if(!in_array($result[0]['eo_status'], $support_status)){
            $sub_msg = $io_bn.'当前OMS单据状态不能入库!';
            return false;
        }
        $po_id = $result[0]['po_id'];
        $eo_data['io_bn'] = $io_bn;
        $eo_data['io_status'] = $params['io_status'];
        $eo_data['oper']  = $op_info['op_name'];
        $branch_id = $result[0]['branch_id'];
        
        unset($result);
        
        $items = json_decode($params['items'],true);
        if(!$items){
            $sub_msg = '提交的数据明细有误!';
            return false;
        }
        $iso_items = array();
        foreach($items as $val){
            $bn = trim($val['bn']);
            $nums = trim($val['nums']);
            if(!$bn){
                $sub_msg = '提交的数据商品有误!';
                return false;
            }
            #出入库数量小于等于0的直接过滤扔掉
            if($nums <=0 ){
                continue;
            }
            $iso_items[$bn]  += $nums;
        }
        if(empty($iso_items)){
            $sub_msg = '本次提交商品数量无需入库!';
            return false;
        }
        $item_product_bns = array_keys($iso_items);

        // $basicMStorageLifeLib = kernel::single('material_storagelife');
        // $basic_material_conf_data = $basicMStorageLifeLib->getStorageLifeInfoByBns($item_product_bns);
        // if (!empty($basic_material_conf_data)) {
        //     $expire_bm_ids = array();
        //     foreach ($basic_material_conf_data as $bkey => $bvalue) {
        //         $expire_bm_ids[] = $bvalue['bm_id'];
        //     }
        // }

        #获取该单据所有明细
        $items = $obj_purchase_po_items->getList('*',array('po_id'=>$po_id));
        $check_taoguaninventory = array();
        #统计还需入库数量
        $total_remain_nums = 0;
        $total_entry_nums = 0;#本次总入库
        #重新组织入库数据
        foreach($items as $val){
            $total_remain_nums += $val['num'] - $val['in_num'];#统计所有货品上次剩下总数量
            $bn = $val['bn'];
            $name = $val['name'];
            if(!in_array( $bn,$item_product_bns)){
                continue;
            } 

            $remain_num = $val['num'] - $val['in_num'];#上次剩下数量=总数量-已入库数量
            #已入库完毕的，不能再入库
            if($remain_num <= 0){
                $sub_msg =  $name.'已入库完毕，不能再入库';
                return false;
            }
            $entry_num = $iso_items[$bn];#本次入库，相当于在页面输入一个入库数量
            #本次入库大于上次剩下数量，不能入库
            if($entry_num > $remain_num){
                $sub_msg =  $name.'入库数量大于OMS未入库数量';
                return false;
            }
            $total_entry_nums += $entry_num;
            $eo_data['items'][] = array(
                'bn'=>$bn,
                'normal_num'=>$entry_num,#入库数量
                'defective_num'=>'0'#取消数量 
            );
            
            #check_taoguaninventory是用来检查货号是否存在盘点中
            $check_taoguaninventory[$po_id]['product_id'][$val['product_id']] = $val['product_id'];#一个po_id,有多个product_id
            $check_taoguaninventory[$po_id]['po_bn'] =  $io_bn;#一个po_id，只有一个po_bn
        }
        #当客户填写的是部分入库，实际可能本次入库就完成了，需要对io_status进行纠正
        if($params['io_status'] == 'PARTIN'){
            #上次剩余未入数量，和本次总入库数量一样，则属于入库完成，需要纠正这个状态，因为客户可能填写错了
            if($total_remain_nums == $total_entry_nums){
                $eo_data['io_status'] = 'FINISH';
            }
        }
        #检查货品是否在盘点中
        if(app::get('taoguaninventory')->is_installed()){
            $exist_taoguaninventory_po_bns = array();
            foreach($check_taoguaninventory as $v){
                $product_ids = $v['product_id'];
                $po_bn = $v['po_bn'];
                #检查货品是否在盘点中
                $_rs = kernel::single('taoguaninventory_inventorylist')->checkproductoper($product_ids,$branch_id);
                if(!$_rs){
                     $exist_taoguaninventory_po_bns[] = $po_bn;
                }
            }
            if(!empty($exist_taoguaninventory_po_bns)){
                $sub_msg = '货品正在盘点中，不能入库单据：'.implode(';', $exist_taoguaninventory_po_bns);
                return false;
            }
        }
        unset($items,$check_taoguaninventory);
        $method = $this->_type_relation[$params['io_type']]['type'].'Storage';
        $eo_data['type_id'] = $params['io_type'];
        #以上检查完毕，正式开始入库
        $trx_status = kernel::database()->beginTransaction();

        $_re = kernel::single('openapi_data_original_pda_iostock')->$method($eo_data);
       
        if($_re['rsp'] == 'fail'){
            $result['message'] = 'fail';
            $result['sub_msg'] = $sub_msg = $_re['msg'];

            kernel::database()->rollBack();
        }else{
            $result['msg'] = "出入库成功";
            $result['message'] = 'success';
            $result['io_bn'] = $io_bn;
            kernel::database()->commit($trx_status);
        }
        return $result;
    }
    /**
     * 保存_taoguaniostockorder_confirm
     * @param mixed $params 参数
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回操作结果
     */
    public function save_taoguaniostockorder_confirm($params,&$sub_msg){
        $io_bn = trim($params['io_bn']);
        #未入库或部分入库
        $support_iso_status = array('1','2');
        $_data = app::get('taoguaniostockorder')->model('iso')->getList('*',array('iso_bn'=>$io_bn,'type_id'=> $params['io_type']));
        $data = $_data[0];
        $obj_iso_items = app::get('taoguaniostockorder')->model('iso_items');
        
        if(empty($data)){
            $sub_msg = '找不到出入库单据！';
            return false;
        }
        #只能是已审核审核状态
        if($data['check_status'] !='2'){
            $sub_msg = 'OMS未审核状态不能出入库！';
            return false;
        }
        #只能是未入库或部分入库
        if(!in_array($data['iso_status'], $support_iso_status)){
            $sub_msg = 'OMS当前状态不能出入库!';
            return false;
        }
        $relation = $this->_type_relation[$params['io_type']];
        $op_info = kernel::single('ome_func')->getDesktopUser();
        $iso_data['io_bn'] = $data['iso_bn'];
        $iso_data['io_status'] = $params['io_status'];
        $iso_data['oper']  = $op_info['op_name'];
        
        $items = json_decode($params['items'],true);
        if(!$items){
            $sub_msg = '出入库明细有误!';
            return false;
        }
        $iso_items = array();
        foreach($items as $val){
            $bn = trim($val['bn']);
            $nums = trim($val['nums']);
            if(!$bn){
                $sub_msg = '提交的数据货品有误!';
                return false;
            }
            #出入库数量小于等于0的直接过滤扔掉
            if($nums <=0 ){
                continue;
            }
            $iso_items[$bn]  += $nums;
        }
        if(empty($iso_items)){
            $sub_msg = '本次提交商品数量无需出入库!';
            return false;
        }
        $item_product_bns = array_keys($iso_items);
        
        $items = $obj_iso_items->getlist('iso_id,bn,unit,product_id,product_name,iso_items_id,bn,price,nums,normal_num,defective_num',array('iso_id'=>$data['iso_id']));
        #check_taoguaninventory数据是用来检查货品是否在盘点中
        $check_taoguaninventory = array();
        $total_remain_nums = 0; #统计还需入库数量
        $total_normal_nums = 0;#本次总入库
        #重新组织入库数据
        foreach($items as $key=>$val){
            $total_remain_nums += $val['nums'] - $val['normal_num'];#统计所有货品上次剩下总数量
            $bn = $val['bn'];
            if(!in_array( $bn,$item_product_bns)){
                continue;
            }
            $branch_id = $data['branch_id'];
        
            $remain_num = $val['nums'] - $val['normal_num'];#上次剩下数量=总数量-已入库数量
            #已入库完毕的，不能再入库
            if($remain_num <= 0){
                $sub_msg =  $bn.'已入库完毕，不能再入库';
                return false;
            }
            $entry_num = $iso_items[$bn];#本次入库，相当于在页面输入一个入库数量
            #本次入库大于上次剩下数量，不能入库
            if($entry_num > $remain_num){
                $sub_msg =  $bn.'本次入库不能大于未入库数量';
                return false;
            }
            $total_normal_nums += $entry_num;
           
            if($relation['type'] == 'in'){
                $iso_data['items'][] = array('bn'=>$bn,'normal_num'=>$entry_num,'defective_num'=>'0');
            }else{
                $iso_data['items'][] = array('bn'=>$bn,'num'=>$entry_num,'defective_num'=>'0');
            }
            #check_taoguaninventory是用来检查货号是否存在盘点中
            $check_taoguaninventory[$data['iso_id']]['product_id'][$val['product_id']] = $val['product_id'];#一个iso_id,有多个product_id
            $check_taoguaninventory[$data['iso_id']]['branch_id'] = $branch_id;#一个iso_id，只有一个branch_id
            $check_taoguaninventory[$data['iso_id']]['iso_bn'] = $io_bn;#一个id，只有一个io_bn
        }
        #当客户填写的是部分入库，实际可能本次入库就完成了，需要对io_status进行纠正
        if($params['io_status'] == 'PARTIN'){
            #上次剩余未入数量，和本次总入库数量一样，则属于入库完成，需要纠正这个状态，因为客户可能填写错了
            if($total_remain_nums == $total_normal_nums){
                $iso_data['io_status'] = 'FINISH';
            }
        }
        #检查货品是否在盘点中
        if(app::get('taoguaninventory')->is_installed()){
            $exist_taoguaninventory_iso_bns = array();
            foreach($check_taoguaninventory as $v){
                $product_ids = $v['product_id'];
                $branch_id = $v['branch_id'];
                $iso_bn = $v['iso_bn'];
                #检查货品是否在盘点中
                $_rs = kernel::single('taoguaninventory_inventorylist')->checkproductoper($product_ids,$branch_id);
                if(!$_rs){
                    $exist_taoguaninventory_iso_bns[] = $iso_bn;
                }
            }
            if(!empty($exist_taoguaninventory_iso_bns)){
                $sub_msg = '有货品正在盘点，不能出入库！';
                return false;
            }
        }
        unset($items,$iso_items,$check_taoguaninventory);
        $method = $relation['type'].'Storage';
        $iso_data['type_id'] = $params['io_type'];
        #以上检查完毕，正式开始入库
        $trx_status = kernel::database()->beginTransaction();

        $_re = kernel::single('openapi_data_original_pda_iostock')->$method($iso_data);
       
        if($_re['rsp'] == 'fail'){
            $result['message'] = 'fail';
            $result['sub_msg'] = $sub_msg = $_re['msg'];

            kernel::database()->rollBack();
        }else{
            $result['msg'] = "出入库成功";
            $result['message'] = 'success';
            $result['io_bn'] = $io_bn;
            kernel::database()->commit($trx_status);
        }
        return $result;
    }
    /**
     * 保存_purchase_return_confirm
     * @param mixed $params 参数
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回操作结果
     */
    public function save_purchase_return_confirm($params,&$sub_msg){
        $io_bn = trim($params['io_bn']);
        $obj_returned_purchase = app::get('purchase')->model('returned_purchase');
        $obj_returned_purchase_items = app::get('purchase')->model('returned_purchase_items');
        
        $op_info = kernel::single('ome_func')->getDesktopUser();
        #审核状态check_status是2,已审;退货状态return_status是1已新建;退货类型rp_type是'eo'采购退货单
        $_datas = $obj_returned_purchase->getList('*',array('rp_bn'=>$io_bn,'check_status'=>'2','return_status|in'=>array('1','4'),'rp_type'=>'eo'));
        $data = $_datas[0];
        if(empty( $data)){
            $sub_msg = '找不到出库单据或单据不是待入库状态';
            return false;
        }
        $branch_id = $data['branch_id'];
        $rp_id = $data['rp_id'];
        $iso_data['io_bn'] = $io_bn;
        $iso_data['io_status'] = $params['io_status'];
        $iso_data['oper']  = $op_info['op_name'];
      
        $items = json_decode($params['items'],true);
        if(!$items){
            $sub_msg = '出库明细有误！';
            return false;
        }
        $iso_items = array();
        foreach($items as $val){
            $bn = trim($val['bn']);
            $nums = trim($val['nums']);
            if(!$bn){
                $sub_msg = '提交的数据商品有误!';
                return false;
            }
            #出入库数量小于等于0的直接过滤扔掉
            if($nums <=0 ){
                continue;
            }
            $iso_items[$bn]  += $nums;
        }
        if(empty($iso_items)){
            $sub_msg = '本次提交商品数量无需出库!';
            return false;
        }
        $item_product_bns = array_keys($iso_items);
        
        #根据获取改单据所有明细
        $items =  $obj_returned_purchase_items->getlist('*',array('rp_id'=>$data['rp_id']));
        $check_taoguaninventory = array();
        #统计还需入库数量
        $total_remain_nums = 0;
        $total_entry_nums = 0;#本次总入库
        #重新组织入库数据
        foreach($items as $val){
            $total_remain_nums += $val['num'] - $val['out_num'];#统计所有货品上次剩下总数量
            $bn = $val['bn'];
            if(!in_array( $bn,$item_product_bns)){
                continue;
            }
            $remain_num = $val['num'] - $val['out_num'];#上次剩下数量=总数量-已入库数量
            #已入库完毕的，不能再入库
            if($remain_num <= 0){
                $sub_msg =  $bn.'已出库完毕，不能再出库';
                return false;
            }
            $entry_num = $iso_items[$bn];#本次入库，相当于在页面输入一个入库数量
            #本次入库大于上次剩下数量，不能入库
            if($entry_num > $remain_num){
                $sub_msg =  $bn.'出库数量大于OMS未出库数量';
                return false;
            }
            $total_entry_nums += $entry_num;
            $iso_data['items'][] = array('bn'=>$bn,'num'=>$entry_num,'defective_num'=>'0');
        
            #check_taoguaninventory是用来检查货号是否存在盘点中
            $check_taoguaninventory[$rp_id]['product_id'][$val['product_id']] = $val['product_id'];#一个rp_id,有多个product_id
            $check_taoguaninventory[$rp_id]['branch_id'] = $branch_id;#一个po_id，只有一个branch_id
            $check_taoguaninventory[$rp_id]['io_bn'] = $io_bn;#一个rp_id，只有一个io_bn
        }
        #当客户填写的是部分入库，实际可能本次入库就完成了，需要对io_status进行纠正
        if($params['io_status'] == 'PARTIN'){
            #上次剩余未入数量，和本次总入库数量一样，则属于入库完成，需要纠正这个状态，因为客户可能填写错了
            if($total_remain_nums == $total_entry_nums){
                $iso_data['io_status'] = 'FINISH';
            }
        }
        #检查货品是否在盘点中
        if(app::get('taoguaninventory')->is_installed()){
            $exist_taoguaninventory_po_bns = array();
            foreach($check_taoguaninventory as $v){
                $product_ids = $v['product_id'];
                $branch_id = $v['branch_id'];
                $po_bn = $v['io_bn'];
                #检查货品是否在盘点中
                $_rs = kernel::single('taoguaninventory_inventorylist')->checkproductoper($product_ids,$branch_id);
                if(!$_rs){
                    $exist_taoguaninventory_po_bns[] = $po_bn;
                }
            }
            if(!empty($exist_taoguaninventory_po_bns)){
                $sub_msg = '货品正在盘点中，不能入库单据：'.implode(';', $exist_taoguaninventory_po_bns);
                return false;
            }
        } 
        unset($items,$iso_items,$check_taoguaninventory);
        $method = $this->_type_relation[$params['io_type']]['type'].'Storage';
        $iso_data['type_id'] = $params['io_type'];
        #以上检查完毕，正式开始入库
        $trx_status = kernel::database()->beginTransaction();

        $_re = kernel::single('openapi_data_original_pda_iostock')->$method($iso_data);
        if($_re['rsp'] == 'fail'){
            $result['message'] = 'fail';
            $result['sub_msg'] = $sub_msg = $_re['msg'];

            kernel::database()->rollBack();
        }else{
            $result['msg'] = "出入库成功";
            $result['message'] = 'success';
            $result['io_bn'] = $io_bn;
            kernel::database()->commit($trx_status);
        }
        return $result;
    }
}