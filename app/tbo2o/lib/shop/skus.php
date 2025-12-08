<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 前端店铺货品处理Lib类
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class tbo2o_shop_skus
{
    public static $taog_products = array();
    
    public static $taog_pkg = array();
    
    function __construct(){
        $this->_shopSkuObj    = app::get('tbo2o')->model('shop_skus');
    }
    
    /**
     * 批量加入货品
     *
     * @return void
     * @author 
     **/
    public function batchInsert($items, $shop)
    {
        if (empty($items)) return false;

        $bnList = $taog_id = array();
        foreach ($items as $key => $item) {
            $iid = $item['iid'] ? $item['iid'] : $item['num_iid'];

            if (isset($item['skus']['sku'])) {
                foreach ($item['skus']['sku'] as $k => $sku) {
                    $bnList[] = $sku['outer_id'];

                    $id = md5($shop['shop_id'].$iid.$sku['sku_id']);
                    $items[$key]['skus']['sku'][$k]['taog_id'] = $id;
                    $taog_id[] = $id;
                }
            } else {
                $bnList[] = $item['outer_id'];

                $id = md5($shop['shop_id'].$iid);
                $items[$key]['taog_id'] = $id;
                $taog_id[] = $id;
            }
        }
        
        $shop_id = $shop['shop_id'];
        $shop_bn = $shop['shop_bn'];
        unset($bnList);

        $shop_bn_crc32         = $this->crc32($shop['shop_bn']);

        $request = array();
        $rows = $this->_shopSkuObj->getList('request,id',array('id' => $taog_id));
        foreach ($rows as $key=>$row) {
            $request[$row['id']] = $row['request'];
        }
        unset($rows,$taog_id);

        $VALUES = array();
        $delSku = array();
        $data = array();
        
        foreach ($items as $key => $item) {
            $spbn = array();
            $iid = $item['iid'] ? $item['iid'] : $item['num_iid'];
            
            //shop_iid已经存在则跳过
            $sku_row    = $this->_shopSkuObj->dump(array('shop_iid'=>$iid), 'id');
            if($sku_row)
            {
                continue;
            }
            
            if (isset($item['skus']['sku'])) {
                # 多规格
                foreach ($item['skus']['sku'] as $sku) {
                    $shop_product_bn = $sku['outer_id'];
                    $spbn[] = $shop_product_bn;
                    $shop_product_bn_crc32 = $this->crc32($shop_product_bn);

                    $download_time = time();
                    
                    //绑定本地销售物料
                    $mapping    = $bind = 0;
                    
                    if ($bind == 1) {
                        $pkgFlag[] = $shop_product_bn;
                    }else{
                        $productFlag[] = $shop_product_bn;
                    }
                    
                    #  判断是否存在发布库存
                    $release_stock = 0;
                    
                    $data[] = array(
                        'id' => $sku['taog_id'],
                        'shop_id' => $shop['shop_id'],
                        'shop_bn' => $shop['shop_bn'],
                        'shop_sku_id' => $sku['sku_id'],
                        'shop_iid' => $iid,
                        'shop_product_bn' => $shop_product_bn,
                        'shop_product_bn_crc32' => $shop_product_bn_crc32,
                        'shop_price' => $sku['price'],
                        'download_time' => $download_time,
                        'shop_title' => $item['title'],
                        'update_time'=>time(),
                    );
                }
            }else{
                # 单商品
                $shop_product_bn        = $item['outer_id'];
                $spbn[]                 = $shop_product_bn;
                $shop_product_bn_crc32  = $this->crc32($shop_product_bn);

                $download_time        = time();
                $shop_properties_name = '';
                
                //绑定本地销售物料
                $mapping    = $bind = 0;
                
                if ($bind == 1) {
                    $pkgFlag[] = $shop_product_bn;
                }else{
                    $productFlag[] = $shop_product_bn;
                }

                #  判断是否存在发布库存
                $release_stock = 0;

                $data[] = array(
                    'id' => $item['taog_id'],
                    'shop_id' => $shop['shop_id'],
                    'shop_bn' => $shop['shop_bn'],
                    'shop_sku_id' => '',
                    'shop_iid' => $iid,
                    'shop_product_bn' => $shop_product_bn,
                    'shop_product_bn_crc32' => $shop_product_bn_crc32,
                    'shop_price' => $item['price'],
                    'download_time' => $download_time,
                    'shop_title' => $item['title'],
                    'update_time'=>time(),
                );
            }
        }

        //获取REPLACE sql语句(使用inventorydepth公共函数库)
        $sql    = inventorydepth_func::get_replace_sql($this->_shopSkuObj, $data);
        $this->_shopSkuObj->db->exec($sql);
    }

    /**
     * 清空表数据
     *
     * @return void
     * @author
     **/
    public function truncate()
    {
        $sql    = 'TRUNCATE TABLE `tbo2o_shop_skus`';
        $this->_shopSkuObj->db->exec($sql);
    }
    
    /**
     * 删除货品
     *
     * @return void
     * @author
     **/
    public function deleteSkus($filter)
    {
        $sql    = 'DELETE FROM `tbo2o_shop_skus` where '.$this->_shopSkuObj->_filter($filter);
        return $this->_shopSkuObj->db->exec($sql);
    }
    
    /**
     * @description 删除过时数据
     * @access public
     * @param void
     * @return void
     */
    public function deletePassData($shop_id,$time)
    {
        $sql    = 'DELETE FROM `tbo2o_shop_skus` WHERE shop_id = "'.$shop_id.'" AND download_time < '.$time;
        $this->_shopSkuObj->db->exec($sql);
    }

    /**
     * 将字符串做crc32
     *
     * @return void
     * @author
     **/
    public function crc32($val)
    {
        return sprintf('%u',crc32($val));
    }
    
    /**
     * 同步商品至淘宝(仅支持单个回写)
     *
     * @param Array $ids
     * @return Boolean
     **/
    public function scitemMapAdd($id, &$error_msg)
    {
        if(empty($id))
        {
            $error_msg    = '无效操作';
            return false;
        }
        
        //验证数据
        $item    = $this->_shopSkuObj->dump(array('id'=>$id), 'id, shop_iid, shop_sku_id, shop_product_bn, product_id, product_bn');
        if(empty($item['shop_iid']) || empty($item['shop_sku_id']))
        {
            $error_msg    = '没有需要同步的数据';
            return false;
        }
        if(empty($item['product_id']))
        {
            $error_msg    = '没有关联后端商品';
            return false;
        }
        
        //淘宝后端货品的外部商品ID
        $shopProductObj    = app::get('tbo2o')->model('shop_products');
        $productRow        = $shopProductObj->dump(array('id'=>$item['product_id']), 'outer_id');
        if($productRow['outer_id'])
        {
            $item['outer_id']    = $productRow['outer_id'];//Api可选项
        }
        
        //全渠道店铺
        $shopProductLib    = kernel::single('tbo2o_shop_products');
        $shop_id           = $shopProductLib->getTbo2oShopId($error_msg);
        if(empty($shop_id))
        {
            return false;
        }
        
        //执行同步
        $tbEventStore  = kernel::single('tbo2o_event_trigger_store');
        $result        = $tbEventStore->storeScitemMapAdd($item);
        
        if($result['rsp'] == 'success')
        {
            //$rsp_data    = json_decode($result['data'], true);
            //$outer_code  = $rsp_data['scitem_map_add_response']['outer_code'];//商家编码
            
            //更新绑定状态
            $this->_shopSkuObj->update(array('is_bind'=>1, 'bind_time'=>time()), array('id'=>$id));
        }
        else
        {
            $rsp_data    = json_decode($result['error_response'], true);
            $error_msg   = $rsp_data['sub_msg'];
            return false;
        }
        
        return true;
    }
    
    /**
     * 同步商品至淘宝(仅支持单个回写)
     *
     * @param Array $ids
     * @return Boolean
     **/
    public function scitemMapDelete($id, &$error_msg)
    {
        if(empty($id))
        {
            $error_msg    = '无效操作';
            return false;
        }
        
        //验证数据
        $item    = $this->_shopSkuObj->dump(array('id'=>$id, 'is_bind'=>1), 'id, shop_iid, shop_sku_id, shop_product_bn, product_id, product_bn');
        if(empty($item['id']))
        {
            $error_msg    = '没有需要解绑的数据';
            return false;
        }
        
        //淘宝后端货品的外部商品ID
        $shopProductObj    = app::get('tbo2o')->model('shop_products');
        $productRow        = $shopProductObj->dump(array('id'=>$item['product_id']), 'outer_id');
        if(empty($productRow['outer_id']))
        {
            $error_msg    = '淘宝后端货品的外部商品ID不存在';
            return false;
        }
        $item['outer_id']    = $productRow['outer_id'];
        
        //全渠道店铺
        $shopProductLib    = kernel::single('tbo2o_shop_products');
        $shop_id           = $shopProductLib->getTbo2oShopId($error_msg);
        if(empty($shop_id))
        {
            return false;
        }
        
        //执行同步
        $tbEventStore  = kernel::single('tbo2o_event_trigger_store');
        $result        = $tbEventStore->storeScitemMapDelete($item);
        
        if($result['rsp'] == 'success')
        {
            $rsp_data    = json_decode($result['data'], true);
            $unbind_num  = $rsp_data['scitem_map_delete_response']['module'];//解绑条数
            
            //更新绑定状态
            if($unbind_num)
            {
                $this->_shopSkuObj->update(array('is_bind'=>0, 'bind_time'=>0), array('id'=>$id));
            }
        }
        else
        {
            $rsp_data    = json_decode($result['error_response'], true);
            $error_msg   = $rsp_data['sub_msg'];
            return false;
        }
        
        return true;
    }
}