<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* RPC接口实现类
*
* @author chenping<chenping@shopex.cn>
* @version 2012-6-25
*/
class inventorydepth_taog_rpc_request_shop_items extends ome_rpc_request
{
    public $err_msg;
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
     * 实时下载店铺商品
     *
     * @param Array $filter 筛选条件(approve_status)
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array $items
     **/
    public function items_all_get($filter,$shop_id,$offset=0,$limit=100)
    {
        if(!$shop_id) return false;

        $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_itemsAllGet($filter,$offset,$limit);
        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result['rsp'] !== 'succ'){
            $errMsg = $result['err_msg'] ?: $result['msg'];
            $this->set_err_msg('请求失败：'.$errMsg . '('. $result['msg_id'] .')');
            return false;
        }
        return $result['data'];
    }

    /**
     * 实时下载店铺商品
     *
     * @param Array $filter 筛选条件(approve_status)
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array $items
     **/
    public function fenxiao_products_get($filter,$shop_id,$offset=0,$limit=100)
    {
        if(!$shop_id) return false;

        $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_fenxiaoProductsGet($filter,$offset,$limit);
        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result['rsp'] !== 'succ'){
            $this->set_err_msg('请求失败：'.$result['err_msg'] . '('. $result['msg_id'] .')');
            return false;
        }
        return $result['data'];
    }

    /**
     * 根据IID，实时下载店铺商品
     *
     * @param Array $iids 商品ID(不要超过限度20个)
     * @param String $shop_id 店铺ID
     * @param Int $offset 页码
     * @param Int $limit 每页条数
     * @return Array
     **/
    public function items_list_get($iids,$shop_id)
    {

        if(!$iids || !$shop_id) return false;

        $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_itemsListGet($iids);
        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result['rsp'] !== 'succ'){
            $errMsg = $result['err_msg'] ?: $result['msg'];
            $this->set_err_msg('请求失败：'.$errMsg . '('. $result['msg_id'] .')');
            return false;
        }
        return $result['data'];
    }

    /**
     * 获取单个商品明细
     *
     * @param Int $iid商品ID
     * @param String $shop_id 店铺ID
     * @return void
     * @author
     **/
    public function item_get($iid,$shop_id)
    {
        if(!$iid || !$shop_id) return false;

        $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_item_get($iid);
        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result['rsp'] !== 'succ'){
            $this->set_err_msg('请求失败：'.$result['err_msg'] . '('. $result['msg_id'] .')');
            return false;
        }
        return $result['data'];
    }

    public function fenxiao_product_update($product,$shop_id)
    {
        if(!$product || !$shop_id) return false;

        $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_fenxiaoProductUpdate($product);
        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result['rsp'] !== 'succ'){
            $this->set_err_msg('请求失败：'.$result['err_msg'] . '('. $result['msg_id'] .')');
            return false;
        }
        return true;
    }

    /**
     * 测试数据
     *
     * @return void
     * @author
     **/
    private function test_data($iids = '')
    {
        require_once(ROOT_DIR.'/app/inventorydepth/testcase/data.php');
        $data = json_decode($data,true);
        $data['data'] = json_encode($data['data']);
        $data = json_encode($data);
        $data = json_decode($data);
        if ($iids) {
            foreach ($data['data']['items']['item'] as &$value) {
                if (!in_array($value['iid'], $iids)) {
                    unset($value);
                }
            }
        }
        return $data;
    }
}