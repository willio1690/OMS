<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 淘宝商品处理
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class tbo2o_shop_service_taobao extends tbo2o_shop_service_common
{
    public $approve_status = array(
            array('filter'=>array('approve_status'=>'onsale'),'name'=>'全部','flag'=>'onsale','alias'=>'在架'),
    );

    function __construct(&$app)
    {
        $this->app = $app;
    }

    /**
     * 下载全部
     *
     * @return void
     * @author 
     **/
    public function downloadList($filter, $shop_id, $offset=0, $limit=200, &$errormsg)
    {
        $shopService = kernel::single('tbo2o_shop_request_items');
        $count       = 0;
        
        do
        {
            if ($count>60) {
                $errormsg = '超出最大循环次数';
                return false;
            }
            usleep(1000000);
            $result = $shopService->items_all_get($filter,$shop_id,$offset,$limit);
            if ($result === false) {
                $errormsg = $shopService->get_err_msg();
                return false;
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
        
        $this->totalResults = $result['totalResults'];

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
                            if ($sku['properties_name']) {
                                $properties = explode(';', $sku['properties_name']);
                                foreach ($properties as $p) {
                                    list($pid,$vid,$pid_name,$vid_name) = explode(':', $p);
                                    $shop_properties_name .= $pid_name.':'.$vid_name.';';
                                }
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
}