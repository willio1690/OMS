<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_rule_jingdong_jushou{

    /**
     * 获取Params
     * @return mixed 返回结果
     */
    public function getParams(){
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
        $title[1] = finance_io_bill_title::getTitle('jingdong_jushou');
        return $title;
    }

    /**
     * isTitle
     * @param mixed $row row
     * @param mixed $line line
     * @return mixed 返回值
     */
    public function isTitle($row,$line){
        $result = true;
        $title = finance_io_bill_title::getTitle('jingdong_jushou');
        foreach($title as $v){
            if(!in_array($v,$row)){
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * isFilterLine
     * @param mixed $row row
     * @param mixed $line line
     * @return mixed 返回值
     */
    public function isFilterLine($row,$line){
        $result = true;
        $notLine = array('1');
        if(!in_array($line,$notLine)){
            $result = false;
        }
        return $result;
    }
}
?>