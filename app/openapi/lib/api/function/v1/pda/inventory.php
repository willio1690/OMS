<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

//pda盘点
class openapi_api_function_v1_pda_inventory extends openapi_api_function_v1_pda_abstract {
    
    /**
     * 创建
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function create($params,&$code,&$sub_msg){
        $method = __FUNCTION__;
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $data = array();
        $branch_info = app::get('ome')->model('branch')->getList('branch_id,name',array('branch_bn'=>$params['branch_bn']));
        if(empty($branch_info)){
            $sub_msg = '仓库数据编码有误！';
            return false;
        }
        if(empty($params['inventory_type'])){
            $params['inventory_type'] = 3;
        }
        $user_info = app::get('desktop')->model('users')->getList('user_id,name',array('user_id'=>$_SESSION['account']['shopadmin']));
        $items = json_decode($params['items'],true);
        if(!$items){
            $sub_msg = '盘点明细有误！';
            return false;
        }
        $inventory_items = array();

        foreach($items as $v){
            $bn = $v['bn'];
            $num = $v['num'];
            #盘点货品无数量，则退出
            if(empty($bn) || !is_numeric($num)){
                $sub_msg = '盘点明细格式有误！';
                return false;
            }
            $count = 0;
            $is_use_expire = false;
            $expire_bn_info = array();
            if (!empty($v['expire_bn_info'])) {
                foreach ($v['expire_bn_info'] as $key => $val) {
                    $expire_bn_num = intval($val['in_num']);
                    if ($expire_bn_num < 0) {
                        $sub_msg = $bn . '关联保质期的' . $val['expire_bn'] . '输入盘点数量不能小于0';
                        return false;
                    }
                    $count += $expire_bn_num;
                    $expire_bn_info[] = array(
                        'bmsl_id' => $val['bmsl_id'],
                        'expire_bn' => $val['expire_bn'],
                        'in_num' => $expire_bn_num,
                    );
                }
                if ($count != $num) {
                    $sub_msg = $bn . '输入盘点数量和关联保质期输入的盘点数量总和不相等';
                    return false;
                }
                $is_use_expire = true;
            }
            $inventory_item[] = array(
                'bn' => $bn,
                'number' => $num,
                'is_use_expire' => $is_use_expire,
                'expire_bn_info' => $expire_bn_info,
            );
            $bns[] = $bn;
        }
        //创建盘点主表
        $inventory_data = array (
            'inventory_name' => $params['inventory_name'],
            'pos' => '0',
            '_DTYPE_DATE' =>
            array (
                0 => 'add_time',
            ),
            'add_time' => $params['add_time'] ? $params['add_time'] : date('Y-m-d H:i:s'),
            'inventory_type' => $params['inventory_type'],
            'memo' => '',
            'branch_id' => $branch_info[0]['branch_id'],
            'inventory_id' => '',
            'join_pd' => '',
            'branch_name' => $branch_info[0]['name'],
        );
        $status = $this->check_status($params,$branch_info[0]['branch_id'],$method,$sub_msg);
        if(!$status){
            return false;
        }
        $rs = app::get('material')->model('basic_material')->getList('bm_id, material_bn',array('material_bn|in'=>$bns));
        $product_info = array();
        #先一次查询所有货品的product_id
        foreach($rs as $v){
            $product_info[$v['material_bn']] = $v['bm_id'];
        }
        unset($bns);
        $obj_inventorylist = kernel::single('taoguaninventory_inventorylist');
        kernel::database()->beginTransaction();
        #创建盘点主表
        $inventory_id = $obj_inventorylist->create_inventory($inventory_data);
        $item_rs = true;
        foreach($inventory_item as $v){
            $data['pos_name'] = $params['pos']?$params['pos']:'';
            $data['branch_id'] = $branch_info[0]['branch_id'];
            $data['number'] = $v['number'];
            $data['is_use_expire'] = $v['is_use_expire'];
            $data['expire_bn_info'] = $v['expire_bn_info'];
            $data['inventory_id'] =  $inventory_id;
            $data['product_id'] = $product_info[$v['bn']]; 
            $_rs = $obj_inventorylist->save_inventory($data,$sub_msg);
            if(!$_rs){
                $item_rs = false;
                break;
            }
        }
        if($inventory_id && $item_rs){
            kernel::database()->commit();
            $result['msg'] = '创建成功';
            $result['message'] = 'success';
        }else{
            kernel::database()->rollBack();
            $sub_msg = '创建失败:'.$sub_msg;
            $result = false;
        }
        return $result;
    }
    #盘点更新（相当于追加）
    /**
     * 更新
     * @param mixed $params 参数
     * @param mixed $code code
     * @param mixed $sub_msg sub_msg
     * @return mixed 返回值
     */
    public function update($params,&$code,&$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }
        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }
        $mdl_Inventory = app::get('taoguaninventory')->model('inventory');
        $inventory_info = $mdl_Inventory->getlist('inventory_name,inventory_date,op_name,inventory_id,branch_id,branch_name,confirm_status',array('inventory_bn'=>$params['inventory_bn']));
        if(empty( $inventory_info)){
            $sub_msg = '找不到盘点更新单据！';
            return false;
        }
        if($inventory_info[0]['confirm_status'] !=1){
            $sub_msg = '该盘点单状态已处理！不能再更新';
            return false;
        }
        $items = json_decode($params['items'],true);
        #更新的时候，盘点明细是空的，说明没有新加入，就当是更新成功
        if(empty($items)){
            $result['msg'] = '没有明细需要更新';
            $result['message'] = 'success';
            return $result;
        }
        $inventory_items = array();
        foreach($items as $v){
            $bn = $v['bn'];
            $num = $v['num'];
            #盘点货品无数量，则退出
            if(empty($bn) || !is_numeric($num)){
                $sub_msg = '盘点明细格式有误！';
                return false;
            }
            $count = 0;
            $is_use_expire = false;
            $expire_bn_info = array();
            if (!empty($v['expire_bn_info'])) {
                foreach ($v['expire_bn_info'] as $key => $val) {
                    $expire_bn_num = intval($val['in_num']);
                    if ($expire_bn_num < 0) {
                        $sub_msg = $bn . '关联保质期的' . $val['expire_bn'] . '输入盘点数量不能小于0';
                        return false;
                    }
                    $count += $expire_bn_num;
                    $expire_bn_info[] = array(
                        'bmsl_id' => $val['bmsl_id'],
                        'expire_bn' => $val['expire_bn'],
                        'in_num' => $expire_bn_num,
                    );
                }
                if ($count != $num) {
                    $sub_msg = $bn . '输入盘点数量和关联保质期输入的盘点数量总和不相等';
                    return false;
                }
                $is_use_expire = true;
            }
            $inventory_item[] = array(
                'bn' => $bn,
                'number' => $num,
                'is_use_expire' => $is_use_expire,
                'expire_bn_info' => $expire_bn_info,
            );
            $bns[] = $bn;
        }
        $rs = app::get('material')->model('basic_material')->getList('bm_id, material_bn',array('material_bn|in'=>$bns));
        $product_info = array();
        #先一次查询所有货品的product_id
        foreach($rs as $v){
            $product_info[$v['material_bn']] = $v['bm_id'];
        }
        unset($bns,$rs);
        $obj_inventorylist = kernel::single('taoguaninventory_inventorylist');
        $branch_id = $inventory_info[0]['branch_id'];
        $inventory_id = $inventory_info[0]['inventory_id'];
        #逐条插入
        foreach($inventory_item as $v){
            $data['pos_name'] = $params['pos']?$params['pos']:'';
            $data['branch_id'] = $branch_id;
            $data['number'] = $v['number'];
            $data['is_use_expire'] = $v['is_use_expire'];
            $data['expire_bn_info'] = $v['expire_bn_info'];
            $data['inventory_id'] =  $inventory_id;
            $data['product_id'] = $product_info[$v['bn']];
            $obj_inventorylist->save_inventory($data,$sub_msg);
        }
        kernel::single('taoguaninventory_inventorylist')->update_inventorydifference($inventory_id);
        $opObj  = app::get('ome')->model('operation_log');
        $opObj->write_log('inventory_modify@taoguaninventory', $inventory_id, '更新盘点明细');
        $result['msg'] = '更新成功';
        $result['message'] = 'success';
        return $result;
    } 
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

        $filter = array();
        if ($params['inventory_bn']) $filter['inventory_bn'] = $params['inventory_bn'];
        if ($params['start_time']) $filter['add_time|bthan'] = strtotime($params['start_time']);
        if ($params['end_time']) $filter['add_time|lthan'] = strtotime($params['end_time']);
        if ($params['status']) $filter['confirm_status'] = $params['status'];

        if (!$filter) {
            $sub_msg ='至少传递一个查询条件';
            return false;
        }

        $filter['branch_id'] = array(0); $branchInfos = array();
        foreach ($this->get_all_branchs('1') as $value) {
            $filter['branch_id'][] = $value['branch_id'];

            $branchInfos[$value['branch_id']] = $value;
        }

        $inventoryMdl = app::get('taoguaninventory')->model('inventory');
        $count = $inventoryMdl->count($filter);
        if (!$count) {
            return array('lists' => array(),'count' => 0);
        }

        $data = $inventoryMdl->getList('*',$filter,$offset,$limit);

       if(empty($data)) return array('lists' => array(),'count' => 0);
        
        $inventory_ids = array();
        $inventory_data = array();
        foreach($data as $val){
            $inventory_id = $val['inventory_id'];
            $inventory_ids[] = $inventory_id;
            $inventory_data[$inventory_id]['inventory_id']      = $inventory_id;
            $inventory_data[$inventory_id]['inventory_name']    = $val['inventory_name'];
            $inventory_data[$inventory_id]['inventory_bn']      = $val['inventory_bn'];
            $inventory_data[$inventory_id]['inventory_date']    = $val['inventory_date']?date('Y-m-d',$val['inventory_date']):'';
            $inventory_data[$inventory_id]['inventory_checker'] = $val['inventory_checker'];
            $inventory_data[$inventory_id]['finance_dept']      = $val['finance_dept'];
            $inventory_data[$inventory_id]['warehousing_dept']  = $val['warehousing_dept'];
            $inventory_data[$inventory_id]['difference']        = $val['difference'];
            $inventory_data[$inventory_id]['op_name']           = $val['op_name'];
            $inventory_data[$inventory_id]['branch_name']       = $val['branch_name'];
            $inventory_data[$inventory_id]['branch_bn']         = $branchInfos[$val['branch_id']]['branch_bn'];
            $inventory_data[$inventory_id]['confirm_status']    = $val['confirm_status'];#判断类型，需要转出来
            $inventory_data[$inventory_id]['confirm_op']        = $val['confirm_op'];
            $inventory_data[$inventory_id]['confirm_time']      = $val['confirm_time']?date('Y-m-d H:s:i',$val['confirm_time']):'';
        }

        $items_datas = app::get('taoguaninventory')->model('inventory_items')->getList('*',array('inventory_id'=>$inventory_ids));
        $inventory_list = array();
        $obj_branch_pos = app::get('ome')->model('branch_pos');
        $basicMStorageLifeLib = kernel::single('material_storagelife');
        foreach($items_datas as $v){
            #获取货位
            $pos_info = $obj_branch_pos->getList('store_position',array('pos_id'=>intval($v['pos_id']) ));

            $items = array (
                'item_id'        => $v['item_id'],
                'inventory_id'   => $v['inventory_id'],
                'product_id'     => $v['product_id'],
                'name'           => $this->charFilter($v['name']),
                'bn'             => $v['bn'],
                'store_position' => $pos_info[0]['store_position']?$pos_info[0]['store_position']:'',
                'barcode'        => $v['barcode'],
                'accounts_num'   => $v['accounts_num'],
                'actual_num'     => $v['actual_num'],
                'shortage_over'  => $v['shortage_over'],
                'price'          => $v['price'],
                'is_use_expire'  => $basicMStorageLifeLib->checkStorageLifeById($v['product_id']),
            );

            $inventory_id = $v['inventory_id'];

            $inventory_data[$inventory_id]['items'][] = $items;
        }

        $inventory_list = array_values($inventory_data);

        return array(
            'lists' => $inventory_list,
            'count' => $count,
        );
    }
    #检查是否可以操作盘点
    function  check_status($params,$branch_id='',$method='',&$sub_msg){
        $inventory_name = $params['inventory_name'];
        $inventory_type = $params['inventory_type'];
        $invObj = app::get('taoguaninventory')->model('inventory');
        $_inventory = $invObj->getList('inventory_id,confirm_status,inventory_type',array('inventory_name'=>$inventory_name));
        $inventory_data =  $_inventory[0];
        if(isset($inventory_data['confirm_status']) && $inventory_data['confirm_status'] != '1'){
            $sub_msg = '盘点任务名称已存在，且已完成，请更改名称！';
            return false;
        }
        #2是全盘
        if($inventory_type == '2' ){
            $inv_exist = $invObj->getList('inventory_id',array('branch_id'=>$branch_id,'inventory_type'=>'2','confirm_status'=>'1'));
            if($inv_exist){
                $sub_msg = '仓库已有全盘单据存在！';
                return false;
            }
            $inv_exist1 = $invObj->getList('inventory_id',array('branch_id'=>$branch_id,'inventory_type'=>'3','confirm_status'=>'1'));
            if($inv_exist1){
                $sub_msg = '仓库存在部分盘点单据,请先确认后再新建';
                return false;
            }
        }
        #3是部分盘点
        if($inventory_type =='3'){
            $inv_exist2 = $invObj->getList('inventory_id',array('branch_id'=>$branch_id,'inventory_type'=>'2','confirm_status'=>'1'));
            if($inv_exist2){
                $sub_msg = '仓库存在全盘单据，请先确认后，再新建！';
                return false;
            }
        }
        #4是期初
        if($inventory_type == '4'){
            $branch_product = kernel::single('taoguaninventory_inventorylist')->check_product_iostock($branch_id);
            if($branch_product){
                $sub_msg = '仓库已有出入库记录，不可以用期初盘点';
                return false;
            }
            $branch_inventory = kernel::single('taoguaninventory_inventorylist')->get_inventorybybranch_id($branch_id);
            if($branch_inventory){
                $sub_msg = '仓库已有期初盘点单据存在!';
                return false;
            }
        }
        if($_inventory){
            $sub_msg = '盘点名称已存在,请更改盘点名称，或使用盘点加入功能';
            return false;
        }
        return true;
    }

    /**
     * 获取保质期批次
     */
    public function getExpireBnInfo($params, &$code, &$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }

        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        if(empty($params['barcode']) || empty($params['branch_id']) || empty($params['selecttype']))
        {
            $sub_msg = '必填参数不能为空';
            return false;
        }

        $data = kernel::single('taoguaninventory_inventorylist')->get_expire_bn_info($params, $code, $sub_msg);
        return $data;
    }

    /**
     * 获取关联的保质期列表
     */
    public function getStorageLife($params, &$code, &$sub_msg){
        if(empty($params['pda_token']) || !$this->checkPdaToken($params['pda_token'])) {
            $sub_msg = '未登录或登录过期,请先登录';
            return false;
        }

        if(empty($params['device_code']) || !$this->check_device_code($params['device_code'])){
            $sub_msg = '设备未授权';
            return false;
        }

        if(empty($params['inventory_id']) || empty($params['bm_id']))
        {
            $sub_msg = '必填参数不能为空';
            return false;
        }

        $data = kernel::single('taoguaninventory_inventorylist')->get_storage_life($params, $code, $sub_msg);
        return $data;
    }
}