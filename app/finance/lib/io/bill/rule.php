<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_rule{

    private $importFiletype = '';
    private $importRuleObject = '';

    /**
     * type
     * @param mixed $importFiletype importFiletype
     * @return mixed 返回值
     */
    public function type($importFiletype = 'normal'){
        $this->importFiletype = $importFiletype;
        $this->importRuleObject = kernel::single('finance_io_bill_rule_'.$importFiletype);
        return $this;
    }

    /**
     * 获取Params
     * @return mixed 返回结果
     */
    public function getParams(){
        return $this->importRuleObject->getParams();
    }

    /**
     * 获取Title
     * @return mixed 返回结果
     */
    public function getTitle(){
        return $this->importRuleObject->getTitle();
    }

    /**
     * isTitle
     * @param mixed $row row
     * @param mixed $line line
     * @return mixed 返回值
     */
    public function isTitle($row,$line){
        return $this->importRuleObject->isTitle($row,$line);
    }

    /**
     * isFilterLine
     * @param mixed $row row
     * @param mixed $line line
     * @return mixed 返回值
     */
    public function isFilterLine($row,$line){
        return $this->importRuleObject->isFilterLine($row,$line);
    }
}
?>