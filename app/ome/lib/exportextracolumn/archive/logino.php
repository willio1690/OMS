<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 归档订单导出扩展字段物流单号
 * @author liuzecheng@shopex.cn
 * @version 1.0
 */
class ome_exportextracolumn_archive_logino extends ome_exportextracolumn_abstract implements ome_exportextracolumn_interface{

    protected $__pkey = 'order_id';

    protected $__extra_column = 'column_logi_no';

    /**
     *
     * 获取归档订单的物流单号
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){

        $sql = 'select ad.logi_no,aod.'.$this->__pkey.' from  sdb_archive_delivery_order as aod left join sdb_archive_delivery as ad on ad.delivery_id=aod.delivery_id where aod.order_id in ('.implode(',',$ids).')';
        $lists    = kernel::database()->select($sql);
        $tmp_array= array();
        foreach($lists as $list){
            $logi_no = $list['logi_no'];

            if(isset($tmp_array[$list[$this->__pkey]])){
               $tmp_array[$list[$this->__pkey]] .= ";".$logi_no;
            }else{
               $tmp_array[$list[$this->__pkey]] = $logi_no;
            }
        }
        return $tmp_array;
    }

}