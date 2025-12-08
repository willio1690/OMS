<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_rule_normal{

    public function getParams($public = array()){
        $params = array(
            'read_line' => 2000,
            'public' => $public,
        );
        return $params;
    }

    /**
     * 获取Title
     * @return mixed 返回结果
     */
    public function getTitle(){
        $title[1] = finance_io_bill_title::getTitle('normal');
        return $title;
    }

    /**
     * isTitle
     * @param mixed $row row
     * @param mixed $line line
     * @return mixed 返回值
     */
    public function isTitle($row,$line){
        return $sp = strpos(implode(',',$row),'*:')===false?false:true;
    }

    /**
     * isFilterLine
     * @param mixed $row row
     * @param mixed $line line
     * @return mixed 返回值
     */
    public function isFilterLine($row,$line){
        return false;
    }
}
?>