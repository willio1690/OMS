<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 转储单推送
 *
 * @category
 * @package
 * @author yaokangming<yaokangming@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_sku360_request_stockdump extends erpapi_wms_request_stockdump
{
    protected function _format_stockdump_create_params($sdf)
    {
        $data['orders'][0]['order_code'] = $sdf['stockdump_bn'];

        $items = array();
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                $items[] = array(
                    'product_code'     => $v['bn'],
                    'qty' => $v['num'],
                );
            }
        }
        $data['orders'][0]['skus'] = $items;
        $params['data'] = json_encode($data);
        return $params;
    }

    /**
     * 转储单创建
     *
     * @return void
     * @author
     **/

    public function stockdump_create($sdf){
        return $this->error('对方不支持转储单', 500);
    }

    /**
     * 转储单取消
     *
     * @return void
     * @author
     **/
    public function stockdump_cancel($sdf){
        return $this->succ('取消成功');
    }
}