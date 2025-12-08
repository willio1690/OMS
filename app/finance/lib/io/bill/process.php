<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_process{

    private $importFiletype = '';
    private $importProcessObject = '';

    /**
     * type
     * @param mixed $importFiletype importFiletype
     * @return mixed 返回值
     */
    public function type($importFiletype = 'normal'){
        $this->importFiletype = $importFiletype;

        if (!class_exists('finance_io_bill_process_'.$importFiletype)) {
            return false;
        }

        $this->importProcessObject = kernel::single('finance_io_bill_process_'.$importFiletype);
        return $this;
    }

    public function structure_import_data(&$mdl,$row,&$format_row=array(),&$result){
        $this->importProcessObject->structure_import_data($mdl,$row,$format_row,$result);
    }

    /**
     * 检查ing_import_data
     * @param mixed $mdl mdl
     * @param mixed $row row
     * @param mixed $result result
     * @return mixed 返回验证结果
     */
    public function checking_import_data(&$mdl,$row,&$result){
        $this->importProcessObject->checking_import_data($mdl,$row,$result);
    }

    /**
     * finish_import_data
     * @param mixed $mdl mdl
     * @param mixed $row row
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function finish_import_data(&$mdl,$row,&$result){
        $this->importProcessObject->finish_import_data($mdl,$row,$result);
    }

    /**
     * 读取到的数据格式化
     *
     * @param Object $mdl MODEL层对象
     * @param Array $row 读取一行
     * @return void
     * @author 
     **/
    public function getSDf(&$mdl,$row,&$mark)
    {
        return $this->importProcessObject->getSDf($mdl,$row,$mark);
    }

}
?>