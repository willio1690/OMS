<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 
*/
class inventorydepth_mdl_shop_items extends dbeav_model
{
    public $appendCols = 'iid,shop_id';

    public $export_name = '前端店铺商品';

    function __construct($app)
    {
        parent::__construct($app);

        $this->app = $app;
    }

    public function isave($value,$shop)
    {
        $id = md5($shop['shop_id'].$value['iid']);
        $item = array(
            'id'              => $id,
            'shop_id'         => $shop['shop_id'],
            'shop_bn'         => $shop['shop_bn'],
            'shop_bn_crc32'   => sprintf('%u',crc32($shop['shop_bn'])),
            'shop_name'       => $shop['name'],
            'shop_type'       => $shop['shop_type'],
            'iid'             => $value['iid'],
            'bn'              => $value['outer_id'],
            'title'           => $value['title'],
            'detail_url'      => $value['detail_url'],
            'approve_status'  => $value['approve_status'],
            'default_img_url' => $value['default_img_url'] ? $value['default_img_url'] : $value['pic_url'],
            'sku_num'         => $value['skus']['sku'] ? count($value['skus']['sku']) : 1,
            'price'           => $value['price'],
            'shop_store'      => (int)$value['num'],
            'download_time'   => time(),
            'outer_createtime' => strtotime($value['created']),
            'outer_lastmodify' => strtotime($value['modified']),
            'frame_set'        => 'true',
        );

        $oldItem = $this->db_dump($id,'frame_set');
        if ($oldItem) $item['frame_set'] = $oldItem['frame_set'];

        return $this->save($item);
    }

    /**
     * 清空表
     *
     * @return void
     * @author 
     **/
    public function truncate()
    {
        $sql = 'TRUNCATE TABLE '.$this->table_name(true);

        $this->db->exec($sql);
    }

    
    
    /**
     * 批量插入
     *
     * @return void
     * @author 
     **/
    public function batchInsert($items,$shop,$stores)
    {
        if (empty($items)) return false;
        $shopSkuLib = kernel::single('inventorydepth_shop_skus');

        $shop_bn_crc32         = $shopSkuLib->crc32($shop['shop_bn']);
        $download_time = time();
        
        $taog_id = array();
        foreach ($items as $key=>$item) {
            $iid = $item['iid'] ? $item['iid'] : $item['num_iid'];
            $id = md5($shop['shop_id'].$iid);
            $items[$key]['taog_id'] = $id;
            $taog_id[] = $id;
        }

        $frame_set = array();
        $rows = $this->getList('id,frame_set',array('id'=>$taog_id));
        foreach ($rows as $row) {
            $frame_set[$row['id']] = $row['frame_set'];
        }
        unset($taog_id,$rows);

        $data = array();
        foreach ($items as $item) {
            $iid = $item['iid'] ? $item['iid'] : $item['num_iid'];

            $taog_store = $stores[strval($iid)]['taog_store'] ? $stores[strval($iid)]['taog_store'] : 0;
            
            $data[] = array(
                'shop_id' => $shop['shop_id'],    
                'shop_bn' => $shop['shop_bn'],    
                'shop_bn_crc32' => $shop_bn_crc32,    
                'shop_name' => $shop['name'],    
                'shop_type' => $shop['shop_type'],    
                'iid' => $iid,    
                'bn' => $item['outer_id'],    
                'title' => $item['title'],    
                'detail_url' => $item['detail_url'],    
                'approve_status' => $item['approve_status'] ? $item['approve_status'] : $item['status'],
                'default_img_url' => $item['default_img_url'],    
                'price' => $item['price'],    
                'download_time' => $download_time,    
                'id' => $item['taog_id'],    
                'shop_store' => $item['num'],    
                'taog_store' => $taog_store,    
                'frame_set' =>  $frame_set[$item['taog_id']] == 'false' ? 'false' : 'true',
            );
        }
        if($data) {
            $sql = inventorydepth_func::get_replace_sql($this,$data);
            $this->db->exec($sql);
        }
    }

    /**
     * @description 删除过时数据
     * @access public
     * @param void
     * @return void
     */
    public function deletePassData($shop_id,$time) 
    {
        $sql = ' DELETE FROM `'.$this->table_name(1).'` WHERE shop_id = "'.$shop_id.'" AND download_time < '.$time;
        $this->db->exec($sql);
    }

    public function saveItem($value,$shop,$front=true)
    {
        $skuMdl = app::get('inventorydepth')->model('shop_skus');

        $item = array(
            'iid' => $value['iid'],
            'title' => $value['title'],
            'simple' => $value['simple'],
        );

        $skus = $value['skus'];

        if($front){
            # 如果没有规格
            if (!isset($value['skus']['sku']) && $value['skus']) {
                unset($skus);
                $skus['sku'][0] = array(
                    'price'      => $value['skus']['price'],
                    'iid'        => $value['iid'],
                    'outer_id'   => $value['outer_id'],
                    'properties' => 'props',
                    'simple'     => 'true',
                    'sku_id'     => '',
                    'quantity'   => $value['num'],
                );
            }

            if (!$skuMdl->isave($skus,$shop,$item)) {
                return false;
            }
        }else{
            // 如果没有规格
            if (!$skus) {
                $skus['sku'] = array(
                    array(
                        'price'      => $value['price'],
                        'iid'        => $value['iid'],
                        'outer_id'   => $value['outer_id'],
                        'properties' => 'props',
                        'simple'     => 'true',
                        'sku_id'     => '',
                        'quantity'   => $value['num'],
                    ),
                );
            }

            if (!$skuMdl->isave_back($skus,$shop,$item)) {
                return false;
            }
        }

        // 保存商品
        return $this->isave($value,$shop,$stores);
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function import($data) 
    {
        
    }

    public function modifier_detail_url($row)
    {
        return <<<EOF
        <a target='_blank' href="{$row}">{$row}</a>
EOF;
    }

}