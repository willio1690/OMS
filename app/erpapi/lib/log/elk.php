<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_log_elk{

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function write_log($logsdf,$step='request')
    {
        $message = array(
            'spendtime'   => $logsdf['spendtime'],
            'title'       => $logsdf['title'],
            'method'      => $logsdf['method'],
            'original_bn' => $logsdf['original_bn'],
            'msg_id'      => $logsdf['msg_id'],
            'status'      => $logsdf['status'],
            'createtime'  => $logsdf['createtime'],
            'step'        => $step,
            'node_id'     => base_shopnode::node_id('ome'),
            'domain'      => $_SERVER['HTTP_HOST'],
            'type'        => 'api',
            'data'        => json_encode($logsdf['data']),
        );

        $message = json_encode($message);

       if (defined('API_RAKAFKA_SERVER') && constant('API_RAKAFKA_SERVER')) {
            $topic = defined('API_RAKAFKA_TOPIC') && constant('API_RAKAFKA_TOPIC') ? constant('API_RAKAFKA_TOPIC') : 'erp';

            kernel::single('base_queue_rdkafka')->set_server(API_RAKAFKA_SERVER)->publish($message,$topic);
       }
    }
}
