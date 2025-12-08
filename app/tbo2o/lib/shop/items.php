<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 前端店铺商品处理Lib类
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class tbo2o_shop_items
{
    function __construct(){
        $this->_shopItemObj    = app::get('tbo2o')->model('shop_items');
    }
    
    /**
     * 批量插入
     *
     * @return void
     * @author
     **/
    public function batchInsert($items, $shop)
    {
        if (empty($items)) return false;
        
        $shopSkuLib      = kernel::single('tbo2o_shop_skus');
        
        $shop_bn_crc32   = $shopSkuLib->crc32($shop['shop_bn']);
        $dateline        = time();
        
        $taog_id = array();
        foreach ($items as $key=>$item)
        {
            $iid = $item['iid'] ? $item['iid'] : $item['num_iid'];
            $id = md5($shop['shop_id'].$iid);
            $items[$key]['taog_id'] = $id;
            $taog_id[] = $id;
        }
        
        $frame_set   = array();
        $rows        = $this->_shopItemObj->getList('id,frame_set', array('id'=>$taog_id));
        foreach ($rows as $row) {
            $frame_set[$row['id']] = $row['frame_set'];
        }
        unset($taog_id,$rows);
        
        $data    = array();
        foreach ($items as $item)
        {
            $iid    = $item['iid'] ? $item['iid'] : $item['num_iid'];
            
            //iid已经存在则跳过
            $item_row    = $this->_shopItemObj->dump(array('iid'=>$iid), 'id');
            if($item_row)
            {
                continue;
            }
            
            #在售库存
            $taog_store   = 0;
            
            #店铺库存
            $shop_store    = $item['num'];
            
            $data[] = array(
                    'id' => $item['taog_id'],
                    'shop_id' => $shop['shop_id'],
                    'shop_bn' => $shop['shop_bn'],
                    'shop_name' => $shop['name'],
                    'shop_type' => $shop['shop_type'],
                    'iid' => $iid,
                    'bn' => $item['outer_id'],
                    'title' => $item['title'],
                    'detail_url' => $item['detail_url'],
                    'approve_status' => $item['approve_status'] ? $item['approve_status'] : $item['status'],
                    'price' => $item['price'],
                    'shop_store' => $shop_store,
                    'taog_store' => $taog_store,
                    'frame_set' =>  $frame_set[$item['taog_id']] == 'false' ? 'false' : 'true',
                    'download_time' => $dateline,
                    'update_time' => $dateline,
            );
        }
        
        //获取REPLACE sql语句(使用inventorydepth公共函数库)
        $sql    = inventorydepth_func::get_replace_sql($this->_shopItemObj, $data);
        $this->_shopItemObj->db->exec($sql);
    }

    /**
     * @description 删除过时数据
     * @access public
     * @param void
     * @return void
     */
    public function deletePassData($shop_id,$time)
    {
        $sql    = 'DELETE FROM `tbo2o_shop_items` WHERE shop_id = "'.$shop_id.'" AND download_time < '.$time;
        $this->_shopItemObj->db->exec($sql);
    }

    /**
     * 清空表
     *
     * @return void
     * @author
     **/
    public function truncate()
    {
        $sql    = 'TRUNCATE TABLE `tbo2o_shop_items`';
        $this->_shopItemObj->db->exec($sql);
    }
}