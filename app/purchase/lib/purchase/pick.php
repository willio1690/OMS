<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会JIT拣货单Lib类
 *
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class purchase_purchase_pick
{
    function __construct()
    {
        $this->_pickObj        = app::get('purchase')->model('pick_bills');
        $this->_logObj         = app::get('ome')->model('operation_log');
    }
    
    /**
     * 保存拣货单数据
     */

    function save_pick($pick_list)
    {
        $purchaseObj    = app::get('purchase')->model('order');
        $pickItemObj    = app::get('purchase')->model('pick_bill_items');
        
        foreach ($pick_list as $key => $val)
        {
            $val['delivery_status']    = ($val['delivery_status'] ? $val['delivery_status'] : 1);
            
            $quality_check_flag = ''; // 是否重点检查
            $priorityDelivery   = ''; // 是否优先发货

            $pickInfo    = $this->_pickObj->dump(array('pick_no'=>$val['pick_no']), 'bill_id');
            if($pickInfo)
            {
                //更新
                $data    = array(
                        'bill_id'=>$pickInfo['bill_id'],
                        'to_branch_bn'=> $val['sell_site'],//平台送货仓库
                        'delivery_num'=> intval($val['delivery_num']),//平台发货数量
                );
                $result    = $this->update_pick($data);

                $shop_id = $pickInfo['shop_id'];

                $barcodeList = app::get('material')->model('codebase')->getList('bm_id, code', [
                    'code' => array_column($val['product_list'], 'art_no'),
                    'type' => '1',
                ]);
                $barcodeList = array_column($barcodeList, 'bm_id', 'code');

                //拣货单明细
                foreach ($val['product_list'] as $p_key => $p_item)
                {
                    $sql    = "select bill_item_id from sdb_purchase_pick_bill_items where bill_id=". $pickInfo['bill_id'] ." AND barcode='". $p_item['barcode'] ."'";
                    $temp_data    = $this->_pickObj->db->select($sql);
                    if($temp_data)
                    {
                        continue;
                    }
                    
                    $itemData    = array(
                            'bill_id' => $pickInfo['bill_id'],
                            'product_name'=>$p_item['product_name'],//商品名称
                            'bn'=>$p_item['art_no'],//货号
                            'product_id' => $barcodeList[$p_item['art_no']],//商品ID
                            'barcode'=>$p_item['barcode'],//商品条码
                            'size'=>$p_item['size'],//尺寸
                            'num'=>intval($p_item['stock']),//拣货数量
                            'not_delivery_num'=>intval($p_item['not_delivery_num']),//未送货数量
                    );
                    $pickItemObj->save($itemData);

                    // 是否有重点检查的标识
                    if ($p_item['quality_check_flag'] == '1') {
                        // 保存检测表
                        if ($p_item['check_items']) {
                            kernel::single('ome_bill_label')->markBillLabel($itemData['bill_item_id'], '', 'quality_check', 'pick_bill_item', $err);
                            $quality_check_flag = '1';

                            $this->saveCheckItems($itemData, $p_item['check_items'], $shop_id);
                        }
                    }

                    // 是否有优先发货的标识
                    if ($p_item['goods_type_map'] && $p_item['goods_type_map']['priorityDelivery'] == '1') {
                        kernel::single('ome_bill_label')->markBillLabel($itemData['bill_item_id'], '', 'priority_delivery', 'pick_bill_item', $err);
                        $priorityDelivery = '1';
                    }
                }

                $bill_id = $pickInfo['bill_id'];
            }
            else
            {
                //PO单信息
                $poInfo    = $purchaseObj->dump(array('po_bn'=>$val['po_no']), 'po_id');
                
                //新建
                $data    = array(
                        'pick_no'=>$val['pick_no'],//拣货单编号
                        'po_id'=>$poInfo['po_id'],//拣货单ID
                        'po_bn'=>$val['po_no'],//po单号
                        'to_branch_bn'=>$val['sell_site'],//入库仓编码
                        'order_cate'=>$val['order_cate'],//订单类别
                        'pick_num'=>intval($val['pick_num']),//拣货数量
                        'delivery_status'=>intval($val['delivery_status']),//平台送货状态
                        'delivery_num'=> intval($val['delivery_num']),//平台发货数量
                        'shop_id' => $poInfo['shop_id'],
                        'price' => $val['price'],
                        'market_price' => $val['market_price'],
                );
                
                $barcodeList = app::get('material')->model('codebase')->getList('bm_id, code', [
                    'code' => array_column($val['product_list'], 'art_no'),
                    'type' => '1',
                ]);
                $barcodeList = array_column($barcodeList, 'bm_id', 'code');

                //拣货单明细
                foreach ($val['product_list'] as $p_key => $p_item)
                {
                    $p_item['product_name']    = str_replace(array("\t","\r","\n"), array("","",""), $p_item['product_name']);
                    
                    $data['detail'][]    = array(
                        'product_name'=>$p_item['product_name'],//商品名称
                        'bn'=>$p_item['art_no'],//货号
                        'product_id' => $barcodeList[$p_item['art_no']],
                        'barcode'=>$p_item['barcode'],//商品条码
                        'size'=>$p_item['size'],//尺寸
                        'num'=>intval($p_item['stock']),//拣货数量
                        'not_delivery_num'=>intval($p_item['not_delivery_num']),//未送货数量
                        //'price'=>$p_item['actual_unit_price'],//供货价(不含税)已下架，请从getSkuPriceInfo获取
                        //'market_price'=>$p_item['actual_market_price'],//供货价(含税)已下架，请从getSkuPriceInfo获取
                        'quality_check_flag'=>$p_item['quality_check_flag'],
                        'priorityDelivery'=>$p_item['goods_type_map']['priorityDelivery']?:'',
                        'check_items' => $p_item['check_items']?:[],
                    );

                    // 是否有重点检查的标识
                    if ($p_item['quality_check_flag'] == '1') {
                        $quality_check_flag = '1';
                    }

                    // 是否有优先发货的标识
                    if ($p_item['goods_type_map'] && $p_item['goods_type_map']['priorityDelivery'] == '1') {
                        $priorityDelivery = '1';
                    }
                }
                $result    = $this->create_pick($data);

                $result && $bill_id = $result;
            }

            // 是否换货质检
            if ($quality_check_flag && $bill_id) {
                kernel::single('ome_bill_label')->markBillLabel($bill_id, '', 'quality_check', 'pick_bill', $err);
            }

            // 是否优先发货
            if ($priorityDelivery && $bill_id) {
                kernel::single('ome_bill_label')->markBillLabel($bill_id, '', 'priority_delivery', 'pick_bill', $err);
            }

        }
        
        return true;
    }
    
    /**
     * 创建拣货单
     */
    function create_pick($sdf)
    {
        $pickItemObj    = app::get('purchase')->model('pick_bill_items');
        
        $product_list    = $sdf['detail'];
        
        $sdf['create_time']    = time();
        $sdf['last_modified']  = time();
        unset($sdf['detail']);
        
        //开启事务
        $this->_pickObj->db->beginTransaction();
        
        if(!$this->_pickObj->save($sdf))
        {
            //事务回滚
            $this->_pickObj->db->rollBack();
            return false;
        }
        
        //保存明细
        foreach ($product_list as $key => $item)
        {
            $item['bill_id']    = $sdf['bill_id'];
            
            if(!$pickItemObj->save($item))
            {
                //事务回滚
                $this->_pickObj->db->rollBack();
                return false;
            }
            // 是否有重点检查的标识
            if ($item['quality_check_flag'] == '1') {
                // 保存检测表
                if ($item['check_items']) {
                    kernel::single('ome_bill_label')->markBillLabel($item['bill_item_id'], '', 'quality_check', 'pick_bill_item', $err);

                    $this->saveCheckItems($item, $item['check_items'], $sdf['shop_id']);
                }
            }
            // 是否有优先发货的标识
            if ($item['priorityDelivery'] == '1') {
                kernel::single('ome_bill_label')->markBillLabel($item['bill_item_id'], '', 'priority_delivery', 'pick_bill_item', $err);
            }
        }
        
        //事务确认
        $this->_pickObj->db->commit();
        
        //增加拣货单创建日志
        $this->_logObj->write_log('create_vopick@ome', $sdf['bill_id'], '拣货单创建成功');
        
        return $sdf['bill_id'];
    }
    
    /**
     * 更新拣货单
     */
    function update_pick($sdf)
    {
        //开启事务
        $this->_pickObj->db->beginTransaction();
        
        $sdf['last_modified']  = time();
        
        if(!$this->_pickObj->save($sdf))
        {
            //事务回滚
            $this->_pickObj->db->rollBack();
            return false;
        }
        else
        {
            //事务确认
            $this->_pickObj->db->commit();
        }
        
        //增加拣货单更新日志
        $this->_logObj->write_log('update_vopick@ome', $sdf['bill_id'], '拣货单更新');
        
        return true;
    }
    
    /**
     * 检查唯品会条形码在OMS中是否存在并检查仓库供货关系是否存在
     * 
     * @param string $barcode 条形码
     * @param intval $branch_id 仓库ID
     * @return Array $productInfo 货品信息
     */
    function checkProduct($barcode, $branch_id, &$error_msg)
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        
        //通过barcode条形码找到bn基础物料
        $bn    = kernel::single('material_codebase')->getBnBybarcode($barcode);
        if(empty($bn))
        {
            $error_msg    = '条形码：'. $barcode .' 系统中不存在';
            return false;
        }
        
        //基础物料ID
        $productInfo   = $basicMaterialObj->dump(array('material_bn'=>$bn), 'bm_id,material_bn');
        if(empty($productInfo))
        {
            $error_msg    = '货号：'. $bn .' 系统中不存在';
            return false;
        }
        
        //仓库供货关系
        $branchProObj = app::get('ome')->model('branch_product');
        $branchInfo   = $branchProObj->dump(array('branch_id'=>$branch_id, 'product_id'=>$productInfo['bm_id']), 'branch_id');
        if(empty($branchInfo))
        {
            $error_msg    = '条形码：'. $barcode .' 对应仓库供货关系不存在,请先采购库存!';
            return false;
        }
        
        $productInfo['bn']    = $productInfo['material_bn'];
        
        return $productInfo;
    }
    
    /**
     * 自动批量生成出库单
     */
    function auto_batch_create_stockout($shop_id)
    {
        //应用的店铺
        $setShopObj     = app::get('purchase')->model('setting_shop');
        $setShopInfo    = $setShopObj->dump(array('shop_id'=>$shop_id), 'sid');
        if(empty($setShopInfo))
        {
            return false;
        }
        
        //[读取]自动审核配置
        $setObj    = app::get('purchase')->model('setting');
        $config    = $setObj->dump(array('sid'=>$setShopInfo['sid']), '*');
        
        if(empty($config) || empty($config['is_auto_combine']))
        {
            return false;//自动审核配置未开启
        }
        if(empty($config['branch_id']))
        {
            return false;
        }
        
        $params    = array(
                'is_merge'=>$config['is_merge'],
                'branch_id'=>$config['branch_id'],
                'dly_mode'=>$config['dly_mode'],
                'carrier_code'=>$config['carrier_code'],
                'arrival_type'=>$config['arrival_type'],//弃用
                'arrival_day'=>intval($config['arrival_day']),//弃用
                'arrival_hour'=>$config['arrival_hour'],//弃用
        );
        
        if($params['is_merge'])
        {
            //合并创建出库单
            $this->merge_create_stockout($params, $shop_id);
        }
        else 
        {
            //创建出库单
            $this->batch_create_stockout($params, $shop_id);
        }
        
        return true;
    }
    
    /**
     * [按店铺]批量创建出库单
     * 
     * @param Array $params 自动审单配置
     * @param string $shop_id 店铺ID
     * @return true
     */
    public function batch_create_stockout($params, $shop_id)
    {
        $pickItemObj    = app::get('purchase')->model('pick_bill_items');
        $stockLib        = kernel::single('purchase_purchase_stockout');
        
        $sql    = "SELECT a.* FROM sdb_purchase_pick_bills AS a LEFT JOIN sdb_purchase_order AS b ON a.po_id=b.po_id
                   WHERE a.status=1 AND a.is_download_finished='1' AND b.shop_id='". $shop_id ."'";
        $picList    = $this->_pickObj->db->select($sql);
        if(empty($picList))
        {
            return false;
        }
        
        //批量创建出库单
        foreach ($picList as $key => $val)
        {
            $po_id      = $val['po_id'];
            $bill_id    = $val['bill_id'];
            $true_num   = $val['pick_num'] - $val['branch_send_num'];
            
            //检查可拣货数量
            if($true_num <= 0)
            {
                continue;
            }
            
            //检查是否存在未出库的出库单
            $sql    = "SELECT a.stockout_id FROM sdb_purchase_pick_stockout AS a LEFT JOIN sdb_purchase_pick_stockout_bills AS b 
                       ON a.stockout_id=b.stockout_id WHERE a.bill_id=". $bill_id ." AND b.status=1";
            $stockoutInfo    = $this->_pickObj->db->selectrow($sql);
            if($stockoutInfo)
            {
                continue;
            }
            
            //拣货单明细
            $error_msg       = '';
            $pickDetail      = $pickItemObj->getList('*', array('bill_id'=>$bill_id));
            if(empty($pickDetail))
            {
                continue;
            }
            
            //送货批次和要求到货时间
            $dataTime    = $stockLib->reckonTiem($params['dly_mode']);
            
            //生成出库单
            $data    = array(
                    'branch_id' => $params['branch_id'],//出库仓库
                    'pick_num' => $true_num,//拣货数量
                    'dly_mode' => $params['dly_mode'],//配送方式
                    'carrier_code' => $params['carrier_code'],//承运商
                    'delivery_time' => $dataTime['delivery_time'],//送货批次
                    'arrival_time' => $dataTime['arrival_time'],//要求到货时间
                    'is_auto'=>true,//自动标识
            );
            $data['bill_ids'][]    = $bill_id;
            
            //组织出库单明细
            foreach ($pickDetail as $d_key => $d_val)
            {
                //唯品会货号在OMS系统中是否存在
                $productInfo    = $this->checkProduct($d_val['barcode'], $params['branch_id'], $error_msg);
                if(!$productInfo)
                {
                    unset($data['detail']);//注销
                    break;
                }
                
                $data['detail'][]    = array(
                                        'po_id'=>$po_id,//采购单ID
                                        'bill_id'=>$bill_id,//拣货单ID
                                        'product_name' => $d_val['product_name'],
                                        'bn' => $productInfo['bn'],//OMS货号
                                        'barcode' => $d_val['barcode'],
                                        'size' => $d_val['size'],
                                        'num' => $d_val['num'],//申请数量
                                        'item_num' => $d_val['num'],//拣货数量
                                        'actual_num' => 0,//实际出库数量
                                        'price' => $d_val['price'],
                                        'market_price' => $d_val['market_price'],
                                        'bill_item_id' =>$d_val['bill_item_id'],
                                        'product_id'    =>  $productInfo['bm_id'],
                );
            }
            
            //检查出库单明细
            if(empty($data['detail']))
            {
                continue;
            }
            
            //创建出库单
            $stockout_no = $stockLib->create_stockout($data);
            if($stockout_no)
            {
                //更新审核状态
                $this->_pickObj->update(array('status'=>2), array('bill_id'=>$bill_id));
                
                //日志
                $this->_logObj->write_log('check_vopick@ome', $bill_id, '自动审核完成,创建出库单号：'. $stockout_no);
            }
        }
        
        return true;
    }
    
    /**
     * [按店铺]同入库仓合并创建出库单
     * 
     * @param Array $params 自动审单配置
     * @param string $shop_id 店铺ID
     * @return true
     */
    public function merge_create_stockout($params, $shop_id)
    {
        $pickItemObj    = app::get('purchase')->model('pick_bill_items');
        $stockLib        = kernel::single('purchase_purchase_stockout');
        
        //入库仓分组
        $sql          = "SELECT a.po_id, a.to_branch_bn FROM sdb_purchase_pick_bills AS a LEFT JOIN sdb_purchase_order AS b ON a.po_id=b.po_id 
                         WHERE a.status=1 AND a.is_download_finished='1' AND b.shop_id='". $shop_id ."' GROUP BY a.to_branch_bn, a.po_id";
        $branchList   = $this->_pickObj->db->select($sql);
        if(empty($branchList))
        {
            return false;
        }
        
        foreach ($branchList as $branchRow)
        {
            $sql        = "SELECT a.* FROM sdb_purchase_pick_bills AS a LEFT JOIN sdb_purchase_order AS b ON a.po_id=b.po_id 
                           WHERE a.status=1 AND a.to_branch_bn='". $branchRow['to_branch_bn'] ."' AND b.shop_id='". $shop_id ."' AND a.po_id=". $branchRow['po_id'];
            $dataList   = $this->_pickObj->db->select($sql);
            if(empty($dataList))
            {
                continue;
            }
            
            $stockData   = array();
            $stockItems  = array();
            $bill_ids    = array();
            $count_num   = 0;
            foreach ($dataList as $val)
            {
                $po_id     = $val['po_id'];
                $bill_id   = $val['bill_id'];
                $true_num  = $val['pick_num'] - $val['branch_send_num'];
                
                //检查可拣货数量
                if($true_num <= 0)
                {
                    continue;
                }
                
                //检查是否存在未出库的出库单
                $sql    = "SELECT a.stockout_id FROM sdb_purchase_pick_stockout AS a LEFT JOIN sdb_purchase_pick_stockout_bills AS b 
                           ON a.stockout_id=b.stockout_id WHERE a.bill_id=". $bill_id ." AND b.status=1";
                $stockoutInfo    = $this->_pickObj->db->selectrow($sql);
                if($stockoutInfo)
                {
                    continue;
                }
                
                //拣货单明细
                $error_msg   = '';
                $items       = $pickItemObj->getList('*', array('bill_id'=>$bill_id));
                if(empty($items))
                {
                    continue;
                }
                
                $itemList    = array();
                foreach ($items as $item_key => $item_val)
                {
                    $bill_item_id    = $item_val['bill_item_id'];
                    
                    //唯品会货号在OMS系统中是否存在
                    $productInfo    = $this->checkProduct($item_val['barcode'], $params['branch_id'], $error_msg);
                    if(!$productInfo)
                    {
                        unset($itemList);//注销
                        break;
                    }
                    
                    $itemList[$bill_item_id]    = array(
                                                'po_id'=>$po_id,//采购单ID
                                                'bill_id'=>$bill_id,//拣货单ID
                                                'product_name' => $item_val['product_name'],
                                                'bn' => $productInfo['bn'],//OMS货号
                                                'barcode' => $item_val['barcode'],
                                                'size' => $item_val['size'],
                                                'num' => $item_val['num'],//申请数量
                                                'item_num' => $item_val['num'],//拣货数量
                                                'actual_num' => 0,//实际出库数量
                                                'price' => $item_val['price'],
                                                'market_price' => $item_val['market_price'],
                                                'bill_item_id' => $bill_item_id,
                                                'product_id'    =>  $productInfo['bm_id'],
                    );
                }
                
                if(empty($itemList))
                {
                    continue;//没有出库明细,跳过
                }
                
                $bill_ids[]    = $bill_id;
                $stockItems    = array_merge($stockItems, $itemList);
                
                //要求到货时间
                $dataTime    = $stockLib->reckonTiem($params['dly_mode']);
                
                //拣货总数量
                $count_num    += $true_num;
                
                //出库单主信息
                $stockData    = array(
                        'branch_id' => $params['branch_id'],//出库仓库
                        'pick_num' => $count_num,//拣货数量
                        'dly_mode' => $params['dly_mode'],//配送方式
                        'carrier_code' => $params['carrier_code'],//承运商
                        'delivery_time' => $dataTime['delivery_time'],//送货批次
                        'arrival_time' => $dataTime['arrival_time'],//要求到货时间
                        'is_auto'=>true,//自动标识
                        'bill_ids'=>$bill_ids,
                );
            }
            
            //检查是否有拣货单明细
            if(empty($stockItems))
            {
                continue;
            }
            
            //出库单数据
            $stockData['detail']    = $stockItems;
            
            //创建出库单
            $stockout_no = $stockLib->create_stockout($stockData);
            if($stockout_no)
            {
                //更新审核状态
                $this->_pickObj->update(array('status'=>2), array('bill_id'=>$bill_ids));
                
                //日志
                $log_str    = '自动审核完成,';
                $log_str    .= (count($bill_ids)> 1 ? '合并' : '') .'创建出库单号：'. $stockout_no;
                foreach ($bill_ids as $key => $bill_id)
                {
                    $this->_logObj->write_log('check_vopick@ome', $bill_id, $log_str);
                }
            }
        }
        
        return true;
    }
    
    /**
     * 获取同仓拣货单
     * 
     * @param Array $pickInfo 当前拣货单信息
     * @param bool $is_reissue 是否补发(补发审核时不支持合并)
     * 
     * @return Array
     */
    function fetchCombinePick($pickInfo, $is_reissue=false)
    {
        $pickItemObj    = app::get('purchase')->model('pick_bill_items');
        
        $po_id        = $pickInfo['po_id'];
        $bill_id      = $pickInfo['bill_id'];
        $branch_bn    = $pickInfo['to_branch_bn'];
        $where        = "";
        
        //补发审核(不支持合并)
        if($is_reissue)
        {
            $where    = " AND `status`=2 AND bill_id=". $bill_id;
        }
        else
        {
            //只支持同采购单、同仓拣货单允许合并
            $where    = " AND `status`=1 AND po_id=". $po_id;
        }
        
        $sql        = "SELECT bill_id, pick_no, po_id, po_bn, pick_num, create_time FROM sdb_purchase_pick_bills 
                       WHERE to_branch_bn='". $branch_bn ."' ". $where;
        $dataList   = $this->_pickObj->db->select($sql);
        
        $bill_ids = array();
        $pickList    = array();
        foreach ($dataList as $key => $val)
        {
            $billId   = $val['bill_id'];
            $items    = $pickItemObj->getList('*', array('bill_id'=>$billId));
            if(empty($items)){
                continue;
            }

            $billItemIdArr = array_column($items, 'bill_item_id');
            $labelList     = kernel::single('ome_bill_label')->getLabelFromOrder($billItemIdArr, 'pick_bill_item');

            $itemList    = array();
            foreach ($items as $item_key => $item_val)
            {
                if ($labelList[$item_val['bill_item_id']]) {
                    $item_val['order_label'] = '';
                    foreach ($labelList[$item_val['bill_item_id']] as $lk => $lv) {
                        $item_val['order_label'] .= sprintf("<span class='tag-label' title='%s' style='background-color:%s;color:#FFF;'>%s</span>", $lv['label_name'], $lv['label_color'], $lv['label_name']);
                    }
                }

                //条形码对应的货品仓库库存
                $item_val['branch_store']    = $this->getAllBranchStoreByBarcode($item_val['barcode']);
                
                /***
                 * [补发]计算剩余未发货数量
                 *
                if($is_reissue)
                {
                    $sql            = "SELECT sum(actual_num) as num FROM sdb_purchase_pick_stockout_bill_items
                                       WHERE po_id=". $val['po_id'] ." AND bill_id=". $val['bill_id']. " AND barcode='". $item_val['barcode'] ."'";
                    $actual_num     = $this->_pickObj->db->selectrow($sql);
                    
                    $item_val['num'] = $item_val['num'] - intval($actual_num['num']);
                }
                ***/
                
                $itemList[$item_val['bill_item_id']]    = $item_val;
            }
            $val['items']        = $itemList;
            
            $pickList[$billId]   = $val;
            
            $bill_ids[] = $billId;
        }
        
        //[补发时]重新计算拣货单剩余未发货数量
        if($is_reissue){
            $dlyItemList = array();
            $sql = "SELECT po_id, bill_id, barcode, actual_num FROM sdb_purchase_pick_stockout_bill_items 
                    WHERE bill_id IN(". implode(',', $bill_ids) .")";
            $tempList = $this->_pickObj->db->select($sql);
            foreach ($tempList as $key => $val){
                $bill_id = $val['bill_id'];
                $barcode = $val['barcode'];
                
                if(empty($itemList[$bill_id][$barcode])){
                    $dlyItemList[$bill_id][$barcode] = $val['actual_num']; //实际出库数量
                }else{
                    $dlyItemList[$bill_id][$barcode] += $val['actual_num'];
                }
            }
            
            foreach ($pickList as $pickKey => $pickVal){
                $bill_id = $pickVal['bill_id'];
                
                foreach ($pickVal['items'] as $bill_item_id => $itemVal){
                    $barcode = $itemVal['barcode'];
                    
                    $num = $itemVal['num'] - intval($dlyItemList[$bill_id][$barcode]);
                    
                    $pickList[$billId]['items'][$bill_item_id]['num'] = $num;
                }
            }
        }
        
        return $pickList;
    }
    
    /**
     * 条形码对应所有的货品仓库库存
     * 
     * @param varchar $barcode 条形码
     * @return Array
     */
    function getAllBranchStoreByBarcode($barcode)
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        //根据barcode条形码返回bn基础物料编号
        $bn    = kernel::single('material_codebase')->getBnBybarcode($barcode);
        
        //货品信息
        $row    = $basicMaterialObj->dump(array('material_bn'=>$bn), 'bm_id');
        if(empty($row))
        {
            return array();
        }
        
        //仓库
        $purchaseLib    = kernel::single('purchase_purchase_order');
        
        $branch_ids     = array();
        $branch_list    = $purchaseLib->get_branch_list();
        if(empty($branch_list))
        {
            return array();
        }
        foreach ($branch_list as $key => $val)
        {
            $branch_ids[]    = $val['branch_id'];
        }
        
        //库存
        $result    = array();
        $sql       = "SELECT * FROM sdb_ome_branch_product WHERE product_id=". $row['bm_id'] ." AND branch_id in(". implode(',', $branch_ids) .")";
        $branch_product    = $basicMaterialObj->db->select($sql);
        if($branch_product)
        {
            foreach($branch_product as $v)
            {
                //根据仓库ID、基础物料ID获取该物料仓库级的预占
                $v['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($v['product_id'], $v['branch_id']);
                
                $result[$v['branch_id']]    = max(0,$v['store']-$v['store_freeze']);
            }
        }
        
        return $result;
    }
    
    /**
     * 拉取拣货单明细
     * 
     * @param array $pickInfo 拣货单信息
     * @param string $shop_id 店铺ID
     * 
     * @return bool
     */
    public function pullPickDetail($pickInfo, $shop_id){
        $page    = 1;
        $limit   = 50;
        $product_list    = array();
        
        $is_download_finished = true;

        do {
            
            //组织数据
            $params = [
                'po_no' => $pickInfo['po_no'],
                'pick_no' => $pickInfo['pick_no'],
                'page' => 1,
                'limit' => 50,
            ];
            
            $rsp_detail  = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getPickDetail($params);
            
            //组织拣货单信息
            if($rsp_detail['rsp'] == 'succ' && $rsp_detail['pick_product_list'])
            {
                
                $barcodes = array_column($rsp_detail['pick_product_list'], 'barcode');
                
                // 获取供货价 start ++++++++++
                list($skuPriceRs,,$skuPriceData) = kernel::single('purchase_purchase_sku')->getSkuPriceInfo($shop_id, $pickInfo['po_no'],$barcodes);
                if ($skuPriceRs) {
                    $skuPriceData = array_column($skuPriceData, null, 'barcode');

                    foreach ($rsp_detail['pick_product_list'] as $ppKey => $ppVal) {
                        // 不含税 -> 改为原价
                        $rsp_detail['pick_product_list'][$ppKey]['price'] = $ppVal['price'];

                        // 含税
                        $rsp_detail['pick_product_list'][$ppKey]['market_price'] = $ppVal['actual_market_price'];
                    }
                }
                // 获取供货价 end ++++++++++
                

                $product_list    = array_merge($product_list, $rsp_detail['pick_product_list']);
                
                $page++;
            }

        } while (true);
        
        

        
        
        //组织数据
        $pick_list       = array();
        $pick_list[0]    = $pickItem;
        $pick_list[0]['product_list']    = $product_list;
        
        $result    = $this->save_pick($pick_list);
        
        return $result;
    }

    /**
     * 保存CheckItems
     * @param mixed $billItems billItems
     * @param mixed $checkItems checkItems
     * @param mixed $shop_id ID
     * @return mixed 返回操作结果
     */
    public function saveCheckItems($billItems = [], $checkItems = [], $shop_id = '')
    {
        if (!$billItems['bill_id'] || !$billItems['bill_item_id']) {
            return false;
        }
        $mdl  = app::get('purchase')->model('pick_bill_check_items');

        foreach ($checkItems as $k => $checkItem) {
            // $image_list = $video_list = [];
            // $storager = kernel::single('base_storager');
            // if ($checkItem['image_list']) {
            //     foreach ($checkItem['image_list'] as $k => $v) {
            //         $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getDownload(['file_id'=>$v]);
            //         if ($file_data = $rsp_data['data']['result']['file_data']) {
            //             $file_data = pack('C*', ...$file_data);
            //             $file_name = 'purchase_pick_bill'.'-'.$checkItem['check_item_id'].'-'.$k.'.png';
            //             $storager->save_upload();
            //         }
            //     }
            // }
            // if ($checkItem['video_list']) {
            //     foreach ($checkItem['video_list'] as $k => $v) {
            //         $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getDownload(['file_id'=>$v]);
            //     }
            // }

            $data = [
                'bill_id'               => $billItems['bill_id'],
                'bill_item_id'          => $billItems['bill_item_id'],
                'bn'                    => $billItems['bn'],
                'barcode'               => $billItems['barcode'],
                'channel'               => $checkItem['channel'],
                'problem_desc'          => $checkItem['problem_desc'],
                'order_label'           => $checkItem['order_label'],
                'image_fileid_list'     => $checkItem['image_list'] ? json_encode($checkItem['image_list']) : '',
                'video_fileid_list'     => $checkItem['video_list'] ? json_encode($checkItem['video_list']) : '',
                'delivery_warehouse'    => $checkItem['delivery_warehouse'],
                'order_sn'              => $checkItem['order_sn'],
                'first_classification'  => $checkItem['first_classification'],
                'second_classification' => $checkItem['second_classification'],
                'third_classification'  => $checkItem['third_classification'],
            ];
            $mdl->save($data);
        }
        return true;
    }
}
?>