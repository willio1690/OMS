<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_autotask_timer_sysordertaking
{
    /**
     * @description 订单获取后台处理
     * @access public
     * @param void
     * @return void
     */
    public function process($params, &$error_msg='') 
    {
        // 验证是否开始系统自动获取订单
        $cfg_ordertaking = app::get('ome')->getConf('ome.order.is_auto_ordertaking');
        $cfg_combine     = app::get('ome')->getConf('ome.order.is_auto_combine');

        if ($cfg_ordertaking != 'true') { return true;}
        if ($cfg_combine == 'true') { return true;}

        if ('running' == cachecore::fetch('ome_autotask_sysordertaking')) return true;

        @ini_set('memory_limit','512M');

        cachecore::store('ome_autotask_sysordertaking', 'running',50);

        $batchMdl = app::get('ome')->model('batch_log');

        // 2小时之前未完成的任务重置掉
        $batchMdl->update(array ('status' => '1'), array ('log_type'=>'ordertaking','source'=>'direct','status'=>array('0','2'), 'createtime|sthan' => time()-600) );

        $count = $batchMdl->count(array('log_type'=>'ordertaking','source'=>'direct','status'=>array('0','2')));
        if ($count > 0) {
            return true;
        }

        $orderAuto = new omeauto_auto_combine();
        $orderGroup = $orderAuto->getBufferGroup();

        if (!$orderGroup) return true;

        $orderCnt = 0; $params = array ();
        foreach ($orderGroup as $key=>$group) {
            $orderCnt += $group['cnt'];

            list ($hash, $idx) = explode('||', $key);

            $params[] = array('idx' => $idx, 'hash' => $hash, 'orders' => explode(',', $group['orders']));
        }

        $batchLogModel = app::get('ome')->model('batch_log');
        $op = kernel::single('ome_func')->getDesktopUser();
        $batchLog = array(
            'createtime'   => time(),
            'op_id'        => $op['op_id'],
            'op_name'      => $op['op_name'],
            'batch_number' => $orderCnt,
            'succ_number'  => '0',
            'fail_number'  => '0',
            'status'       => '0',
            'log_type'     => 'ordertaking',
            'log_text'     => serialize($params),
        );
        $batchLogModel->save($batchLog);
        if (defined('SAAS_COMBINE_MQ') && SAAS_COMBINE_MQ == 'true') {
            foreach (array_chunk($params, 5) as $param) {
                $push_params = array(
                    'orderidx'  => json_encode($param),
                    'task_type' => 'ordertaking',
                    'log_id'    => $batchLog['log_id'],
                    'uniqid'    => 'combine_'.$batchLog['log_id'],
                );
                taskmgr_func::multiQueue($GLOBALS['_MQ_COMBINE_CONFIG'], 'TG_COMBINE_EXCHANGE', 'TG_COMBINE_QUEUE','tg.order.combine.*',$push_params);
            }
        } else {
           foreach (array_chunk($params, 5) as $param) {
               $push_params = array(
                   'data' => array(
                       'orderidx'  => json_encode($param),
                       'log_id'    => $batchLog['log_id'],
                       'task_type' => 'ordertaking'
                   ),
                   'url' => kernel::openapi_url('openapi.autotask','service')
               );
               kernel::single('taskmgr_interface_connecter')->push($push_params);
           }

       }
        cachecore::store('ome_autotask_sysordertaking', '',1);

        return  true;
    }
}
