<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 淘宝后端商品处理Lib类
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class tbo2o_shop_products
{
    const DOWNLOAD_ALL_LIMIT = 40;//每页下载宝贝数量
    
    function __construct($app)
    {
        $this->_shopProductObj = app::get('tbo2o')->model('shop_products');
        $this->_productExtObj  = app::get('tbo2o')->model('shop_product_ext');
    }

    /**
     * 同步本地基础物料
     *
     * @param intval $page
     * @param intval $limit
     * @return array
     **/
    public function syncMaterial($page, $limit=50, &$errormsg)
    {
        $barcodeObj          = app::get('material')->model('codebase');
        
        $result      = array('status'=>'running', 'count'=>0);
        $page_size   = intval($page) * $limit;
        
        $sql    = "SELECT a.material_bn, a.material_name, a.type, a.serial_number, a.visibled, b.*, c.use_expire 
                   FROM sdb_material_basic_material AS a 
                   LEFT JOIN sdb_material_basic_material_ext AS b ON a.bm_id=b.bm_id 
                   LEFT JOIN sdb_material_basic_material_conf AS c ON a.bm_id=c.bm_id 
                   WHERE a.disabled='false' LIMIT ". $page_size. ", ". $limit;
        $dataList    = $this->_shopProductObj->db->select($sql);
        if(empty($dataList))
        {
            $result['status']    = 'finish';
            return $result;
        }
        
        //插入基础物料
        $count_i     = 0;
        $dateline    = time();
        foreach ($dataList as $key => $val)
        {
            #已存在则跳过
            $productRow    = $this->_shopProductObj->dump(array('bn'=>$val['material_bn']), 'id');
            if($productRow)
            {
                continue;
            }
            
            #条形码
            $barcode    = $barcodeObj->dump(array('bm_id'=>$val['bm_id'], 'type'=>1), 'code');
            
            #主信息
            $data    = array(
                        'bn'=>$val['material_bn'],
                        'name'=>$val['material_name'],
                        'barcode'=>$barcode['code'],
                        'type'=>$val['type'],
                        'visibled'=>$val['visibled'],
                        'create_time'=>$dateline,
            );
            $product_id    = $this->_shopProductObj->insert($data);
            if($product_id)
            {
                #扩展信息
                $data    = array(
                        'id'=>$product_id,
                        'is_warranty'=>$val['use_expire'],
                        'price'=>$val['retail_price'],
                        'weight'=>$val['weight'],
                        'brand_id'=>$val['brand_id'],
                );
                $this->_productExtObj->insert($data);
            }
            else 
            {
                $errormsg    .= '插入商品编码:'. $val['material_bn'] .'出错;';
                return false;
            }
            
            $count_i++;
        }
        
        $result['count']    = $count_i;
        return $result;
    }
    
    /**
     * 全渠道绑定的店铺信息
     * 
     * 
     */
    function getTbo2oShopId(&$error_msg)
    {
        //全渠道店铺
        $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
        $shop_id         = $tbo2o_shop['id'];
        if(empty($shop_id))
        {
            $error_msg    = '淘宝全渠道店铺没有绑定';
            return false;
        }
        
        return $shop_id;
    }
    
    /**
     * 同步商品至淘宝(仅支持单个回写)
     *
     * @param Array $ids
     * @return Boolean
     **/
    public function syncTaobaoScitem($id, &$error_msg)
    {
        if(empty($id))
        {
            $error_msg    = '无效操作';
            return false;
        }
        
        //全渠道店铺
        $shop_id    = $this->getTbo2oShopId($error_msg);
        if(empty($shop_id))
        {
            return false;
        }
        
        //读取数据
        $sql        = "SELECT a.bn, a.name, a.barcode, b.* 
                       FROM sdb_tbo2o_shop_products AS a LEFT JOIN sdb_tbo2o_shop_product_ext AS b ON a.id=b.id 
                       WHERE a.id=". $id ." AND a.is_sync=0";
        $item       = $this->_shopProductObj->db->selectrow($sql);
        if(empty($item))
        {
            $error_msg    = '没有需要同步的数据';
            return false;
        }
        
        //执行同步
        $tbEventStore    = kernel::single('tbo2o_event_trigger_store');
        
        $result    = $tbEventStore->storeScitemAdd($item);
        if($result['rsp'] == 'success')
        {
            $rsp_data    = json_decode($result['data'], true);
            
            //更新同步状态
            $this->updateProduct($item['id'], $rsp_data['scitem_add_response']['sc_item']);
        }
        else
        {
            //同步失败后更新状态
            //$this->_shopProductObj->update(array('is_sync'=>2), array('id'=>$item['id']));
            
            $error_msg    = $result['msg'];
            return false;
        }
        
        return true;
    }
    
    /**
     * 更新同步的淘宝商品信息
     *
     * @param intval $id
     * @param Array  $ids
     * @return Boolean
     **/
    public function updateProduct($id, $data)
    {
        if(empty($data))
        {
            return false;
        }
        
        $item_id    = $data['item_id'];
        
        $sdf          = array('outer_id'=>$item_id, 'sync_time'=>time(), 'is_sync'=>1);
        $is_update    = $this->_shopProductObj->update($sdf, array('id'=>$id));
        
        return $is_update;
    }
    
    /**
     * 更新同步商品至淘宝(仅支持单个回写)
     *
     * @param Array $ids
     * @return Boolean
     **/
    public function updateSyncTaobaoScitem($id, &$error_msg)
    {
        if(empty($id))
        {
            $error_msg    = '无效操作';
            return false;
        }
        
        //全渠道店铺
        $shop_id    = $this->getTbo2oShopId($error_msg);
        if(empty($shop_id))
        {
            return false;
        }
        
        //读取数据
        $sql        = "SELECT a.bn, a.name, a.outer_id, a.barcode, b.* 
                       FROM sdb_tbo2o_shop_products AS a LEFT JOIN sdb_tbo2o_shop_product_ext AS b ON a.id=b.id 
                       WHERE a.id=". $id ." AND a.is_sync=2";
        $item       = $this->_shopProductObj->db->selectrow($sql);
        if(empty($item))
        {
            $error_msg    = '没有需要更新同步的数据';
            return false;
        }
        
        //执行同步
        $tbEventStore    = kernel::single('tbo2o_event_trigger_store');
        
        $result    = $tbEventStore->storeScitemUpdate($item);
        
        if($result['rsp'] == 'success')
        {
            $rsp_data    = json_decode($result['data'], true);
            
            //更新同步状态
            $this->updateProduct($item['id'], $rsp_data['scitem_add_response']['sc_item']);
        }
        else
        {
            $error_msg    = $result['msg'];
            return false;
        }
        
        return true;
    }
    
    /**
     * 设置同步状态
     *
     * @return void
     * @author
     **/
    public function setTaobaoSync($shop_id, $value)
    {
        base_kvstore::instance('tbo2o/taobao/syncproducts')->store('shop_syncproducts_'.$shop_id, $value, (time()+3600));
    }
    
    /**
     * 获取同步状态
     *
     * @return void
     * @author
     **/
    public function getTaobaoSync($shop_id)
    {
        $sync    = '';
        base_kvstore::instance('tbo2o/taobao/syncproducts')->fetch('shop_syncproducts_'.$shop_id, $sync);
        return ($sync === 'true') ? 'true' : 'false';
    }
    
    /**
     * 查询全部后端商品
     *
     * @return void
     * @author
     **/
    public function queryAllScitem($offset=0, &$error_msg)
    {
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        
        //全渠道店铺
        $shop_id    = $this->getTbo2oShopId($error_msg);
        if(empty($shop_id))
        {
            return false;
        }
        
        $tbEventStore = kernel::single('tbo2o_event_trigger_store');
        $count        = 0;
        $totalResults = 0;
        
        do
        {
            if ($count>60) {
                $error_msg = '超出最大循环次数';
                return false;
            }
            
            usleep(1000000);
            
            $sdf       = array('page'=>$offset, 'page_size'=>self::DOWNLOAD_ALL_LIMIT);
            $result    = $tbEventStore->storeScitemQuery($sdf);
            
            if ($result['rsp'] != 'success')
            {
                $error_msg    = $result['err_msg'];
                return false;
            } else {
                break;
            }
            
            $count++;
        }while(true);
        
        #数据处理
        $rsp_data     = json_decode($result['data'], true);
        $totalResults = intval($rsp_data['scitem_query_response']['total_page']);
        $item_list    = $rsp_data['scitem_query_response']['sc_item_list']['sc_item'];
        if(empty($item_list))
        {
            $error_msg    = '没有获取到数据';
            return false;
        }
        
        foreach ($item_list as $key => $val)
        {
            //更新已同步的本地后端商品
            $row    = $this->_shopProductObj->dump(array('bn'=>$val['outer_code'], 'is_sync'=>1), 'id');
            if($row)
            {
                $updateData    = array(
                                    'is_area_sale'=>intval($val['is_area_sale']),
                                    'weight'=>intval($val['weight']),
                                    'item_type'=>intval($val['item_type']),
                                    'price'=>floatval($val['price']),
                                    'height'=>intval($val['height']),
                                    'volume'=>intval($val['volume']),
                                    'width'=>intval($val['width']),
                                    'length'=>intval($val['length']),
                                    //'options'=>intval($val['options']),//后端商品options字段
                                    //'properties'=>$sdf['properties'],//商品属性
                                );
                $this->_productExtObj->update($updateData, array('id'=>$row['id']));
            }
        }
        
        return array('totalResults'=>$totalResults);
    }
}