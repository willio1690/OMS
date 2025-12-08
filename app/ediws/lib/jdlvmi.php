<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 出入库单据相关处理
*/
class ediws_jdlvmi{

    /**
     * dealStockinItems
     * @param mixed $iso_id ID
     * @return mixed 返回值
     */

    public function dealStockinItems($iso_id){

        $iso = $this->getisoitems($iso_id);
      
        if($iso['source'] == 'refundinfo'){//新的切换
            $rs = $this->dealIsoItems($iso);

            return true;
        }
        $return_sn = $iso['original_bn'];
        $items = $iso['items'];
        if($items){

            $vopreturnMdl = app::get('console')->model('vopreturn');

            $return_sn = $iso['original_bn'];
            $vopreturn = $vopreturnMdl->db_dump(array('return_sn'=>$return_sn),'*');

            if(empty($vopreturn)) return true;
            if(in_array($vopreturn['in_status'],array('3'))){
                return false;
            }

            $inItems = array();
            
            $return_id = $vopreturn['id'];
            $itemsMdl = app::get('console')->model('vopreturn_items');
            
            foreach($items as $v){

                $num = $v['in_nums'];
                if($num<=0) continue;

                $itemsMdl->db->exec("UPDATE sdb_console_vopreturn_items SET num=".$num." WHERE return_id=".$return_id." AND bm_id='".$v['product_id']."' and num=0 limit 1");

            }

          
            $time = time();
            
            $vopreturnMdl->db->exec("UPDATE sdb_console_vopreturn set status='2',in_status='3',last_modified=".$time.",iostock_time=".$time." WHERE id=".$return_id."");

            $logMsg = '入库完成';
            app::get('ome')->model('operation_log')->write_log('vopreturn@console',$return_id,$logMsg);

            //生成售后单
            $this->syncAftersale($return_id);
        }


    }




    /**
     * confirm
     * @param mixed $returnId ID
     * @param mixed $branch_id ID
     * @return mixed 返回值
     */
    public function confirm($returnId,$branch_id=0){
       
        
        list($rs,$msg) = $this->autoConfirm($returnId,$branch_id);
        $msg = '确认'.$msg;
        app::get('ome')->model('operation_log')->write_log('vopreturn@console',$returnId,$msg);
        return [$rs, $msg];

    }

    /**
     * _dealInItems
     * @param mixed $addData 数据
     * @return mixed 返回值
     */
    public function _dealInItems($addData) {
        if(empty($addData['items'])) {
            return [true, ['msg'=>'缺少明细']];
        }

        $data = array(
            'iostockorder_name' => date('Ymd') . '入库单',
            'supplier'          => '',
            'supplier_id'       => 0,
            'branch'            => $addData['branch_id'],
            'extrabranch_id'    => $addData['extrabranch_id'],
            'type_id'           => ome_iostock::DIRECT_STORAGE,
            'iso_price'         => 0,
            'memo'              => (string)$addData['memo'],
            'operator'          => $op['op_name'],
            'original_bn'       => $addData['original_bn'],
            'original_id'       => $addData['original_id'],
            'products'          => $products,
            'appropriation_no'  => '',
            'bill_type'         => $addData['bill_type'] ? $addData['bill_type'] : 'jdlreturn',
            'iostockorder_bn'   => $addData['io_bn'],
            'io_bn'             => $addData['io_bn'],
            'logi_no'           => $addData['logi_no'],
            'business_bn'       => $addData['business_bn'],
          
        );

        if($addData['source']) $data['source'] = $addData['source'];
        $isoMdl = app::get('taoguaniostockorder')->model('iso');

        $iso = $isoMdl->db_dump(array('iso_bn'=>$data['io_bn'],'type_id'=>$data['type_id'],'bill_type'=>$data['bill_type']),'iso_id');

        if($iso){
            return [false, ['msg'=>$data['io_bn'].':已存在']];
        }

        $itemsMdl = app::get('taoguaniostockorder')->model('iso_items');
        kernel::database()->beginTransaction();
        $products = [];
        foreach ($addData['items'] as $v) {
            $products[$v['product_id']] = [
                'bn' => $v['bn'],
                'name' => $v['name'],
                'nums' => $v['nums'],
                'price' => 0,
                'partcode'  => $v['partcode'],
            ];

            kernel::database()->exec('UPDATE sdb_console_vopreturn_items SET split_num=split_num+'.$v['nums'].' WHERE id='.$v['return_item_id'].' AND qty >= split_num+'.$v['nums'].' limit 1');

            $affect_rows = kernel::database()->affect_row();
            if ($affect_rows !== 1) {
                kernel::database()->rollBack();

                return [false, ['msg'=>$v['partcode'].'确认失败：拆分数量超过申请数量']];
            }


            // $partcode = $v['partcode'];
            // $items =$itemsMdl->db_dump(array('partcode'=>$partcode),'iso_items_id');

            // if($items){
            //     return [false, ['msg'=>$partcode.':已存在']];
            // }
        }

        $data['products'] = $products;
        $op = kernel::single('ome_func')->getDesktopUser();
        
        
        $iostockorder_instance = kernel::single('console_iostockorder');
        $rs = $iostockorder_instance->save_iostockorder($data, $msg);
        if($rs) {
           
            kernel::database()->commit();
        } else {
            kernel::database()->rollBack();
        }
        return [$rs, ['msg'=>$msg,'iso_id'=>$rs]];
    }

    /**
     * 获取_sku
     * @param mixed $shop_id ID
     * @param mixed $bn bn
     * @return mixed 返回结果
     */
    public function get_sku($shop_id,$bn){

       
        $skuMdl = app::get('inventorydepth')->model('shop_skus');
        $skus = $skuMdl->db_dump(array('shop_id'=>$shop_id,'shop_sku_id'=>$bn),'shop_product_bn');
       

        if($skus){
            $materials = $this->getMaterials($skus['shop_product_bn']);

            return $materials;
        }else{
            return array();
        }

    }

    /**
     * autocheckIso
     * @param mixed $iso_id ID
     * @return mixed 返回值
     */
    public function autocheckIso($iso_id) {

        #库存状态判断
        $isoObj = app::get('taoguaniostockorder')->model('iso');
        $iso       = $isoObj->dump($iso_id, 'check_status,branch_id,iso_bn,type_id,bill_type');
        $branch_id = $iso['branch_id'];
        if ($iso['check_status'] != '1') {
            return [false, 'msg'=>'此单据已审核!'];
        }

      
        $oIso_items = app::get('taoguaniostockorder')->model('iso_items');
        #需要判断可用库存是否足够
        $iso_items = $oIso_items->getlist('bn,nums,nums as num,product_id', array('iso_id' => $iso_id), 0, -1);
        
        $iso_data = array('check_status' => '2');
        $result   = $isoObj->update($iso_data, array('iso_id' => $iso_id, 'check_status'=>'1'));
        if (is_bool($result)) {
            return [false, ['msg'=>'更新状态失败']];
        }

        //新加在tu库存
        if($iso['bill_type'] == 'JDMASTERRO'){
            

        }
        kernel::single('console_event_trigger_otherstockin')->create(array('iso_id' => $iso_id), false);

        
        return [true, ['msg'=>'操作成功']];
    }

    /**
     * autoConfirm
     * @param mixed $returnId ID
     * @param mixed $branch_id ID
     * @return mixed 返回值
     */
    public function autoConfirm($returnId,$branch_id=0){
        $vopreturnMdl = app::get('console')->model('vopreturn');

        if($branch_id){
            $vopreturnMdl->update(array('in_branch_id'=>$branch_id),array('id'=>$returnId));

        }
        $oldRow = $vopreturnMdl->db_dump(array('id'=>$returnId,'bill_status'=>'1'), '*');

        if(!in_array($oldRow['shop_type'],array('360buy'))){
            return [false, '店铺类型错误或不是待读取状态'];
        }

        if($oldRow['status'] != '0') {
           // return [false, '不可审核'];
        }

        $accountordersLib = kernel::single('ediws_accountorders');

        $itemObj = app::get('console')->model('vopreturn_items');
        $items = $itemObj->getList('*', ['return_id'=>$returnId, 'filter_sql'=>'qty > 0']);

        $partcodeList = array();

        foreach($items as $iv){
            if(empty($iv['refundid'])) return [false, '退货单号为空'];
            if(empty($iv['originsaleordid'])) return [false, '原始订单号为空'];
           
        }

        $shop_id = $oldRow['shop_id'];

        $shops = $vopreturnMdl->db->selectrow("select shop_bn from sdb_ome_shop where shop_id='".$shop_id."'");

        $shop_bn =  $shops['shop_bn'];

        $extrabranchs = $vopreturnMdl->db->selectrow("select branch_id from sdb_ome_extrabranch where branch_bn='".$shop_bn."'");
        if(empty($items)) {
          
            //return [false, '缺少明细'];
        }
        $in_branch_id = $oldRow['in_branch_id'];
        if(!$in_branch_id) {
          
            $error_msg    = '缺少入库仓';
            return [false, $error_msg];
        }
        
        $isoData = array();
        
        
        $shop_id = $oldRow['shop_id'];
      
        foreach($items as $v) {

           
            if(empty($v['material_bn'])){
                $bm = $this->get_sku($shop_id,$v['shop_product_bn']);

                if($bm){
                    $material_bn = $bm['material_bn'];
                    $bm_id = $bm['bm_id'];
                    $vopreturnMdl->db->exec("UPDATE sdb_console_vopreturn_items SET material_bn='".$material_bn."',bm_id=".$bm_id." WHERE id=".$v['id']."");
                    $v['material_bn'] = $material_bn;

                    $v['bm_id'] = $bm_id;
            
                }
            }
            
            if(empty($v['material_bn'])) {
                
                return [false, $v['shop_product_bn'].'货品不存在'];
            }

            if($v['price']<=0 && $v['saleordid']){
                $accorders = $accountordersLib->getPiriceByOrderId($v['shop_product_bn'],$v['saleordid']);
                if($accorders){
                    
                    $vopreturnMdl->db->exec("UPDATE sdb_console_vopreturn_items SET price='".$accorders['price']."' WHERE id=".$v['id']."");

                }
            }
            
            
            $num      = $v['qty'] - $v['split_num'];

            if($num<=0) continue;
            $bm_id = $v['bm_id'];
            $addData= array(
                'io_bn'         => $v['refundid'],//退货单号
                'original_bn'   => $oldRow['return_sn'],//退供单号
                'original_id'   => $oldRow['id'],
                'branch_id'     => $in_branch_id,
                'memo'          =>'return',
                'logi_no'       =>  $oldRow['logi_no'],
                'extrabranch_id'=>  $extrabranchs['branch_id'],
                'originsaleordid'=>  $v['originsaleordid'],
                'business_bn'   => $v['originsaleordid'],//业务单号
                'source'        => 'refundinfo',

            );
            $products = [];
            $products[] = [
                'bn'        => $v['material_bn'],
                'name'      => $v['product_name'],
                'nums'      => $v['qty'],
                'product_id'=> $bm_id,
                'price'     => $v['price'],
                'partcode'  => $v['partcode'],
                'return_item_id' => $v['id'],
            ];

            $addData['items'] = $products;
            $isoData[] = $addData;
        }
        
        $logMsg = array();
        if($isoData){
            foreach($isoData as $v){
                list($rs,$msg) = $this->_dealInItems($v);
                if(!$rs){
                    $logMsg[] = '生成失败,原因'.$msg;
                }else{
                    $logMsg[] = '生成入库单:'.$msg['iso_id'].'入库单自动审核';

                    $this->autocheckIso($msg['iso_id']);
                }
            }

            $logMsg = implode(',',$logMsg);
            $status = '4';
            if (!$itemObj->db_dump(['return_id' => $returnId, 'filter_sql' => 'split_num < qty'], 'id')) {
                $status = '1';
            }

            $data = array('status'=>$status,'bill_status'=>'2');
            $vopreturnMdl->update($data,array('id'=>$returnId));
        }
        
       
        return [true, $logMsg];
    }


    /**
     * syncAftersale
     * @param mixed $return_id ID
     * @return mixed 返回值
     */
    public function syncAftersale($return_id){

        $vopreturnMdl = app::get('console')->model('vopreturn');
        $itemsMdl = app::get('console')->model('vopreturn_items');

        $vopreturn = $vopreturnMdl->db_dump(array('id'=>$return_id,'status'=>'2'),'*');
       
        if(!$vopreturn) return false;

        $items = $itemsMdl->getlist('*',array('return_id'=>$return_id));

        $bm_ids = array_column($items, 'bm_id');


        $bmExMdl = app::get('material')->model('basic_material_ext');
        $bm_ext_list = $bmExMdl->getList('bm_id,retail_price', array('bm_id' => $bm_ids));

        $bm_exts    = array_column($bm_ext_list, null, 'bm_id');

        $in_branch_id = $vopreturn['in_branch_id'];
        $branch = app::get('ome')->model('branch')->db_dump([
            'check_permission' => 'false',
            'branch_id'        => $in_branch_id
        ], 'branch_id,branch_bn,name');

         $shop = app::get('ome')->model('shop')->db_dump([
            'shop_id'        => $vopreturn['shop_id']
        ], 'shop_bn,name');

        foreach($items as $v){
            $aftersale = array();
            $retail_price = $bm_exts[$v['bm_id']] ? $bm_exts[$v['bm_id']]['retail_price'] : 0;
            $price = $v['price'];

            $amount = $retail_price*$v['num'];
            $settlement_amount = $price*$v['num'];
            $aftersale = array(

                'bill_bn'           => $vopreturn['return_sn'],
                'bill_type'         => 'jdlreturn',
                'bill_id'           => $v['id'],
                'shop_id'           => $vopreturn['shop_id'],
                'shop_bn'           => $shop['shop_bn'],
                'shop_name'         => $shop['name'], 
                'aftersale_time'    => $vopreturn['iostock_time'] ? $vopreturn['iostock_time'] : time(),
                'original_bn'       => $vopreturn['return_sn'].'_'.$v['id'],
                'original_id'       => $vopreturn['id'],
                'branch_id'         => $in_branch_id,
                'branch_bn'         => $branch['branch_bn'],
                'branch_name'       => $branch['name'],
                'logi_code'         => $vopreturn['logi_name'],
                'logi_no'           => $vopreturn['logi_no'],
                'po_bn'             => $v['saleordid'],
                'total_amount'      => $amount,
                'settlement_amount' => $settlement_amount,
                'total_sale_price'  => $settlement_amount,
                'order_bn'          => $v['saleordid'],
            );

            
            $itemsdf = array();
            $itemsdf[] = array(
                'material_bn'       => $v['material_bn'],
                'barcode'           => $v['barcode'],
                'material_name'     => $v['product_name'],
                'bm_id'             => $v['bm_id'],
                'nums'              => $v['num'],
                'price'             => $retail_price,
                'amount'            => $amount,
                'sale_price'        => $settlement_amount,
                'settlement_amount' => $settlement_amount,


            );

            $aftersale['items'] = $itemsdf;


            
            list($result, $err_msg) = app::get('billcenter')->model('aftersales')->create_aftersales($aftersale);


        }
        
        $msg = '生成财务售后单';
        app::get('ome')->model('operation_log')->write_log('vopreturn@console',$return_id,$msg);


    }


    /**
     * 获取Materials
     * @param mixed $bn bn
     * @return mixed 返回结果
     */
    public function getMaterials($bn){
        $materialObj= app::get('material')->model('basic_material');
        $material = $materialObj->dump(array('material_bn'=>$bn),'material_bn,bm_id');
        if($material){
            return $material;
        }     
        
        return false;
    }

    /**
     * 获取isoitems
     * @param mixed $iso_id ID
     * @return mixed 返回结果
     */
    public function getisoitems($iso_id){

        $isoMdl    = app::get('taoguaniostockorder')->model("iso");
        $itemsMdl  = app::get('taoguaniostockorder')->model("iso_items");

        $isos = $isoMdl->db_dump(array('iso_id'=>$iso_id,'bill_type'=>'jdlreturn'),'business_bn,original_bn,original_id,iso_id,iso_bn,source');
       
        if(!$isos){
            return false;
        }

        $items = $itemsMdl->getlist('*',array('iso_id'=>$iso_id));

        $items = array_column($items,null,'bn');
        foreach($items as $k=>$v){
            $items[$k]['in_nums'] = $v['normal_num']+$v['defective_num'];
        }
      
     
        $ioitems = array();
        foreach($items as $v){
            
            $in_nums = $v['in_nums'];

            if($in_nums>1){

                for($i=$in_nums;$i--;$i<0){
                    $v['in_nums'] = 1;
                    $ioitems[] = $v;
                }
            }else{
                $v['in_nums'] = 1;
                $ioitems[] = $v;
            }
        }
       
        //平铺
        $isos['items'] = $ioitems;

        return $isos;

    }


    /**
     * dealIsoItems
     * @param mixed $iso iso
     * @return mixed 返回值
     */
    public function dealIsoItems($iso){

        $iso_id = $iso['iso_id'];
      
        $items = $iso['items'];
        $iso_bn = $iso['iso_bn'];
        $vopreturnMdl = app::get('console')->model('vopreturn');
        $itemsMdl = app::get('console')->model('vopreturn_items');
        $logMdl = app::get('ome')->model('operation_log');
        if($items){

            $return_id = $iso['original_id'];
            $vopreturnItems = $itemsMdl->getList('id,return_id,material_bn,bm_id,partcode,price,num,product_name,originsaleordid', ['return_id' => $return_id,'refundid'=>$iso_bn]);

            if(!$vopreturnItems){

                return [false, $iso_bn.'退货单号不存在'];
            }

            foreach($items as $v){

                $in_nums = $v['normal_num']+$v['defective_num'];

                $itemsMdl->db->exec("UPDATE sdb_console_vopreturn_items SET num=".$in_nums." WHERE return_id=".$return_id." AND bm_id='".$v['product_id']."' AND refundid='".$iso_bn."' and num=0 limit 1");
            }

            //更新主单状态    
            //
            $vopData     = ['in_status' => '2'];//部分入库

            $logMsg   = $iso_bn.'入库完成';
            $itemList = $itemsMdl->db_dump(['return_id' => $return_id, 'filter_sql' => 'num < qty'], 'id');
            if (!$itemList) {
                $vopData['status']    = '2';//已完成
                $vopData['in_status'] = '3';//全部入库
                $vopData['iostock_time'] = time();//入库时间
            }
            $rsUp = $vopreturnMdl->update($vopData, ['id' => $return_id, 'status' => ['1', '4']]);
        
        
            $logMdl->write_log('vopreturn@console', $return_id, $logMsg);

        }
        

        list($result,$msg) = $this->syncRefundAftersale($iso_id);

        if($result){
            $msg.='生成售后单成功,入库单号：'.$iso_bn;
        }else{
            $msg.='失败';
        }
        $logMdl->write_log('vopreturn@console',$return_id,$msg);


    }

    /**
     * syncRefundAftersale
     * @param mixed $iso_id ID
     * @return mixed 返回值
     */
    public function syncRefundAftersale($iso_id){

        $isoMdl    = app::get('taoguaniostockorder')->model('iso');
        $itemsMdl  = app::get('taoguaniostockorder')->model('iso_items');

        $isos = $isoMdl->db_dump(array('iso_id'=>$iso_id,'bill_type'=>'jdlreturn','source'=>'refundinfo'),'business_bn,original_bn,original_id,iso_id,iso_bn,source');
       
        if(!$isos){
            return [false, $isos['iso_bn'].'入库单不存在'];
        }

        $isoitems = $itemsMdl->getlist('*',array('iso_id'=>$iso_id));

        $vopreturnMdl = app::get('console')->model('vopreturn');
        $itemsMdl = app::get('console')->model('vopreturn_items');
        $return_id = $isos['original_id'];
        $vopreturn = $vopreturnMdl->db_dump(array('id'=>$return_id),'*');
       
        if(!$vopreturn) return [false, '退供单不存在'];

        $aftersalesMdl = app::get('billcenter')->model('aftersales');

        $aftersales = $aftersalesMdl->db_dump(array('original_bn'=>$isos['iso_bn'],'bill_type'=>'jdlreturn'),'id');

        if($aftersales) return [false, '售后单已生成:'.$isos['iso_bn'].''];

        $vopitems = $itemsMdl->getlist('*',array('return_id'=>$return_id));

        $bm_ids = array_column($isoitems, 'product_id');

        $vopitems = array_column($vopitems,null,'partcode');

        $bmExMdl = app::get('material')->model('basic_material_ext');
        $bm_ext_list = $bmExMdl->getList('bm_id,retail_price', array('bm_id' => $bm_ids));

        $bm_exts    = array_column($bm_ext_list, null, 'bm_id');
      
        $in_branch_id = $vopreturn['in_branch_id'];
        $branch = app::get('ome')->model('branch')->db_dump([
            'check_permission' => 'false',
            'branch_id'        => $in_branch_id
        ], 'branch_id,branch_bn,name');

         $shop = app::get('ome')->model('shop')->db_dump([
            'shop_id'        => $vopreturn['shop_id']
        ], 'shop_bn,name');
        

        foreach ($isoitems as $v) {
            
            $in_nums = $v['normal_num']+$v['defective_num'];
            if ($in_nums == 0) {
                continue;
            }

            $partcode = $v['partcode'];
            $vopitem = $vopitems[$partcode];
            $retail_price = $bm_exts[$v['product_id']] ? $bm_exts[$v['product_id']]['retail_price'] : 0;
            $price = $vopitem['price'];

            $amount = $retail_price*$in_nums;
            $settlement_amount = $price*$in_nums;
            
            $aftersale = array(

                'bill_bn'           => $vopreturn['return_sn'],
                'bill_type'         => 'jdlreturn',
                'bill_id'           => $vopreturn['id'],
                'shop_id'           => $vopreturn['shop_id'],
                'shop_bn'           => $shop['shop_bn'],
                'shop_name'         => $shop['name'], 
                'aftersale_time'    => $vopreturn['iostock_time'] ? $vopreturn['iostock_time'] : time(),
                'original_bn'       => $isos['iso_bn'],
                'original_id'       => $isos['iso_id'],
                'branch_id'         => $in_branch_id,
                'branch_bn'         => $branch['branch_bn'],
                'branch_name'       => $branch['name'],
                'logi_code'         => 'JD',
                'logi_name'         => $vopreturn['logi_name'],
                'logi_no'           => $vopreturn['logi_no'],
                'po_bn'             => $isos['business_bn'],//原始订单号
                'total_amount'      => $amount,
                'settlement_amount' => $settlement_amount,
                'total_sale_price'  => $settlement_amount,
                'order_bn'          => $isos['business_bn'],
                
            );

            $itemsdf = array();
            $itemsdf[] = array(
                'material_bn'       => $v['bn'],
                'barcode'           => $v['barcode'],
                'material_name'     => $v['product_name'],
                'bm_id'             => $v['product_id'],
                'nums'              => $in_nums,
                'price'             => $retail_price,
                'amount'            => $amount,
                'sale_price'        => $settlement_amount,
                'settlement_amount' => $settlement_amount,

            );

            $aftersale['items'] = $itemsdf;


            //判断是否已生成
           list($result, $err_msg) = $aftersalesMdl->create_aftersales($aftersale);

           return [$result, $err_msg];
        }

    }

    
}


?>
