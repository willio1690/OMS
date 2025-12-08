<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_purchase_order
{
    /**
     * 下载采购订单列表
     * @param array $filter 条件
     * @param string $shop_id
     * @return array
     */
    public function getPullList($filter, $shop_id)
    {
        $page = 1;
        $limit = 50;
        
        // 分页循环查询
        do {
            
            if ($page > 100) {
                break;
            }

            $filter['limit'] = $limit;
            $filter['page'] = $page;

            list($result, $msg, $is_finished) = $this->getPoList($filter, $shop_id);

            if ($is_finished) {
                return [true];
            }
            
            if ($result == false) {
                return [false, $msg];
            }

            $page++;
            
        } while (true);

        
        return [true];
    }


    /**
     * 获取PoList
     * @param mixed $params 参数
     * @param mixed $shop_id ID
     * @return mixed 返回结果
     */
    public function getPoList($params, $shop_id)
    {
        $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getPoList($params);

        if ($rsp_data['rsp'] != 'succ') {
            return [false, $rsp_data['err_msg']];
        }

        if($rsp_data['is_empty']) {
            return [true, '未获取到采购单数据', 1];
        }

        if (!$rsp_data['is_empty'] && !$rsp_data['po_nos']) {
            return [true, '采购单可拣货数量为0', 1];
        }

        return kernel::single('vop_purchase_pick')->getPullList($rsp_data['po_nos'], $shop_id);
    }
}
