<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2019/4/22
 * @describe 待寻仓订单
 */
class erpapi_shop_response_process_branch {
    
    /**
     * wait
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function wait($params) {
        $rsp = array('rsp'=>'succ', 'msg'=>'处理成功');

        if($params['order_wait']) {
            if($params['status'] == $params['order_wait']['status']) {
                $rsp['msg'] = '状态一致不需更新';
                return $rsp;
            }
            $owId = $params['order_wait']['ow_id'];
            $upData = array(
                'status' => $params['status'],
                'last_modified' => $params['last_modified']
            );
            if($params['status_remark']) {
                $statusRemark = $params['order_wait']['status_remark'] . ',' . $params['status'] . ':' . $params['status_remark'];
                $upData['status_remark'] = $statusRemark;
            }
            app::get('purchase')->model('order_wait')->update($upData, array('ow_id'=>$owId));
            if($upData == 'JIT') {
                kernel::single('purchase_branch_freeze')->delete($owId);
            }
            $operateLog = app::get('ome')->model('operation_log');
            $operateLog->write_log('purchase_order_wait@purchase',$owId,'更新成功：' . $upData['status']);
        } else {
            $items = $params['items']; unset($params['items']);

            $params['status_remark'] = $params['status'] . ':' . $params['status_remark'];
            $params['create_time']   = time();

            $owId = app::get('purchase')->model('order_wait')->insert($params);

            if (!$owId) return array('rsp'=>'fail', 'msg'=>'新建失败');

            foreach ($items as $val) {
                $val['ow_id'] = $owId;
                app::get('purchase')->model('order_wait_items')->insert($val);
            }

            $operateLog = app::get('ome')->model('operation_log');
            $operateLog->write_log('purchase_order_wait@purchase',$owId,'新建成功');
        }

        kernel::single('purchase_branch')->feedbackDelivery($owId);

        return $rsp;
    }
}