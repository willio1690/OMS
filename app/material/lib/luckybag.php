<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_luckybag
{
    /**
     * 处理luckybag_log表数据（此方法已经弃用）
     * author：20180410 by wangjianjun
     *
     * @param $arr_data
     * @param $filter_data
     * @return void
     */
    public function deal_luckybag_log($arr_data,$filter_data=array()){
        $ma_lu_lo = app::get('material')->model('luckybag_log');
        if(!empty($filter_data)){ //更新
            $ma_lu_lo->update($arr_data,$filter_data);
        }else{ //新增
            $ma_lu_lo->insert($arr_data);
        }
    }
    
    /**
     * 通过sm_id获取可用库存
     *
     * @param $sm_id
     * @param $error_msg
     * @return int
     */
    public function getStockBySmid($sm_id, &$error_msg=null)
    {
        $basicMaterialStockObj = app::get('material')->model('basic_material_stock');
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        $bmIds = $this->getLuckyBmidsBySmid($sm_id, $error_msg);
        if(!$bmIds){
            return false;
        }
        
        $bmStoreInfo = $basicMaterialStockObj->getList('bm_id,store,store_freeze',array('bm_id'=>$bmIds));
        
        $stockList = array();
        foreach($bmStoreInfo as $bmKey => $storeInfo)
        {
            //根据基础物料ID获取对应的冻结库存
            $storeInfo['store_freeze'] = $basicMStockFreezeLib->getMaterialStockFreeze($storeInfo['bm_id']);
            
            $store_num = $storeInfo['store'] - $storeInfo['store_freeze'];
            
            $stockList[] = $store_num > 0 ? $store_num : 0;
        }
        
        //升序排列
        sort($stockList);
        
        return $stockList[0];
    }
    
    /**
     * 通过sm_id获取关联福袋组合的基础物料ID
     *
     * @param $sm_id
     * @param $error_msg
     * @return array
     */
    public function getLuckyBmidsBySmid($sm_id, &$error_msg=null)
    {
        $saleFukuMdl = app::get('material')->model('sales_material_fukubukuro');
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        
        //销售物料与福袋组合关联列表(按销售价贡献占比排序)
        $filter = array('sm_id'=>$sm_id);
        $itemList = $saleFukuMdl->getList('fd_id,sm_id,combine_id,rate', $filter, 0, -1, 'rate DESC');
        if(empty($itemList)){
            $error_msg = '没有获取到销售关联的福袋组合';
            return false;
        }
        $itemList = array_column($itemList, null, 'combine_id');
        $combineIds = array_keys($itemList);
        
        //福袋组合列表
        $filter = array('combine_id'=>$combineIds, 'is_delete'=>'false');
        $combineList = $combineMdl->getList('combine_id,combine_bn,selected_number,include_number', $filter, 0, -1);
        if(empty($combineList)){
            $error_msg = '销售物料关联的福袋组合列表为空';
            return false;
        }
        $combineList = array_column($combineList, null, 'combine_id');
        $combineIds = array_keys($combineList);
        
        //福袋组合关联基础物料列表(按实际比例real_ratio字段进行降序排序)
        $filter = array('combine_id'=>$combineIds);
        $combineItems = $combineItemMdl->getList('item_id,combine_id,bm_id', $filter, 0, -1, 'item_id ASC');
        if(empty($combineItems)){
            $error_msg = '销售物料关联的福袋组合规则为空';
            return false;
        }
        
        $bmIds = array_column($combineItems, 'bm_id');
        
        return $bmIds;
    }
    
    /**
     * 批量通过基础物料bm_id获取关联的销售物料sm_id
     *
     * @param $bmIds
     * @param $error_msg
     * @return array
     */
    public function batchGetSmidByBmid($bmIds, &$error_msg=null)
    {
        $saleFukuMdl = app::get('material')->model('sales_material_fukubukuro');
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        
        $smIds = array();
        
        //基础物料关联的福袋组合列表
        $filter = array('bm_id'=>$bmIds);
        $combineItems = $combineItemMdl->getList('item_id,combine_id', $filter, 0, -1);
        if(empty($combineItems)){
            $error_msg = '销售物料关联的福袋组合规则为空';
            return $smIds;
        }
        
        //combine_id
        $combineIds = array_column($combineItems, 'combine_id');
        
        //福袋组合关联的销售物料列表
        $filter = array('combine_id'=>$combineIds);
        $itemList = $saleFukuMdl->getList('fd_id,sm_id', $filter, 0, -1);
        if(empty($itemList)){
            $error_msg = '没有获取到销售关联的福袋组合';
            return $smIds;
        }
        
        //sm_id
        $smIds = array_column($itemList, 'sm_id');
        $smIds = array_unique($smIds);
        
        return $smIds;
    }
    
    /**
     * [批量]通过福袋组合规则获取关联的所有基础物料列表
     *
     * @param $combineIds 福袋组合规则ID
     * @param $error_msg
     * @param $is_need_extend 是否获取基础物料的扩展信息
     * @return array
     */
    public function getMaterialByCombineid($combineIds, &$error_msg=null, $is_need_extend=false)
    {
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        $basicMaterialObj = app::get('material')->model('basic_material');
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        
        //items
        $itemList = $combineItemMdl->getList('item_id,combine_id,bm_id', array('combine_id'=>$combineIds));
        if(empty($itemList)){
            $error_msg = '没有明细列表数据';
            return array();
        }
        
        //福袋组合列表
        $filter = array('combine_id'=>$combineIds);
        $combineList = $combineMdl->getList('combine_id,combine_bn,selected_number,include_number', $filter, 0, -1);
        if(empty($combineList)){
            $error_msg = '未找到福袋组合列表';
            return array();
        }
        $combineList = array_column($combineList, null, 'combine_id');
        
        //获取最大的基础物料数量
        $bmNumList = array();
        foreach ($itemList as $itemKey => $itemInfo)
        {
            $combine_id = $itemInfo['combine_id'];
            $bm_id = $itemInfo['bm_id'];
            
            //福袋组合信息
            $combineInfo = $combineList[$combine_id];
            
            //包含基础物料数量
            $include_number = ($combineInfo['include_number'] ? $combineInfo['include_number'] : 1);
            
            //取最大的基础物料数量
            if($bmNumList[$bm_id]){
                if($bmNumList[$bm_id] < $include_number){
                    $bmNumList[$bm_id] = $include_number;
                }
            }else{
                $bmNumList[$bm_id] = $include_number;
            }
        }
        
        //material
        $bmIds = array_column($itemList, 'bm_id');
        $bmIds = array_unique($bmIds);
        $materialList = $basicMaterialObj->getList('bm_id,material_bn,material_name', array('bm_id'=>$bmIds));
        $materialList = array_column($materialList, null, 'bm_id');
        
        //extend
        $extendList = array();
        if($is_need_extend){
            $extendList = $basicMaterialExtObj->getList('*', array('bm_id'=>$bmIds));
            $extendList = array_column($extendList, null, 'bm_id');
        }
        
        //format
        foreach ($materialList as $itemKey => $itemVal)
        {
            $bm_id = $itemVal['bm_id'];
            
            //包含基础物料数量
            $include_number = $bmNumList[$bm_id];
            $include_number = ($include_number ? $include_number : 1);
            
            //extend
            if(isset($extendList[$bm_id])){
                $itemVal['cost'] = $extendList[$bm_id]['cost'];
                $itemVal['retail_price'] = $extendList[$bm_id]['retail_price'];
                $itemVal['specifications'] = $extendList[$bm_id]['specifications'];
                $itemVal['unit'] = $extendList[$bm_id]['unit'];
            }
            
            //基础物料数量
            $itemVal['number'] = $include_number;
            
            $materialList[$itemKey] = $itemVal;
        }
        
        //unset
        unset($itemList, $extendList);
        
        return $materialList;
    }
    
    /**
     * 重新保存销售物料关联的福袋组合规中的基础物料
     *
     * @param $cursor_id
     * @param $params
     * @param $error_msg
     * @return false
     */
    public function resaveLuckySalesBmids(&$cursor_id, $params, &$error_msg=null)
    {
        $saleFukuMdl = app::get('material')->model('sales_material_fukubukuro');
        $salesMaterialMdl = app::get('material')->model('sales_basic_material');
        
        //data
        $sdfdata = $params['sdfdata'];
        $sm_id = intval($sdfdata['sm_id']);
        
        //销售物料与福袋组合关联列表
        $filter = array('sm_id'=>$sm_id);
        $tempList = $saleFukuMdl->getList('fd_id,sm_id,combine_id', $filter, 0, -1);
        if(empty($tempList)){
            $error_msg = '销售物料没有福袋组合';
            return false;
        }
        
        //combine_id
        $combineIds = array_column($tempList, 'combine_id');
        
        //通过福袋组合规则获取关联的所有基础物料列表
        $materialList = $this->getMaterialByCombineid($combineIds, $error_msg);
        if(empty($materialList)){
            $error_msg = '获取相关的基础物料失败：'. $error_msg;
            return false;
        }
        
        //删除销售物料关联的基础物料
        $salesMaterialMdl->delete(array('sm_id'=>$sm_id));
        
        //保存关联的基础物料列表
        foreach ($materialList as $maerialKey => $materialVal)
        {
            $sdf = array(
                'sm_id' => $sm_id,
                'bm_id' => $materialVal['bm_id'],
                'number' => $materialVal['number'],
            );
            $salesMaterialMdl->insert($sdf);
        }
        
        return false;
    }
    
    /**
     * 计算[已经随机挑选好的]福袋基础物料列表的销售价贡献占比
     *
     * @param $bmIds
     * @param $error_msg
     * @return void
     */
    public function getMaterialBasicRates($bmIds, &$error_msg=null)
    {
        //check
        if(empty($bmIds)){
            $error_msg = '没有到基础物料ID';
            return false;
        }
        
        //获取基础物料价格
        $basicMInfos = kernel::single('material_basic_material')->getBasicMaterialByBmids($bmIds);
        if(empty($basicMInfos)){
            $error_msg = '没有获取到基础物料';
            return false;
        }
        
        $bmCount = count($basicMInfos);
        
        //retail_price
        $retailPrices = array_column($basicMInfos, 'retail_price');
        $total_price = array_sum($retailPrices);
        
        //rate
        $total_rate = 100;
        $less_rate = $total_rate;
        if($total_price > 0){
            //场景：总零售价大于0
            $bm_line = 0;
            foreach ($basicMInfos as $bmKey => $bmVal)
            {
                $bm_line++;
                
                $retail_price = $bmVal['retail_price'];
                
                if($bm_line == $bmCount){
                    $basicMInfos[$bmKey]['rate'] = $less_rate;
                }else{
                    //高精度除法(不保留小数位)
                    $bm_rate = bcdiv($retail_price, $total_price, 2) * 100;
                    
                    //高精度减法(保留小数位必需与上面保持一致)
                    $less_rate = bcsub($less_rate, $bm_rate);
                    
                    $basicMInfos[$bmKey]['rate'] = $bm_rate;
                }
            }
        }else{
            //场景：总零售价等于0
            $bm_line = 0;
            foreach ($basicMInfos as $bmKey => $bmVal)
            {
                $bm_line++;
                
                if($bm_line == $bmCount){
                    $basicMInfos[$bmKey]['rate'] = $less_rate;
                }else{
                    //高精度除法(不保留小数位)
                    $bm_rate = bcdiv($total_rate, $bmCount, 0);
                    
                    //高精度减法(保留小数位必需与上面保持一致)
                    $less_rate = bcsub($less_rate, $bm_rate, 0);
                    
                    $basicMInfos[$bmKey]['rate'] = $bm_rate;
                }
            }
        }
        
        return $basicMInfos;
    }
    
    /**
     * 计算换出商品关联的基础物料价格
     *
     * @param $changeInfo 换出商品信息
     * @param $error_msg
     * @return void
     */
    public function getReshipMaterialPrices($changeInfo, &$error_msg=null)
    {
        $salesMLib = kernel::single('material_sales_material');
        
        //check
        if(empty($changeInfo)){
            $error_msg = '无效的换出商品数据';
            return false;
        }
        
        if(empty($changeInfo['items'])){
            $error_msg = '没有换出商品明细';
            return false;
        }
        
        $change_price = $changeInfo['price'];
        if($change_price <= 0){
            $error_msg = '换出价格为0';
            return false;
        }
        
        $itemList = array_column($changeInfo['items'], null, 'bm_id');
        
        //bm_id
        $bmIds = array_column($changeInfo['items'], 'bm_id');
        
        //check
        if(empty($bmIds)){
            $error_msg = '没有到基础物料ID';
            return false;
        }
        
        //获取基础物料价格
        $basicMInfos = kernel::single('material_basic_material')->getBasicMaterialByBmids($bmIds);
        if(empty($basicMInfos)){
            $error_msg = '没有获取到基础物料';
            return false;
        }
        
        $bmCount = count($basicMInfos);
        
        //retail_price
        $retailPrices = array_column($basicMInfos, 'retail_price');
        $total_price = array_sum($retailPrices);
        
        //rate
        $total_rate = 100;
        $less_rate = $total_rate;
        if($total_price > 0){
            //场景：总零售价大于0
            $bm_line = 0;
            foreach ($basicMInfos as $bmKey => $bmVal)
            {
                $bm_line++;
                
                $retail_price = $bmVal['retail_price'];
                
                if($bm_line == $bmCount){
                    $basicMInfos[$bmKey]['rate'] = $less_rate;
                }else{
                    //高精度除法(不保留小数位)
                    $bm_rate = bcdiv($retail_price, $total_price, 2) * 100;
                    
                    //高精度减法(保留小数位必需与上面保持一致)
                    $less_rate = bcsub($less_rate, $bm_rate);
                    
                    $basicMInfos[$bmKey]['rate'] = $bm_rate;
                }
            }
        }else{
            //场景：总零售价等于0
            $bm_line = 0;
            foreach ($basicMInfos as $bmKey => $bmVal)
            {
                $bm_line++;
                
                if($bm_line == $bmCount){
                    $basicMInfos[$bmKey]['rate'] = $less_rate;
                }else{
                    //高精度除法(不保留小数位)
                    $bm_rate = bcdiv($total_rate, $bmCount, 0);
                    
                    //高精度减法(保留小数位必需与上面保持一致)
                    $less_rate = bcsub($less_rate, $bm_rate, 0);
                    
                    $basicMInfos[$bmKey]['rate'] = $bm_rate;
                }
            }
        }
        
        //均摊价格
        $salesMLib->calProSaleMPriceByRate($change_price, $basicMInfos);
        
        //按退货数量均摊价格
        $less_price = $change_price;
        $item_count = count($basicMInfos);
        $line_i = 0;
        foreach ($basicMInfos as $bmKey => $bmVal)
        {
            $line_i++;
            
            $bm_id = $bmVal['bm_id'];
            $rate_price = $bmVal['rate_price'];
            
            //number
            $number = $itemList[$bm_id]['number'];
            
            //rate_price
            if(empty($rate_price)){
                continue;
            }
            
            if(empty($number)){
                continue;
            }
            
            //换出数量
            $basicMInfos[$bmKey]['change_num'] = $number;
            
            //price
            if($line_i == $item_count){
                //最后一个保留三位小数位
                $avg_price = bcdiv($less_price, $number, 3);
            }else{
                $avg_price = bcdiv($rate_price, $number, 2);
                
                $less_price = bcsub($less_price, $avg_price * $number, 2);
            }
            
            //avg_price
            $basicMInfos[$bmKey]['avg_price'] = $avg_price;
        }
        
        return $basicMInfos;
    }
}