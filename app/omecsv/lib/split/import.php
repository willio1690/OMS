<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 逻辑处理类DEMO
 * Class omecsv_split_import
 */
class omecsv_split_import implements omecsv_data_split_interface
{
    public function process($cursor_id, $params, &$errmsg)
    {
        @ini_set('memory_limit', '128M');
        $oFunc = kernel::single('omecsv_func');
    
        $oFunc->writelog('处理任务-开始', 'settlement', $params);
        //业务逻辑处理
        //任务数据统计更新等
        $oFunc->writelog('处理任务-完成', 'settlement', 'Done');
        return [true];
    }
    
    /**
     * 检查文件是否有效
     * @param $file_name 文件名
     * @param $file_type 文件类型
     * @return array
     * @date 2024-06-06 3:52 下午
     */
    public function checkFile($file_name, $file_type)
    {
        $ioType = kernel::single('omecsv_io_split_' . $file_type);
        $row    = $ioType->getData($file_name, 0, 5);
        //导入文件内容验证
        return array(true, '文件模板匹配', $row[1]);
    }
    
    /**
     * 导入文件表头定义
     * @date 2024-06-06 3:52 下午
     */
    public function getTitle($filter=null,$ioType='csv' )
    {
    }
    
    public function getConfig($key)
    {
    }
}