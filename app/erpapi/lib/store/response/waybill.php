<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_waybill extends erpapi_store_response_abstract
{
    
    /**
     * 获取
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get($params){

        if (!$params['logi_code']) {
            $this->__apilog['result']['msg'] = '缺少物流编码';

            return false;
        }

        $corp = app::get('ome')->model('dly_corp')->db_dump(array('type' => $params['logi_code'],'tmpl_type'=>'electron'), 'corp_id,name,tmpl_type,channel_id,type');
        if (!$corp) {
            $this->__apilog['result']['msg'] = '配货失败：物流公司编码不存在';

            return false;
        }
        $data = array(

            'package_id'    =>  $params['package_id'],
            'package_type'  =>  $params['package_type'],
            'logi_code'     =>  $params['logi_code'],
            'logi_name'     =>  $params['logi_name'],
            'corp'          =>  $corp,

        );

        $package_type = $params['package_type'];

        if (!in_array($package_type,array('delivery','iostock'))){
            $this->__apilog['result']['msg'] = '业务类型'.$package_type.',无法识别';
            return false;
        }
        if ($package_type == 'delivery'){

            $deliveryMdl = app::get('ome')->model('delivery');
            $delivery = $deliveryMdl->dump(array('delivery_bn'=>$params['package_id'],'parent_id'=>0,'status'=>array('ready','progress')));

            if(!$delivery){
                $this->__apilog['result']['msg'] = sprintf('[%s]不存在', $params['package_id']);

                return false;
            }

            $data['delivery_bn'] = $params['package_id'];
        }
        if ($package_type == 'iostock'){
            $isoMdl = app::get('taoguaniostockorder')->model('iso');

            $iso = $isoMdl->db_dump(array('iso_bn' => $params['package_id']), 'iso_id,iso_bn,check_status,iso_status,type_id');

            if (!$iso) {
                $this->__apilog['result']['msg'] = sprintf('[%s]不存在', $params['package_id']);

                return false;
            }
        }

        return $data;
    }
    
}

?>