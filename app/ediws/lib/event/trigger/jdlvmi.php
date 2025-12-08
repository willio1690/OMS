<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_event_trigger_jdlvmi {

   
    /**
     * 添加Shippackage
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function addShippackage($data) {
       
        $vopreturnObj = app::get('console')->model('vopreturn');

        $itemObj = app::get('console')->model('vopreturn_items');
        
        $main = $this->formatShippackagedata($data);

        $shop_id = $main['shop_id'];


        $shops = $this->getShops($shop_id);
        if(!$main['shop_type'])  $main['shop_type'] = $shops['shop_type'];
        if($shops['config']['ediwbranch_bn']){
            $branchs = $this->getBranch($shops['config']['ediwbranch_bn']);
            if($branchs){
                $main['in_branch_id'] = $branchs['branch_id'];
            }
        }


        $logObj = app::get('ome')->model('operation_log');
        $items = $main['items'];
        unset($main['items']);
        if(empty($main) || empty($main['return_sn']) || empty($items)) {
            return array(false, '数据不全');
        }
        $mainObj = app::get('console')->model('vopreturn');
        $r = $mainObj->db_dump(array('return_sn'=>$main['return_sn']), 'id');
  
        if(!$r) {
           
            app::get('console')->model('vopreturn')->insert($main);
            if(!$main['id']) {
                return [false, $main['return_sn'].'主表保存失败'];
            }
            $mainId = $main['id'];
            $logObj->write_log('vopreturn@console',$mainId,'主表写入成功');
           
        } else {
            
            
           return array(false, $main['packageid'].'已存在');
        }
        
        
        $itemObj = app::get('console')->model('vopreturn_items');
        if($itemObj->db_dump(['return_id'=>$mainId],'id')) {
            return [true, $main['return_sn'].'已存在明细'];
        }
        $insertData = array();
        
        foreach($items as $v) {
            $v['return_id'] = $mainId;
            
            $insertData[] = $v;
        }

    
        kernel::database()->beginTransaction();
      
        $sql = kernel::single('ome_func')->get_insert_sql($itemObj, $insertData);
        $itemObj->db->exec($sql);
        $logObj->write_log('vopreturn@console',$mainId,'明细写入成功');
        kernel::database()->commit();

        $rs = kernel::single('ediws_jdlvmi')->autoConfirm($mainId);

        return array(true);
    }


   
   

    /**
     * formatShippackagedata
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function formatShippackagedata($params){
       
        $data = array(
            'return_sn'     =>  $params['packageId'],
            'total_qtys'    =>  $params['wareNum'],
            'total_amount'  =>  $params['sumPackagePrice'],
            'warehouse'     =>  $params['storeName'],

            'out_time'      =>  $params['createTime']/1000,
            'signtime'      =>  $params['signTime']/1000,
            'create_time'   =>  time(),
         
            'consignee'     =>  $params['confirmReceiptPeople'],

            'address'       =>  $params['receiveAddress'],
            'logi_no'       =>  $params['shipCode'],

            'logi_name'     =>  $params['carriersName'],
            'storeid'       =>  $params['storeId'],

            'shop_id'       =>  $params['shop_id'],
            'shop_type'     =>  $params['shop_type'],

        );


        $items = array();
        $packagedetaillist = $params['items']['packageDetailList'];

        $accountordersLib = kernel::single('ediws_accountorders');
        if(empty($packagedetaillist)){
            $result = array('rsp'=>'fail','msg'=>'明细不可以为空');
            return $result;
        }
        $edijdlLib = kernel::single('ediws_jdlvmi');
     
        foreach($packagedetaillist as $v){
            $bm = array();
            $barcode = $v['upcCode'];
            //
            $bm = $edijdlLib->get_sku($params['shop_id'],$v['wareId']);

            if($v['packagePrice']<=0 && $v['saleOrdId']){

                $accorders = $accountordersLib->getPiriceByOrderId($v['wareId'],$v['saleOrdId']);
                if($accorders){
                    $v['packagePrice'] = $accorders['price'];
                }
            }
            $items[] = array(

                'shop_product_bn'=> $v['wareId'],
                'partcode'      =>  $v['partCode'],
                'product_name'  =>  $v['wareName'],
                //'barcode'       =>  $v['upcCode'],
                'material_bn'   =>  $bm['material_bn'],
                'bm_id'         =>  $bm['bm_id'],
                'qty'           =>  1,
                'price'         =>  $v['packagePrice'],
                'saleordid'     =>  $v['saleOrdId'],
                'transferoutcode'=> $v['transferOutCode'],
                'originsaleordid'=> $v['originSaleOrdId'],
            );
        }

        $data['items'] = $items;
        return $data;
    }



    /**
     * 获取BnBybarcode
     * @param mixed $barcode barcode
     * @return mixed 返回结果
     */
    public function getBnBybarcode($barcode){
        $materialObj= app::get('material')->model('basic_material');
        $codebaseObj = app::get('material')->model('codebase');
        $code_info = $codebaseObj->dump(array('type'=>1,'code'=>$barcode),'bm_id');
         
        if($code_info){
            $material = $materialObj->dump(array('bm_id'=>$code_info['bm_id']),'material_bn,bm_id');
             
            return $material;
        }
        return false;
    }


    /**
     * 获取BranchByaddress
     * @param mixed $address address
     * @return mixed 返回结果
     */
    public function getBranchByaddress($address){
        $db = kernel::database();
        $branchs = $db->selectrow("select branch_id,branch_bn from sdb_ome_branch WHERE address='".$address."'");

        return $branchs;
    }


    /**
     * 获取Branch
     * @param mixed $branch_bn branch_bn
     * @return mixed 返回结果
     */
    public function getBranch($branch_bn){
        $db = kernel::database();
        $branchs = $db->selectrow("select branch_id,branch_bn from sdb_ome_branch WHERE branch_bn='".$branch_bn."'");

        return $branchs;
    }

    /**
     * 添加Reship
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function addReship($data){
        if(empty($data) || empty($data['id']) || empty($data['details'])) {
            return array(false, '数据不全');
        }


        $main = $this->formatReshipData($data);
        $items = $main['items'];
        if(empty($items)) {
            return array(false, '数据不全');
        }

        unset($main['items']);
       
       
        $mainObj = app::get('ediws')->model('reship');
        $r = $mainObj->db_dump(array('reship_bn'=>$main['reship_bn']), 'reship_bn');
       
        if(!$r) {
            
            
            $main['addtime'] = time();
            $mainObj->insert($main);


            if(!$main['reship_id']) {
                return array(false, $main['reship_bn'].'主表保存失败');
            }
            $mainId = $main['reship_id'];
           
        } else {
            return array(false, $main['reship_bn'].'已存在');
        }
        $itemObj = app::get('ediws')->model('reship_items');
        if($itemObj->db_dump(array('reship_id'=>$mainId),'reship_id')) {
            return array(true, $main['reship_bn'].'已存在明细');
        }
        
        kernel::database()->beginTransaction();
       
        if($itemObj->db_dump(array('reship_id'=>$mainId),'items_id')) {
            kernel::database()->rollBack();
            return array(true, $main['reship_bn'].'已存在明细');
        }
        $insertData = array();
        foreach($items as $v) {
                
            $v['reship_id'] = $mainId;
            $insertData[] = $v;
        }
        $sql = kernel::single('ediws_func')->get_insert_sql($itemObj, $insertData);
        $itemObj->db->exec($sql);
        
        kernel::database()->commit();
        return array(true);
    }

    /**
     * formatReshipData
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function formatReshipData($params){

        if(!in_array($params['source'],array('3','10'))){
            return false;
        }
        $data = array(
            'shop_id'           => $params['shop_id'],
            'reship_bn'         => $params['id'],
            'purchasechannelid' => $params['purchaseChannelId'],
            'outchannelname'    => $params['outChannelName'],
            'createtime'        => strtotime($params['createTime']),
            'wareaddr'          => $params['wareAddr'],
            'source'            => $params['source'],
            'sourcename'        => $params['sourceName'],
            'extends'           => serialize($params),

        );

        $details = $params['details'];
        $items = array();
        foreach($details as $v){
            $items[] = array(
                'skuid'     =>$v['skuId'],
                'skuname'   =>$v['skuName'],
                'actualnum' =>$v['actualNum'],

               
            );
        }
        $data['items'] = $items;
        return $data;
    }


    /**
     * 获取Shops
     * @param mixed $shop_id ID
     * @return mixed 返回结果
     */
    public function getShops($shop_id){

        $shopObj    = app::get('ome')->model('shop');
        $shops   = $shopObj->db_dump(array('shop_id'=>$shop_id),'shop_type,shop_id, shop_bn, name,config');
        $shops['config'] = $shops['config'] ? unserialize($shops['config']) : '';
          
        return $shops;
    }


    /**
     * 添加Refundinfo
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function addRefundinfo($data){
        if(empty($data) || empty($data['outNo']) || empty($data['refundId'])) {
            return array(false, '数据不全');
        }


        $main = $this->formatRefundinfodata($data);
        $items = $main['items'];
        if(empty($items)) {
            return array(false, '数据不全');
        }

        unset($main['items']);
       
       
        $mainObj = app::get('ediws')->model('refundinfo');
        $r = $mainObj->db_dump(array('refundid'=>$main['refundid']), 'refundid');
       
        if(!$r) {
            
            
            $main['create_time'] = time();
            $mainObj->insert($main);
            if(!$main['refundinfo_id']) {
                return array(false, $main['refundid'].'主表保存失败');
            }
            $mainId = $main['refundinfo_id'];
           
        } else {
            return array(false, $main['refundid'].'已存在');
        }
        $itemObj = app::get('ediws')->model('refundinfo_items');
        if($itemObj->db_dump(array('refundinfo_id'=>$mainId),'refundinfo_id')) {
            return array(true, $main['refundid'].'已存在明细');
        }
        
        kernel::database()->beginTransaction();
       
        if($itemObj->db_dump(array('refundinfo_id'=>$mainId),'items_id')) {
            kernel::database()->rollBack();
            return array(true, $main['refundid'].'已存在明细');
        }
        $insertData = array();
        foreach($items as $v) {
                
            $v['refundinfo_id'] = $mainId;
            $insertData[] = $v;
        }
        $sql = kernel::single('ediws_func')->get_insert_sql($itemObj, $insertData);
        $itemObj->db->exec($sql);
        
        kernel::database()->commit();
        return array(true);
    }

    
    /**
     * formatRefundinfodata
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function formatRefundinfodata($params){

        $data = array(

            'amount'            =>  $params['amount'],
            'financestatus'     =>  $params['financeStatus'],
            'orgname'           =>  $params['orgName'],
            'outtypedesc'       =>  $params['outTypeDesc'],
            'storeid'           =>  $params['storeId'],
            'outtype'           =>  $params['outType'],
            'orgid'             =>  $params['orgId'],
            'paytype'           =>  $params['payType'],
            'warecount'         =>  $params['wareCount'],
            'statusname'        =>  $params['statusName'],
            'salespin'          =>  $params['salesPin'],
            'storename'         =>  $params['storeName'],
            'applydatetime'     =>  $params['applyDateTime']/1000,
            'outno'             =>  $params['outNo'],
            'refundid'          =>  $params['refundId'],
            'providername'      =>  $params['providerName'],
            'contacts'          =>  $params['contacts'],

        );
        $edijdlLib = kernel::single('ediws_jdlvmi');
        $items = array();
        foreach($params['items'] as $v){
            
            $bm = array();
            $barcode = $v['wareId'];
            //
            $bm = $edijdlLib->get_sku($params['shop_id'],$barcode);
            $items[] = array(
                'wareid'                =>$v['wareId'],
                'warename'              =>$v['wareName'],
                'material_bn'           =>  $bm['material_bn'],
                'bm_id'                 =>  $bm['bm_id'],
                'signtime'              =>$v['signTime']/1000,
                'remark'                =>$v['remark'],
                'partcode'              =>$v['partCode'],
                'warename'              =>$v['wareName'],
                'saleordid'             =>$v['saleOrdId'],
                'shipcode'              =>$v['shipCode'],
                'confirmreceiptpeople'  =>$v['confirmReceiptPeople'],
                'price'                 =>$v['price'],
                'returnReason'          =>$v['returnReason'],

            );
        }
        $data['items'] = $items;
        return $data;
    }


    /**
     * 更新ShippackageList
     * @return mixed 返回值
     */
    public function updateShippackageList(){
        


        return true;
    }
    
}
