<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 福袋订单业务Lib方法
 *
 * @author wangbiao@shopex.cn
 * @version 2024.12.26
 */
class ome_order_luckybag
{
    /**
     * 通过订单ID获取福袋使用记录表
     *
     * @param $order_id
     * @param $luckyObjIds 订单obj_id
     * @return array
     */
    public function getOrderLuckyBagList($order_id, $luckyObjIds=[])
    {
        $luckyMdl = app::get('ome')->model('order_luckybag');
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        
        //filter
        $filter = ['order_id'=>$order_id];
        
        //list
        $dataList = $luckyMdl->getList('*', $filter, 0, -1);
        if(empty($dataList)){
            return [];
        }
        
        //通过obj_id查找销售物料信息
        $fukuList = array();
        if($luckyObjIds){
            $orderObjectMdl = app::get('ome')->model('order_objects');
            $saleFukuMdl = app::get('material')->model('sales_material_fukubukuro');
            
            //object
            $objectList = $orderObjectMdl->getList('obj_id,goods_id', array('obj_id'=>$luckyObjIds));
            $objectList = array_column($objectList, null, 'obj_id');
            $smIds = array_column($objectList, 'goods_id');
            
            //sales_material_fukubukuro
            $tempList = $saleFukuMdl->getList('fd_id,sm_id,combine_id,rate', array('sm_id'=>$smIds), 0, -1, 'rate DESC');
            
            $fukuList = [];
            foreach ($tempList as $fukuKey => $fukuVal)
            {
                $sm_id = $fukuVal['sm_id'];
                $combine_id = $fukuVal['combine_id'];
                
                $fukuList[$sm_id][$combine_id] = $fukuVal;
            }
        }
        
        //福袋组合列表信息
        $combineIds = array_column($dataList, 'combine_id');
        
        //福袋组合列表
        $combineList = $combineMdl->getList('combine_id,combine_name,selected_number,include_number', array('combine_id'=>$combineIds), 0, -1);
        $combineList = array_column($combineList, null, 'combine_id');
        
        //format
        $luckyItems = array();
        foreach ($dataList as $itemKey => $itemVal)
        {
            $item_id = $itemVal['item_id'];
            $obj_id = $itemVal['obj_id'];
            $combine_id = $itemVal['combine_id'];
            
            //福袋组合名称
            $itemVal['combine_name'] = $combineList[$combine_id]['combine_name'];
            
            //sm_id
            $sm_id = $objectList[$obj_id]['goods_id'];
            
            //福袋组合价格贡献占比
            $fukuInfo = array();
            if(isset($fukuList[$sm_id][$combine_id])){
                $fukuInfo = $fukuList[$sm_id][$combine_id];
            }
            
            $itemVal['rate'] = $fukuInfo['rate'];
            
            $luckyItems[$item_id] = $itemVal;
        }
        
        //unset
        unset($filter, $dataList, $combineIds, $combineList);
        
        return $luckyItems;
    }
    
    /**
     * 创建订单后保存福袋使用日志
     *
     * @param $orderInfo
     * @return bool
     */
    public function saveLuckyBagUseLogs($orderInfo)
    {
        $luckyMdl = app::get('ome')->model('order_luckybag');
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        
        //order_id
        $order_id = $orderInfo['order_id'];
        
        //data
        $dataList = array();
        foreach ($orderInfo['order_objects'] as $objKey => $objVal)
        {
            $obj_id = $objVal['obj_id'];
            
            //check
            if($objVal['obj_type'] != 'lkb'){
                continue;
            }
            
            //order_items
            foreach ($objVal['order_items'] as $item_id => $itemVal)
            {
                $item_id = $itemVal['item_id'];
                $luckybag_id = $itemVal['luckybag_id']; //福袋组合ID
                $price_rate = ($itemVal['price_rate'] ? $itemVal['price_rate'] : 0); //价格贡献占比
                
                //check
                if(empty($luckybag_id)){
                    continue;
                }
                
                //data
                $dataList[] = array(
                    'obj_id' => $obj_id,
                    'item_id' => $item_id,
                    'luckybag_id' => $luckybag_id,
                    'bm_id' => $itemVal['product_id'],
                    'price_rate' => $price_rate,
                );
            }
        }
        
        //check
        if(empty($dataList)){
            return false;
        }
        
        //luckybag_id
        $combineIds = array_column($dataList, 'luckybag_id');
        
        //福袋组合列表
        $combineList = $combineMdl->getList('combine_id,combine_bn,selected_number,include_number', array('combine_id'=>$combineIds), 0, -1);
        $combineList = array_column($combineList, null, 'combine_id');
        
        //福袋组合关联基础物料列表(按实际比例real_ratio字段进行降序排序)
        if($combineList){
            $combineItems = $combineItemMdl->getList('item_id,combine_id,bm_id,real_ratio', array('combine_id'=>array_keys($combineList)), 0, -1, 'real_ratio DESC');
            foreach ((array)$combineItems as $itemKey => $itemVal)
            {
                $combine_id = $itemVal['combine_id'];
                $bm_id = $itemVal['bm_id'];
                
                $combineList[$combine_id]['items'][$bm_id] = $itemVal;
            }
        }
        
        //save
        foreach ($dataList as $itemKey => $itemVal)
        {
            $luckybag_id = $itemVal['luckybag_id'];
            $bm_id = $itemVal['bm_id'];
            
            //info
            $combineInfo = $combineList[$luckybag_id];
            if(empty($combineInfo)){
                continue;
            }
            
            //real_ratio
            $real_ratio = $combineInfo['items'][$bm_id]['real_ratio'];
            if(empty($real_ratio)){
                $real_ratio = 0;
            }
            
            //sdf
            $sdf = array(
                'order_id' => $order_id,
                'obj_id' => $itemVal['obj_id'],
                'item_id' => $itemVal['item_id'],
                'combine_id' => $luckybag_id,
                'combine_bn' => $combineInfo['combine_bn'],
                'bm_id' => $itemVal['bm_id'],
                'selected_number' => $combineInfo['selected_number'],
                'include_number' => $combineInfo['include_number'],
                'real_ratio' => $real_ratio,
                'price_rate' => $itemVal['price_rate'],
            );
            $luckyMdl->save($sdf);
        }
        
        return true;
    }
    
    /**
     * 获取福袋销售物料最大可售卖的库存数量
     * @todo：福袋销售物料是三层结构(销售物料-->福袋组合-->基础物料);
     *
     * @param $sales_material 销售物料信息
     * @param $method 获取基础物料库存的方法名
     * @return array
     */
    public function getLuckyBagProductStock($sales_material, $method='get_actual_stock')
    {
        $basicObj = kernel::single('inventorydepth_calculation_basicmaterial');
        
        //指定查询库存的店铺
        $skuInfo = $sales_material['skuInfo'];
        
        //福袋关联的基础物料列表
        $basicMaterials = $sales_material['products'];
        
        //check
        if(empty($method) || empty($skuInfo) || empty($basicMaterials)){
            return [];
        }
        
        //指定店铺库存
        $shop_id = $skuInfo['shop_id'];
        $shop_bn = $skuInfo['shop_bn'];
        
        //获取基础物料可用库存
        //@todo：福袋销售物料是三层结构(销售物料-->福袋组合-->基础物料);
        foreach ($basicMaterials as $combine_id => $combineInfo)
        {
            //包含基础物料件数
            $include_number = $combineInfo['include_number'];
            
            //基础物料列表
            foreach ($combineInfo['items'] as $bm_id => $bmInfo)
            {
                //method：get_actual_stock
                list($oriNum, $msg) = $basicObj->{$method}($bm_id, $shop_bn, $shop_id);
                
                //check
                if(!$oriNum){
                    $oriNum = 0;
                }
                
                //实际可用库存 = 总可用库存 / 基础物料数量
                $actual_stock = floor($oriNum / $include_number);
                
                //merge
                $basicMaterials[$combine_id]['items'][$bm_id]['material_stock'] = $oriNum; //基础物料库存数量
                $basicMaterials[$combine_id]['items'][$bm_id]['actual_stock'] = $actual_stock; //可用库存数量
                $basicMaterials[$combine_id]['items'][$bm_id]['number'] = $include_number; //购买件数
                $basicMaterials[$combine_id]['items'][$bm_id]['stock_msg'] = $msg; //报错信息
            }
        }
        
        //计算基础物料可回写库存数量
        foreach ($basicMaterials as $combine_id => $combineInfo)
        {
            //选中物料个数
            $selected_number = $combineInfo['selected_number'];
            
            //可售库存数量降序排序
            usort($combineInfo['items'], array($this, 'compare_by_actual_stock'));
            
            //按选中物料个数进行分组,给最小可回写库存的基础物料打标记
            $basicMaterials[$combine_id]['items'] = $this->getMaterialMinStock($combineInfo['items'], $selected_number);
        }
        
        //循环获取每个福袋组合最大可回写库存数量
        foreach ($basicMaterials as $combine_id => $combineInfo)
        {
            $luckybag_stock = 0;
            
            //基础物料列表
            foreach ($combineInfo['items'] as $bm_id => $bmInfo)
            {
                //累加可回写的库存数量
                if($bmInfo['is_checked']){
                    $luckybag_stock += $bmInfo['actual_stock'];
                }
            }
            
            //福袋最大可回写库存数量
            $basicMaterials[$combine_id]['luckybag_stock'] = $luckybag_stock;
        }
        
        return $basicMaterials;
    }
    
    /**
     * 按指定字段进行降序排序
     *
     * @param $a
     * @param $b
     * @return int
     */
    protected function compare_by_actual_stock($a, $b)
    {
        if ($a['actual_stock'] == $b['actual_stock']) {
            return 0;
        }else{
            return ($a['actual_stock'] > $b['actual_stock']) ? -1 : 1;
        }
    }
    
    /**
     * 获取基础物料列表最小可回写库存数量
     *
     * @param $materailList 基础物料列表
     * @param $selected_number 福袋组合选择的基础物料行数
     * @return array
     */
    public function getMaterialMinStock($materailList, $selected_number)
    {
        //切割数组
        $splitList = array_chunk($materailList, $selected_number);
        
        //format
        $dataList = array();
        foreach ($splitList as $splitKey => $itemList)
        {
            //count
            $item_count = count($itemList);
            
            //setting
            $min_stock = 0;
            $line_i = 0;
            $min_bm_id = 0;
            
            //按分组,获取最小库存数量
            if($item_count == $selected_number){
                foreach ($itemList as $itemKey => $itemVal)
                {
                    $line_i++;
                    
                    //min stock
                    if($line_i == 1){
                        //初始化
                        $min_stock = $itemVal['actual_stock'];
                        
                        //bm_id
                        $min_bm_id = $itemVal['bm_id'];
                    }elseif($min_stock > $itemVal['actual_stock']){
                        //获取最小库存数量
                        $min_stock = $itemVal['actual_stock'];
                        
                        //bm_id
                        $min_bm_id = $itemVal['bm_id'];
                    }
                }
            }
            
            //flag
            foreach ($itemList as $itemKey => $itemVal)
            {
                $bm_id = $itemVal['bm_id'];
                
                //打标记
                if($min_bm_id == $bm_id){
                    $itemVal['is_checked'] = true;
                }else{
                    $itemVal['is_checked'] = false;
                }
                
                $dataList[$bm_id] = $itemVal;
            }
        }
        
        return $dataList;
    }
    
    /**
     * [换货订单]通过订单ID获取福袋使用记录表
     *
     * @param $order_id
     * @param $luckyObjIds 订单obj_id
     * @return array
     */
    public function getChangeOrderLuckyBagList($order_id, $luckyObjIds=[])
    {
        $itemMdl = app::get('ome')->model('order_items');
        $orderObjectMdl = app::get('ome')->model('order_objects');
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        $saleFukuMdl = app::get('material')->model('sales_material_fukubukuro');
        
        //object
        $objectList = $orderObjectMdl->getList('obj_id,goods_id', array('obj_id'=>$luckyObjIds));
        if(empty($objectList)){
            return [];
        }
        
        $objectList = array_column($objectList, null, 'obj_id');
        $smIds = array_column($objectList, 'goods_id');
        
        //items
        $itemList = $itemMdl->getList('*', array('order_id'=>$order_id, 'obj_id'=>$luckyObjIds));
        if(empty($itemList)){
            return [];
        }
        
        $itemList = array_column($itemList, null, 'item_id');
        
        //luckybag_id
        $combineIds = array_column($itemList, 'luckybag_id');
        
        //福袋组合列表
        $combineList = $combineMdl->getList('combine_id,combine_name,selected_number,include_number', array('combine_id'=>$combineIds), 0, -1);
        $combineList = array_column($combineList, null, 'combine_id');
        if(empty($combineList)){
            return [];
        }
        
        //fukubukuro_combine_items
        $combineItemsList = [];
        $combineItems = $combineItemMdl->getList('item_id,combine_id,bm_id,real_ratio', array('combine_id'=>$combineIds), 0, -1, 'real_ratio DESC');
        foreach ((array)$combineItems as $itemKey => $itemVal)
        {
            $combine_id = $itemVal['combine_id'];
            $bm_id = $itemVal['bm_id'];
            
            $combineItemsList[$combine_id]['items'][$bm_id] = $itemVal;
        }
        
        //sales_material_fukubukuro
        $fukuList = [];
        $tempList = $saleFukuMdl->getList('fd_id,sm_id,combine_id,rate', array('sm_id'=>$smIds), 0, -1, 'rate DESC');
        foreach ($tempList as $fukuKey => $fukuVal)
        {
            $sm_id = $fukuVal['sm_id'];
            $combine_id = $fukuVal['combine_id'];
            
            $fukuList[$sm_id][$combine_id] = $fukuVal;
        }
        
        //format
        foreach ($itemList as $item_id => $itemVal)
        {
            $obj_id = $itemVal['obj_id'];
            $luckybag_id = $itemVal['luckybag_id'];
            $bm_id = $itemVal['product_id'];
            
            //check
            if(!isset($combineList[$luckybag_id])){
                continue;
            }
            
            //sm_id
            $sm_id = $objectList[$obj_id]['goods_id'];
            
            //福袋组合价格贡献占比
            $fukuInfo = array();
            if(isset($fukuList[$sm_id][$luckybag_id])){
                $fukuInfo = $fukuList[$sm_id][$luckybag_id];
            }
            
            $itemVal['rate'] = ($fukuInfo['rate'] ? $fukuInfo['rate'] : 0);
            
            //merge
            $itemList[$item_id] = array_merge($itemVal, $combineList[$luckybag_id]);
            
            //real_ratio
            if(isset($combineItemsList[$luckybag_id]['items'][$bm_id])){
                $itemList[$item_id]['real_ratio'] = $combineItemsList[$luckybag_id]['items'][$bm_id]['real_ratio'];
            }
        }
        
        return $itemList;
    }
}