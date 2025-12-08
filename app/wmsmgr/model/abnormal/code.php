<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * WMS仓储异常错误码
 * 
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class wmsmgr_mdl_abnormal_code extends dbeav_model
{
    //导入的数据集合
    var $import_data = array();
    
    //导出的文件名
    var $export_name = 'WMS异常错误码';
    
    /**
     * 导入导出的标题
     */
    function io_title($ioType='csv')
    {
        $this->oSchema['csv'] = array(
            '*:单据类型' => 'abnormal_type',
            '*:错误码' => 'abnormal_code',
            '*:错误标题' => 'abnormal_name',
        );
        
        return $this->oSchema['csv'];
    }
    
    /**
     * 导入模板的标题
     *
     * @param Null
     * @return Array
     */
    function exportTemplate()
    {
        $titleList = $this->io_title();
        $titleList = array_keys($titleList);
        
        foreach ($titleList as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        
        return $title;
    }
    
    /**
     * 第一步：准备导入的参数定义
     *
     * @param Null
     * @return Null
     */
    function prepared_import_csv()
    {
        set_time_limit(0);
        
        $this->ioObj->cacheTime = time();
    }
    
    /**
     * 第二步：准备导入的数据明细内容部分检查和处理
     *
     * @param Array $row
     * @param String $title
     * @param String $tmpl
     * @param Boolean $mark
     * @param Boolean $newObjFlag
     * @param String $msg
     * @return Null
     */
    function prepared_import_csv_row($row, $title, &$tmpl, &$mark, &$newObjFlag, &$msg)
    {
        $mark = false;
        if (empty($row)){
            return true;
        }
        
        //标题
        if(substr($row[0], 0, 1) == '*'){
            $titleRs = array_flip($row);
            $mark = 'title';
            
            //防止重复
            $this->code_list = array();
            $this->import_num = 1;
            
            return $titleRs;
        }else{
            //check
            if(isset($this->import_num)){
                $this->import_num ++;
                if($this->import_num > 1000){
                    $msg['error'] = "导入的数量量过大,一次最多可导入1000行!";
                    return false;
                }
            }
            
            //format
            $row[0] = trim($row[0]);
            $row[1] = trim($row[1]);
            
            //check内容检查
            if(empty($row[0])){
                $msg['error'] = '第 '. $this->import_num .' 行,单据类型必须填写';
                return false;
            }
            
            if(empty($row[1])){
                $msg['error'] = '第 '. $this->import_num .' 行,错误码必须填写';
                return false;
            }
            
            if(empty($row[2])){
                $msg['error'] = '第 '. $this->import_num .' 行,错误标题必须填写';
                return false;
            }
            
            //判断重复
            if(in_array($row[1], $this->code_list)){
                $msg['error'] = '第 '.$this->import_num.' 行,错误码【'. $row[1] .'】重复!';
                return false;
            }
            $this->code_list[] = $row[1];
            
            //单据类型
            $type_code = $this->getAbnormalTypeCode($row[0]);
            if(empty($type_code)){
                $msg['error'] = "单据类型不存在：".$row[0];
                return false;
            }
            
            //判断是否存在
            $tempInfo = $this->dump(array('abnormal_type'=>$type_code, 'abnormal_code'=>$row[1]));
            if($tempInfo){
                $msg['error'] = "已存在此错误码：".$row[1];
                return false;
            }
            
            //组织数据
            $titleList = $this->io_title();
            $titleList = array_flip($titleList);
            
            $key_i = 0;
            $sdf = array();
            foreach ($titleList as $tKey => $tVal)
            {
                if($tKey == 'abnormal_type'){
                    $row[$key_i] = $type_code;
                }
                
                $sdf[$tKey] = $row[$key_i];
                
                $key_i++;
            }
            
            //导入时间节点
            $sdf['import_time'] = $this->ioObj->cacheTime;
            
            $this->import_data[] = $sdf;
            
            //销毁
            unset($row, $sdf);
            
            $newObjFlag = true;
        }
        
        return null;
    }
    
    /**
     * 第三步：检查准备导入的数据主体内容
     *
     * @param Array $data
     * @param Boolean $mark
     * @param String $tmpl
     * @param String $msg
     * @return Null
     */
    function prepared_import_csv_obj($data, $mark, $tmpl, &$msg = '')
    {
        return null;
    }
    
    /**
     * 第四步：最终处理导入
     * @todo：导入数据比较小,没有走queue队列
     *
     * @return NULL
     */
    function finish_import_csv()
    {
        if(empty($this->import_data)){
            $msg['error'] = '没有可执行的导入数据';
            return false;
        }
        
        //save
        foreach ($this->import_data as $key => $item)
        {
            $saveData = array(
                    'abnormal_code' => $item['abnormal_code'],
                    'abnormal_name' => $item['abnormal_name'],
                    'abnormal_type' => $item['abnormal_type'],
                    'create_time' => time(),
                    'last_modified' => time(),
            );
            
            $this->save($saveData);
        }
        
        unset($this->import_data);
        
        return null;
    }
    
    /**
     * 获取异常单据类型
     * 
     * @param string $abnormal_type_name
     * @return boolean
     */
    public function getAbnormalTypeCode($abnormal_type_name)
    {
        //单据类型
        $schema = $this->get_schema();
        $abnormal_types = $schema['columns']['abnormal_type']['type'];
        if(empty($abnormal_types)){
            return false;
        }
        
        foreach ($abnormal_types as $key => $val){
            if($val == $abnormal_type_name){
                return $key;
            }
        }
        
        return false;
    }
    
    /**
     * 定义导出的文件名
     */
    public function exportName(&$filename,$filter='')
    {
        $filename['name'] = $this->export_name;
        
        return $this->export_name;
    }
}