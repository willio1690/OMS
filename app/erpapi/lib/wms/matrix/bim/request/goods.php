<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 商品分配推送
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_bim_request_goods extends erpapi_wms_request_goods
{
    /**
     * summary
     * 
     * @return void
     * @author 
     */

    public function get($sdf)
    {
        $title = $this->__channelObj->channel['channel_name'].'商品同步';

        $callback = array(
            'class'  => get_class($this),
            'method' => 'get_callback',
            'params' => array('wms_id'=>$this->__channelObj->channel['channel_id']),
        );

        foreach ($sdf as $good) {
            if (!$good || !is_array($good)) continue;

            $params = $this->_format_get_params($good);

            $rs = $this->__caller->call(WMS_ITEM_GET, $params, $callback, $title, 10, $good['bn']);
        }
    }

    protected function _format_get_params($good) 
    {
        $owner_user_id = app::get('wmsmgr')->getConf('owner_user_id_'.$this->__channelObj->channel['channel_id']);
        $params = array(
            'item_code'     => $good['bn'],
            'owner_user_id' => $owner_user_id,
        );

        return $params;
    }

    /**
     * summary
     * 
     * @return void
     * @author 
     */
    public function get_callback($response, $callback_params)
    {
        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'];
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];

        $wms_id    = $callback_params['wms_id'];

        if (!is_array($data)) $data = @json_decode($data,true);

        if (!$data['item_code'] || !$wms_id) return $this->callback($response,$callback_params);

        $updateData = array(
            'sync_status'   => $rsp == 'succ' ? '3' : '1',
        );

        if ($data['wms_item_code']) {
            $updateData['outer_sku'] = $data['wms_item_code'];
        }

        $materialModel = app::get('wms')->model('material');
        $materialModel->update($updateData,array('inner_sku'=>$data['item_code'],'wms_id'=>$wms_id));

        return $this->callback($response,$callback_params);
    }

    /**
     * goods_add
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function goods_add($sdf){
        $this->get($sdf);

    /**
        $title = $this->__channelObj->channel['channel_name'].'商品添加';

        $callback = array(
            'class'  => get_class($this),
            'method' => 'goods_callback',
            'params' => array('wms_id'=>$this->__channelObj->channel['channel_id']),
        );

        foreach ($sdf as $good) {
            if (!$good || !is_array($good)) continue;

            $params = $this->_format_goods_params($good);

            $callback['params']['inner_sku'] = array($good['bn']);

            $this->__caller->call(WMS_ITEM_ADD, $params, $callback, $title, 10, $good['bn']);
        }
     * */
    }

        /**
     * goods_update
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function goods_update($sdf)
    {
        $this->get($sdf);
    /**
        $title = $this->__channelObj->channel['channel_name'].'商品更新';

        $callback = array(
            'class'  => get_class($this),
            'method' => 'goods_callback',
            'params' => array('wms_id'=>$this->__channelObj->channel['channel_id']),
        );

        foreach ($sdf as $good) {
            if (!$good || !is_array($good)) continue;

            $params = $this->_format_goods_params($good);

            $callback['params']['inner_sku'] = array($good['bn']);

            $this->__caller->call(WMS_ITEM_UPDATE, $params, $callback, $title, 10, $good['bn']);
        }
     * */
    }

    protected function _format_goods_params($p)
    {
        $params = $items = array();

        $spec_info = preg_replace(array('/：/','/、/'),array(':',';'),$p['property']);
        $items[] = array(
            'name'                => $p['name'].($p['isNameSpec']=='1'? ' '.$spec_info:''),
            'title'               => $p['name'].($p['isNameSpec']=='1'? ' '.$spec_info:''),// 商品标题
            'item_code'           => $p['bn'],
            'remark'              => '',//商品备注
            'type'                => $p['type'] =='pkg' ? 'COMBINE' : 'NORMAL',
            'is_sku'              => '1',
            'gross_weight'        => $p['weight'] ? $p['weight']/1000 : '',// 毛重,单位G
            'net_weight'          => $p['weight'] ? $p['weight']/1000 : '',// 商品净重,单位G
            'tare_weight'         => '',// 商品皮重，单位G
            'is_friable'          => '',// 是否易碎品
            'is_dangerous'        => '',// 是否危险品
            //'weight'            => $p['weight'] ? $p['weight'] : '0',
            //'length'            => '0.00',// 商品长度，单位厘米
            //'width'             => '0.00',// 商品宽度，单位厘米
            //'height'            => '0.00',// 商品高度，单位厘米
            //'volume'            => '0.00',// 商品体积，单位立方厘米
            'pricing_cat'         => '',// 计价货类
            'package_material'    => '',// 商品包装材料类型
            'price'               => '',
            'support_batch'       => '否',
            'support_expire_date' => '否',
            'expire_date'         => date('Y-m-d'),
            'support_barcode'     => '0',
            'barcode'             => $p['barcode'] ? $p['barcode'] : '',
            'support_antifake'    => '否',
            'unit'                => $p['unit'] ? $p['unit'] : '',
            'package_spec'        => '',// 商品包装规格
            'ename'               => '',// 商品英文名称
            'brand'               => '',
            'batch_no'            => '',
            'goods_cat'           => $p['goods_cat'] ? $p['goods_cat'] : '',// 商品分类
            'color'               => '',// 商品颜色
            'property'            => $spec_info,//规格
            'item_id'             => $p['outer_sku'] ? $p['outer_sku'] : $p['bn'],
        );
        
        $params['item_lists'] = json_encode(array('item'=>$items));
        $params['uniqid'] = self::uniqid();
        $params['to_version'] = '2.0';
        $params['warehouse_code'] = $p['warehouse_code'];

        return $params;
    }

    /**
     * 同步库存
     * 
     * @return void
     * @author 
     */
    public function goods_syncStore($sdf)
    {
        $title = $this->__channelObj->channel['channel_name'] . '查询库存';

        $params = $this->_format_syncstore_params($sdf);

        $callback_params = array(
            'store_code' => $params['store_code'],
            'node_id'    => $this->__channelObj->channel['node_id'],
            'wms_id'     => $this->__channelObj->channel['channel_id'],
        );

        // $callback = array(
        //     'class'  => get_class($this),
        //     'method' => 'synStore_callback',
        //     'params' => $callback_params,
        // );

        $result = $this->__caller->call(WMS_ITEM_INVENTORY_QUERY, $params, $callback, $title,10);

        return $this->synStore_callback($result,$callback_params);
    }

    /**
     * summary
     * 
     * @return void
     * @author 
     */
    protected function _format_syncstore_params($sdf)
    {
        $params = array();

        if ($sdf['outer_sku']) $params['item_id'] = $sdf['outer_sku'];

        if ($sdf['branch_info']) $params['store_code'] = $this->_getBranchCode($sdf['branch_info']);

        $params['inventory_type'] = $sdf['branch_info']['type'] == 'main' ? 1 : 101;

        $params['type'] = 1;

        $params['page_no']   = $sdf['page_no'] ? $sdf['page_no'] : 1;
        $params['page_size'] = $sdf['page_size'] ? $sdf['page_size'] : 25;

        return $params;
    }

    /**
     * summary
     * 
     * @return void
     * @author 
     */
    public function synStore_callback($response, $callback_params)
    {
        if ($response['rsp'] != 'succ') return $this->callback($response,$callback_params);

        $data = @json_decode($response['data'],true);

        if (!$data['total_count'] || !$data['item_list']) return $this->callback($response,$callback_params);

        $item_id = array();
        foreach ($data['item_list']['wms_inventory_query_itemlist'] as $value) {
            $item_id[] = $value['item']['item_id'];
        }

        $materialMdl = app::get('console')->model('foreign_sku');
        $list = $materialMdl->getList('*',array('wms_id'=>$callback_params['wms_id'],'outer_sku'=>$item_id));

        $material = array();
        foreach ($list as $value) {
            $material[$value['outer_sku']] = $value;
        }

        $inventory = array(
            'inventory_bn' => uniqid('cn_'),
            'warehouse'    => $callback_params['store_code'],
            'memo'         => '同步菜鸟保税仓库存',
            'autoconfirm'  => 'Y',
        );

        $items = array();
        foreach ($data['item_list']['wms_inventory_query_itemlist'] as $value) {
            $item = array();
            $item['product_bn']      = $material[$value['item']['item_id']]['inner_sku'];
            $item['item_id'] = $value['item']['item_id'];
            $item['mode']    = '1';

            switch ($value['item']['inventory_type']) {
                case '1':
                    $item['normal_num'] = (int) $value['item']['quantity'];
                    break;
                case '101':
                    $item['defective_num'] = (int)$value['item']['quantity'];
                    break;
            }

            $items[] = $item;
        }
        $inventory['item'] = json_encode($items);


        if ($inventory) {
            kernel::single('erpapi_router_response')->set_node_id($callback_params['node_id'])->set_api_name('wms.inventory.add')->dispatch($inventory);
        }

        return $this->callback($response,$callback_params);
    }
}
