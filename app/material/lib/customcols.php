<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_customcols{

    /**
     * 获取cols
     * @return mixed 返回结果
     */
    public function getcols(){

        $customcolsMdl = app::get('desktop')->model('customcols');

        $customcolslist = $customcolsMdl->getlist('col_name,col_key',array('tbl_name'=>'sdb_material_basic_material'));

        if($customcolslist){

            
            return $customcolslist;
        }
    }


    /**
     * 获取colstemplate
     * @return mixed 返回结果
     */
    public function getcolstemplate(){

        $cols = $this->getcols();
        $coltemp = array();
        foreach($cols as $v){

            $key = '*:'.$v['col_name'];
            $name = 'custom/'.$v['col_key'];

            $coltemp[$key] = $name;
        }

        return $coltemp;
    }

}