<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_rule_ar{

    /**
     * 获取Params
     * @return mixed 返回结果
     */
    public function getParams(){
        $params = array(
            'read_line' => 2000,
            'relation' => array(
                'mfkey' => array(
                    1 => array('mkey'=>'*:业务流水号','fkey'=>'*:业务流水号'),
                    2 => array('mkey'=>'*:业务流水号','fkey'=>'*:业务流水号'),
                ),
            ),
            'public' => $public,
        );
        return $params;
    }

    /**
     * 获取Title
     * @return mixed 返回结果
     */
    public function getTitle(){
        $title = finance_io_bill_title::getTitle('ar');
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