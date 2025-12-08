<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 福袋组合规则分配基础物料Lib类
 * @todo：订单创建后，根据平台销售物料编码，进行分配福袋组合规则中的基础物料；
 *
 * @author wangbiao@shopex.cn
 * @version 2024.09.10
 */
class material_fukubukuro_dispatch extends material_abstract
{
    /**
     * Model对象
     */
    protected $_saleFukuMdl = null;
    protected $_combineMdl = null;
    protected $_materialMdl = null;
    
    /**
     * Lib类
     */
    protected $_stockLib = null;
    
    /**
     * 变量(使用前需要初始化)
     */

    private $_orderMaterial = array(); //最终分配的订单items明细列表
    private $_allotMaterialList = array(); //已被使用的基础物料列表
    
    private $_sale_sm_id = 0; //销售物料ID
    private $_sale_material_nums = 0; //销售物料购买数量
    private $_shop_bn = ''; //店铺编码
    
    /**
     * 变量(重复使用的)
     */
    private $_existProducts = array(); //已经分配过的基础物料列表
    private $_materialList = array(); //所有基础物料列表信息(包含可用库存数量)
    private $_stockSummary = array(); //库存汇总信息，方便使用
    
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        //Model
        $this->_saleFukuMdl = app::get('material')->model('sales_material_fukubukuro');
        $this->_combineMdl = app::get('material')->model('fukubukuro_combine');
        $this->_materialMdl = app::get('material')->model('basic_material');
        
        //Lib
        $this->_stockLib = kernel::single('material_basic_material_stock');
    }
    
    /**
     * 通过订单object行信息，进行分配福袋组合规则中的基础物料
     * 主要传参：sm_id（销售物料ID）、sale_material_nums（平台商品购买数量）
     * 
     * @param $orderObjInfo 订单object行信息
     * @return array
     */
    public function process($orderObjInfo)
    {
        //Setting
        $this->_orderMaterial = array();
        $this->_allotMaterialList = array();
        $error_msg = '';
        
        //购买的销售物料ID
        $this->_sale_sm_id = $orderObjInfo['sm_id'];
        $this->_shop_bn = $orderObjInfo['shop_bn'];
        
        //销售物料购买数量
        $this->_sale_material_nums = intval($orderObjInfo['sale_material_nums']);
        if($this->_sale_material_nums < 1){
            $this->_sale_material_nums = 1;
        }
        
        //check
        $result = $this->_checkParams($error_msg);
        if(!$result){
            return $this->error($error_msg);
        }
        
        //获取福袋组合中的基础物料
        $result = $this->allotLuckybagBasicMaterial();
        if($result['rsp'] != 'succ'){
            $error_msg = $result['error_msg'];
            return $this->error($error_msg);
        }
        
        //分配的基础物料格式化成订单item明细信息
        $result = $this->getOrderLuckybagBasicMaterial();
        if($result['rsp'] != 'succ'){
            $error_msg = $result['error_msg'];
            return $this->error($error_msg);
        }
        
        return $this->succ('分配福袋成功', $this->_orderMaterial);
    }
    
    /**
     * 检查传参
     * 
     * @param $error_msg
     * @return bool
     */
    public function _checkParams(&$error_msg=null)
    {
        if(empty($this->_sale_sm_id)){
            $error_msg = '无效的获取福袋组合规则';
            return false;
        }
        
        if(empty($this->_sale_material_nums)){
            $error_msg = '没有提供销售物料购买数量';
            return false;
        }
        
        return true;
    }
    
    /**
     * 按订单中销售物料：分配福袋组合中的基础物料
     * 
     * @param $params
     * @param $errory_msg
     * @return array
     */
    public function allotLuckybagBasicMaterial()
    {
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        
        //销售物料与福袋组合关联列表(按销售价贡献占比排序)
        $filter = array('sm_id'=>$this->_sale_sm_id);
        $itemList = $this->_saleFukuMdl->getList('fd_id,sm_id,combine_id,rate', $filter, 0, -1, 'rate DESC');
        if(empty($itemList)){
            $error_msg = '销售物料没有福袋组合关系';
            return $this->error($error_msg);
        }
        
        $itemList = array_column($itemList, null, 'combine_id');
        $combineIds = array_keys($itemList);
        
        //福袋组合列表
        $filter = array('combine_id'=>$combineIds, 'is_delete'=>'false');
        $combineList = $this->_combineMdl->getList('combine_id,combine_bn,selected_number,include_number', $filter, 0, -1);
        if(empty($combineList)){
            $error_msg = '销售物料关联的福袋组合列表为空';
            return $this->error($error_msg);
        }
        $combineList = array_column($combineList, null, 'combine_id');
        $combineIds = array_keys($combineList);
        
        //福袋组合关联基础物料列表(按实际比例real_ratio字段进行降序排序)
        $filter = array('combine_id'=>$combineIds);
        $combineItems = $combineItemMdl->getList('item_id,combine_id,bm_id,ratio,real_ratio', $filter, 0, -1, 'real_ratio DESC');
        if(empty($combineItems)){
            $error_msg = '销售物料关联的福袋组合规则为空';
            return $this->error($error_msg);
        }
        
        //所有基础物料列表信息
        $bmIds = array_column($combineItems, 'bm_id');
        $this->_materialList = $this->_materialMdl->getList('bm_id,material_name,material_bn', array('bm_id'=>$bmIds));
        $this->_materialList = array_column($this->_materialList, null, 'bm_id');
        
        //批量获取基础物料的可用库存(按店铺供货仓)
        $stockList = $this->_getProductShopBranchStock($bmIds, $this->_shop_bn);
        
        //基础物料对应可用库存
        $this->_originalStockList = array(); // 缓存原始库存信息
        foreach ($this->_materialList as $bmKey => $bmInfo)
        {
            $bm_id = $bmInfo['bm_id'];
            
            //stock
            if(isset($stockList[$bm_id])){
                $stock = $stockList[$bm_id];
            }else{
                $stock = 0;
            }
            
            //可用库存数量
            $this->_materialList[$bm_id]['reality_stock'] = $stock;
            
            //已使用预占的库存数量
            $this->_materialList[$bm_id]['stock_freeze'] = 0;
            
            // 缓存原始库存信息（用于日志）
            $this->_originalStockList[$bm_id] = $stock;
        }
        
        //format
        foreach ($combineItems as $itemKey => $itemVal)
        {
            $combine_id = $itemVal['combine_id'];
            $item_id = $itemVal['item_id'];
            
            $combineList[$combine_id]['items'][$item_id] = $itemVal;
        }
        
        foreach ($combineList as $combine_id =>$combineVal)
        {
            //check items empty
            if(empty($combineVal['items'])){
                //unset
                unset($combineList[$combine_id]);
                
                continue;
            }
            
            //销售价贡献占比
            $combineList[$combine_id]['rate'] = $itemList[$combine_id]['rate'];
            
            //明细中包含基础物料行数
            $combineList[$combine_id]['item_count'] = count($combineVal['items']);
        }
        
        //check
        if(empty($combineList)){
            $error_msg = '销售物料没有有效的福袋组合规则';
            return $this->error($error_msg);
        }
        
        //[福袋组合列表]按销售价贡献占比降序排序
        usort($combineList, array($this, 'compare_by_name'));
        
        //按购买数量循环分配基础物料
        $allotProducts = array();
        $error_msg = '';
        for ($i=1; $i<=$this->_sale_material_nums; $i++)
        {
            foreach ($combineList as $combinKey =>$combineVal)
            {
                $combine_id = $combineVal['combine_id'];
                $combine_bn = $combineVal['combine_bn'];
                
                //exec
                if($combineVal['selected_number'] == $combineVal['item_count']){
                    //场景一：分配所有基础物料
                    $productList = $this->getFullBasicMaterial($combineVal, $error_msg);
                }else{
                    //场景二：分配部分基础物料
                    $productList = $this->getPartBasicMaterial($combineVal, $error_msg);
                }
                
                //fail
                if($productList === false){
                    $error_msg = '福袋组合编码：'. $combine_bn .'，'. $error_msg;
                    return $this->error($error_msg);
                }
                
                //empty
                if(empty($productList)){
                    $error_msg = '福袋组合编码：'. $combine_bn.'没有可分配的基础物料';
                    return $this->error($error_msg);
                }
                
                //记录已经存在的基础物料
                $this->_existProducts = array_merge($this->_existProducts, $productList);
                $this->_existProducts = array_column($this->_existProducts, null, 'bm_id');
                
                $allotProducts[$i][$combine_id] = $productList;
            }
        }
        
        //check
        if(empty($allotProducts)){
            $error_msg = '福袋组合没有找到可分配的基础物料';
            return $this->error($error_msg);
        }
        
        //格式化基础物料
        foreach ($allotProducts as $allotKey => $allotVal)
        {
            foreach ($allotVal as $combine_id => $productList)
            {
                foreach ($productList as $bm_id => $bmVal)
                {
                    $bm_id = $bmVal['bm_id'];
                    $quantity = $bmVal['quantity'];
                    
                    //初始化
                    if(!isset($this->_allotMaterialList[$combine_id][$bm_id])){
                        $this->_allotMaterialList[$combine_id][$bm_id] = array(
                            'combine_id' => $combine_id,
                            'bm_id' => $bm_id,
                            'quantity' => $quantity,
                        );
                    }else{
                        //已经存在,累加数量
                        $this->_allotMaterialList[$combine_id][$bm_id]['quantity'] += $quantity;
                    }
                }
            }
        }
        
        return $this->succ('分配基础物料成功');
    }
    
    /**
     * 场景一：福袋组合规则是分配所有基础物料
     * 
     * @param $combineInfo 福袋组合规则信息
     * @return array
     */
    public function getFullBasicMaterial($combineInfo, &$error_msg=null)
    {
        //选中物料个数
        $selected_number = $combineInfo['selected_number'];
        
        //包含基础物料件数
        $include_number = $combineInfo['include_number'];
        
        //items
        $assignProducts = array();
        foreach ($combineInfo['items'] as $itemKey => $itemVal)
        {
            $bm_id = $itemVal['bm_id'];
            
            //基础物料信息
            $materialInfo = $this->_materialList[$bm_id];
            
            //@todo：分配所有基础物料,不用判断可用库存数量;
            //可用库存 = 总可用库存 - 占用库存数量
            //$stock = $materialInfo['reality_stock'] - $materialInfo['stock_freeze'];
            //if($stock < $include_number){
            //    continue;
            //}
            
            //库存预占
            $this->_materialList[$bm_id]['stock_freeze'] += $include_number;
            
            //data
            $assignProducts[$bm_id] = array('bm_id'=>$bm_id, 'quantity'=>$include_number, 'real_ratio'=>$itemVal['real_ratio']);
        }
        
        return $assignProducts;
    }
    
    /**
     * 获取部分基础物料（优化版 - 添加时间权重调整）
     * 
     * @param array $combineInfo 组合信息
     * @param string &$error_msg 错误信息
     * @return array|false
     */
    public function getPartBasicMaterial($combineInfo, &$error_msg=null)
    {
        //选中物料个数
        $selectedNumber = $combineInfo['selected_number'];
        
        //包含基础物料件数
        $includeNumber = $combineInfo['include_number'];
        
        //items
        $products = array();
        $allBns = array();
        $trueBns = array();
        foreach ($combineInfo['items'] as $itemKey => $itemVal)
        {
            $bm_id = $itemVal['bm_id'];
            
            //基础物料信息
            $materialInfo = $this->_materialList[$bm_id];
            
            //福袋中的所有基础物料
            $allBns[] = $materialInfo['material_bn'];
            
            //可用库存 = 总可用库存 - 占用库存数量
            $stock = $materialInfo['reality_stock'] - $materialInfo['stock_freeze'];
            if($stock < $includeNumber){
                continue;
            }
            
            //满足条件的基础物料
            $trueBns[] = $materialInfo['material_bn'];
            
            //data
            $products[$bm_id] = array('bm_id'=>$bm_id, 'quantity'=>$includeNumber, 'real_ratio'=>$itemVal['real_ratio']);
        }
        
        //check
        if(empty($products)){
            $error_msg = $this->buildDetailedErrorMessage($allBns, $combineInfo, 'no_stock');
            return false;
        }
        
        //只有部分基础物料库存满足
        $succCount = count($products);
        if($succCount < $selectedNumber){
            $diffLine = $selectedNumber - $succCount;
            
            // 传递所有基础物料，让错误信息方法内部分类显示
            $error_msg = $this->buildDetailedErrorMessage($allBns, $combineInfo, 'insufficient_stock', $diffLine);
            return false;
        }
        
        // 优化：添加时间权重调整
        $weightedProducts = $this->adjustWeightByUsageTime($products);
        
        // 检查时间权重调整后的结果
        if (empty($weightedProducts)) {
            $error_msg = $this->buildDetailedErrorMessage($allBns, $combineInfo, 'time_conflict');
            return false;
        }
        
        //temp
        $itemList = $weightedProducts;
        $assignProducts = array();
        
        //随机选中基础物料(依据基础物料的分派比例)
        for ($i=1; $i<=$selectedNumber; $i++)
        {
            $productInfo = $this->ratioRandom($itemList);
            
            $bm_id = $productInfo['bm_id'];
            
            //去除已经分派的基础物料
            unset($itemList[$bm_id]);
            
            //库存预占
            $this->_materialList[$bm_id]['stock_freeze'] += $includeNumber;
            
            //成功分配
            $assignProducts[$bm_id] = $productInfo;
            
            // 记录使用时间（新增优化）
            $this->recordUsageTime($bm_id);
        }
        
        return $assignProducts;
    }

    /**
     * 时间权重调整（方案A）
     * 
     * @param array $products 产品列表
     * @return array
     */
    public function adjustWeightByUsageTime($products) {
        $weightedProducts = array();
        $processingTime = 3; // 处理时间：3秒（符合实际处理时间）
        
        foreach ($products as $bm_id => $productInfo) {
            $userWeight = $productInfo['real_ratio'];
            
            // 时间权重调整
            $lastUsedTime = $this->getLastUsedTime($bm_id);
            $currentTime = time();
            $timeDiff = $currentTime - $lastUsedTime;
            
            if ($timeDiff < $processingTime) {
                // 刚被使用，降低权重
                $timeRatio = $timeDiff / $processingTime;
                $timeWeight = 0.1 + $timeRatio * 0.2; // 10%-30%
            } else {
                // 超过处理时间，完全恢复
                $timeWeight = 1.0;
            }
            
            // 简化权重计算：用户权重 * 时间权重
            $finalWeight = $userWeight * $timeWeight;
            
            $weightedProducts[$bm_id] = array_merge($productInfo, array(
                'real_ratio' => $finalWeight
            ));
        }
        
        return $weightedProducts;
    }

    /**
     * 获取SKU最近使用时间
     * 
     * @param int $bm_id 基础物料ID
     * @return int
     */
    public function getLastUsedTime($bm_id) {
        // 使用cachecore获取，key格式：luckybag_last_used_{bm_id}
        $cacheKey = "luckybag_last_used_{$bm_id}";
        $lastUsedTime = cachecore::fetch($cacheKey);
        
        return $lastUsedTime ? $lastUsedTime : 0;
    }

    /**
     * 记录SKU使用时间
     * 
     * @param int $bm_id 基础物料ID
     */
    public function recordUsageTime($bm_id) {
        $cacheKey = "luckybag_last_used_{$bm_id}";
        cachecore::store($cacheKey, time(), 86400); // 24小时过期
    }

    /**
     * 根据编码获取基础物料ID
     * 
     * @param string $bm_bn 基础物料编码
     * @return int|null
     */
    public function getBmIdByBn($bm_bn) {
        foreach ($this->_materialList as $bm_id => $materialInfo) {
            if ($materialInfo['material_bn'] == $bm_bn) {
                return $bm_id;
            }
        }
        return null;
    }

    /**
     * 获取基础物料的仓库分布信息
     * 
     * @param int $bm_id 基础物料ID
     * @return string
     */
    public function getWarehouseInfo($bm_id) {
        // 使用已经查询的库存信息
        if (!isset($this->_materialList[$bm_id])) {
            return '';
        }
        
        $materialInfo = $this->_materialList[$bm_id];
        $availableStock = $materialInfo['reality_stock'] - $materialInfo['stock_freeze'];
        
        if ($availableStock <= 0) {
            return '';
        }
        
        // 获取店铺供货仓库信息
        $shopBranchs = app::get('ome')->getConf('shop.branch.relationship');
        $branchList = isset($shopBranchs[$this->_shop_bn]) ? $shopBranchs[$this->_shop_bn] : [];
        
        if (empty($branchList)) {
            return "总可用库存: {$availableStock}";
        }
        
        // 构建仓库信息
        $warehouseInfo = array();
        foreach ($branchList as $branch_id => $branch_bn) {
            $branchProductInfo = ['branch_id' => $branch_id, 'product_id' => $bm_id];
            
            // 获取基础物料库存信息
            $stockInfo = ome_branch_product::storeFromRedis($branchProductInfo);
            if ($stockInfo[0] === true) {
                $stock = $stockInfo[2]['store'] - $stockInfo[2]['store_freeze'];
                $warehouseInfo[] = "{$branch_bn}: {$stock}";
            } else {
                $warehouseInfo[] = "{$branch_bn}: 0";
            }
        }
        
        return "仓库分布: " . implode(', ', $warehouseInfo);
    }

    /**
     * 构建详细错误信息
     * 
     * @param array $bm_bns 基础物料编码列表
     * @param array $combineInfo 组合信息
     * @param string $error_type 错误类型
     * @param int $diff_line 差异行数
     * @return string
     */
    public function buildDetailedErrorMessage($bm_bns, $combineInfo, $error_type, $diff_line = 0) {
        $error_msg = '';
        
        // 设置错误摘要
        switch ($error_type) {
            case 'no_stock':
                $error_msg = '福袋分配失败 - 所有基础物料库存不足';
                break;
            case 'insufficient_stock':
                $error_msg = "福袋分配失败 - 缺少{$diff_line}行基础物料";
                break;
            case 'time_conflict':
                $error_msg = '福袋分配失败 - 高并发冲突';
                break;
            default:
                $error_msg = '福袋分配失败';
        }
        
        // 添加详细信息（最精简版）
        $skuRequired = $combineInfo['include_number'] * $this->_sale_material_nums;
        $error_msg .= "\n配置: 选{$combineInfo['selected_number']}含{$combineInfo['include_number']} | 物料:" . implode(',', $bm_bns) . " | 单SKU需{$skuRequired}";
        
        // 添加仓库编码信息
        $shopBranchs = app::get('ome')->getConf('shop.branch.relationship');
        $branchList = isset($shopBranchs[$this->_shop_bn]) ? $shopBranchs[$this->_shop_bn] : [];
        if (!empty($branchList)) {
            $branchBns = array_values($branchList);
            $error_msg .= " | 仓库:" . implode(',', $branchBns);
        }
        
        $error_msg .= "\n库存: ";
        
        // 分类显示满足和不满足的基础物料
        $satisfiedBms = array();
        $unsatisfiedBms = array();
        
        foreach ($bm_bns as $bm_bn) {
            $bm_id = $this->getBmIdByBn($bm_bn);
            if ($bm_id) {
                // 使用缓存的原始库存信息
                $originalStock = isset($this->_originalStockList[$bm_id]) ? $this->_originalStockList[$bm_id] : 0;
                $requiredStock = $combineInfo['include_number'] * $this->_sale_material_nums;
                
                // 根据是否满足条件分类，包含库存信息
                if ($originalStock >= $requiredStock) {
                    $satisfiedBms[] = $bm_bn . "({$originalStock})";
                } else {
                    $unsatisfiedBms[] = $bm_bn . "({$originalStock})";
                }
            }
        }
        
        // 显示满足和不满足的基础物料
        if (!empty($satisfiedBms)) {
            $error_msg .= "✓" . implode(',', $satisfiedBms);
        }
        if (!empty($unsatisfiedBms)) {
            if (!empty($satisfiedBms)) {
                $error_msg .= " ✗" . implode(',', $unsatisfiedBms);
            } else {
                $error_msg .= "✗" . implode(',', $unsatisfiedBms);
            }
        }
        
        // 添加时间权重信息（如果是时间冲突）
        if ($error_type == 'time_conflict') {
            $error_msg .= "\n时间权重信息：\n";
            foreach ($bm_bns as $bm_bn) {
                $bm_id = $this->getBmIdByBn($bm_bn);
                if ($bm_id) {
                    $lastUsedTime = $this->getLastUsedTime($bm_id);
                    $timeDiff = time() - $lastUsedTime;
                    $error_msg .= "- {$bm_bn}: 上次使用时间{$lastUsedTime}, 间隔{$timeDiff}秒\n";
                }
            }
        }
        
        // 添加每个仓库的库存信息到错误消息中
        if (!empty($this->_stockSummary)) {
            $error_msg .= "\n库存详情:";
            foreach ($bm_bns as $bm_bn) {
                $bm_id = $this->getBmIdByBn($bm_bn);
                if ($bm_id && isset($this->_stockSummary[$bm_id])) {
                    $shopFreeze = $this->_stockSummary[$bm_id]['shopFreezeDetails'];
                    $error_msg .= "\n{$bm_bn} - 店铺冻结:{$shopFreeze}";
                    
                    if (!empty($this->_stockSummary[$bm_id]['warehouseStockDetails'])) {
                        foreach ($this->_stockSummary[$bm_id]['warehouseStockDetails'] as $branch_bn => $stockInfo) {
                            $total = $stockInfo['store'];
                            $freeze = $stockInfo['store_freeze'];
                            $error_msg .= " | {$branch_bn}(总:{$total}, 冻:{$freeze})";
                        }
                    }
                }
            }
        }
        
        return $error_msg;
    }
    
    /**
     * 分配的基础物料格式化成订单item明细信息
     * 
     * @param $params 订单object层信息(包含销售物料sm_id)
     * @return array
     */
    public function getOrderLuckybagBasicMaterial()
    {
        $materialExtObj = app::get('material')->model('basic_material_ext');
        
        //check
        if(empty($this->_allotMaterialList)){
            $error_msg = '没有分配的基础物料';
            return $this->error($error_msg);
        }
        
        //bm_id
        $bmIds = array();
        foreach ($this->_allotMaterialList as $combine_id => $allotList)
        {
            foreach ($allotList as $allotKey => $allotInfo)
            {
                $bm_id = $allotInfo['bm_id'];
                
                $bmIds[$bm_id] = $bm_id;
            }
        }
        
        //list
        $basicMaterialList = $this->_materialMdl->getList('bm_id,material_name,material_bn,type', array('bm_id'=>$bmIds));
        if(empty($basicMaterialList)){
            $error_msg = '福袋内的基础物料不存在';
            return $this->error($error_msg);
        }
        $basicMaterialList = array_column($basicMaterialList, null, 'bm_id');
        
        //basic_material_ext
        $bmExtList = $materialExtObj->getList('bm_id,retail_price,cost,weight', array('bm_id'=>$bmIds));
        $bmExtList = array_column($bmExtList, null, 'bm_id');
        
        //format
        $productList = array();
        foreach ($this->_allotMaterialList as $combine_id => $allotList)
        {
            foreach ($allotList as $allotKey => $allotProductInfo)
            {
                $bm_id = $allotProductInfo['bm_id'];
                
                //extend info
                $extendInfo = $bmExtList[$bm_id];
                
                //material info
                $bmInfo = $basicMaterialList[$bm_id];
                if(empty($bmInfo)){
                    continue;
                }
                
                //merge
                $productList[] = array(
                    'bm_id' => $bm_id,
                    'material_bn' => $bmInfo['material_bn'],
                    'material_name' => $bmInfo['material_name'],
                    'type' => $bmInfo['type'],
                    'price' => $extendInfo['retail_price'],
                    'cost' => $extendInfo['cost'],
                    'weight' => ($extendInfo['weight'] ? $extendInfo['weight'] : 0),
                    'number' => $allotProductInfo['quantity'], //分配数量
                    'combine_id' => $combine_id, //使用的福袋组合ID
                    'reality_stock' => $this->_materialList[$bm_id]['reality_stock'], //基础物料可用库存(此字段只为开发观察数据)
                    'stock_freeze' => $this->_materialList[$bm_id]['stock_freeze'], //福袋将要预占的库存(此字段只为开发观察数据)
                );
            }
        }
        
        //Setting
        $this->_orderMaterial = $productList;
        
        //unset
        unset($bmIds, $basicMaterialList, $bmExtList, $productList);
        
        return $this->succ('格式化基础物料信息成功');
    }
    
    /**
     * 批量获取基础物料的可用库存(可用库存 = 总库存 - 冻结库存)
     * 
     * @param $bmIds
     * @return array
     */
    public function _getProductAvailableStock($bmIds)
    {
        $stockList = array();
        
        //items
        foreach ($bmIds as $key => $bm_id)
        {
            //获取基础物料库存信息
            $productInfo = array('bm_id'=>$bm_id);
            $stockInfo = material_basic_material_stock::storeFromRedis($productInfo);
            if($stockInfo[0] === true){
                $stock = $stockInfo[2]['store'] - $stockInfo[2]['store_freeze'];
                
                $stockList[$bm_id] = ($stock > 0 ? $stock : 0);
            }else{
                $stockList[$bm_id] = 0;
            }
        }
        
        return $stockList;
    }
    
    /**
     * 批量获取基础物料--店铺供货仓的可用库存(按店铺关联的仓库列表)
     * 
     * @param $bmIds
     * @return array
     */
    public function _getProductShopBranchStock($bmIds, $shop_bn)
    {
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        
        //店铺供货仓库列表
        $shopBranchs = app::get('ome')->getConf('shop.branch.relationship');
        $branchList = isset($shopBranchs[$shop_bn]) ? $shopBranchs[$shop_bn] : [];
        
        //没有店铺供货仓,取基础物料总可用库存数量
        if(empty($branchList)){
            $stockList = $this->_getProductAvailableStock($bmIds);
            return $stockList;
        }
        
        //通过branch_id + product_id获取可售库存数量
        $stockList = [];
        
        // 初始化库存汇总数组
        $this->_stockSummary = [];
        foreach ($bmIds as $bm_id) {
            $this->_stockSummary[$bm_id] = [
                'shopFreezeDetails' => 0,
                'warehouseStockDetails' => []
            ];
        }
        
        foreach ($branchList as $branch_id => $branch_bn)
        {
            foreach ($bmIds as $bmKey => $bm_id)
            {
                $branchProductInfo = ['branch_id'=>$branch_id, 'product_id'=>$bm_id];
                
                //获取基础物料库存信息
                $stockInfo = ome_branch_product::storeFromRedis($branchProductInfo);
                if($stockInfo[0] === true){
                    $stock = $stockInfo[2]['store'] - $stockInfo[2]['store_freeze'];
                    
                    //可用库存小于0,则为0
                    //if($stock < 0){
                    //    $stock = 0;
                    //}
                    
                    //关联的所有仓库可用库存之和
                    $stockList[$bm_id] += $stock;
                    
                    // 直接更新库存汇总数组
                    $this->_stockSummary[$bm_id]['warehouseStockDetails'][$branch_bn] = [
                        'store' => $stockInfo[2]['store'],
                        'store_freeze' => $stockInfo[2]['store_freeze']
                    ];
                }else{
                    $stockList[$bm_id] += 0;
                    // 记录库存获取失败的情况
                    $this->_stockSummary[$bm_id]['warehouseStockDetails'][$branch_bn] = [
                        'store' => 0,
                        'store_freeze' => 0
                    ];
                }
            }
        }
        
        //订单仓库预占
        $bill_type = $basicMStockFreezeLib::__ORDER_YOU;
        
        //获取店铺冻结(订单冻结)
        $sql = "SELECT sum(num) as total,bm_id FROM sdb_material_basic_material_stock_freeze WHERE bm_id in (".implode(",", $bmIds).") AND obj_type=1 AND bill_type<>".$bill_type." group by bm_id";
        $shopfreezelist = $this->_materialMdl->db->select($sql);
        $shopfreezelist = $shopfreezelist ? array_column($shopfreezelist, null, 'bm_id') : array();

        //减去店铺冻结预占
        if($shopfreezelist && $stockList){
            foreach ($stockList as $bm_id => $stock)
            {
                if(!isset($shopfreezelist[$bm_id])){
                    continue;
                }
                
                if($stock <= 0){
                    continue;
                }
                
                //店铺总冻结数量
                $store_freeze = $shopfreezelist[$bm_id]['total'];
                if($store_freeze <= 0){
                    continue;
                }
                
                //可用库存数量 = 仓库可用库存数量 - 店铺冻结数量
                $stockList[$bm_id] = $stock - $store_freeze;

                $this->_stockSummary[$bm_id]['shopFreezeDetails'] = $store_freeze;
            }
        }


        
        return $stockList;
    }
    

}