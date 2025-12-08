INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('rpc_warning', 'RPC调用失败报警', 'rpc_warning', 'email', 'RPC调用失败报警
    >业务：<font color=\"warning\">{title}</font>
    >单据：<font color=\"warning\">{bill_bn}</font>
    >接口名：<font color=\"warning\">{method}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-08-13 00:00:00', '2024-08-13 00:00:00');


INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('wms_consign_notify', 'WMS发货失败报警', 'wms_delivery_consign', 'email', 'WMS发货失败报警
    >发货单：<font color=\"warning\">{delivery_bn}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('wms_reship_notify', 'WMS退货失败报警', 'wms_reship_finish', 'email', 'WMS退货失败报警
    >退货单：<font color=\"warning\">{reship_bn}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('wms_stockchange_notify', 'WMS异动失败报警', 'wms_stock_change', 'email', 'WMS异动失败报警
    >单据：<font color=\"warning\">{order_code}</font>
    >类型：<font color=\"warning\">{order_type}</font>
    >批次：<font color=\"warning\">{batch_code}</font>
    >仓库：<font color=\"warning\">{warehouse}</font>
    >物料：<font color=\"warning\">{product_bn}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('wms_stockin_notify', 'WMS入库失败报警', 'wms_stockin_finish', 'email', 'WMS入库失败报警
    >入库单：<font color=\"warning\">{io_bn}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('wms_stockout_notify', 'WMS出库失败报警', 'wms_stockout_finish', 'email', 'WMS出库失败报警
    >入库单：<font color=\"warning\">{io_bn}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('wms_stockprocess_notify', 'WMS加工单确认失败报警', 'wms_stockprocess_confirm', 'email', 'WMS加工单确认失败报警
    >加工单：<font color=\"warning\">{mp_bn}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('wms_transferorder_notify', 'WMS移库失败报警', 'wms_transferorder_finish', 'email', 'WMS移库失败报警
    >移库单：<font color=\"warning\">{stockdump_bn}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('pos_stock_notify', 'POS库存同步通知', 'pos_stock_sync', 'email', 'POS库存同步失败通知
    >门店：<font color=\"warning\">{store_bn}</font>
    >仓库：<font color=\"warning\">{branch_bn}</font>
    >PageNo：<font color=\"warning\">{page_no}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('pos_o2oundelivery_notify', 'POS现货订单未发货报警', 'pos_o2oundelivery', 'email', 'POS现货订单未发货报警
>现货未发货订单号为：<font color=\"warning\">{order_bns}</font>', '1', 'system', '2024-03-07 00:00:00',
        '2024-03-07 00:00:00');

INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('process_undelivery_notify', '未发货订单通知', 'process_undelivery', 'email', '{content}', '1', 'system',
        '2024-03-07 00:00:00', '2024-03-07 00:00:00');


INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('under_safty_inventory_notify', '低于安全库存报警', 'under_safty_inventory', 'email',
        '<{仓库名称：<font color=\"warning\">{branch_name}</font>，商品编码：<font color=\"warning\">{bn}</font>，商品名称：<font color=\"warning\">{goods_name}</font>，库存数量：<font color=\"warning\">{store}</font>，安全库存：<font color=\"warning\">{safe_store}</font>}>',
        '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('stock_diff_alarm', '实物库存差异报警', 'stock_diff_alarm', 'email', '实物库存差异报警
    >日期：<font color=\"warning\">{stock_date}</font>
    >渠道：<font color=\"warning\">{channel_bn}</font>
    >仓库：<font color=\"warning\">{warehouse_code}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('stock_sync_notify', '平台库存同步失败报警', 'stock_sync', 'email', '平台库存同步失败报警
    >日期：<font color=\"warning\">{stock_date}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('system_message_notify', '系统消息通知', 'system_message', 'email', '系统消息通知
    >消息内容：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-03-07 00:00:00', '2024-03-07 00:00:00');

-- 新增发货单取消成功通知模板
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('delivery_cancel_success_notify', '发货单取消成功通知', 'delivery_cancel_success', 'email', '发货单取消成功通知
    >发货单号：<font color=\"warning\">{delivery_bn}</font>
    >仓库：<font color=\"warning\">{branch_name}</font>
    >取消时间：<font color=\"warning\">{cancel_time}</font>
    >原因：<font color=\"warning\">{memo}</font>', '1', 'system', '2024-12-19 00:00:00', '2024-12-19 00:00:00');

-- 新增退货单取消成功通知模板
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('reship_cancel_success_notify', '退货单取消成功通知', 'reship_cancel_success', 'email', '退货单取消成功通知
    >退货单号：<font color=\"warning\">{reship_bn}</font>
    >仓库：<font color=\"warning\">{branch_name}</font>
    >取消时间：<font color=\"warning\">{cancel_time}</font>
    >原因：<font color=\"warning\">{memo}</font>', '1', 'system', '2024-12-19 00:00:00', '2024-12-19 00:00:00');
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`,
                                         `source`, `at_time`, `up_time`)
VALUES ('invoice_result_error_notify', '发票处理失败报警', 'invoice_result_error', 'workwx', '发票处理失败报警
    >发票操作类型：<font color=\"warning\">{invoice_type}</font>
    >订单号：<font color=\"warning\">{order_bn}</font>
    >错误信息：<font color=\"warning\">{errmsg}</font>', '1', 'system', '2024-05-08 10:22:23', '2024-05-08 10:22:23');

INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('store_freeze_abnormal', '库存或冻结消费异常报警', 'store_freeze_abnormal', 'workwx', '**仓库存冻结差异报警**
    >{errmsg}', '1', 'system', '2024-10-11 00:00:00', '2024-10-11 00:00:00');

INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('process_accountorders_notify', '实销实结未同步报警', 'rpc_accountorders', 'email', '实销实结未同步报警
>未同步订单号为：<font color=\"warning\">{order_bns}</font>', '1', 'system', '2025-05-09 00:00:00',
        '2025-05-09 00:00:00');

INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`,
                                         `source`, `at_time`, `up_time`)
VALUES ('order_360buy_delivery_error', '【京东】订单挂起不可以发货', 'order_360buy_delivery_error', 'workwx', '以下订单挂起不可以发货：
    >订单号：<font color=\"warning\">{order_bn}</font>', '1', 'system', '2024-05-08 10:22:23', '2024-05-08 10:22:23');

-- 库存回写监控模板
INSERT INTO `sdb_monitor_event_template`(`template_bn`, `template_name`, `event_type`, `send_type`, `content`, `status`, `source`, `at_time`, `up_time`)
VALUES ('inventory_calc_error', '库存计算异常报警', 'inventory_calc_error', 'email', '库存计算异常报警
    >时间：<font color=\"warning\">{datetime}</font>
    >商品编码：<font color=\"warning\">{product_bn}</font>
    >店铺ID：<font color=\"warning\">{shop_id}</font>
    >店铺名称：<font color=\"warning\">{shop_name}</font>
    >异常信息：<font color=\"warning\">{error_message}</font>
    >异常位置：<font color=\"warning\">{error_location}</font>
    >规则信息：<font color=\"warning\">{regulation_info}</font>', '1', 'system', '2024-12-19 00:00:00', '2024-12-19 00:00:00');
