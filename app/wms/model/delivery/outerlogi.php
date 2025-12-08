<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_mdl_delivery_outerlogi extends dbeav_model{

    //是否有导出配置
    var $has_export_cnf = true;
    public $export_name = 'wms发货单';

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false)
    {
        $table_name = 'delivery';
        if($real){
            return DB_PREFIX.'wms_'.$table_name;
        }else{
            return $table_name;
        }
    }

    /**
     * exportName
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function exportName(&$data)
    {
        $data['name'] = '快速发货模板'.date('Ymd');
    }
    
    #导出快速发货模板
    function exportTemplate($filter = ''){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }
    /**
     * io_title
     * @param mixed $filter filter
     * @param mixed $ioType ioType
     * @return mixed 返回值
     */
    public function io_title( $filter=null,$ioType='csv'){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
                    '*:发货单号' => 'delivery_bn',
                    '*:物流单号' => 'logi_no',
                    '*:重量' => 'weight',
                    '*:物流公司' => 'logi_name',
                    '*:收件人' => 'ship_name'
                );
        }
       $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
    }

    /**
     * fgetlist_csv
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $exportType exportType
     * @return mixed 返回值
     */
    public function fgetlist_csv(&$data,$filter,$offset,$exportType = 1)
    {
        if($filter['isSelectedAll']){
            
            $new_filter = Array
            (
                'type' => 'normal',
                'pause' => "FALSE",
                'parent_id' => 0,
                'disabled' => "false",
                'status' => array('ready','progress'),
                'ext_branch_id' => $filter['ext_branch_id']
            );
        }else{
            $filter['process'] = 'false';
            $new_filter = $filter;
        }
        
        $title = array();
        if ( !$data['title'] ) {
            foreach( $this->io_title() as $k => $v ){
                $title[] = $v;
            }
            $data['title'] = '"'.implode('","',$title).'"';

            return true;
        }

 

        $list = $this->getList('delivery_bn,logi_name,ship_name',$new_filter,0,-1);

        if ($list) {
            $contents = array();
            foreach ($list as $v) {
                $contents = array(
                    'delivery_bn' => $v['delivery_bn']."\t",
                    'logi_no'     => '',
                    'weight'      => '',
                    'logi_name' => $v['logi_name'],
                    'ship_name' => $v['ship_name'],
                );
                $data['contents'][] = implode(',', $contents);
            }
        }
        return false;
    }
    /**
     * 获取OuterData
     * @param mixed $filter filter
     * @return mixed 返回结果
     */
    public function getOuterData($filter){
        if($filter['isSelectedAll']){
            $new_filter = Array(
                    'type' => 'normal',
                    'pause' => "FALSE",
                    'parent_id' => 0,
                    'disabled' => "false",
                    'status' => array('ready','progress'),
                    'ext_branch_id' => $filter['ext_branch_id']
            );
            
            //第三方发货_加入固定条件
            $oBranch = app::get('ome')->model('branch');
            
            //所有第三方仓
            $outerBranch = array();
            $tmpBranchList = $oBranch->getList('branch_id',array('owner'=>'2'));
            foreach ($tmpBranchList as $key => $value) {
                $outerBranch[] = $value['branch_id'];
            }
            unset($tmpBranchList);
            
            //获取操作员管辖仓库
            $is_super = kernel::single('desktop_user')->is_super();
            if (!$is_super) {
                $branch_ids = $oBranch->getBranchByUser(true);
                if ($branch_ids) {
                    $new_filter['ext_branch_id'] = $_POST['branch_id'] ? $_POST['branch_id'] : $branch_ids;
            
                    $new_filter['ext_branch_id'] = array_intersect($new_filter['ext_branch_id'], $outerBranch);
                } else {
                    $new_filter['ext_branch_id'] = 'false';
                }
            } else {
                $new_filter['ext_branch_id'] = $_POST['branch_id'] ? $_POST['branch_id'] : $outerBranch;
            }
            
            #商品种类
            if(isset($_POST['skuNum']) && isset($_POST['_skuNum_search']))
            {
                $new_filter['skuNum|' . $_POST['_skuNum_search']]    = intval($_POST['skuNum']);
            }
            
            #商品总数量
            if(isset($_POST['itemNum']) && isset($_POST['_itemNum_search']))
            {
                $new_filter['itemNum|' . $_POST['_itemNum_search']]    = intval($_POST['itemNum']);
            }
            
            #发货单分组
            if($_POST['delivery_group'])
            {
                $new_filter['delivery_group']    = $_POST['delivery_group'];
            }
            
            #来源店铺
            if($_POST['shop_id'])
            {
                $new_filter['shop_id']    = $_POST['shop_id'];
            }
            
            #货号
            if($_POST['product_bn'])
            {
                $itemsObj    = $this->app->model("delivery_items");
                $rows        = $itemsObj->getDeliveryIdByPbn($filter['product_bn']);
                
                if($rows)
                {
                    $deliveryId_list    = array();
                    foreach($rows as $row){
                        $deliveryId_list[]    = $row['delivery_id'];
                    }
                    $new_filter['delivery_id']    = $deliveryId_list;
                }
            }
        }else{
            $filter['process'] = 'false';
            $new_filter = $filter;
        }
        $list = $this->getList('delivery_bn,logi_name,ship_name',$new_filter,0,-1);
        if ($list) {
            $contents = array();
            foreach ($list as $v) {
                $contents = array(
                        'delivery_bn' => $v['delivery_bn']."\t",
                        'logi_no'     => '',
                        'weight'      => '',
                        'logi_name' => $v['logi_name'],
                        'ship_name' => $v['ship_name'],
                );
               $data['content'][] =  kernel::single('base_charset')->utf2local('"'.implode( '","', $contents ).'"');
            }
        }
        return $data;
    }    


    /**
     * prepared_import_csv
     * @return mixed 返回值
     */
    public function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
        $this->fileData = [];
    }

    /**
     * prepared_import_csv_row
     * @param mixed $row row
     * @param mixed $title title
     * @param mixed $tmpl tmpl
     * @param mixed $mark mark
     * @param mixed $newObjFlag newObjFlag
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function prepared_import_csv_row($row,&$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
    {
        if(empty($row)) return false;
        
        //读取缓存中的导入数据
        $fileData = $this->fileData;
        
        //title
        //$mark = false;
        if( substr($row[0],0,1) == '*' ){
            $this->nums = 1;
            $title = array_flip($row);

            $this->logi_no_list    = array();
            
            //$mark = 'title';
            
            return false;
        }

        if (empty($title)) {
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['error'] = "请使用正确的模板格式！";
            return false;
        }

        $delivery_bn = trim(str_replace(['"', "'"], '', $row[$title['*:发货单号']]));
        //$outer_delivery_bn = $row[$title['*:外部发货单号']];
        //$outer_supplier = $row[$title['*:承接商']];
        $logi_no = trim($row[$title['*:物流单号']]);
        //$delivery_cost_actual = $row[$title['*:实际物流费用']];
        $weight = trim($row[$title['*:重量']]);

        if(isset($this->nums)){
            $this->nums++;
            if($this->nums > 5000){
                
                //清空缓存导入数据
                base_kvstore::instance('wms_delivery')->store('outerlogi-'.$this->ioObj->cacheTime, '');
                
                $msg['error'] = "导入的数据量过大，请减少到5000条以下！";
                return false;
            }
        }
        
        if (empty($logi_no)) {
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['warning'][] = 'Line '.$this->nums.'：运单号不能都为空！';
            return false;
        }

        if (empty($delivery_bn)) {
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['warning'][] = 'Line '.$this->nums.'：发货单号不能都为空！';
            return false;
        }

        # 获取第三方发货仓
        $branchList = app::get('ome')->model('branch')->getList('branch_id',array('owner'=>'2'));
        $branchIds = array();
        foreach ($branchList as $key => $value) {
            $branchIds[] = $value['branch_id'];
        }
        
        if (empty($branchIds)) {
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['error'] = '第三方仓不存在，请新建！！！';
            return false;
        }

        //判断发货单是否存在
        $deliModel = app::get('wms')->model('delivery');
        $delivery = $deliModel->dump(array('delivery_bn'=>$delivery_bn),'delivery_id,status,logi_id,branch_id,net_weight,ship_area');
        if (!$delivery) {
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['warning'][] = 'Line '.$this->nums.'：发货单号不存在！';
            return false;
        }

        //验证运单号是否被使用过
        $dlyCheckLib = kernel::single('wms_delivery_check');
        $logi_no_exist = $dlyCheckLib->existExpressNoBill($logi_no,$delivery['delivery_id']);
        if ($logi_no_exist) {
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['warning'][] = 'Line '.$this->nums.'：运单号已被使用！';
            return false;
        }

        //判断本次导入是否有重复的物流单号
        if(in_array($logi_no, $this->logi_no_list)){
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['warning'][] = 'Line '.$this->nums.'：物流单号【'. $logi_no .'】重复！';
            return false;
        }
        $this->logi_no_list[]    = $logi_no;

        if ($delivery['status'] == 3) {
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['warning'][] = 'Line '.$this->nums.'：发货单号【'.$delivery_bn.'】已发货！';
            return false;
        }

        if (!in_array($delivery['branch_id'], $branchIds)) {
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['warning'][] = 'Line '.$this->nums.'：不是第三方仓不能发货！';
            return false;
        }
        
        $logi_name = trim($row[$title['*:物流公司']]);
        if(!empty($logi_name)){
            //检测物流公司是否改变
            $filter['delivery_bn']  = $delivery_bn;
            $filter['logi_name'] = $logi_name;
            $rs = $deliModel->getList('delivery_id',$filter);
            
            //当改变了物流公司,检测物流公司是否存在
            if(empty($rs)){
                //检测物流公司是否存在
                $obj_dly_corp = app::get('ome')->model('dly_corp');
                $corp_info = $obj_dly_corp->dump(array('name'=>$logi_name),'corp_id,name');
                if(empty($corp_info)){
                    
                    //清空缓存导入数据
                    $this->fileData = [];
                    
                    $msg['warning'][] = 'Line '.$this->nums.'：物流公司不存在！';
                    return false;
                }
            }
        }
        
        // 库存验证
        $branch_pObj = app::get('ome')->model('branch_product');
        $delivery_items = $this->app->model('delivery_items')->getList('*',array('delivery_id'=>$delivery[0]['delivery_id']));
        foreach ($delivery_items as $key=>$value) {
            $bp = $branch_pObj->dump(array('branch_id'=>$delivery['branch_id'],'product_id'=>$value['product_id']),'store');
            if ($bp['store'] < $value['number']) {
                
                //清空缓存导入数据
                $this->fileData = [];
                
                $msg['warning'][] = 'Line '.$this->nums.'：【'.$value['product_name'].'】商品库存不足';
                return false;
            }
        }

        $minWeight = app::get('wms')->getConf('wms.delivery.minWeight');
        if (empty($weight)) {
            $weight = $delivery['net_weight'] ? $delivery['net_weight'] : $minWeight;
        }

        $weightSet = app::get('wms')->getConf('wms.delivery.weight');
        if (empty($weight) && $weightSet=='on'){
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['warning'][] = 'Line '.$this->nums.'：请输入重量信息！';
            return false;
        }

        $maxWeight = app::get('wms')->getConf('wms.delivery.maxWeight');
        if($weight < $minWeight || $weight > $maxWeight){
            
            //清空缓存导入数据
            $this->fileData = [];
            
            $msg['warning'][] = 'Line '.$this->nums.'：包裹重量超出系统设置范围！';
            return false;
        }

        //获取物流费用
        $wmsCommonLib = kernel::single('wms_common');

        $area = $delivery['consignee']['area'];
        $arrArea = explode(':', $area);
        $area_id = $arrArea[2];
        $delivery_cost_actual = $wmsCommonLib->getDeliveryFreight($area_id,$delivery['logi_id'],$weight);

        $opInfo = kernel::single('ome_func')->getDesktopUser();

        $sdf = array(
            'delivery_id' => $delivery['delivery_id'],
            //'outer_delivery_bn' => $outer_delivery_bn,
            //'outer_supplier' => $outer_supplier,
            'logi_no' => $logi_no,
            'delivery_cost_actual' => $delivery_cost_actual ? $delivery_cost_actual : 0,
            'weight' => $weight ? $weight : 0,
            'opInfo' => $opInfo,
            'is_super' => kernel::single('desktop_user')->is_super(),
            'user_data' => kernel::single('desktop_user')->user_data,
            //'verify' => $delivery[0]['verify'],
            //'stock_status' => $delivery[0]['stock_status'],
            //'deliv_status' => $delivery[0]['deliv_status'],
            //'expre_status' => $delivery[0]['expre_status'],
        );
        
        if ($corp_info) {
            $sdf['logi_id'] = $corp_info['corp_id'];
            $sdf['logi_name'] = $corp_info['name'];
        }

        $fileData['outerlogi']['contents'][] = $sdf;
        $this->fileData = $fileData;
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    /**
     * finish_import_csv
     * @return mixed 返回值
     */
    public function finish_import_csv(){
        $fileData = $this->fileData;

        $queueObj = app::get('base')->model('queue');
        
        $pSdf = array();
        $count = 0;
        $limit = 50;
        $page = 0;
        
        //分页
        foreach ($fileData['outerlogi']['contents'] as $k => $aPi){
            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }
            $pSdf[$page][] = $aPi;
        }
        
        //放入queue队列
        foreach($pSdf as $v){
            $queueData = array(
                'queue_title'=>'第三方发货导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'wms',
                    'mdl' => 'delivery_outerlogi'
                ),
                'worker'=>'wms_delivery_outerlogi_to_import.run',
            );
            $queueObj->save($queueData);
        }
        
        //注销
        unset($fileData, $pSdf);
        
        return null;
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        $wmsObj = app::get('wms')->model("delivery");
        
        //setting
        $where = '';
        
        if(isset($filter['extend_delivery_id'])){
            $where .= ' OR delivery_id IN ('.implode(',', $filter['extend_delivery_id']).')';
            unset($filter['extend_delivery_id']);
        }
        if (isset($filter['member_uname'])){
            $memberObj = app::get('ome')->model("members");
            $rows = $memberObj->getList('member_id',array('uname|has'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }
            $where .= '  AND member_id IN ('.implode(',', $memberId).')';
            unset($filter['member_uname']);
        }
        if (isset($filter['order_bn'])){
            $orderObj = app::get('ome')->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn'=>$filter['order_bn']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }

            $deliOrderObj = app::get('ome')->model("delivery_order");
            $rows = $deliOrderObj->getList('delivery_id',array('order_id'=>$orderId));
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            //
            
            $_delivery_bn = $wmsObj->_getdelivery_bn($deliveryId);

            $where .= '  AND outer_delivery_bn IN (\''.implode('\',\'', $_delivery_bn).'\')';
            unset($filter['order_bn']);
        }

        if(isset($filter['no_logi_no']) && $filter['no_logi_no'] == true){
            $rows = $this->db->select("select delivery_id from sdb_wms_delivery_bill where logi_no = '' or logi_no is null");
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['no_logi_no']);
        }

        if(isset($filter['product_bn'])){
            $itemsObj = $this->app->model("delivery_items");
            $rows = $itemsObj->getDeliveryIdByPbn($filter['product_bn']);
            
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            //$_delivery_bn = $this->_getdelivery_bn($deliveryId);
            $where .= '  AND delivery_id IN (\''.implode('\',\'', $deliveryId).'\')';
            unset($filter['product_bn'],$_delivery_bn);
        }
        if(isset($filter['product_barcode'])){
            $itemsObj = $this->app->model("delivery_items");
            $rows = $itemsObj->getDeliveryIdByPbarcode($filter['product_barcode']);
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            //$_delivery_bn = $this->_getdelivery_bn($deliveryId);
            $where .= '  AND delivery_id IN (\''.implode('\',\'', $deliveryId).'\')';
            
            unset($filter['product_barcode'],$_delivery_bn);
        }
        if(isset($filter['logi_no_ext'])){
            $logObj = $this->app->model("delivery_bill");
            $rows = $logObj->getlist('delivery_id',array('logi_no'=>$filter['logi_no_ext']));
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['logi_no_ext']);
        }
         if(isset($filter['addonSQL'])){
            $where .= ' AND '.$filter['addonSQL'];
            unset($filter['addonSQL']);
        }
        if(isset($filter['delivery_ident'])){
            $arr_delivery_ident = explode('_',$filter['delivery_ident']);
            $mdl_queue = app::get('ome')->model("print_queue");
            if(count($arr_delivery_ident) == 2){
                $ident_dly = array_pop($arr_delivery_ident);
                $ident = implode('-',$arr_delivery_ident);
                $queueItem = $mdl_queue->findQueueItem($ident,$ident_dly);
                if($queueItem){
                    $where .= '  AND delivery_id ='.$queueItem['delivery_id'].'';
                }else{
                    $where .= '  AND delivery_id IN (0)';
                }
            }else{
                $queue = $mdl_queue->findQueue($filter['delivery_ident'],'dly_bns');
                if($queue){
                    $where .= '  AND delivery_id IN ('.$queue['dly_bns'].')';
                }else{
                    $where .= '  AND delivery_id IN (0)';
                }
            }

            unset($filter['delivery_ident']);
        }
        if(isset($filter['ship_tel_mobile'])){
            $where .= ' AND (ship_tel=\''.$filter['ship_tel_mobile'].'\' or ship_mobile=\''.$filter['ship_tel_mobile'].'\')';
            unset($filter['ship_tel_mobile']);
        }
        if($filter['todo']==1){
            $where .= " AND ((print_status & 1) !=1 or (print_status & 2) !=2 or (print_status & 4) !=4)";
            unset($filter['todo']);
        }
        if($filter['todo']==2){
            $where .= " AND ((print_status & 1) !=1 or (print_status & 4) !=4)";
            unset($filter['todo']);
        }
        if($filter['todo']==3){
            $where .= " AND ((print_status & 2) !=2 or (print_status & 4) !=4)";
            unset($filter['todo']);
        }
        if($filter['todo']==4){
            $where .= " AND (print_status & 4) !=4";
            unset($filter['todo']);
        }

        if (isset($filter['print_finish'])) {
            $where_or = array();
            foreach((array)$filter['print_finish'] as $key=> $value){
                $or = "(deli_cfg='".$key."'";
                switch($value) {
                    case '1_1':
                        $or .= " AND (print_status & 1) =1 and (print_status & 2) =2 ";
                        break;
                    case '1_0':
                        $or .= " AND (print_status & 1) =1 ";
                        break;
                    case '0_1':
                        $or .= " AND (print_status & 2) =2 ";
                        break;
                    case '0_0':
                        break;
                }
                $or .= ')';
                $where_or[] = $or;
            }
            if($where_or){
                $where .= ' AND ('.implode(' OR ',$where_or).')';
            }
            unset($filter['print_finish']);
        }
        if (isset($filter['ext_branch_id'])) {
            if (isset($filter['branch_id'])){
                $filter['branch_id'] = array_intersect((array)$filter['branch_id'],(array)$filter['ext_branch_id']);
                $filter['branch_id'] = $filter['branch_id'] ? $filter['branch_id'] : 'false';
            }else{
                $filter['branch_id'] = $filter['ext_branch_id'];
            }
           
            unset($filter['ext_branch_id']);
        }
         #客服备注
        if(isset($filter['mark_text'])){
            $mark_text = $filter['mark_text'];
            $sql = "SELECT do.delivery_id FROM sdb_ome_delivery_order do JOIN sdb_ome_orders o ON do.order_id=o.order_id  and o.process_status='splited' and  o.mark_text like "."'%{$mark_text}%'";
            $_rows = $this->db->select($sql);
            if(!empty($_rows)){
                foreach($_rows as $_orders){
                    $_delivery[] = $_orders['delivery_id'];
                }
                $_delivery_bn = $wmsObj->_getdelivery_bn($_delivery);
                $where .= '  AND outer_delivery_bn IN (\''.implode('\',\'', $_delivery_bn).'\')';
                unset($filter['mark_text'],$_delivery,$_delivery_bn);
            }
        
        }
        #买家留言
        if(isset($filter['custom_mark'])){
            $custom_mark = $filter['custom_mark'];
            $sql = "SELECT do.delivery_id FROM sdb_ome_delivery_order do JOIN sdb_ome_orders o ON do.order_id=o.order_id  and o.process_status='splited' and  o.custom_mark like "."'%{$custom_mark}%'";
            $_rows = $this->db->select($sql);
            if(!empty($_rows)){
                foreach($_rows as $_orders){
                    $_delivery[] = $_orders['delivery_id'];
                }
                $_delivery_bn = $wmsObj->_getdelivery_bn($_delivery);

                $where .= '  AND outer_delivery_bn IN (\''.implode('\',\'', $_delivery_bn).'\')';
                unset($filter['custom_mark'],$_delivery,$_delivery_bn);
            }
        
        } 
        if (isset($filter['stock_status'])) {
            if ($filter['stock_status'] == 'true') {
                $where .= " AND (print_status & 1) =1";
            }else{
                $where .= " AND (print_status & 1) !=1";
            }
            unset($filter['stock_status']);
        }
        if (isset($filter['deliv_status'])) {
            if ($filter['deliv_status']=='true') {
                $where .= " AND (print_status & 2) =2";
            }else{
                $where .= " AND (print_status & 2) !=2";
            }
            unset($filter['deliv_status']);    
        }
        if (isset($filter['expre_status'])) {
            if ($filter['expre_status']=='true') {
                $where .= " AND (print_status & 4) =4";
            }else{
                $where .= " AND (print_status & 4) !=4";
            }
            unset($filter['expre_status']);    
        }

        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'delivery_bn'=>app::get('base')->_('发货单号'),
            'order_bn'=>app::get('base')->_('订单号'),
            'member_uname'=>app::get('base')->_('用户名'),
            'ship_name'=>app::get('base')->_('收货人'),
            'ship_tel_mobile'=>app::get('base')->_('联系电话'),
            'product_bn'=>app::get('base')->_('货号'),
            'product_barcode'=>app::get('base')->_('条形码'),
            'delivery_ident'=>app::get('base')->_('打印批次号'),
            'outer_delivery_bn'=>app::get('base')->_('外部发货单号'),
            'logi_no_ext'=>app::get('base')->_('物流单号'),
        );

        return array_merge($childOptions,$parentOptions);
    }


    /**
     * 获取ExportDataByCustom
     * @param mixed $fields fields
     * @param mixed $filter filter
     * @param mixed $has_detail has_detail
     * @param mixed $curr_sheet curr_sheet
     * @param mixed $start start
     * @param mixed $end end
     * @param mixed $op_id ID
     * @return mixed 返回结果
     */
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id){
        $data = app::get('wms')->model('delivery')->getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id);
        return $data;
    }

    /**
     * 获取PrimaryIdsByCustom
     * @param mixed $filter filter
     * @param mixed $op_id ID
     * @return mixed 返回结果
     */
    public function getPrimaryIdsByCustom($filter, $op_id){
        $data = app::get('wms')->model('delivery')->getPrimaryIdsByCustom($filter, $op_id);
        return $data;
    }

    /**
     * disabled_export_cols
     * @param mixed $cols cols
     * @return mixed 返回值
     */
    public function disabled_export_cols(&$cols){

        $cols['order_bn'] = array(
            'type' => 'varchar(32)',
            'required' => true,
            'label' => '订单号',
            'editable' => false,
        );

        $cols['logi_no'] = array(
            'type' => 'varchar(50)',
            'default' => '0',
            'label' => '物流单号',
            'editable' => false,
        );

        $cols['tax_no'] = array(
            'type' => 'varchar(50)',
            'label' => '发票号',
            'editable' => false,
        );

        $cols['custom_mark'] = array(
            'type' => 'longtext',
            'required' => true,
            'label' => '买家留言',
            'editable' => false,
            );

        $cols['mark_text'] = array(
            'type' => 'longtext',
            'required' => true,
            'label' => '客服备注',
            'editable' => false,
        );

        unset($cols['is_protect'],$cols['column_ident'],$cols['last_modified'],$cols['memo'],$cols['column_status'],$cols['column_process_status'],$cols['column_print_status'],$cols['column_create'],$cols['column_beartime'],$cols['column_deliveryNumInfo'],$cols['column_content'],$cols['delivery_group'],$cols['sms_group'],$cols['ship_email']);
    }
}