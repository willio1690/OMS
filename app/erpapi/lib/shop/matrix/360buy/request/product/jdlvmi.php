<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author sunjing
 * @describe 处理店铺商品相关类
 */
class erpapi_shop_matrix_360buy_request_product_jdlvmi extends erpapi_shop_request_product {

    #实时下载店铺商品
    /**
     * itemsAllGet
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回值
     */

    public function itemsAllGet($filter,$offset=0,$limit=100)
    {
        $timeout = 10;

        $page_no = ($offset-1)*$limit;
        $param = array(
            'page_no'        => $offset,
            'offset'         => $page_no,   
            'page_size'      => $limit,
            'fields'         => 'iid,outer_id,bn,num,title,default_img_url,modified,detail_url,approve_status,skus,price,barcode ',
        );
        $param = array_merge((array)$param,(array)$filter);
        $title = "获取店铺(" . $this->__channelObj->channel['name'] .')商品';
        $result = $this->__caller->call(SHOP_JDLVMI_GET_ITEMS_ALL_RPC,$param,array(),$title,$timeout);

        if($result['data']) {
            $data = json_decode($result['data'], true);
            $jos_result_dto = $data['jingdong_vc_item_products_find_responce']['jos_result_dto'];

            $jos_data = array('totalResults'=>$jos_result_dto['count']);
            $item = array();
            foreach($jos_result_dto['result'] as $v){
                $item[] = array(
                    'iid'=>$v['ware_id'],
                    
                );
            }
            $jos_data['items']['item'] = $item;
            $result['data'] = $jos_data;
        }

        return $result;
    }

    #根据IID获取单个商品
    /**
     * item_get
     * @param mixed $iid ID
     * @return mixed 返回值
     */
    public function item_get($iid) {
        $title = '单拉商品[' . $iid . ']';
        $params = array(
            'wareId' => $iid,
        );
        $result = $this->__caller->call(SHOP_JDLVMI_ITEMS_PRODUCT_GET,$params,array(),$title, 10, $iid);
        

        if ($result['rsp'] != 'succ' || !$result['data']) return array();

        $jos_data = array();
        if ($result['data']) {

            $data = json_decode($result['data'],true);
            $data = $data['jingdong_vc_item_product_get_responce']['jos_result_dto']['single_obj'];

            unset($data['mobile_decoration_html'],$data['pc_decoration_html'],$data['ext_propI_infos_list']);

            $sku = array();
            foreach($data['skuList'] as $v){
                $sku[] = array(
                    'sku_id'    =>  $v['skuId'],
                    'outer_id'  =>  $v['itemNum'],
                    'properties_name'=>  $v['skuName'],
                );
            }
            $skus['sku'] = $sku;
            $data['item'] = array(
                'iid'       =>  $data['wareId'],
                'outer_id'  =>  $data['wareId'],
                'title'     =>  $data['wreadme'],
                'skus'      =>  $skus,
            );
            $result['data'] = $data;
        }

        return $result;
    }


    /**
     * item_sku_get
     * @param mixed $sku sku
     * @return mixed 返回值
     */
    public function item_sku_get($sku) {
        $title = '单拉商品SKU[' . ($sku['sku_id'] ? $sku['sku_id'] : $sku['iid']) . ']';
        $params = array(
            'sku_id' => $sku['sku_id'],
            'iid' => $sku['iid'],
            'num_iid' => $sku['iid'],
        );
        if ($sku['seller_uname']) $params['seller_uname'] = $sku['seller_uname'];
        for ($i=0; $i<3; $i++) {
            $result = $this->__caller->call(SHOP_ITEM_SKU_I_GET,$params,array(),$title, 10, ($sku['sku_id'] ? $sku['sku_id'] : $sku['iid']));
            if ($result['rsp'] == 'succ') break;
        }
        if ($result['rsp'] != 'succ' || !$result['data']) return array();

        if ($result['data']) $result['data'] = @json_decode($result['data'],true);

        //if ($result['data']['sku']) $result['data']['sku'] = @json_decode($result['data']['sku'],true);

        return $result;
    }


    protected function getUpdateStockApi() {

        $api_name = SHOP_JDVMI_UPDATE_ITEMS_QUANTITY_LIST_RPC;
               

        return $api_name;
    }

    /**
     * format_stocks
     * @param mixed $stockList stockList
     * @return mixed 返回值
     */
    public function format_stocks($stockList)
    {
        $config = $this->__channelObj->channel['config'];
     
        $platform = kernel::single('ome_wms')->getPlatform('jdlvmi');


        $stocks = array();
        $operateTime = date('Y-m-d H:i:s');
        foreach($stockList as $k=>$v){

            if($v['sku_id']) {
               
                $v['operateTime'] = $operateTime;
                $v['operateUser'] = 'system';
                $v['bn'] = $v['bn'];
                $v['sku_id'] = $v['sku_id'];
                $v['targetDeptNo'] = $config['targetdeptno'];
                $v['warehouseNo'] = $platform['relation_branch_bn'];
                $v['originDeptNo'] = $config['ownercode'];
                $stocks[]=$v;
            }
            
        }
      
        return $stocks;
    }

}
