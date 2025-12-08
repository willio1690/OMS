<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ShopEX License
 *
 * @author dqiujing@gmail.com
 * @version ocs
 */

return array(
    'loop' => array(
        'minite' => array(
            array(
                'title' => '按店铺生成实时请求交易记录队列',
                'queue' => 'slow',
                'worker' => 'trade_search_queue@finance_cronjob_tradeScript'
            ),
            array(
                'title' => '获取交易任务号结果',
                'queue' => 'slow',
                'worker' => 'get_taskid_result@finance_cronjob_tradeScript'
            ),
            array(
                'title' => '按任务日志表生成自动重试请求队列',
                'queue' => 'slow',
                'worker' => 'autoretry@finance_cronjob_tradeScript'
            ),
            array(
                'title' => '按店铺生成获取交易任务号队列',
                'queue' => 'slow',
                'worker' => 'taskid_queue@finance_cronjob_tradeScript'
            ),
            array(
                'title' => '定时拉销售数据',
                'queue' => 'slow',
                'worker' => 'get_sales@finance_cronjob_tradeScript',
            ),
        ),
        'hour' => array(
            array(
                'title' => '账单自动核销队列',
                'queue' => 'slow',
                'worker' => 'autoflag_queue@finance_cronjob_autoflagScript'
            )
        )
    )
);
