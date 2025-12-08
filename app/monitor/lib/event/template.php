<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/13
 * @Describe: 监控预警模板Lib类
 */
class monitor_event_template
{
    /**
     * 获取预警事件类型
     * @Author: xueding
     * @Vsersion: 2022/10/13 下午4:57
     * @return string[]
     */
    public function getEventType()
    {
        $eventType = array(
            // 'wms_bill_fail_warning'    => 'WMS单据请求失败报警', // 已经废弃
            // 'wms_delivery_consign'     => 'WMS发货失败报警',
            // 'wms_reship_finish'        => 'WMS退货失败报警',
            // 'wms_stockin_finish'       => 'WMS入库失败报警',
            // 'wms_stockout_finish'      => 'WMS出库失败报警',
            // 'wms_stock_change'         => 'WMS异动失败报警',
            // 'wms_stockprocess_confirm' => 'WMS加工单确认失败报警',
            // 'wms_transferorder_finish' => 'WMS移库失败报警',

            'process_undelivery'       => '未发货订单通知',

            'stock_sync'               => '平台库存同步失败报警',
            'under_safty_inventory'    => '低于安全库存报警',
            'stock_diff_alarm'         => '实物库存差异报警',

            'pos_stock_sync'           => 'POS库存同步失败报警',
            'pos_o2oundelivery'        => 'POS现货订单未发货报警',

            'system_message'           => '系统消息通知',
            'invoice_result_error'     => '发票处理失败报警',
            'order_360buy_delivery_error' => '【京东】订单挂起不可以发货',
            'store_freeze_abnormal'    => '库存或冻结消费异常报警',
            'rpc_warning'              => 'RPC调用失败报警',
            'store_freeze_abnormal'    => '库存或冻结消费异常报警',
            'inventory_calc_error'     => '库存计算异常报警',
            // 新增事件类型
            'delivery_cancel_success'  => '发货单取消成功通知',
            'reship_cancel_success'    => '退货单取消成功通知',
            'sap_sync_error'            => 'SAP同步异常报警',
        );
        return $eventType;
    }

    /**
     * 获取各事件类型的默认模板内容
     * 内容格式参考 initial/monitor.event_template.sql
     * @return array<string,string>
     */
    public function getEventDefaultContent()
    {
        return array(
            'rpc_warning' => <<<TPL
RPC调用失败报警
    >业务：<font color="warning">{title}</font>
    >单据：<font color="warning">{bill_bn}</font>
    >接口名：<font color="warning">{method}</font>
    >错误信息：<font color="warning">{errmsg}</font>
TPL
,
            'pos_stock_sync' => <<<TPL
POS库存同步失败通知
    >门店：<font color="warning">{store_bn}</font>
    >仓库：<font color="warning">{branch_bn}</font>
    >PageNo：<font color="warning">{page_no}</font>
    >错误信息：<font color="warning">{errmsg}</font>
TPL
,
            'pos_o2oundelivery' => <<<TPL
POS现货订单未发货报警
>现货未发货订单号为：<font color="warning">{order_bns}</font>
TPL
,
            'process_undelivery' => <<<TPL
{content}
TPL
,
            'under_safty_inventory' => <<<TPL
<{仓库名称：<font color="warning">{branch_name}</font>，商品编码：<font color="warning">{bn}</font>，商品名称：<font color="warning">{goods_name}</font>，库存数量：<font color="warning">{store}</font>，安全库存：<font color="warning">{safe_store}</font>}> 
TPL
,
            'stock_diff_alarm' => <<<TPL
实物库存差异报警
    >日期：<font color="warning">{stock_date}</font>
    >渠道：<font color="warning">{channel_bn}</font>
    >仓库：<font color="warning">{warehouse_code}</font>
    >错误信息：<font color="warning">{errmsg}</font>
TPL
,
            'stock_sync' => <<<TPL
平台库存同步失败报警
    >日期：<font color="warning">{stock_date}</font>
    >错误信息：<font color="warning">{errmsg}</font>
TPL
,
            'system_message' => <<<TPL
系统消息通知
    >消息内容：<font color="warning">{errmsg}</font>
TPL
,
            'invoice_result_error' => <<<TPL
发票处理失败报警
    >发票操作类型：<font color="warning">{invoice_type}</font>
    >订单号：<font color="warning">{order_bn}</font>
    >错误信息：<font color="warning">{errmsg}</font>
TPL
,
            'store_freeze_abnormal' => <<<TPL
**仓库存冻结差异报警**
    >{errmsg}
TPL
,
            'rpc_accountorders' => <<<TPL
实销实结未同步报警
>未同步订单号为：<font color="warning">{order_bns}</font>
TPL
,
            'order_360buy_delivery_error' => <<<TPL
以下订单挂起不可以发货：
    >订单号：<font color="warning">{order_bn}</font>
TPL
,
            'inventory_calc_error' => <<<TPL
库存计算异常报警
    >时间：<font color="warning">{datetime}</font>
    >商品编码：<font color="warning">{product_bn}</font>
    >店铺ID：<font color="warning">{shop_id}</font>
    >店铺名称：<font color="warning">{shop_name}</font>
    >异常信息：<font color="warning">{error_message}</font>
    >异常位置：<font color="warning">{error_location}</font>
TPL
,
            'sap_sync_error' => <<<TPL
SAP同步异常报警
    >业务：<font color="warning">{title}</font>
    >单据：<font color="warning">{original_bn}</font>
    >同步时间：<font color="warning">{sync_time}</font>
    >同步状态：<font color="warning">{sync_status}</font>
    >接口名：<font color="warning">{method}</font>
    >错误信息：<font color="warning">{errmsg}</font>
TPL
,
        );
    }
}
