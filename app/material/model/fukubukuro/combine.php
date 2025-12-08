<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 福袋组合model类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.09.10
 */
class material_mdl_fukubukuro_combine extends dbeav_model
{
    //是否有导出配置
    var $has_export_cnf = true;
    
    //导出的文件名
    var $export_name = '福袋组合';
    
    /**
     * 销售物料的过滤方法
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        //支持多个福袋组合编码搜索
        if($filter['combine_bn'] && is_string($filter['combine_bn']) && strpos($filter['combine_bn'], "\n") !== false){
            $filter['combine_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['combine_bn']))));
        }
        
        return parent::_filter($filter,$tableAlias,$baseWhere);
    }
    
    //导出字段配置 移除不需要的字段
    public function disabled_export_cols(&$cols)
    {
        unset($cols['column_edit']);
    }
    
    /**
     * 自定义导出数据
     *
     * @param $fields
     * @param $filter
     * @param $has_detail
     * @param $curr_sheet
     * @param $start
     * @param $end
     * @param $op_id
     * @return array|false
     */
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id)
    {
        $combineLib = kernel::single('material_fukubukuro_combine');
        
        //[标题行]根据选择的字段定义导出的第一行标题
        if ($curr_sheet == 1) {
            $data['content']['main'][] = $this->getExportTitle($fields).mb_convert_encoding(',基础物料编码,基础物料名称,基础物料零售价,选中比例', 'GBK', 'UTF-8');;
        }
        
        //list
        $dataList = $this->getList('*', $filter);
        if (empty($dataList)) {
            return false;
        }
        
        //combine_id
        $combineIds = array_column($dataList, 'combine_id');
        
        //items
        $error_msg = '';
        $luckyItems = $combineLib->batchGetCombineItems($combineIds, $error_msg);
        
        //schema
        $mainColumns = $this->get_schema();
        
        //foramt
        foreach ($dataList as $dataKey => $dataVal)
        {
            $combine_id = $dataVal['combine_id'];
            
            //date
            $dataVal['create_time'] = $dataVal['create_time'] ? date('Y-m-d H:i:s', $dataVal['create_time']) : '';
            $dataVal['last_modified'] = $dataVal['last_modified'] ? date('Y-m-d H:i:s', $dataVal['last_modified']) : '';
            
            //fields
            $rowInfo = array();
            foreach (explode(',', $fields) as $key => $col)
            {
                if(!isset($mainColumns['columns'][$col])){
                    continue;
                }
                
                if (isset($dataVal[$col])) {
                    $rowInfo[] = mb_convert_encoding($dataVal[$col], 'GBK', 'UTF-8');
                } else {
                    $rowInfo[] = '';
                }
            }
            
            //关联基础物料列表
            $itemList = $luckyItems[$combine_id];
            if(empty($itemList)){
                //data
                $data['content']['main'][] = implode(',', $rowInfo);
                continue;
            }
            
            //按照基础物料纬度导出数据
            foreach ($itemList as $itemKey => $itemVal)
            {
                $itemInfo = $rowInfo;
                
                //material
                $itemInfo[] = $itemVal['material_bn']; //基础物料编码
                $itemInfo[] = mb_convert_encoding($itemVal['material_name'], 'GBK', 'UTF-8'); //基础物料名称
                $itemInfo[] = $itemVal['retail_price']; //基础物料零售价
                $itemInfo[] = mb_convert_encoding($itemVal['ratio_str'], 'GBK', 'UTF-8'); //选中比例
                
                //data
                $data['content']['main'][] = implode(',', $itemInfo);
            }
        }
        
        return $data;
    }
}