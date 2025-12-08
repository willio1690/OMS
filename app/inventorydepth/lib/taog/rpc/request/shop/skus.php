<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* RPC接口实现类
*
* @author chenping<chenping@shopex.cn>
* @version 2012-7-11     
*/
class inventorydepth_taog_rpc_request_shop_skus extends ome_rpc_request
{

    public function __construct() 
    {
    }

    public function get_err_msg(){
        return $this->err_msg;
    }

    public function set_err_msg($err_msg){
        return $this->err_msg = $err_msg;
    }

    /**
     * 下载货品
     *
     * @param Array $sku
     * $sku = array(
     *  'sku_id' => {SKU的ID}
     *  'iid'    => {商品ID}
     *  'seller_uname' => {卖家帐号}
     * );
     * @param String $shop_id 店铺ID
     * @return void
     * @author 
     **/
    public function item_sku_get($sku,$shop_id)
    {
        if(!$sku || !$shop_id) return false;

        return kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_item_sku_get($sku);
    }

    /**
     * 测试数据
     *
     * @return void
     * @author 
     **/
    private function test_data($iids = '')
    {
        require_once(ROOT_DIR.'/app/inventorydepth/testcase/skus.php');
        $data = json_decode($data,true);
        if ($iids) {
            foreach ($data['data']['items']['item'] as &$value) {
                if (!in_array($value['iid'], $iids)) {
                    unset($value);
                }
            }
        }        
        return $data;
    }

    public function queryInventory($sku_id,$shop_id)
    {
        if(!$sku_id || !$shop_id) return ['rsp'=>'fail', 'msg'=>'参数错误,缺少：shop_id或sku_id'];

        $sdf = [
            'sku_id' => $sku_id,
        ];

        return kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_queryInventory($sdf);
    }
}