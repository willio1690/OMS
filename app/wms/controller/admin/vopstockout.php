<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会JIT出库单管理
 * 
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 1.0 vopick.php 2017-02-23
 */
class wms_ctl_admin_vopstockout extends desktop_controller{

    var $workground = "wms_center";
    
    function _views()
    {
        $stockoutObj    = app::get('purchase')->model('pick_stockout_bills');
        
        $base_filter    = array('confirm_status'=>2, 'status'=>array(1,3));
        
        /* 获取操作员管辖自有仓库 */
        $is_super    = kernel::single('desktop_user')->is_super();
        $branch_ids  = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        
        $base_filter['branch_id']    = $branch_ids;
        
        //Tab
        $sub_menu = array(
                0 => array('label'=>__('全部'),'filter'=>array()),
                1 => array('label'=>__('未出库'),'filter'=>array('o_status'=>1), 'optional'=>false),
                2 => array('label'=>__('部分出库'),'filter'=>array('o_status'=>2), 'optional'=>false),
                3 => array('label'=>__('已出库'),'filter'=>array('o_status'=>3), 'optional'=>false),
        );
        
        foreach($sub_menu as $k => $v)
        {
            $v['filter']    = array_merge($base_filter, $v['filter']);
            
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $stockoutObj->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=wms&ctl=admin_vopstockout&act=index&view='. $k;
        }
        
        return $sub_menu;
    }
    
    function index()
    {
        $this->title = '出库单列表';
        $base_filter = array('confirm_status'=>2, 'status'=>array(1,3));
        
        /* 获取操作员管辖自有仓库 */
        $is_super    = kernel::single('desktop_user')->is_super();
        $branch_ids  = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        
        $base_filter['branch_id']    = $branch_ids;
        
        $params = array('title'=>$this->title,
                'actions' => array(
                    'vopstockout' => array(
                        'label' => '打印出库单',
                        'submit' => 'index.php?app=wms&ctl=admin_vopstockout&act=toPrintVopstockout',
                        'target' => '_blank',
                    ),
                ),
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'base_filter' => $base_filter,
        );
        
        $this->finder('wms_mdl_pick_stockout_bills', $params);
    }
    
    //打印出库单
    function toPrintVopstockout(){
        $mdl_stockout_boxs = app::get('purchase')->model('pick_stockout_bill_item_boxs');
        $mdl_stockout_bills = app::get('purchase')->model('pick_stockout_bills');
        $mdl_purchase_order = app::get('purchase')->model('order');
        $mdl_ome_shop = app::get('ome')->model('shop');
        $stockLib = kernel::single('purchase_purchase_stockout');
        $purchaseLib = kernel::single('purchase_purchase_order');
        //判断选中的记录是否有装箱信息 过滤掉无装箱信息的出库单
        $rs_stockout_boxs = $mdl_stockout_boxs->getList("stockout_id,box_no",array("stockout_id"=>$_REQUEST["stockout_id"]));
        if(empty($rs_stockout_boxs)){
            header("Content-type: text/html; charset=utf-8");
            exit("请确认选择的出库单必须要有装箱信息。");
        }
        //统一获取stockout_id的相关数据数组
        $stockout_ids = array();
        foreach ($rs_stockout_boxs as $var_sb_f){
            if(!in_array($var_sb_f["stockout_id"], $stockout_ids)){
                $stockout_ids[] = $var_sb_f["stockout_id"];
            }
        }
        $rs_stockout = $mdl_stockout_bills->getList("stockout_id,carrier_code,delivery_no,storage_no,arrival_time",array("stockout_id"=>$stockout_ids));
        $rl_stockout_id_info = array();
        foreach ($rs_stockout as $var_s){
            //相关店铺和送货仓库的获取
            $sql = "SELECT b.to_branch_bn,b.po_id FROM sdb_purchase_pick_stockout AS a 
                    LEFT JOIN sdb_purchase_pick_bills AS b
                    ON a.bill_id=b.bill_id WHERE a.stockout_id=". $var_s["stockout_id"]." limit 1";
            $rs_pick_info = $mdl_stockout_bills->db->select($sql);
            $branchInfo = $purchaseLib->getWarehouse($rs_pick_info[0]['to_branch_bn']);
            $rs_purchase_order= $mdl_purchase_order->dump(array("po_id"=>$rs_pick_info[0]['po_id']),"shop_id");
            $rs_shop = $mdl_ome_shop->dump(array("shop_id"=>$rs_purchase_order["shop_id"]));
            $rl_stockout_id_info[$var_s["stockout_id"]] = array(
                "shop_name" => $rs_shop["name"], //店铺名
                "branch_name" => $branchInfo["branch_name"], //送货仓库
                "storage_no" => $var_s["storage_no"], //入库单号
                "arrival_time" => $var_s["arrival_time"], //要求到货时间
                "carrier_code" => $stockLib->getCarrierCode($rs_purchase_order["shop_id"], $var_s['carrier_code']),  //承运商
                "delivery_no" => $var_s["delivery_no"], //运单号
            );
        }
        //以stockout_id和box_no作为维度获取数据
        $data = array();
        foreach ($rs_stockout_boxs as $var_sb_e){
            $current_key = $var_sb_e["stockout_id"]."_".$var_sb_e["box_no"];
            if(!isset($data[$current_key])){
                $data[$current_key] = $rl_stockout_id_info[$var_sb_e["stockout_id"]];
                $data[$current_key]["box_no"] = $var_sb_e["box_no"];
            }
        }
        
        $this->pagedata["data"] = $data;
        
        //加载默认打印模板
        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],"vopstockout",$this);
    }
    
    /**
     * 审核
     */
    function confirm($stockout_id)
    {
        if(empty($stockout_id)){
            die('操作出错，请重新操作');
        }
        
        $pickObj    = app::get('purchase')->model('pick_bills');
        
        //状态值
        $stockLib    = kernel::single('purchase_purchase_stockout');
        $dly_mode    = $stockLib->getDlyMode();//配送方式
        
        //出库单
        $stockoutObj    = app::get('purchase')->model('pick_stockout_bills');
        $row            = $stockoutObj->dump(array('stockout_id'=>$stockout_id,'status'=>1,'confirm_status'=>2, ), '*');
        if(empty($row))
        {
            die('出库单不存在');
        }
        
        //出库仓
        $branchObj    = app::get('ome')->model('branch');
        $branchInfo   = $branchObj->dump(array('branch_id'=>$row['branch_id']), 'name');
        $row['branch_name']    = $branchInfo['name'];
        
        //关联拣货单
        $stockoutObj    = app::get('purchase')->model('pick_stockout_bills');
        $sql            = "SELECT b.pick_no, b.po_id, b.to_branch_bn FROM sdb_purchase_pick_stockout AS a LEFT JOIN sdb_purchase_pick_bills AS b
                           ON a.bill_id=b.bill_id WHERE a.stockout_id=". $stockout_id;
        $pickInfo    = $stockoutObj->db->selectrow($sql);
        
        if($pickInfo['to_branch_bn'])
        {
            $purchaseLib    = kernel::single('purchase_purchase_order');
            $branchInfo     = $purchaseLib->getWarehouse($pickInfo['to_branch_bn']);
            
            $pickInfo['to_branch_bn']    = $branchInfo['branch_name'];
        }
        
        $this->pagedata['pickInfo']    = $pickInfo;
        
        //出库单明细
        $sql        = "SELECT a.* FROM sdb_purchase_pick_stockout_bill_items AS a WHERE a.stockout_id=". $stockout_id ." AND is_del='false'";
        $dataList   = $stockoutObj->db->select($sql);
        
        foreach ($dataList as $key => $val)
        {
            $bill_id     = $val['bill_id'];
            $pickInfo    = $pickObj->dump(array('bill_id'=>$bill_id), 'pick_no, po_bn');
            
            $val['pick_no']    = $pickInfo['pick_no'];
            $val['po_bn']      = $pickInfo['po_bn'];
            
            $dataList[$key]    = $val;
        }
        $this->pagedata['dataList'] = $dataList;
        
        $detaiCount    = count($dataList);
        $this->pagedata['detaiCount'] = $detaiCount;
        $this->pagedata['tdHeight'] = ($detaiCount == 1 ? 50 : $detaiCount * 25);

        if (!kernel::single('purchase_purchase_stockout')->is_vopcp($row['carrier_code'])) {
            unset($row['delivery_no']);
        }

        //配送方式
        if($row['dly_mode'])
        {
            $row['dly_mode']    = $stockLib->getDlyMode($row['dly_mode']);
        }
        
        //承运商
        if($row['carrier_code'])
        {
            $row['carrier_code']    = $stockLib->getCarrierCode('', $row['carrier_code']);
        }
        
        $this->pagedata['data'] = $row;
        $this->singlepage('admin/vop/stockout_confirm.html');
    }
    
    /**
     * 确认出库
     */
    function doConfirm()
    {
        $this->begin('index.php?app=wms&ctl=admin_vopstockout&act=index');
        
        $stockout_id     = $_POST['stockout_id'];
        $delivery_no     = trim($_POST['delivery_no']);
        $expire_box_info = $_POST['expire_box_info'];
        
        if(empty($stockout_id) || empty($delivery_no))
        {
            $this->end(false, '请填写运单号');
        }
        
        if(empty($expire_box_info))
        {
            $this->end(false, '请先录入装箱信息');
        }
        
        $stockoutObj     = app::get('purchase')->model('pick_stockout_bills');
        $stockitemObj    = app::get('purchase')->model('pick_stockout_bill_items');
        $stockLib        = kernel::single('purchase_purchase_stockout');
        $logObj          = app::get('ome')->model('operation_log');
        
        //出库单
        $stockout_detail    = $stockoutObj->dump(array('stockout_id'=>$stockout_id, 'confirm_status'=>2, 'status'=>1, 'o_status'=>1), '*');
        if(empty($stockout_detail))
        {
            $this->end(false, '出库单不存在');
        }elseif($stockout_detail['o_status'] != 1)
        {
            $this->end(false, '已经出库,无法再操作');
        }elseif($stockout_detail['status'] != 1)
        {
            $this->end(false, '单据状态已完成,无法再操作');
        }
        
        //录入的装箱信息
        $temp_bn    = array();
        $temp_num   = array();
        $temp_box   = array();
        $product_info = array();
        $branch_out_num    = 0;
        
        $box_data    = json_decode($expire_box_info, true);
        foreach ($box_data as $key => $val)
        {
            $bill_id   = $val['bill_id'];
            $bn        = $val['bn'];
            $box_num   = intval($val['box_num']);
            $box_no    = $val['box_no'];
            
            if(empty($box_num) || empty($box_no))
            {
                $this->end(false, '货号：'. $bn .'信息录入有误');
            }
            
            //检查不允许同货号出现相同的箱号(不同货号箱号可以相同)
            if($temp_box[$bill_id][$bn])
            {
                if(in_array($box_no, $temp_box[$bill_id][$bn])){
                    $this->end(false, '货号：'. $bn .' 录入的箱号：'. $box_no .' 不能重复');
                }
            }
            
            $temp_box[$bill_id][$bn][]  = $box_no;
            
            $temp_bn[$bill_id][$bn]     = $bn;//货号
            $temp_num[$bill_id][$bn]    += $box_num;//统计数量
            
            //关联出库单stockout_item_id
            $row_item    = $stockitemObj->dump(array('stockout_id'=>$stockout_id, 'bill_id'=>$bill_id, 'bn'=>$bn, 'is_del'=>'false'), 'stockout_item_id, bill_id, po_id');
            $val['stockout_id']      = $stockout_id;
            $val['stockout_item_id'] = $row_item['stockout_item_id'];
            
            $val['bill_id']    = $row_item['bill_id'];
            $val['po_id']      = $row_item['po_id'];
            
            //累加货品数量
            $product_info[$bn]    += $box_num;
            
            //仓库出库数量
            $branch_out_num    += $box_num;
            
            $box_data[$key]    = $val;
        }
        
        //检查出库单货品数量
        $itemList        = $stockitemObj->getList('stockout_item_id, bill_id, bn, num, actual_num', array('stockout_id'=>$stockout_id, 'is_del'=>'false'));
        $out_status      = 3;//全部出库
        
        foreach ($itemList as $key => $val)
        {
            $bill_id   = $val['bill_id'];
            $bn        = $val['bn'];
            $num       = intval($val['num']) - intval($val['actual_num']);
            
            //货号未录入
            if(empty($temp_bn[$bill_id][$bn]))
            {
                $out_status = 2;//部分出库
                continue;
            }
            
            //检查数量
            if($temp_num[$bill_id][$bn] > $num)
            {
                $this->end(false, '货号：'. $bn .' 最多可录入数量：'. $num);
            }
            elseif($temp_num[$bill_id][$bn] < $num)
            {
                $out_status = 2;//部分出库
            }
        }
        
        //检查装箱货品的库存
        $error_msg   = '';
        $result      = $stockLib->checkBoxStock($product_info, $stockout_detail['branch_id'], $error_msg, true);
        if(!$result)
        {
            $this->end(false, $error_msg);
        }
        
        //保存装箱信息
        $boxLib    = kernel::single('purchase_purchase_box');
        $result    = $boxLib->batch_create($box_data);
        if(!$result)
        {
            $this->end(false, '保存装箱信息失败');
        }
        
        //更新运单号、仓库出库数量
        $stockoutObj->update(array('delivery_no'=>$delivery_no, 'branch_out_num'=>$branch_out_num), array('stockout_id'=>$stockout_id));
        
        //事件触发,通知OMS出库
        $result    = kernel::single('wms_event_trigger_vopoutstorage')->outStorage(array('iso_id'=>$stockout_id), true);
        
        if($result['rsp'] == 'fail')
        {
            $log_msg    = '通知OMS出库失败：'. $result['msg'];
            $logObj->write_log('check_stockout_bills@ome', $stockout_id, $log_msg);
            
            $this->end(true, $log_msg);
        }
        
        //logs
        $log_msg    = '自有仓储';
        $log_msg    .= ($out_status == 2 ? '部分出库' : '全部出库');
        $log_msg    .= '成功,运单号：'. $delivery_no;
        
        $logObj->write_log('check_stockout_bills@ome', $stockout_id, $log_msg);
        
        //[部分发货]需要人工确认后才生成出入库明细
        if($out_status == 2)
        {
            $this->end(true, '部分出库成功');
        }
        
        $this->end(true, '出库成功');
    }
    
    /**
     * 录入装箱信息
     */
    function storage_life_box()
    {
        $stockout_id     = $_POST['stockout_id'];
        $has_box_info    = $_POST['has_box_info'] ? $_POST['has_box_info'] : 1;
        
        if(empty($stockout_id))
        {
            die('无效操作，请检查！');
        }
        
        if($has_box_info != 1)
        {
            $pickObj         = app::get('purchase')->model('pick_bills');
            $stockitemObj    = app::get('purchase')->model('pick_stockout_bill_items');
            $data            = json_decode($has_box_info, true);
            
            foreach ($data as $key => $val)
            {
                //出库单明细
                $filter = array('stockout_id'=>$stockout_id, 'bill_id'=>$val['bill_id'], 'bn'=>$val['bn'], 'is_del'=>'false');
                $row    = $stockitemObj->dump($filter, 'bill_id, product_name, size, price, market_price');
                $val['size']            = $row['size'];
                $val['price']           = $row['price'];
                $val['market_price']    = $row['market_price'];
                $val['product_name']    = $row['product_name'];
                
                //拣货单号和PO单号
                $bill_id     = $row['bill_id'];
                $pickInfo    = $pickObj->dump(array('bill_id'=>$bill_id), 'pick_no, po_bn');
                
                $val['pick_no']    = $pickInfo['pick_no'];
                $val['po_bn']      = $pickInfo['po_bn'];
                
                $data[$key]    = $val;
            }
            
            $has_box_info    = json_encode($data);
        }
        
        $this->pagedata['stockout_id']  = $stockout_id;
        $this->pagedata['has_box_info'] = $has_box_info;
        $this->page('admin/vop/storage_life_box.html');
    }
    
    /**
     * 获取出库单货号信息
     */
    function getItemBn()
    {
        $stockout_id   = $_POST['stockout_id'];
        $pick_bn       = trim($_POST['pick_bn']);
        $product_bn    = trim($_POST['product_bn']);
        $box_num       = intval($_POST['box_num']);
        $box_no        = trim($_POST['box_no']);
        
        if(empty($stockout_id) || empty($pick_bn) || empty($product_bn) || empty($box_num) || empty($box_no))
        {
            echo json_encode(array('rsp'=>'error', 'msg'=>'无效操作'));
            exit;
        }
        
        //拣货单信息
        $pickObj    = app::get('purchase')->model('pick_bills');
        $pickInfo   = $pickObj->dump(array('pick_no'=>$pick_bn), 'bill_id');
        if(empty($pickObj))
        {
            echo json_encode(array('rsp'=>'error', 'msg'=>'拣货单不存在'));
            exit;
        }
        
        //出库单明细
        $stockitemObj    = app::get('purchase')->model('pick_stockout_bill_items');
        
        $filter  = array('stockout_id'=>$stockout_id, 'bill_id'=>$pickInfo['bill_id'], 'bn'=>$product_bn, 'is_del'=>'false');
        $data    = $stockitemObj->dump($filter, '*');
        if(empty($data))
        {
            echo json_encode(array('rsp'=>'error', 'msg'=>'货号：'. $product_bn .'不存在'));
            exit;
        }
        if($box_num > $data['num'])
        {
            echo json_encode(array('rsp'=>'error', 'msg'=>'货号：'. $product_bn .'最多可录入数量：'. $data['num']));
            exit;
        }
        
        $data['rsp']    ='succ';
        
        //拣货单号和PO单号
        $pickObj     = app::get('purchase')->model('pick_bills');
        $bill_id     = $data['bill_id'];
        $pickInfo    = $pickObj->dump(array('bill_id'=>$bill_id), 'pick_no, po_bn');
        
        $data['pick_no']    = $pickInfo['pick_no'];
        $data['po_bn']      = $pickInfo['po_bn'];
        $data['box_num']    = $box_num;
        $data['box_no']     = $box_no;
        $data['item_num']   = $data['num'];
        
        echo json_encode($data);
        exit;
    }
    
    /**
     * 格式化装箱信息
     */
    function checkBoxInfo()
    {
        $stockout_id   = $_POST['stockout_id'];
        $bill_ids      = $_POST['bill_ids'];
        $bns           = $_POST['bns'];
        $box_nums      = $_POST['box_nums'];
        $box_nos       = $_POST['box_nos'];
        
        if(empty($stockout_id) || empty($bill_ids) || empty($bns) || empty($box_nums) || empty($box_nos))
        {
            echo json_encode(array('code'=>'error', 'msg'=>'没有装箱信息或者数据有误'));
            exit;
        }
        
        $data        = array();
        $out_status  = 1;//装箱出库状态1:全部出库,2:部分出库
        
        $temp_bn  = array();
        $temp_num = array();
        $temp_box = array();
        
        foreach ($bns as $key => $val)
        {
            $bill_id       = $bill_ids[$key];
            $in_box_num    = intval($box_nums[$key]);
            $in_box_no     = $box_nos[$key];
            
            if(empty($val) || empty($bill_id) || empty($in_box_num) || empty($in_box_no))
            {
                echo json_encode(array('code'=>'error', 'msg'=>'货号：'. $val .' 未输入数量或箱号'));
                exit;
            }
            
            $data[]    = array('bill_id'=>$bill_id, 'bn'=>$val, 'box_num'=>$in_box_num, 'box_no'=>$in_box_no);
            
            //货号
            $temp_bn[$bill_id][$val]    = $val;
            
            //统计数量
            $temp_num[$bill_id][$val]    += $in_box_num;
            
            //检查不允许同货号出现相同的箱号(不同货号箱号可以相同)
            if(in_array($in_box_no, $temp_box[$bill_id][$val]))
            {
                echo json_encode(array('code'=>'error', 'msg'=>'货号：'. $val .' 录入的箱号：'. $in_box_no .' 不能重复'));
                exit;
            }
            $temp_box[$bill_id][$val][]    = $in_box_no;
        }
        
        //出库单明细
        $stockitemObj    = app::get('purchase')->model('pick_stockout_bill_items');
        $itemList        = $stockitemObj->getList('bill_id, stockout_item_id, bn, num, actual_num', array('stockout_id'=>$stockout_id, 'is_del'=>'false'));
        
        foreach ($itemList as $key => $val)
        {
            $bill_id = $val['bill_id'];
            $bn      = $val['bn'];
            $num     = intval($val['num']) - intval($val['actual_num']);
            
            //货号未录入
            if(empty($temp_num[$bill_id][$bn]))
            {
                $out_status = 2;//部分出库
                continue;
            }
            
            //检查数量
            if($temp_num[$bill_id][$bn] > $num)
            {
                echo json_encode(array('code'=>'error', 'msg'=>'货号：'. $bn .' 最多可录入数量：'. $num));
                exit;
            }
            elseif($temp_num[$bill_id][$bn] < $num)
            {
                $out_status = 2;//部分出库
            }
        }
        
        //格式化实际录入数量
        $input_nums    = array();
        foreach ($temp_bn as $bill_id => $items)
        {
            foreach ($items as $bn_key => $bn_val)
            {
                $input_nums[]    = array('bill_id'=>$bill_id, 'bn'=>$bn_val, 'num'=>$temp_num[$bill_id][$bn_key]);
            }
        }
        $input_nums    = json_encode($input_nums);
        
        $box_data    = json_encode($data);
        unset($temp_bn, $temp_box, $temp_num, $itemList);
        
        echo json_encode(array('code'=>'succ', 'data'=>$box_data, 'out_status'=>$out_status, 'input_nums'=>$input_nums));
        exit;
    }
}