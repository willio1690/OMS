<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class inventorydepth_service_shop_ecstore extends inventorydepth_service_shop_common
{
    public $approve_status = array(
            array('filter'=>array('approve_status'=>'onsale'),'name'=>'在架','flag'=>'onsale'),
            //array('filter'=>array('approve_status'=>'instock'),'name'=>'下架','flag'=>'instock'),
            array('filter'=>array('approve_status'=>'all'),'name'=>'全部','flag'=>'all'),
    );
    
    function __construct(&$app)
    {
        $this->app = $app;
    }

    public function downloadListNOSku($filter,$shop_id,$offset=0,$limit=200,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        
        # 请求商品
        $count = 0;
        do {
            if ($count>60) {
                $errormsg = '超出最大循环次数';
                return false;
            }
            usleep(1000000);
            $result = $shopService->items_all_get($filter,$shop_id,$offset,$limit);
            if ($result === false) {
                $errormsg = $shopService->get_err_msg();
                # 临时做一下兼容，待明天矩阵更新后还原
                if (false !== strpos($errormsg,'请求失败：') && strtotime('2013-3-21')>=time()) {
                    $errormsg = '';
                } elseif (false !== strpos($errormsg,'This ban will last for 1 more seconds') ) {
                    $errormsg = '';
                } else {
                    return false;
                }
            } else {
                break;
            }

            $count++;
        }while(true);
        
        # 数据为空
        if(empty($result['items']['item'])){
            $this->totalResults = 0;
            return array();
        }
        
        $this->totalResults = $result['total_results'];
        
        $data = $result['items']['item'];

        if ($data) {
            $tmpData = array();
            foreach ($data as $key=>$value) {
                $tmpData[] = array(
                    'outer_id' => $value['outer_id'] ? $value['outer_id'] : '',
                    'iid' => $value['iid'] ? $value['iid'] : '',
                    'title' => $value['title'] ? $value['title'] : '',
                    'approve_status' => $value['status'] ? $value['status'] : '',
                    'price' => $value['price'],
                    'num' => $value['num'],
                    'detail_url' => '',
                    'default_img_url' => '',
                    'props' => $value['props'],
                );
            }
            $data = $tmpData; unset($tmpData);
        }

        return $data;
    }

    /**
     * 下载全部
     *
     * @return void
     * @author 
     **/
    public function downloadList($filter,$shop_id,$offset=0,$limit=200,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        
        $count = 0;
        do {
            if ($count>60) {
                $errormsg = '超出最大循环次数';
                return false;
            }
            usleep(1000000);
            $result = $shopService->items_all_get($filter,$shop_id,$offset,$limit);
            
            if ($result === false) {
                $errormsg = $shopService->get_err_msg();
                # 临时做一下兼容，待明天矩阵更新后还原
                if (false !== strpos($errormsg,'请求失败：') && strtotime('2013-3-21')>=time()) {
                    $errormsg = '';
                } elseif (false !== strpos($errormsg,'This ban will last for 1 more seconds') ) {
                    $errormsg = '';
                } else {
                    return false;
                }
            } else {
                break;
            }

            $count++;
        }while(true);

        
        # 数据为空
        if(empty($result['items']['item'])){
            $this->totalResults = 0;
            return array();
        }
        
        $this->totalResults = $result['total_results'];

        foreach ($result['items']['item'] as $value) {
            $iid[] = $value['iid'];
        }
        unset($result);
        
        if ($iid) {
            $data = array();
            $iids = array_chunk($iid, 15);unset($iid);
            foreach ($iids as $key => $value) {

                $count = 0;$suberrormsg = '';
                do {
                    $items = $shopService->items_list_get($value,$shop_id);
                    
                    if ($items === false) {
                        $suberrormsg = $shopService->get_err_msg();

                        if (false !== strpos($suberrormsg,'This ban will last for 1 more seconds') ) {
                            sleep(1);
                        }
                        
                    } else {
                        break;
                    }

                    $count++;
                } while ($count<3);


                if($items === false ){ 
                    $errormsg[] = '【'.implode('、',$value).'】'.$shopService->get_err_msg();
                    continue;
                }
                
                 if(empty($items['items']['item'])){
                    $errormsg[]  = '【'.implode('、',$value).'】不存在！';
                    continue;
                 }

                # 数据结构重组
                foreach ($items['items']['item'] as $item) {
                    if ($item['skus']['sku']) {
                        foreach ($item['skus']['sku'] as $k=>$sku) {
                            $shop_properties_name = '';
                            
                            //Ecstore店铺货品属性
                            if ($sku['properties']){
                                $shop_properties_name    = $sku['properties'];
                            }
                            
                            $item['skus']['sku'][$k]['properties_name'] = $shop_properties_name;
                        }
                    }

                    $data[] = array(
                        'outer_id' => $item['outer_id'] ? $item['outer_id'] : '',
                        'iid' => $item['num_iid'] ? $item['num_iid'] : '',
                        'title' => $item['title'] ? $item['title'] : '',
                        'approve_status' => $item['approve_status'] ? $item['approve_status'] : '',
                        'price' => $item['price'],
                        'num' => $item['num'],
                        'detail_url' => $item['detail_url'],
                        'default_img_url' => '',
                        'props' => $item['props'],
                        'simple' => $item['skus']['sku'] ? 'false' : 'true',
                        'skus' => $item['skus'] ? $item['skus'] : '',
                    );
                }
                
                usleep(10000);
            }
            unset($iids,$items);
            
            return $data;
        } else {
            $errormsg = '抓取数据为空！';
            return false;
        }
    }

    /**
     * 通过IID批量下载
     *
     * @return void
     * @author 
     **/
    public function downloadByIIds($iids,$shop_id,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        
        # 请求商品
        $result = $shopService->items_list_get($iids,$shop_id);
        if($result === false){
            $errormsg = '【'.implode('、',$iids).'】'.$shopService->get_err_msg();
            return false;
        }
        
        if(empty($result['items']['item']))
        {
            $this->totalResults = 0;
            return array();
        }
        
        foreach ($result['items']['item'] as $item) {
            if ($item['skus']['sku']) {
                foreach ($item['skus']['sku'] as $k=>$sku) {
                    $shop_properties_name = '';
                    
                    //Ecstore店铺货品属性
                    if ($sku['properties']){
                        $shop_properties_name    = $sku['properties'];
                    }
                    
                    $item['skus']['sku'][$k]['properties_name'] = $shop_properties_name;
                }
            }
            $data[] = array(
                'outer_id' => $item['outer_id'] ? $item['outer_id'] : '',
                'iid' => $item['num_iid'] ? $item['num_iid'] : '',
                'title' => $item['title'] ? $item['title'] : '',
                'approve_status' => $item['approve_status'] ? $item['approve_status'] : '',
                'price' => $item['price'],
                'num' => $item['num'],
                'skus' => $item['skus'] ? $item['skus'] : '',
                'simple' => $item['skus']['sku'] ? 'false' : 'true',
            );
        }

        return $data;
    }

    public function downloadByIId($iid,$shop_id,&$errormsg)
    {
        $data = parent::downloadByIId($iid,$shop_id,$errormsg);
        if ($data) {
            if ($data['skus']['sku']) {
                foreach ($data['skus']['sku'] as $k=>$sku) {
                    $shop_properties_name = '';
                    
                    //Ecstore店铺货品属性
                    if ($sku['properties']){
                        $shop_properties_name    = $sku['properties'];
                    }
                    
                    $data['skus']['sku'][$k]['properties_name'] = $shop_properties_name;
                }
            }
            $tmpData = array(
                'outer_id' => $data['outer_id'] ? $data['outer_id'] : '',
                'iid' => $data['iid'] ? $data['iid'] : '',
                'title' => $data['title'] ? $data['title'] : '',
                'approve_status' => $data['status'] ? $data['status'] : '',
                'price' => $data['price'],
                'num' => $data['num'],
                'detail_url' => $data['detail_url'],
                'default_img_url' => $data['default_img_url'],
                'props' => $data['props'],
                'simple' => $data['skus']['sku'] ? 'false' : 'true',
                'skus' => $data['skus'] ? $data['skus'] : '',
            );
            
            $data = $tmpData;
        }

        return $data;
    }
    
    /**
     * 通过SKU_ID下载,单个
     *
     * @param Array $sku SKU信息
     * @param String $shop_id 店铺ID
     * @param String $errormsg 错误信息
     * @return void
     * @author
     **/
    public function dowloadBySkuId($sku,$shop_id,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_skus');
        $result = $shopService->item_sku_get($sku,$shop_id);
        
        if ($result === false) {
            $errormsg = $shopService->get_err_msg();
            return false;
        }
        
        if ($result['data']['sku']){
            return $result['data']['sku'];
        } else if($result['sku']){
            return $result['sku'];
        }else{
            $errormsg = 'SKU不存在！';
            return array();
        }
    }
}