<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 福袋组合导入Lib类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.12.18
 */
class material_fukubukuro_combine_import
{
    /**
     * Obj对象
     */
    protected $_combineMdl = null;
    protected $_basicMaterialObj = null;
    protected $_basicMaterialExtObj = null;
    
    /**
     * Lib对象
     */
    protected $_combineLib = null;
    
    /**
     * 导入标题列
     */
    const IMPORT_TITLE = [
        '*:福袋组合编码',
        '*:福袋组合名称',
        '*:选中物料个数',
        '*:包含物料件数',
        '*:基础物料编码',
        '*:选中比例(允许填写：随机)',
    ];
    
    private $importColumn = array(
        '*:福袋组合编码' => 'combine_bn',
        '*:福袋组合名称' => 'combine_name',
        '*:选中物料个数' => 'selected_number',
        '*:包含物料件数' => 'include_number',
        '*:基础物料编码' => 'material_bn',
        '*:选中比例(允许填写：随机)' => 'ratio',
    );
    
    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct()
    {
        //Obj
        $this->_combineMdl = app::get('material')->model('fukubukuro_combine');
        $this->_basicMaterialObj = app::get('material')->model('basic_material');
        $this->_basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        
        //lib
        $this->_combineLib = kernel::single('material_fukubukuro_combine');
    }
    
    /**
     * 获取ExcelTitle
     * @return mixed 返回结果
     */
    public function getExcelTitle()
    {
        $filename = '福袋组合导入模板-' . date('Ymd') .'.xlsx';
        
        //模板案例一
        $data = [];
        $data[0] = array('lucky001', '样例1：福袋组合一', '1', '3', 'material_001', '随机');
        $data[1] = array('lucky001', '样例1：福袋组合一', '1', '3', 'material_002', '随机');
        
        //模板案例二
        $data[2] = array('lucky002', '样例2：福袋组合二', '2', '1', 'material_003', '45');
        $data[3] = array('lucky002', '样例2：福袋组合二', '2', '1', 'material_004', '55');
        
        return [$filename, [self::IMPORT_TITLE, $data[0], $data[1], $data[2], $data[3]]];
    }
    
    /**
     * 导入福袋组合数据处理
     * 
     * @param $import_file
     * @param $post
     * @return mixed
     */
    public function processExcelRow($import_file, $post)
    {
        $format = [];
        
        // 读取文件
        return kernel::single('omecsv_phpoffice')->import($import_file, function ($line, $buffer, $post, $highestRow)
        {
            static $title, $salesMaterial;
            
            //第一行：标题行
            if ($line == 1) {
                $title = $buffer;
                // 验证模板是否正确
                if (array_filter($title) != self::IMPORT_TITLE) {
                    return [false, '导入模板标题不正确', 'lastrow'];
                }
                return [true];
            }else{
                //第二行：导入的数据行
                if(empty($buffer[0])){
                    return [false, '福袋组合编码不能为空', 'lastrow'];
                }
            }
            
            if(count($buffer) < count(self::IMPORT_TITLE)) {
                return [true];
            }
            
            //读取行数据
            $buffer = array_combine(self::IMPORT_TITLE, array_slice($buffer, 0, count(self::IMPORT_TITLE)));
            
            //check
            $msg = '';
            if($buffer['*:福袋组合编码']) {
                //此行[福袋组合编码]与上一行相等时,自动累加数据
                if($salesMaterial['*:福袋组合编码'] == $buffer['*:福袋组合编码']) {
                    $salesMaterial['items'][] = $buffer;
                } else {
                    //此行[福袋组合编码]与上一行不相等时,进行保存数据
                    if($salesMaterial) {
                        list($rs, $rsData) = $this->_dealSalesMaterial($salesMaterial);
                        if(!$rs) {
                            //$msg .= $rsData['msg'];
                            return [false, $rsData['msg'], 'lastrow'];
                        }
                    }
                    
                    //此行[福袋组合编码]与上一行不相等时,覆盖新数据
                    $salesMaterial = $buffer;
                    $salesMaterial['items'][] = $buffer;
                }
            } else {
                if($salesMaterial) {
                    $salesMaterial['items'][] = $buffer;
                }
            }
            
            //最后一行数据
            if($line == $highestRow && $salesMaterial) {
                list($rs, $rsData) = $this->_dealSalesMaterial($salesMaterial);
                if(!$rs) {
                    //$msg .= $rsData['msg'];
                    return [false, $rsData['msg'], 'lastrow'];
                }
                $salesMaterial = [];
            }
            
            //显示报错信息
            //if($msg){
            //    return [false, $msg, 'lastrow'];
            //}
            
            return [true, $msg];
        }, [], $format);
    }
    
    /**
     * 检测有效内容
     * 
     * @param $salesMaterial
     * @return array
     */
    public function _dealSalesMaterial($salesMaterial)
    {
        //内容不能为空的字段列表
        $required = [
            '*:福袋组合编码',
            '*:福袋组合名称',
            '*:选中物料个数',
            '*:包含物料件数',
            '*:基础物料编码',
            '*:选中比例(允许填写：随机)',
        ];
        
        //导入的福袋编码
        $temp_combine_bn = $salesMaterial['*:福袋组合编码'];
        
        //预先检查数据
        foreach ($required as $v)
        {
            if(empty($salesMaterial[$v])) {
                $error_msg = '福袋编码：'. $temp_combine_bn .' '. $v .' 不能为空';
                
                return [false, ['msg'=>$error_msg]];
            }
            
            //去除空格
            $salesMaterial[$v] = trim($salesMaterial[$v]);
        }
        
        //items
        $luckyList = array();
        $existMaterialBns = array();
        foreach ($salesMaterial['items'] as $itemKey => $itemInfo)
        {
            foreach ($itemInfo as $titleKey => $titleVal)
            {
                $field_name = $this->importColumn[$titleKey];
                if(empty($field_name)){
                    continue;
                }
                
                //int
                if(in_array($field_name, array('selected_number', 'include_number'))){
                    $titleVal = intval($titleVal);
                }elseif($field_name == 'material_bn'){
                    //福袋组合内不允许重复的基础物料
                    if(isset($existMaterialBns[$titleVal])){
                        $error_msg = '福袋编码：'. $luckyList[$itemKey]['combine_bn'] .',基础物料编码：'. $titleVal .'不能重复';
                        return [false, ['msg'=>$error_msg]];
                    }else{
                        $existMaterialBns[$titleVal] = $titleVal;
                    }
                }elseif($field_name == 'ratio'){
                    if($titleVal == '随机'){
                        $titleVal = -1;
                    }else{
                        if(!preg_match("/^[1-9][0-9]*$/", $titleVal)){
                            $error_msg = '福袋编码：'. $luckyList[$itemKey]['combine_bn'] .'选中比例(%)只能填写正整数';
                            return [false, ['msg'=>$error_msg]];
                        }
                        
                        //只能填写正整数
                        $titleVal = intval($titleVal);
                    }
                }
                
                $luckyList[$itemKey][$field_name] = $titleVal;
            }
        }
        
        //check
        if(empty($luckyList)){
            $error_msg = '福袋编码：'. $temp_combine_bn ."没有有效的导入数据";
            return [false, ['msg'=> $error_msg]];
        }
        
        //format
        $sdf = array();
        foreach ($luckyList as $itemKey => $items)
        {
            //master
            if(empty($sdf)){
                $sdf = $items;
                
                //unset
                unset($sdf['material_bn'], $sdf['ratio']);
            }
            
            //items
            $itemInfo = array(
                'material_bn' => $items['material_bn'],
                'ratio' => $items['ratio'],
            );
            
            $sdf['items'][] = $itemInfo;
        }
        
        //items
        $itemList = $sdf['items'];
        unset($sdf['items']);
        
        //material_bn
        $materialBns = array_column($itemList, 'material_bn');
        
        $materialList = $this->_basicMaterialObj->getList('bm_id,material_bn', array('material_bn'=>$materialBns));
        $materialList = array_column($materialList, null, 'material_bn');
        if(empty($materialList)){
            $error_msg = '导入基础物料编码：'. implode('、', $materialBns) .'不存在!';
            return [false, ['msg'=>$error_msg]];
        }
        
        //extend
        $bmIds = array_column($materialList, 'bm_id');
        $extendList = $this->_basicMaterialExtObj->getList('bm_id,cost,retail_price', array('bm_id'=>$bmIds));
        $extendList = array_column($extendList, null, 'bm_id');
        if(empty($extendList)){
            $error_msg = '导入基础物料编码：'. implode('、', $materialBns) .'扩展信息不存在!';
            return [false, ['msg'=>$error_msg]];
        }
        
        //format items
        $rates = array();
        $materialItems = array();
        foreach ($itemList as $itemKey => $itemVal)
        {
            $material_bn = $itemVal['material_bn'];
            
            //check
            if(empty($itemVal['ratio'])){
                $error_msg = '基础物料编码：'. $material_bn .'选中比例不能为空!';
                return [false, ['msg'=>$error_msg]];
            }
            
            //check
            if(!isset($materialList[$material_bn])){
                $error_msg = '基础物料编码：'. $material_bn .'未找到!';
                return [false, ['msg'=>$error_msg]];
            }
            
            //基础物料信息
            $materialInfo = $materialList[$material_bn];
            $bm_id = $materialInfo['bm_id'];
            
            //check
            if(empty($bm_id)){
                $error_msg = '基础物料编码：'. $material_bn .'没有查找到!';
                return [false, ['msg'=>$error_msg]];
            }
            
            //基础物料扩展信息
            $extendInfo = $extendList[$bm_id];
            
            //rates
            $rates[$bm_id] = $itemVal['ratio'];
            
            //material
            $materialItems[$bm_id] = array (
                'bm_id' => $bm_id,
                'retail_price' => ($extendInfo['retail_price'] ? $extendInfo['retail_price'] : 0), //零售价
                'ratio' => $itemVal['ratio'],
            );
        }
        
        //merge
        $sdf['rates'] = $rates;
        $sdf['items'] = $materialItems;
        
        //check
        $error_msg = '';
        $isCheck = $this->_combineLib->checkParams($sdf, $error_msg);
        if(!$isCheck){
            $error_msg = '福袋编码：'. $sdf['combine_bn'] .'验证失败：'. $error_msg;
            return [false, ['msg'=>$error_msg]];
        }
        
        //不允许导入更新
        if(isset($sdf['combine_id']) && $sdf['combine_id'] > 0){
            //$error_msg = sprintf('%s福袋组合已经存在，不允许重新导入', $sdf['combine_bn']);
            //return [false, ['msg'=>$error_msg]];
            
            $error_msg = sprintf('%s福袋组合已经存在，跳过!', $sdf['combine_bn']);
            return [true, ['msg'=>$error_msg]];
        }
        
        //格式化数据
        $isFormat = $this->_combineLib->formatData($sdf, $error_msg);
        if(!$isFormat) {
            $error_msg = '福袋编码：'. $sdf['combine_bn'] .'数据格式化失败：'. $error_msg;
            return [false, ['msg'=>$error_msg]];
        }
        
        //保存数据
        $isSave = $this->_combineLib->saveData($sdf, $error_msg);
        if (!$isSave) {
            $error_msg = '福袋编码：'. $sdf['combine_bn'] .'保存失败：'. $error_msg;
            return [false, ['msg'=>$error_msg]];
        }
        
        //save
        return [true, ['msg'=>'新增成功']];
    }
}
