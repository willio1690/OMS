<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class eccommon_platform_address{

    /**
     * sync
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function sync($data){
        $shop_id = $data['shop_id'];

        $params = array(

        );
        $rs = kernel::single('erpapi_router_request')->set('shop', $shop_id)->branch_getProvince($params);

        if($rs['rsp'] == 'succ' && $rs['data']){
            $this->save($rs['data']);
        }
    }


    /**
     * 保存
     * @param mixed $data 数据
     * @return mixed 返回操作结果
     */
    public function save($data){

        $regionsObj = app::get('eccommon')->model('platform_regions');
        foreach($data as $v){
            $province_id    = $v['province_id'];
            $province       = $v['province'];

            $regions = $regionsObj->dump(array('shop_type'=>$shop_type,'province_id'=>$province_id),'id,mapping');
            if($regions){
               if($regions['mapping'] == '0'){
                    $local_regions = $this->getLocalRegion($province);
                    if($local_regions){
                        $update_data = array(
                            'mapping'        =>  '1',
                            'local_region_id'=>$local_regions['region_id'],
                        );
                        $regionsObj->update($update_data,array('id'=>$regions['id']));
                    }
               }
            }else{
                $local_regions = $this->getLocalRegion($province);
                $insert_data = array(

                    'province_id'   =>  $province_id,
                    'province'      =>  $province,
                    'shop_type'     =>  $shop_type,

                );
                if($local_regions){
                    $insert_data['local_region_id'] = $local_regions['region_id'];
                    $insert_data['mapping'] = '1';
                }

                $regionsObj->save($insert_data);

            }
        }

    }


   

}


?>