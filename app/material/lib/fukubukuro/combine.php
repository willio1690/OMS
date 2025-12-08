<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 福袋组合Lib类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.09.10
 */
class material_fukubukuro_combine extends material_abstract
{
    /**
     * 数据检验有效性
     * 
     * @param  Array   $params
     * @param  String  $error_msg
     * @return Boolean
     */

    public function checkParams(&$params, &$error_msg=null)
    {
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        $basicMaterialObj = app::get('material')->model('basic_material');
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        
        $params['combine_bn'] = trim($params['combine_bn']);
        $params['selected_number'] = intval($params['selected_number']);
        $params['include_number'] = intval($params['include_number']);
        
        if(empty($params['combine_bn'])){
            $error_msg = '福袋组合编码不能为空';
            return false;
        }
        
        if(empty($params['combine_name'])){
            $error_msg = '福袋组合名称不能为空';
            return false;
        }
        
        if($params['selected_number'] <= 0){
            $error_msg = '没有填写选择基础物料个数';
            return false;
        }
        
        if($params['include_number'] <= 0){
            $error_msg = '没有填写分别包含件数';
            return false;
        }
        
        if(empty($params['rates'])){
            $error_msg = '没有选择关联基础物料';
            return false;
        }
        
        //判断物料编码只能是由数字英文下划线组成
        $reg_bn_code = "/^[0-9a-zA-Z\_\#\-\/]*$/";
        if(!preg_match($reg_bn_code, $params['combine_bn'])){
            $error_msg = "福袋组合编码只支持(数字、英文、_下划线、-横线、#井号、/斜杠)组成";
            return false;
        }
        
        //编码首字母只支持数字、英文、_下划线
        $reg_rule_2 = "/^[0-9a-zA-Z\_]*$/";
        $first_letter = substr($params['combine_bn'], 0, 1);
        if(!preg_match($reg_rule_2, $first_letter)){
            $error_msg = "福袋组合编码首字母只支持(数字、英文、_下划线)组成";
            return false;
        }
        
        //info
        if($params['combine_id']){
            $combineInfo = $combineMdl->dump(array('combine_id'=>$params['combine_id']), '*');
        }else{
            $combineInfo = $combineMdl->dump(array('combine_bn'=>$params['combine_bn']), '*');
        }
        
        //unset
        unset($params['combine_id']);
        
        //add or update
        $is_update = false;
        if($combineInfo){
            $is_update = true;
            
            $params['combine_id'] = $combineInfo['combine_id'];
        }
        
        //检查有效性
        if($is_update){
            //不能删除combine_bn字段,否则验证失败时,没有提示错误福袋编码
            //unset($params['combine_bn']);
        }else{
            //新增
            $combineRow = $combineMdl->dump(array('combine_bn'=>$params['combine_bn']), 'combine_id');
            if($combineRow){
                $error_msg = $params['combine_bn'] . '编码已经被使用!';
                return false;
            }
        }
        
        //material
        $bmIds = array_keys($params['rates']);
        $materialList = $basicMaterialObj->getList('bm_id,material_bn', array('bm_id'=>$bmIds));
        $materialList = array_column($materialList, null, 'bm_id');
        if(empty($materialList)){
            $error_msg = '关联基础物料不存在!';
            return false;
        }
        
        //extend
        $extendList = $basicMaterialExtObj->getList('bm_id,cost,retail_price', array('bm_id'=>$bmIds));
        $extendList = array_column($extendList, null, 'bm_id');
        
        //format material
        $itemList = array();
        $total_ratio = 0;
        $random_line = 0;
        $line_i = 0;
        foreach ($params['rates'] as $bm_id => $rateVal)
        {
            $line_i++;
            
            $is_flag = 'add';
            $rateVal = trim($rateVal);
            
            //随机为-1
            if($rateVal == '随机' || $rateVal == '-1'){
                $rateVal = -1;
                
                $random_line++;
            }else{
                if(!preg_match("/^[1-9][0-9]*$/", $rateVal)){
                    $error_msg = '第'. $line_i .'行选中比例(%)只能填写正整数';
                    return false;
                }
                
                $rateVal = intval($rateVal);
                
                $total_ratio += $rateVal;
            }
            
            //check
            if(empty($bm_id) || empty($rateVal)){
                $error_msg = '第'. $line_i .'行没有填写选中比例(%)';
                return false;
            }
            
            if(!isset($materialList[$bm_id])){
                $error_msg = '第'. $line_i .'行基础物料不存在';
                return false;
            }
            
            if(empty($materialList[$bm_id])){
                $error_msg = '第'. $line_i .'行基础物料为空';
                return false;
            }
            
            if(empty($rateVal)){
                $error_msg = '第'. $line_i .'行选中比例不能为空';
                return false;
            }
            
            //items
            $itemList[$bm_id] = array('bm_id'=>$bm_id, 'retail_price'=>$extendList[$bm_id]['retail_price'], 'ratio'=>$rateVal, 'is_flag'=>$is_flag);
        }
        
        //check
        if(empty($itemList)){
            $error_msg = '关联基础物料无效,请检查!';
            return false;
        }
        
        //检查比例
        if($total_ratio > 100){
            $error_msg = '选中比例总和不能大于100';
            return false;
        }elseif ($total_ratio == 100 && $random_line > 0){
            $error_msg = '选中比例已经等于100,不能同时存在【随机】';
            return false;
        }elseif($total_ratio < 100 && $random_line == 0){
            $error_msg = '选中比例之和必须等于100';
            return false;
        }elseif($total_ratio > 0 && $random_line > 0){
            $temp_nums = $total_ratio + $random_line;
            if($temp_nums > 100){
                $error_msg = '选中比例之和是：'. $total_ratio .',并且有'. $random_line .'行【随机】,不符合比例！';
                return false;
            }
        }
        
        $params['items'] = $itemList;
        
        //check
        if($params['selected_number'] > count($itemList)){
            $error_msg = '组合规则--选择物料个数不能大于添加的基础物料总行数,请检查!';
            return false;
        }
        
        return true;
    }
    
    /**
     * 格式化数据
     * 
     * @param  Array   $params
     * @param  String  $error_msg
     * @return Boolean
     */
    public function formatData(&$data, &$error_msg=null)
    {
        //check
        if(empty($data['items'])){
            $error_msg = '关联基础物料为空,请检查!';
            return false;
        }
        
        $selected_number = $data['selected_number'];
        $include_number = $data['include_number'];
        $item_count = count($data['items']);
        
        //items
        $random_line = 0;
        $total_ratio = 0;
        $selling_price = 0;
        $retailPrices = array();
        foreach ($data['items'] as $itemKey => $itemVal)
        {
            if($itemVal['ratio'] == -1){
                $random_line++;
            }else{
                $total_ratio += $itemVal['ratio'];
            }
            
            //实际比例,默认取ratio比例字段值
            $data['items'][$itemKey]['real_ratio'] = $itemVal['ratio'];
            
            $selling_price += $itemVal['retail_price'];
            
            $retailPrices[] = $itemVal['retail_price'];
        }
        
        //重新计算real_ratio字段值,分摊【随机】比例
        if($random_line){
            $diff_ratio = 100 - $total_ratio;
            $avg_ratio = intdiv($diff_ratio, $random_line);
            $less_ratio = $diff_ratio;
            
            $item_i = 0;
            foreach ($data['items'] as $itemKey => $itemVal)
            {
                $item_i++;
                
                //check
                if($itemVal['ratio'] != -1){
                    continue;
                }
                
                //real_ratio
                if($item_count == $item_i){
                    $data['items'][$itemKey]['real_ratio'] = $less_ratio;
                }else{
                    $data['items'][$itemKey]['real_ratio'] = $avg_ratio;
                    
                    $less_ratio -= $avg_ratio;
                }
            }
        }
        
        //计算福袋组合最低价&&最高价
        if($selected_number == $item_count){
            //场景一：添加的基础物料行数 与 组合规则中选择个数相同
            $total_amount = array_sum($retailPrices);
            
            //最低价 = 所有基础物料售价之和 * 组合规则中包含件数
            $lowest_price = $total_amount * $include_number;
            
            //最高价 = 所有基础物料售价之和 * 组合规则中包含件数
            $highest_price = $total_amount * $include_number;
        }else{
            //场景二：选取部分添加的基础物料行数
            $sortPrices = $retailPrices;
            sort($sortPrices);
            
            $rsortPrices = $retailPrices;
            rsort($rsortPrices);
            
            $line_i = 0;
            $lowest_price = 0;
            $highest_price = 0;
            foreach ($retailPrices as $priceKey => $priceVal)
            {
                $line_i++;
                
                //check
                if($line_i > $selected_number){
                    continue;
                }
                
                $lowest_price += $sortPrices[$priceKey];
                $highest_price += $rsortPrices[$priceKey];
            }
            
            //最低价 = 所有基础物料售价之和 * 组合规则中包含件数
            $lowest_price = $lowest_price * $include_number;
            
            //最高价 = 所有基础物料售价之和 * 组合规则中包含件数
            $highest_price = $highest_price * $include_number;
        }
        
        $data['lowest_price'] = $lowest_price;
        $data['highest_price'] = $highest_price;
        $data['selling_price'] = $selling_price * $include_number;
        
        return true;
    }
    
    /**
     * 保存数据
     * 
     * @param  Array   $params
     * @param  String  $error_msg
     * @return Boolean
     */
    public function saveData(&$data, &$error_msg=null)
    {
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        $basicMaterialObj = app::get('material')->model('basic_material');
        $operationLogObj = app::get('ome')->model('operation_log');
        
        //format
        $data['combine_id'] = intval($data['combine_id']);
        
        //sdf
        $masterSdf = array(
            'combine_bn' => $data['combine_bn'],
            'combine_name' => $data['combine_name'],
            'selected_number' => $data['selected_number'],
            'include_number' => $data['include_number'],
            'selling_price' => $data['selling_price'],
            'lowest_price' => $data['lowest_price'],
            'highest_price' => $data['highest_price'],
            'last_modified' => time(),
        );
        
        //update
        $combineInfo = array();
        $is_edit_nums = false;
        if($data['combine_id']){
            unset($masterSdf['combine_bn']);
            
            //info
            $combineInfo = $combineMdl->dump(array('combine_id'=>$data['combine_id']), '*');
            if(empty($combineInfo)){
                $error_msg = '福袋组合原数据不存在';
                return false;
            }
            
            //update
            $isUpdate = $combineMdl->update($masterSdf, array('combine_id'=>$data['combine_id']));
            if(!$isUpdate){
                $error_msg = '更新福袋组合失败';
                return false;
            }
            
            //is_edit_nums
            if($combineInfo['include_number'] != $masterSdf['include_number']){
                $is_edit_nums = true;
            }
            
            $data['action'] = 'update';
        }else{
            $masterSdf['create_time'] = time();
            
            //insert
            $insert_id = $combineMdl->insert($masterSdf);
            if(!$insert_id){
                $error_msg = '保存福袋组合失败';
                return false;
            }
            
            $data['combine_id'] = $insert_id;
            $data['action'] = 'add';
        }
        
        //items
        $bmIds = array_column($data['items'], 'bm_id');
        $itemList = array_column($data['items'], null, 'bm_id');
        if(empty($itemList)){
            $error_msg = '福袋组合没有关联的基础物料';
            return false;
        }
        $newBmIds = $bmIds;
        
        //material
        $materialList = $basicMaterialObj->getList('bm_id,material_bn', array('bm_id'=>$bmIds));
        $materialList = array_column($materialList, null, 'bm_id');
        if(empty($materialList)){
            $error_msg = '没有添加基础物料';
            return false;
        }
        
        //items
        $combineItems = $combineItemMdl->getList('combine_id,bm_id,ratio', array('combine_id'=>$data['combine_id']));
        $combineItems = array_column($combineItems, null, 'bm_id');
        $combineInfo['items'] = $combineItems;
        $oldBmIds = array_keys($combineItems);
        
        //比较差异(防止编辑删除了基础物料)
        $is_delete_material = false;
        if($oldBmIds && $newBmIds){
            $deleteBmIds = array_diff($oldBmIds, $newBmIds);
            if($deleteBmIds){
                //删除掉本次舍弃的基础物料
                $combineItemMdl->delete(array('combine_id'=>$data['combine_id'], 'bm_id'=>$deleteBmIds));
                
                $is_delete_material = true;
            }
        }
        
        //items
        $line_i = 0;
        $is_new_material = false;
        foreach ($itemList as $bm_id => $itemVal)
        {
            $line_i++;
            
            $is_update_flag = false;
            
            if(!isset($materialList[$bm_id])){
                $error_msg = '第'. $line_i .'基础物料信息不存在';
                return false;
            }
            
            if(empty($materialList[$bm_id])){
                $error_msg = '第'. $line_i .'基础物料信息为空';
                return false;
            }
            
            //check
            if(isset($combineItems[$bm_id])){
                $is_update_flag = true;
            }
            
            //item sdf
            $itemSdf = array(
                'combine_id' => $data['combine_id'],
                'bm_id' => $itemVal['bm_id'],
                'retail_price' => $itemVal['retail_price'],
                'ratio' => $itemVal['ratio'],
                'real_ratio' => $itemVal['real_ratio'],
                'last_modified' => time(),
            );
            
            //add or update
            if($is_update_flag){
                //unset
                unset($itemSdf['combine_id'], $itemSdf['bm_id']);
                
                //update
                $combineItemMdl->update($itemSdf, array('combine_id'=>$data['combine_id'], 'bm_id'=>$bm_id));
            }else{
                $itemSdf['create_time'] = time();
                
                //insert
                $combineItemMdl->insert($itemSdf);
                
                //新插入基础物料的标识
                $is_new_material = true;
            }
        }
        
        //log msg
        if($data['action'] == 'update'){
            //快照日志
            $log_memo = serialize($combineInfo);
            $operationLogObj->write_log('fukubukuro_combine_edit@wms', $data['combine_id'], $log_memo);
            
            //重新保存销售物料关联的福袋组合规中的基础物料
            //@todo：编辑福袋组合成功,如果关联的基础物料发生变化；需要先删除销售物料与基础物料的关联关系，重新保存销售物料与基础物料的关联关系;
            if($is_new_material || $is_delete_material || $is_edit_nums){
                $isResave = $this->resaveSalesMaterialFukubukuro($data['combine_id'], $error_msg);
                if($isResave){
                    $operationLogObj->write_log('fukubukuro_combine_modify@wms', $data['combine_id'], '重新保存销售物料与基础物料关联关系');
                }
            }
        }else{
            $log_msg = '添加成功';
            $operationLogObj->write_log('fukubukuro_combine_add@wms', $data['combine_id'], $log_msg);
        }
        
        return true;
    }
    
    /**
     * 获取福袋组合关联基础物料
     * 
     * @param $combine_id
     * @param $error_msg
     * @return array
     */
    public function formatCombineItems($combine_id, &$error_msg=null)
    {
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        $basicMaterialObj = app::get('material')->model('basic_material');
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        
        //items
        $itemList = $combineItemMdl->getList('*', array('combine_id'=>$combine_id));
        if(empty($itemList)){
            $error_msg = '没有明细列表数据';
            return array();
        }
        
        //material
        $bmIds = array_column($itemList, 'bm_id');
        $materialList = $basicMaterialObj->getList('bm_id,material_bn,material_name', array('bm_id'=>$bmIds));
        $materialList = array_column($materialList, null, 'bm_id');
        
        //extend
        $extendList = $basicMaterialExtObj->getList('*', array('bm_id'=>$bmIds));
        $extendList = array_column($extendList, null, 'bm_id');
        
        //format
        foreach ($itemList as $itemKey => $itemVal)
        {
            $bm_id = $itemVal['bm_id'];
            
            $itemVal['material_bn'] = $materialList[$bm_id]['material_bn'];
            $itemVal['material_name'] = $materialList[$bm_id]['material_name'];
            
            $itemVal['cost'] = $extendList[$bm_id]['cost'];
            $itemVal['retail_price'] = $extendList[$bm_id]['retail_price'];
            $itemVal['specifications'] = $extendList[$bm_id]['specifications'];
            $itemVal['unit'] = $extendList[$bm_id]['unit'];
            
            //选中比例(%)
            if($itemVal['ratio'] == -1){
                $itemVal['ratio_str'] = '随机';
            }else{
                $itemVal['ratio_str'] = $itemVal['ratio'].'%';
            }
            
            $itemList[$itemKey] = $itemVal;
        }
        
        return $itemList;
    }
    
    /**
     * 批量通过销售物料列表smIds获取福袋组合中基础物料的贡献比rate
     * 
     * @param $smIds 订单object层中的sm_id
     * @param $luckyRuleList 订单items层基础物料关联的福袋组合规则
     * @param $error_msg
     * @return array
     */
    public function getFudaiRateBySmids($smIds, $luckyRuleList, &$error_msg=null)
    {
        $smRateList = array();
        foreach ($smIds as $smKey => $sm_id)
        {
            $luckyRules = $luckyRuleList[$sm_id];
            $bmRatioList = $this->getMaterialRateBySmid($sm_id, $luckyRules, $error_msg);
            if(empty($bmRatioList)){
                continue;
            }
            
            $smRateList[$sm_id] = $bmRatioList;
        }
        
        return $smRateList;
    }
    
    /**
     * 通过销售物料sm_id获取福袋组合中基础物料的贡献比rate
     * 
     * @param $sm_id 订单object层中的sm_id
     * @param $luckyRules 订单items层基础物料关联的福袋组合规则
     * @param $error_msg
     * @return array
     */
    public function getMaterialRateBySmid($sm_id, $luckyRules, &$error_msg=null)
    {
        $saleFukuMdl = app::get('material')->model('sales_material_fukubukuro');
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        
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
        $combineItems = $combineItemMdl->getList('item_id,combine_id,bm_id,ratio,real_ratio', $filter, 0, -1, 'real_ratio DESC');
        if(empty($combineItems)){
            $error_msg = '销售物料关联的福袋组合规则为空';
            return false;
        }
        
        //bm_id
        $bmIds = array_column($combineItems, 'bm_id');
        $bmIds = array_unique($bmIds);
        
        //extend
        $extendList = $basicMaterialExtObj->getList('bm_id,cost,retail_price', array('bm_id'=>$bmIds));
        $extendList = array_column($extendList, null, 'bm_id');
        
        //format
        foreach ($combineItems as $itemKey => $itemVal)
        {
            $combine_id = $itemVal['combine_id'];
            $item_id = $itemVal['item_id'];
            $bm_id = $itemVal['bm_id'];
            
            //基础物料零售价
            $itemVal['retail_price'] = 0;
            if(isset($extendList[$bm_id])){
                $itemVal['retail_price'] = $extendList[$bm_id]['retail_price'];
            }
            
            //items
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
            return false;
        }
        
        //[福袋组合列表]按销售价贡献占比降序排序
        usort($combineList, array($this, 'compare_by_name'));
        
        //分配基础物料
        $smRates = array();
        $existBmIds = array();
        $sumPriceList = array();
        foreach ($combineList as $combinKey =>$combineVal)
        {
            $combine_id = $combineVal['combine_id'];
            
            //包含基础物料件数
            $include_number = $combineVal['include_number'];
            
            //使用的福袋规则
            $productLuckyRules = $luckyRules[$combine_id];
            
            //check
            if(empty($combineVal['items'])){
                continue;
            }
            
            foreach ($combineVal['items'] as $itemKey => $itemVal)
            {
                $bm_id = $itemVal['bm_id'];
                
                //基础物料零售价
                $itemVal['retail_price'] = ($itemVal['retail_price'] ? $itemVal['retail_price'] : 0);
                
                //check订单上分配的销售物料基础物料
                if(empty($productLuckyRules[$bm_id])){
                    continue;
                }
                
                //bm_id
                $existBmIds[$bm_id] = $bm_id;
                
                //基础物料分配数量
                $lucky_quantity = intval($productLuckyRules[$bm_id]['quantity']);
                
                //计算分配倍数
                //@todo：福袋分配基础物料时,是按照销售物料购买数量循环进行分配;
                $multiple = $lucky_quantity / $include_number;
                if($multiple < 1){
                    $multiple = 1;
                }
                
                //总零售价 = 基础物料零售价 * 倍数
                $total_price = $itemVal['retail_price'] * $multiple;
                
                //rate
                if(!isset($smRates[$combine_id])){
                    //销售价贡献占比
                    $smRates[$combine_id]['rate'] = $combineVal['rate'];
                    
                    //基础物料零售价&&购买倍数
                    $smRates[$combine_id]['bmList'][$bm_id] = $total_price;
                    
                    //福袋纬度的总金额
                    $sumPriceList[$combine_id]['total_price'] = $total_price;
                }else{
                    //累加倍数
                    $smRates[$combine_id]['bmList'][$bm_id] += $total_price;
                    
                    //sum price
                    $sumPriceList[$combine_id]['total_price'] += $total_price;
                }
            }
        }
        
        //check
        if(empty($smRates)){
            $error_msg = '没有福袋组合分配的比例';
            return false;
        }
        
        //[兼容]福袋内选择的基础物料总零售价为0的场景
        foreach ($sumPriceList as $combine_id => $total_price)
        {
            if($total_price['total_price'] > 0){
                continue;
            }
            
            $smInfo = $smRates[$combine_id];
            
            //count
            $bmCount = count($smInfo['bmList']);
            
            //avg
            $total = 100;
            $less_num = $total;
            $bm_line = 0;
            foreach ($smInfo['bmList'] as $bm_id => $retail_price)
            {
                $bm_line++;
                
                if($bm_line == $bmCount){
                    $smRates[$combine_id]['bmList'][$bm_id] = $less_num;
                }else{
                    $bm_avg = bcdiv($total, $bmCount, 2) ; //保留两位小数
                    
                    //高精度--减法
                    $less_num = bcsub($less_num, $bm_avg, 2); //保留两位小数
                    
                    $smRates[$combine_id]['bmList'][$bm_id] = $bm_avg;
                }
            }
        }
        
        //按基础物料分配占比
        $bmRatioList = array();
        foreach ($smRates as $combine_id => $rateVal)
        {
            $rate = $rateVal['rate'];
            $bmCount = count($rateVal['bmList']);
            
            //check
            if(empty($rate) || empty($bmCount)){
                continue;
            }
            
            //所有基础物料零售价之和
            $bmTotalRatio = array_sum($rateVal['bmList']);
            if(empty($bmTotalRatio)){
                continue;
            }
            
            //ratio
            $less_rate = $rate;
            $bm_line = 0;
            foreach ($rateVal['bmList'] as $bm_id => $real_ratio)
            {
                $bm_line++;
                
                if($bm_line == $bmCount){
                    $bmRatioList[$combine_id][$bm_id] = $less_rate;
                }else{
                    $bm_ratio = bcdiv($real_ratio, $bmTotalRatio, 4) * $rate; //保留四位小数
                    $bm_ratio = bcmul($bm_ratio, 1, 4); //保留四位小数
                    
                    //高精度减法(保留小数位必需与上面保持一致)
                    $less_rate = bcsub($less_rate, $bm_ratio, 4); //保留四位小数
                    
                    $bmRatioList[$combine_id][$bm_id] = $bm_ratio;
                }
            }
        }
        
        //check
        if(empty($bmRatioList)){
            $error_msg = '没有基础物料的价格分摊比例';
            return false;
        }
        
        //count
        $bmCount = 0;
        $check_total_rate = 0;
        foreach ($bmRatioList as $combine_id => $ratioItems)
        {
            foreach ($ratioItems as $bm_id => $bm_ratio)
            {
                $bmCount++;
                
                $check_total_rate += $bm_ratio;
            }
        }
        
        //重新格式化基础物料占比
        //@todo：所有之和不等于100时,把剩余的统一放到最后一个基础物料上;
        if($check_total_rate != 100){
            $less_rate = 100;
            $bm_line = 0;
            foreach ($bmRatioList as $combine_id => $ratioItems)
            {
                foreach ($ratioItems as $bm_id => $bm_ratio)
                {
                    $bm_line++;
                    
                    if($bm_line == $bmCount){
                        $bmRatioList[$combine_id][$bm_id] = $less_rate;
                    }else{
                        //高精度--减法
                        $less_rate = bcsub($less_rate, $bm_ratio, 4); //保留四位小数
                    }
                }
            }
        }
        
        return $bmRatioList;
    }
    
    /**
     * 重新保存销售物料关联的福袋组合规中的基础物料
     * 
     * @param $combine_id 福袋组合ID
     * @param $error_msg
     * @return bool
     */
    public function resaveSalesMaterialFukubukuro($combine_id, &$error_msg=null)
    {
        $saleFukuMdl = app::get('material')->model('sales_material_fukubukuro');
        $queueMdl = app::get('base')->model('queue');
        
        //销售物料与福袋组合关联列表
        $filter = array('combine_id'=>$combine_id);
        $tempList = $saleFukuMdl->getList('fd_id,sm_id,combine_id', $filter, 0, -1);
        if(empty($tempList)){
            $error_msg = '没有获取到销售关联的福袋组合';
            return false;
        }
        
        //exec
        foreach ($tempList as $tempKey => $tempVal)
        {
            $sm_id = $tempVal['sm_id'];
            
            //放入queue队列中执行
            $queueData = array(
                'queue_title' => '福袋销售物料ID：'. $sm_id .'重新绑定基础物料',
                'start_time' => time(),
                'params' => array(
                    'sdfdata' => array('sm_id'=>$sm_id),
                    'app' => 'material',
                    'mdl' => 'sales_basic_material',
                ),
                'worker' => 'material_luckybag.resaveLuckySalesBmids',
            );
            $queueMdl->save($queueData);
        }
        
        return true;
    }
    
    /**
     * 通过销售物料信息获取关联的福袋组合中基础物料列表
     * 
     * @param $smIds 销售物料ID列表
     * @return array
     */
    public function getLuckyMaterialBySmid($smIds, &$error_msg=null)
    {
        $saleFukuMdl = app::get('material')->model('sales_material_fukubukuro');
        $combineMdl = app::get('material')->model('fukubukuro_combine');
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        $basicMaterialObj = app::get('material')->model('basic_material');
        
        //销售物料与福袋组合关联列表(按销售价贡献占比降序)
        $filter = array('sm_id'=>$smIds);
        $luckyItems = $saleFukuMdl->getList('fd_id,sm_id,combine_id,rate', $filter, 0, -1, 'rate DESC');
        if(empty($luckyItems)){
            $error_msg = '没有获取到销售关联的福袋组合';
            return false;
        }
        $combineIds = array_column($luckyItems, 'combine_id');
        $combineIds = array_unique($combineIds);
        
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
        $tempList = $combineItemMdl->getList('item_id,combine_id,bm_id,real_ratio', $filter, 0, -1, 'real_ratio DESC');
        if(empty($tempList)){
            $error_msg = '销售物料关联的福袋组合规则为空';
            return false;
        }
        $bmIds = array_column($tempList, 'bm_id');
        
        //基础物料列表
        $materialList = $basicMaterialObj->getList('bm_id,material_bn', array('bm_id'=>$bmIds));
        if(empty($materialList)){
            $error_msg = '关联基础物料不存在!';
            return false;
        }
        $materialList = array_column($materialList, null, 'bm_id');
        
        //format
        $combineItems = array();
        foreach ($tempList as $itemKey => $itemVal)
        {
            $combine_id = $itemVal['combine_id'];
            $bm_id = $itemVal['bm_id'];
            
            //基础物料信息
            $bmInfo = $materialList[$bm_id];
            
            //items
            $itemVal['material_bn'] = $bmInfo['material_bn'];
            $combineItems[$combine_id][$bm_id] = $itemVal;
        }
        
        foreach ($combineList as $combine_id =>$combineVal)
        {
            //check items empty
            if(empty($combineItems[$combine_id])){
                //unset
                unset($combineList[$combine_id]);
            
                continue;
            }
            
            //关联的基础物料列表
            $combineVal['items'] = $combineItems[$combine_id];
            
            //merge
            $combineList[$combine_id] = $combineVal;
        }
        
        //format
        $luckyList = array();
        foreach ($luckyItems as $itemKey => $itemInfo)
        {
            $sm_id = $itemInfo['sm_id'];
            $combine_id = $itemInfo['combine_id'];
            
            //福袋组合信息
            $combineInfo = $combineList[$combine_id];
            
            //销售价贡献占比
            $combineInfo['rate'] = ($itemInfo['rate'] ? $itemInfo['rate'] : 0);
            
            $luckyList[$sm_id][$combine_id] = $combineInfo;
        }
        
        //unset
        unset($luckyItems, $combineIds, $tempList, $bmIds, $materialList, $combineItems, $combineList);
        
        return $luckyList;
    }
    
    /**
     * 批量获取福袋组合关联基础物料
     * 
     * @param $combine_id
     * @param $error_msg
     * @return array
     */
    public function batchGetCombineItems($combineIds, &$error_msg=null)
    {
        $combineItemMdl = app::get('material')->model('fukubukuro_combine_items');
        $basicMaterialObj = app::get('material')->model('basic_material');
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        
        //items
        $itemList = $combineItemMdl->getList('*', array('combine_id'=>$combineIds));
        if(empty($itemList)){
            $error_msg = '没有明细列表数据';
            return array();
        }
        
        //material
        $bmIds = array_column($itemList, 'bm_id');
        $materialList = $basicMaterialObj->getList('bm_id,material_bn,material_name', array('bm_id'=>$bmIds));
        $materialList = array_column($materialList, null, 'bm_id');
        
        //extend
        $extendList = $basicMaterialExtObj->getList('bm_id,cost,retail_price', array('bm_id'=>$bmIds));
        $extendList = array_column($extendList, null, 'bm_id');
        
        //format
        $luckyItems = array();
        foreach ($itemList as $itemKey => $itemVal)
        {
            $combine_id = $itemVal['combine_id'];
            $item_id = $itemVal['item_id'];
            $bm_id = $itemVal['bm_id'];
            
            //material
            $itemVal['material_bn'] = $materialList[$bm_id]['material_bn'];
            $itemVal['material_name'] = $materialList[$bm_id]['material_name'];
            $itemVal['cost'] = $extendList[$bm_id]['cost'];
            $itemVal['retail_price'] = $extendList[$bm_id]['retail_price'];
            
            //选中比例(%)
            if($itemVal['ratio'] == -1){
                $itemVal['ratio_str'] = '随机';
            }else{
                $itemVal['ratio_str'] = $itemVal['ratio'].'%';
            }
            
            $luckyItems[$combine_id][$item_id] = $itemVal;
        }
        
        //unset
        unset($itemList, $materialList, $extendList);
        
        return $luckyItems;
    }
}