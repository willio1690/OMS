<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* jdlvmi商品处理
* 
* sunjing
*/
class inventorydepth_service_shop_jdlvmi extends inventorydepth_service_shop_common
{
    public $customLimit = 10; //京东limit为10
    
    function __construct(&$app)
    {
        $this->app = $app;
    }

    public function downloadListNOSku($filter,$shop_id,$offset=0,$limit=200,&$errormsg) {
        $data = parent::downloadListNOSku($filter,$shop_id,$offset,$limit,$errormsg);
        if ($data) {
            $tmpData = array();
            foreach ($data as $key=>$value) {
                $tmpData[] = array(
                    'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                    'iid' => $value['iid'] ? $value['iid'] : '',
                    'title' => $value['title'] ? $value['title'] : '',
                    'approve_status' => $value['approve_status'] ? $value['approve_status'] : '',
                    'price' => $value['price'],
                    'num' => $value['num'],
                    'detail_url' => '',
                    'default_img_url' => $value['default_img_url'],
                    'props' => $value['props'],
                );
            }
            $data = $tmpData; unset($tmpData);
        }

        return $data;
    }

    public function downloadList($filter,$shop_id,$offset=0,$limit=200,&$errormsg)
    {
        $data = parent::downloadList($filter,$shop_id,$offset,$limit,$errormsg);


        # 数据重组
        if ($data) {
            $tmpData = array();
            foreach ($data as $key=>$value) {
                # SKU
                if ($value['skus']['sku']) {
                    $value['num'] = 0;
                    foreach ($value['skus']['sku'] as $key=>$sku) {
                        $value['skus']['sku'][$key]['quantity'] = $sku['num'];
                        $value['num'] += $sku['num'];
                    }
                }

                $tmpData[] = array(
                    'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                    'iid' => $value['iid'] ? $value['iid'] : '',
                    'title' => $value['title'] ? $value['title'] : '',
                    'approve_status' => $value['approve_status'] ? $value['approve_status'] : '',
                    'price' => $value['price'],
                    'num' => $value['num'],
                    'detail_url' => '',
                    'default_img_url' => $value['default_img_url'],
                    'props' => $value['props'],
                    'simple' => 'true',
                    'skus' => $value['skus'] ? $value['skus'] : '',
                );
            }
            $data = $tmpData;unset($tmpData);
        }

        return $data;
    }

    public function downloadByIId($iid,$shop_id,&$errormsg)
    {
        $data = parent::downloadByIId($iid,$shop_id,$errormsg);

        if ($data) {
            # SKU
            $skuList = $data['single_obj']['skuList'];

            $skus = array();
            if ($skuList) {
                $data['num'] = 0;
                foreach ($skuList as $key=>$sku) {
                    $skus['sku'][$key]=array(
                        'sku_id'  =>  $sku['skuId'],
                        'outer_id'  =>  $sku['upc'],
                        'name'      =>  $sku['name'],

                    );
                }
            }

            $tmpData = array(
                'outer_id' => $data['outer_id'] ? $data['outer_id'] : '',
                'iid' => $data['iid'] ? $data['iid'] : '',
                'title' => $data['title'] ? $data['title'] : '',
                'approve_status' => $data['approve_status'] ? $data['approve_status'] : '',
                'price' => $data['price'],
                'num' => $data['num'],
                'detail_url' => '',
                'default_img_url' => $data['default_img_url'],
                'props' => $data['props'],
                'simple' => 'true',
                'skus' => $skus ? $skus : '',
            );
            
            $data = $tmpData;unset($tmpData);
        }

        return $data;
    }

    public function downloadByIIds($iids,$shop_id,&$errormsg)
    {
        $data = parent::downloadByIIds($iids,$shop_id,$errormsg);
        if ($data) {
            $tmpData = array();
            foreach ($data as $key=>$value) {
                # SKU
                if ($value['skus']['sku']) {
                    $value['num'] = 0;
                    foreach ($value['skus']['sku'] as $key=>$sku) {
                        $value['skus']['sku'][$key]['quantity'] = $sku['num'];
                        $value['num'] += $sku['num'];
                    }
                }

                $tmpData[] = array(
                    'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                    'iid' => $value['iid'] ? $value['iid'] : '',
                    'title' => $value['title'] ? $value['title'] : '',
                    'approve_status' => $value['approve_status'] ? $value['approve_status'] : '',
                    'price' => $value['price'],
                    'num' => $value['num'],
                    'skus' => $value['skus'] ? $value['skus'] : '',
                    'simple' => 'true',
                );
            }
            $data = $tmpData;unset($tmpData);
        }
        return $data;
    }

    public function doApproveBatch($approve_status,$shop_id,$check_status=true){
        $request = kernel::single('inventorydepth_shop')->getFrameConf($shop_id);

        if($check_status == true && $request !== 'true'){ 
            $msg = $this->app->_('店铺上下架功能未开启');
            return false;
        }
        $succ = $fail = array();
        set_time_limit(0);
        foreach ($approve_status as $key=>$value) {
            $result = kernel::single('inventorydepth_rpc_request_shop_frame')->approve_status_update($value,$shop_id);
            if ($result['rsp'] == 'succ') {
                $succ[] = $value['iid'];
            }else{
                $fail[] = $value['iid'];
            }
        }
        $succStatus = $approve_status[0]['approve_status'];
        $failStatus = $approve_status[0]['approve_status'] == 'onsale' ? 'instock' : 'onsale';
        $itemModel = app::get('inventorydepth')->model('shop_items');
        if ($succ) {
            $itemModel->update(array('approve_status'=>$succStatus),array('iid'=>$succ,'shop_id'=>$shop_id));
        }
        if($fail) {
            $itemModel->update(array('approve_status'=>$failStatus),array('iid'=>$fail,'shop_id'=>$shop_id));
        }
    }
}