<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 核销误差模型类
* @author 334395174@qq.com
* @version 0.1
*/
class financebase_mdl_bill_verification_error extends dbeav_model
{

    public function getRow($cols='*',$filter=array())
    {
        $sql = "SELECT $cols FROM ".$this->table_name(true)." WHERE ".$this->filter($filter);
        return $this->db->selectrow($sql);
    }

    /**
     * isExist
     * @param mixed $filter filter
     * @param mixed $id ID
     * @return mixed 返回值
     */

    public function isExist($filter,$id = 0)
    {
        $sql = "SELECT id FROM ".$this->table_name(true)." WHERE ".$this->filter($filter);
        $id and $sql.=" and id <> ".$id;
        return $this->db->selectrow($sql) ? true : false;
    }

    /**
     * modifier_shop_id
     * @param mixed $val val
     * @return mixed 返回值
     */
    public function modifier_shop_id($val)
    {
        if(!isset($this->shop_name[$val])){
            $row = app::get('ome')->model('shop')->getList('name',array('shop_id'=>$val),0,1);
            if($row){
                $this->shop_name[$val] = $row[0]['name'];
            }else{
                return '';
            }
            
        }
        return $this->shop_name[$val];
    }

    /**
     * modifier_is_verify
     * @param mixed $val val
     * @return mixed 返回值
     */
    public function modifier_is_verify($val)
    {
         return $val=='1' ? '是' : '否';
    }

}