<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/1/4 17:06:14
 * @describe: model层
 * ============================
 */
class material_mdl_basic_material_stock_arrive extends dbeav_model {

    /**
     * 添加Record
     * @param mixed $obj_type obj_type
     * @param mixed $obj_id ID
     * @param mixed $branch_id ID
     * @param mixed $product_id ID
     * @param mixed $num num
     * @param mixed $operator operator
     * @return mixed 返回值
     */

    public function addRecord($obj_type, $obj_id, $branch_id, $product_id, $num, $operator) {
        $upFilter = ['obj_type'=>$obj_type,'obj_id'=>$obj_id,'branch_id'=>$branch_id,'bm_id'=>$product_id];
        $row = $this->db_dump($upFilter, 'id');
        if($row) {
            switch($operator)
            {
                case "+":
                    $store = "num=IFNULL(num,0)+".$num;
                    break;
                case "-":
                    $store = " num=IF((CAST(num AS SIGNED)-$num)>0,num-$num,0) ";
                    break;
                case "=":
                default:
                    $store = "num=".$num;
                    break;
            }
            $sql   = "UPDATE sdb_material_basic_material_stock_arrive SET ".$store." WHERE id=".intval($row['id']);
            return $this->db->exec($sql);
        }
        if($operator == '-' || $num < 0) {
            return false;
        }
        $data = $upFilter;
        $data['num'] = $num;
        $data['create_time'] = time();
        return $this->insert($data);
    }
}