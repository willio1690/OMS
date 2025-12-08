<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

require_once ROOT_DIR . '/vendor/autoload.php';

use Vtiful\Kernel\Excel;

abstract class omecsv_abstract_io
{
    public $page_size = 500;
    public $local_path = DATA_DIR . '/omecsv/tmp_local/';
    public $file_prefix = '';
    public $file_data = [];
    public $is_write_file = false;
    public $data = [];
    public $file_id = 1;
    public $column_date_cnt = [];
    public $date_time_diff = 0;
    public $is_split = true;
    public $set_class = null;
    
    public function getInfo($file, $sheet = 0)
    {
        try {
            $file = iconv("utf-8", "gb2312", $file);
            
            if (empty($file) || !file_exists($file)) {
                throw new \Exception('文件不存在!');
            }
            
            $info   = pathinfo($file);
            $config = ['path' => $info['dirname']];
            $excel  = new Excel($config);
            $excel->openFile($info['basename'])
                ->openSheet();
            $row      = $excel->nextRow();
            $contents = $excel->setType(array_pad([], count($row), \Vtiful\Kernel\Excel::TYPE_STRING))
                ->getSheetData();
            $contents = array_merge([0 => $row], $contents);
            
            return ['row' => count($contents), 'column' => count($row)];
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    public function getData($file = '', $sheet = 0, $length = 0, $start = 0, $compatible_csv = true)
    {
        try {
            if (empty($file)) {
                throw new \Exception('文件为空!');
            }
            
            if (!file_exists($file)) {
                throw new \Exception('文件不存在!');
            }
            
            $info   = pathinfo($file);
            $config = ['path' => $info['dirname']];
            $excel  = new Excel($config);
            $excel->openFile($info['basename'])
                ->openSheet();
            
            $this->data = [];
            $current_order_id = null; // 用于追踪当前订单的标识符
            $line       = 0;
    
            // 此处判断请使用【!==】运算符进行判断；
            // 如果使用【!=】进行判断，出现空行时，返回空数组，将导致读取中断；
            
            // 先读取第一行来设置类型
            $row = $excel->nextRow();
            if($row !== NULL){
                //统一设置类型为字符串，为了解决货号为00开头的问题
                $excel->setType(array_pad([], count($row), \Vtiful\Kernel\Excel::TYPE_STRING));
                
                // 处理第一行数据
                if($length <= 0 || $line < $length){
                    $this->is_split($row);
                    $this->writeData(false, $compatible_csv);
                    $this->data[] = $row;
                    $line += 1;
                }
            }
            
            // 继续读取后续行
            while (($row = $excel->nextRow()) !== NULL) {
                if($length > 0 && $line >= $length){
                    break;
                }
                
                $this->is_split($row);
                $this->writeData(false, $compatible_csv); // 写入上一个订单的数据
    
                $this->data[] = $row;
                $line += 1;
            }
            $this->writeData(true, $compatible_csv);
            return $this->data;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    
    public function writeData($is_force = false, $compatible_csv = false)
    {
        
        if (!$this->is_write_file) return;
        
        if (!$this->file_prefix) $this->file_prefix = md5(time());
        
        $file_name  = sprintf("%s_%d.json", $this->file_prefix, $this->file_id);
        $local_file = $this->local_path . $file_name;
        if ($is_force || ($this->page_size <= count($this->data) && $this->is_split)) {
            
            if ($compatible_csv) {
                $this->data = array_values($this->data);
                foreach ($this->data as $k => $v) $this->data[$k] = array_values($v);
            }
            
            if (file_put_contents($local_file, json_encode($this->data, JSON_UNESCAPED_UNICODE))) {
                $this->file_data[] = $local_file;
            }
            $this->data = array();
            $this->file_id++;
        }
    }
    
    public function setPageSize($num)
    {
        $this->page_size = $num;
    }
    
    public function setLocalPath($path)
    {
        $this->local_path = $path;
    }
    
    public function setFilePrefix($file_prefix)
    {
        $this->file_prefix = $file_prefix;
    }
    
    public function setWriteFile($is_write_file)
    {
        $this->is_write_file = $is_write_file;
    }
    
    public function setCellDateCnt($column_date_cnt = array(), $time_diff = 0)
    {
        $this->column_date_cnt = $column_date_cnt;
        $this->date_time_diff  = $time_diff;
    }
    
    /**
     * is_split true=可拆分 false=不可拆分
     * @param $row
     * @date 2024-09-05 4:57 下午
     */
    public function is_split($row)
    {
        if (ome_func::class_exists($this->set_class) && $instance = kernel::single($this->set_class)){
            if (method_exists($instance,'is_split')){
                $this->is_split = $instance->is_split($row);
            }
        }
    }
    
    public function setClass($lib)
    {
        $this->set_class = $lib;
    }
    
}