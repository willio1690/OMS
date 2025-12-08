<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会JIT出库装箱Lib类
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class purchase_purchase_box
{
    function __construct()
    {
        $this->_boxObj    = app::get('purchase')->model('pick_stockout_bill_item_boxs');
    }
    
    /**
     * 批量插入装箱信息
     */
    function batch_create($data)
    {
        $values    = array();
        foreach ($data as $key => $val)
        {
            $values[]    = "(". $val['stockout_id'] .",". $val['stockout_item_id'] .",". $val['po_id'] .",". $val['bill_id'] .",'". $val['box_no'] ."',". $val['box_num'] .")";
        }
        
        $sql = 'INSERT INTO `sdb_purchase_pick_stockout_bill_item_boxs` (`stockout_id`,`stockout_item_id`,`po_id`,`bill_id`,`box_no`,`num`) VALUES';
        $sql .= implode(',', $values);
        
        $result    = $this->_boxObj->db->exec($sql);
        if($result)
        {
            return true;
        }
        
        return false;
    }
    
    /**
     * 创建装箱信息
     */
    function create_box($sdf)
    {
        //开启事务
        $this->_boxObj->db->beginTransaction();
        
        if(!$this->_boxObj->save($sdf))
        {
            //事务回滚
            $this->_boxObj->db->rollBack();
            return false;
        }
        
        //事务确认
        $this->_boxObj->db->commit();
        
        return true;
    }
    
    /**
     * 更新拣货单
     */
    function update_box($sdf)
    {
        //开启事务
        $this->_boxObj->db->beginTransaction();
        
        if(!$this->_boxObj->save($sdf))
        {
            //事务回滚
            $this->_boxObj->db->rollBack();
            return false;
        }
        else
        {
            //事务确认
            $this->_boxObj->db->commit();
        }
        
        return true;
    }
    
    /**
     * 批量删除装箱明细
     * 
     * 已弃用，请不要使用
     * 
     * todo: 当入库出入库明细生成失败时,需要删除插入的装箱明细
     */
    function batch_del_box($data){
        foreach ($data as $key => $val)
        {
            $sql = "DELETE FROM sdb_purchase_pick_stockout_bill_item_boxs WHERE stockout_id=".$val['stockout_id']. " AND stockout_item_id=".$val['stockout_item_id']."
                    AND box_no='".$val['box_no']."' AND num=".$val['box_num'];
            $this->_boxObj->db->exec($sql);
        }
        
        return true;
    }
}
?>