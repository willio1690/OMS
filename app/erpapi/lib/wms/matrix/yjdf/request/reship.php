<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 退换货单推送
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_wms_matrix_yjdf_request_reship extends erpapi_wms_request_reship
{
    private $_platform_reason = [
           '收到商品少件 / 错件 / 空包裹' => array(
                    'jd_reason_code'=>'6',
                    'jd_reason_name'=>'少/错商品',
            ),
            '少件／漏发' => array(
                    'jd_reason_code'=>'6',
                    'jd_reason_name'=>'少/错商品',
            ),
            '功能故障' => array(
                    'jd_reason_code'=>'193',
                    'jd_reason_name'=>'质量问题',
            ),
            '商家发错货' => array(
                    'jd_reason_code'=>'7',
                    'jd_reason_name'=>'发错货',
            ),
            '不喜欢 / 效果不好' => array(
                    'jd_reason_code'=>'205',
                    'jd_reason_name'=>'不合适/不满意',
            ),
            '做工粗糙 / 有瑕疵 / 有污渍' => array(
                    'jd_reason_code'=>'5',
                    'jd_reason_name'=>'商品损坏/包装脏污',
            ),
            '商品材质 / 品牌 / 外观等描述不符' => array(
                    'jd_reason_code'=>'8',
                    'jd_reason_name'=>'商品与页面描述不符',
            ),
            '生产日期 / 保质期 / 规格等描述不符' => array(
                    'jd_reason_code'=>'8',
                    'jd_reason_name'=>'商品与页面描述不符',
            ),
            '大小／尺寸／重量与商品描述不符' => array(
                    'jd_reason_code'=>'8',
                    'jd_reason_name'=>'商品与页面描述不符',
            ),
            '品种／规格／成分等描述不符' => array(
                    'jd_reason_code'=>'8',
                    'jd_reason_name'=>'商品与页面描述不符',
            ),
            '品种／产品／规格／成分等描述不符' => array(
                    'jd_reason_code'=>'8',
                    'jd_reason_name'=>'商品与页面描述不符',
            ),
            '规格等描述不符' => array(
                    'jd_reason_code'=>'8',
                    'jd_reason_name'=>'商品与页面描述不符',
            ),
            '其他' => array(
                    'jd_reason_code'=>'9',
                    'jd_reason_name'=>'其他',
            ),

    ];

    /**
     * 通知WMS创建退货单
     * 
     * @param array $sdf
     * @return array
     * */

    public function reship_create($sdf)
    {
        $reshipObj = app::get('ome')->model('reship');
        $queueObj = app::get('base')->model('queue');
        
        $reshipLib = kernel::single('ome_reship');
        
        $order_id = $sdf['order_id'];
        $order_bn = $sdf['order_bn'];
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $msgcode = '';
        
        // 判断是否已被删除
        $iscancel = kernel::single('console_service_commonstock')->iscancel($reship_bn);
        if ($iscancel) {
            return $this->succ('退货单已取消,终止同步');
        }
        
        //渠道ID(仓库上绑定的渠道ID)
        $wms_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        
        //扩展信息
        $extend_info = array();
        if($sdf['extend_info']){
            $extend_info = json_decode($sdf['extend_info'], true);
        }
        
        //京标四级地址转换
        $area_info = array();
        if($this->__channelObj->wms['crop_config']['address_type'] == 'j'){
            $area_info = $extend_info['area_info'];
        }
        
        //获取退货包裹明细
        $sql = "SELECT a.*, b.item_id,b.product_id,b.bn,b.outer_sku,b.return_nums,b.is_wms_gift,b.product_name,b.wms_order_code FROM sdb_ome_reship_package AS a ";
        $sql .= " LEFT JOIN sdb_ome_reship_package_items AS b ON a.package_id=b.package_id WHERE a.reship_id=". $reship_id;
        
        $tempList = $reshipObj->db->select($sql);
        if(empty($tempList)){
            $error_msg = '没有需要退货的包裹';
            return $this->error($error_msg, $msgcode);
        }
        
        //组织数据
        $packageNums = 0;
        $returnPackages = array();
        foreach ($tempList as $key => $val)
        {
            //退货单是否关联多个京东订单号
            $packageNums++;
            
            //已经推送成功,则跳过
            if($val['sync_status'] == 'succ'){
                continue;
            }
            
            //渠道ID(优先使用商品关联的渠道ID)
            $wms_channel_id = ($val['wms_channel_id'] ? $val['wms_channel_id'] : $wms_channel_id);
            
            //京东订单号(发货包裹单号)
            $wms_package_bn = $val['wms_package_bn'];
            
            //master
            if(empty($returnPackages[$wms_package_bn])){
                $returnPackages[$wms_package_bn] = array(
                        'order_id' => $order_id,
                        'order_bn' => $order_bn,
                        'reship_id' => $reship_id,
                        'reship_bn' => $reship_bn,
                        'return_type' => $sdf['return_type'], //退货类型(return:退货,change:换货)
                        'wms_channel_id' => $wms_channel_id, //渠道ID
                        'wms_package_id' => $val['wms_package_id'],
                        'wms_package_bn' => $wms_package_bn, //京东订单号
                        'delivery_id' => $val['delivery_id'],
                        'delivery_bn' => $val['delivery_bn'],
                        'status' => $val['status'],
                        'wms_order_code' => $val['wms_order_code'],
                        'reason_id' => 0, //售后申请原因ID
                        'problem_name' => $sdf['problem_name'], //售后申请问题描述文字
                        'original_delivery_bn' => $sdf['original_delivery_bn'],
                );
                
                // 查询开普勒售后原因
                $sku_reason = $this->reship_resaon([
                    'service_bn'        =>  $wms_package_bn,
                    'bn'                =>  $val['outer_sku'],
                    'warehouse_code'    =>  $wms_channel_id,
                    'reship_bn'         => $reship_bn,
                    'reship_type'       => $sdf['return_type'],
                    'problem_name'      => $sdf['problem_name'],
                ]);
                
                if ($sku_reason['rsp'] == 'succ' && $sku_reason['data']) {
                    $jd_reason = $this->_platform_reason[$sdf['problem_name']];
                    $jd_reason['jd_reason_name'] = $jd_reason['jd_reason_name'] ? $jd_reason['jd_reason_name'] : $sdf['problem_name'];

                    $returnPackages[$wms_package_bn]['reason_id'] = $jd_reason['jd_reason_code'];
                    if ($sku_reason['data'][$jd_reason['jd_reason_name']]) {
                        $returnPackages[$wms_package_bn]['reason_id'] = $sku_reason['data'][$jd_reason['jd_reason_name']]['applyReasonId'];
                    } else if ($sku_reason['data']['其他']) {
                        $returnPackages[$wms_package_bn]['reason_id'] = $sku_reason['data']['其他']['applyReasonId'];
                    }else{
                        $returnPackages[$wms_package_bn]['reason_id'] = reset($sku_reason['data'])['applyReasonId'];
                    }
                }
                if($sdf['apply_remark']){
                    $returnPackages[$wms_package_bn]['problem_name'] = '系统匹配售后原因：' . $returnPackages[$wms_package_bn]['problem_name'] .','. $sdf['apply_remark'];
                }
            }
            
            //items
            $returnPackages[$wms_package_bn]['items'][] = array(
                    'item_id' => $val['item_id'],
                    'product_id' => $val['product_id'],
                    'bn' => $val['bn'],
                    'outer_sku' => $val['outer_sku'], //京东skuId
                    'return_nums' => $val['return_nums'], //退货数量
                    'is_wms_gift' => $val['is_wms_gift'], //是否WMS赠品
                    'product_name' => $val['product_name'],
                    'wms_order_code' => $val['wms_order_code'], //售后申请单号
            );
        }
        
        //按发货包裹号进行创建退货单
        $succList = array();
        foreach ($returnPackages as $key => $packageInfo)
        {
            $package_bn = $packageInfo['wms_package_bn'];
            
            //request
            $res = $this->reship_package_create($packageInfo, $packageNums);
            if($res['rsp'] != 'succ'){
                //错误码
                $error_code = ($res['error_code'] ? $res['error_code'] : $res['msg_code']);
                $error_code = str_replace(array('"', "'"), '', $error_code);
                
                //错误信息
                $error_msg = ($res['err_msg'] ? $res['err_msg'] : $res['msg']);
                $error_msg = strip_tags($error_msg);
                $error_msg = str_replace(array("\r\n", "\r", "\n"), "", $error_msg);
                
                $sql = "UPDATE sdb_ome_reship SET is_check='2',sync_status='2',sync_code='". $error_code ."',sync_msg='". $error_msg ."'";
                
                //部分创建失败时,设置为异常
                if($succList && !in_array($package_bn, $succList)){
                    $abnormal_status = ome_constants_reship_abnormal::__CREATE_CODE;
                    $sql .= ",abnormal_status=abnormal_status | ". $abnormal_status;
                }
                
                //更新sync同步WMS错误码
                $sql .= " WHERE reship_id=".$reship_id;
                $reshipObj->db->exec($sql);
                
                //[兼容]厂直订单请求失败,需要OMS先请求妥投签收接口
                if(strpos($res['msg'], '厂直订单')!== false || strpos($res['msg'], '未完成')!== false){
                    //放入queue队列中执行
                    $queueData = array(
                            'queue_title' => '订单号：'. $order_bn .'自动妥投并重新通知创建退货单',
                            'start_time' => time(),
                            'params' => array(
                                    'sdfdata' => array('reship_id'=>$reship_id, 'order_id'=>$order_id),
                                    'app' => 'oms',
                                    'mdl' => 'reship',
                            ),
                            'worker' => 'ome_reship_kepler.autoSignWmsDelivery',
                    );
                    $queueObj->save($queueData);
                }
                
                return $this->error($error_msg, $msgcode);
            }
            
            //回传成功的京东单号
            $succList[] = $package_bn;
        }
        
        //全部推送成功后
        if($succList){
            //清除异常:创建退货服务单失败
            $abnormal_status = ome_constants_reship_abnormal::__CREATE_CODE;
            $sql = "UPDATE sdb_ome_reship SET abnormal_status=abnormal_status ^ ".$abnormal_status;
            $sql .= " WHERE reship_id=".$reship_id." AND (abnormal_status & ".$abnormal_status." = ".$abnormal_status.")";
            $reshipObj->db->exec($sql);
        }
        
        //回传抖音平台同意退货状态
        $is_auto_approve = app::get('ome')->getConf('aftersale.auto_approve');
        $auto_confirm = app::get('ome')->getConf('return.auto_confirm');
        if($is_auto_approve == 'on' && $auto_confirm != 'on') {
            //放入queue队列中执行
            $queueData = array(
                    'queue_title' => '退货单号：'. $reship_bn .'回传平台同意退货状态',
                    'start_time' => time(),
                    'params' => array(
                            'sdfdata' => array('reship_id'=>$reship_id, 'order_id'=>$order_id),
                            'app' => 'oms',
                            'mdl' => 'reship',
                    ),
                    'worker' => 'ome_reship_luban.syncAfterSaleStatus',
            );
            $queueObj->save($queueData);
        }
        
        return $this->succ('退货单推送创建成功');
    }
    
    /**
     * 逐个京东包裹进行创建售后申请单
     * 
     * @param array $sdf
     * @return array
     */
    protected function reship_package_create($sdf, $packageNums=0)
    {
        $operLogObj = app::get('ome')->model('operation_log');
        $rePackageObj = app::get('ome')->model('reship_package');
        
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $package_bn = $sdf['wms_package_bn'];
        $gateway = ''; //判断是否加密
        
        //有多个京东订单号,需要保持唯一性
        $channel_refund_id = $reship_bn;
        
        /***
         * 场景：有多个货号对应同一个京东订单号的场景,无法保持唯一性。
         * 
        if($packageNums > 1){
            $channel_refund_id = $reship_bn.'_'.$package_bn;
        }
        ***/
        
        //title
        $title = $this->__channelObj->wms['channel_name'] . '退货单添加[京东订单号：'. $package_bn .']';
        
        //渠道ID
        $wms_channel_id = $sdf['wms_channel_id'];
        
        //售后类型
        $reship_type = ($sdf['return_type']=='change' ? 'HHRK' : 'THRK');
        
        //params
        $params = array(
                'warehouse_code' => $wms_channel_id, //渠道ID
                'trade_code' => $package_bn, //京东订单号(发货包裹单号)
                'order_code' => $sdf['order_bn'], //订单号
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                'order_type' => $reship_type, //THRK：退货入库单   HHRK：换货入库
                'reason_id' => $sdf['reason_id'], //售后申请原因ID
                'return_reason' => $sdf['problem_name'], //售后申请问题描述文字
                'pics' => '',
                'channel_refund_id' => $channel_refund_id, //退货单号:渠道售后服务单申请单号(需要保持唯一性)
        );
        
        //按照京东订单明细进行回传(一个SKU传一次)
        foreach ($sdf['items'] as $itemKey => $itemVal)
        {
            $package_item_id = $itemVal['item_id'];
            $outer_sku = $itemVal['outer_sku'];
            
            //已经有售后申请单,则跳过
            if($itemVal['wms_order_code']){
                continue;
            }
            
            //[兼容]京东退货channelAfsApplyId字段是唯一性的，所以退赠品时,退货单号加上赠品货号
            if($itemVal['is_wms_gift'] == 'true'){
                $params['channel_refund_id'] = $reship_bn .'_gift_'. $outer_sku;
            }else{
                $params['channel_refund_id'] = $reship_bn .'_'. $outer_sku;
            }
            
            //商品类型
            $sku_type = ($itemVal['is_wms_gift']=='true' ? '20' : '10');
            
            //items
            $items = array();
            $items['item'][] = array(
                    'item_id' => $outer_sku, //京东skuId
                    'item_name' => $itemVal['product_name'], //物料名称
                    'item_quantity' => $itemVal['return_nums'], //退货数量
                    'item_type' => $sku_type, //商品类型(10主品、20赠品)
            );
            
            $params['items'] = json_encode($items);
            
            //title
            $log_title = $title .'-bn:'. $outer_sku;
            
            //request
            $callback = array();
            $result = $this->__caller->call(WMS_RETURNORDER_CREATE, $params, $callback, $log_title, 10, $reship_bn, true, $gateway);
            if($result['rsp'] == 'succ'){
                //[兼容]格式化数据
                $wms_order_code = '';
                if(is_string($result['data'])){
                    $result['data'] = json_decode($result['data'], true);
                }
                
                //京东售后服务单申请单号
                $wms_order_code = $result['data']['wms_order_code']; //京东接口上,字段名为：afsApplyId
                
                //更新同步状态&&京东售后服务单申请单号
                $rePackageObj->update(array('wms_order_code'=>$wms_order_code, 'sync_status'=>'succ'), array('reship_id'=>$reship_id, 'wms_package_bn'=>$package_bn));
                
                //更新退货包裹明细的申请单号
                $rePackageObj->db->exec("UPDATE sdb_ome_reship_package_items SET wms_order_code='". $wms_order_code ."' WHERE item_id=".$package_item_id);
                
                //log
                $operLogObj->write_log('reship@ome', $reship_id, '京东订单号：'. $package_bn .',bn：'. $itemVal['outer_sku'] .',申请售后成功');
                
            }else{
                $error_msg = ($result['err_msg'] ? $result['err_msg'] : $result['msg']);
                $error_msg .= '[京东订单号：'. $package_bn .'],bn：'. $itemVal['outer_sku'];
                $error_msg = substr($error_msg, 0, 150); //截取150个字符
                
                //更新同步状态&&京东售后服务单申请单号
                $rePackageObj->update(array('sync_status'=>'fail'), array('reship_id'=>$reship_id, 'wms_package_bn'=>$package_bn));
                
                //log
                $error_code = $result['res']; //error_code错误编码
                $operLogObj->write_log('reship@ome', $reship_id, $error_msg);
                
                return $this->error($error_msg, $error_code);
            }
            
        }
        
        return $this->succ('退货单推送创建成功');
    }
    
    /**
     * 申请售后参数
     * 
     * @param array $sdf
     * @return array
     */
    protected function _format_reship_create_params($sdf)
    {
        //渠道ID
        $wms_channel_id = $sdf['wms_channel_id'];
        
        //售后类型
        $reship_type = ($sdf['return_type']=='change' ? 'HHRK' : 'THRK');
        
        //params
        $params = array(
                'warehouse_code' => $wms_channel_id, //渠道ID
                'trade_code' => $sdf['wms_package_bn'], //京东订单号(发货包裹单号)
                'order_code' => $sdf['order_bn'], //订单号
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                'order_type' => $reship_type, //THRK：退货入库单   HHRK：换货入库
                'reason_id' => $sdf['reason_id'], //售后申请原因ID
                'return_reason' => $sdf['problem_name'], //售后申请问题描述文字
                'pics' => '',
                'channel_refund_id' => $sdf['reship_bn'], //退货单号:渠道售后服务单申请单号
        );
        //items
        $items = array();
        foreach ($sdf['items'] as $itemKey => $itemVal)
        {
            //商品类型
            $sku_type = ($itemVal['is_wms_gift']=='true' ? '20' : '10');
            
            $items['item'][] = array(
                    'item_id' => $itemVal['outer_sku'], //京东skuId
                    'item_name' => $itemVal['product_name'], //物料名称
                    'item_quantity' => $itemVal['return_nums'], //退货数量
                    'item_type' => $sku_type, //商品类型(10主品、20赠品)
            );
        }
        $params['items'] = json_encode($items);
        
        return $params;
    }
    
    /**
     * 查询是否允许申请售后
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_query($sdf)
    {
        $reshipObj = app::get('ome')->model('reship');
        $reshipLib = kernel::single('ome_reship');
        
        $reship_id = $sdf['reship_id'];
        $msgcode = '';
        
        //获取退货包裹明细
        $sql = "SELECT a.*, b.item_id,b.product_id,b.bn,b.outer_sku,b.return_nums FROM sdb_ome_reship_package AS a ";
        $sql .= " LEFT JOIN sdb_ome_reship_package_items AS b ON a.package_id=b.package_id WHERE a.reship_id=". $reship_id;
        
        $packageList = $reshipObj->db->select($sql);
        if(empty($packageList)){
            $error_msg = '没有可查询的退货包裹';
            return $this->error($error_msg, $msgcode);
        }
        
        //按发货包裹号进行查询
        $result = array();
        foreach ($packageList as $key => $val)
        {
            if($val['sync_status'] == 'succ'){
                continue; //已经推送成功,则跳过
            }
            
            //merge
            $params = array_merge($sdf, $val);
            
            //查询是否允许申请售后
            //@todo:接口每次只允许查询一个SKU
            $result = $this->reship_package_query($params);
            if($result['rsp'] != 'succ'){
                return $result;
            }
        }
        
        return $this->succ('查询是否允许申请售后成功');
    }
    
    /**
     * 查询是否允许申请售后
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_package_query($sdf)
    {
        $reshipObj = app::get('ome')->model('reship');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $order_id = $sdf['order_id'];
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $package_bn = $sdf['wms_package_bn'];
        $msgcode = '';
        
        //title
        $title = $this->__channelObj->wms['channel_name'] . '退货单申请售后查询(京东包裹号：'. $package_bn .')';
        
        //params
        $params = $this->_format_reship_query_params($sdf);
        
        //request
        $callback = array();
        $gateway = ''; //判断是否加密
        $result = $this->__caller->call(WMS_RETURNORDER_APPLY_QUERY, $params, $callback, $title, 10, $reship_bn, true, $gateway);
        if($result['rsp'] == 'succ'){
            $result['data'] = json_decode($result['data'], true);
            
            //判断是否允许售后
            if($result['data']['canApply'] != 1){
                //error_code错误编码
                $error_code = $result['res'];
                
                //error_msg
                $error_msg = '京东包裹号：'. $package_bn .',不可申请：'.$result['data']['cannotApplyTip'];
                
                //记录报错信息
                $reshipObj->update(array('is_check'=>'2', 'sync_status'=>'2', 'sync_code'=>$error_code, 'sync_msg'=>$error_msg), array('reship_id'=>$reship_id));
                
                //log
                $operLogObj->write_log('reship@ome', $reship_id, $error_msg);
                
                //[开启自动审核]当不可申请京东售后时,OMS自动拒绝抖音平台售后申请
                $auto_confirm = app::get('ome')->getConf('return.auto_confirm');
                if($auto_confirm == 'on'){
                    $queueObj = app::get('base')->model('queue');
                    
                    $sdfdata = array(
                            'reship_id' => $reship_id,
                            'order_id' => $order_id,
                            'canApply' => $result['data']['canApply'], //该订单是否可申请售后 0：不可申请 1：可申请
                            'cannotApplyTip' => $result['data']['cannotApplyTip'], //不可申请提示,例如：该商品已超过售后期
                    );
                    
                    //放入queue队列中执行
                    $queueData = array(
                            'queue_title' => '退货单号：'. $reship_bn .'不可申请售后自动拒绝',
                            'start_time' => time(),
                            'params' => array(
                                    'sdfdata' => $sdfdata,
                                    'app' => 'oms',
                                    'mdl' => 'reship',
                            ),
                            'worker' => 'ome_reship_kepler.cannotApplyAftersale',
                    );
                    $queueObj->save($queueData);
                }
                
                return $this->error($error_msg, $msgcode);
            }
            
            //log
            $operLogObj->write_log('reship@ome', $reship_id, '京东包裹号：'. $package_bn .',查询申请售后成功');
            
            return $this->succ('退货单申请售后查询成功');
        }else{
            //error_code错误编码
            $error_code = $result['res'];
            
            //error_msg
            $error_msg = ($result['err_msg'] ? $result['err_msg'] : $result['msg']);
            $error_msg .= '(京东包裹号：'. $package_bn .')';
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            
            //记录报错信息
            $reshipObj->update(array('is_check'=>'2', 'sync_status'=>'2', 'sync_code'=>$error_code, 'sync_msg'=>$error_msg), array('reship_id'=>$reship_id));
            
            //log
            $operLogObj->write_log('reship@ome', $reship_id, $error_msg);
            
            //[兼容]厂直订单请求失败,需要OMS先请求妥投签收接口
            if(strpos($error_msg, '厂直订单')!== false || strpos($error_msg, '未完成')!== false){
                $queueObj = app::get('base')->model('queue');
                
                //放入queue队列中执行
                $queueData = array(
                        'queue_title' => '退货单号：'. $reship_bn .'自动妥投并重新通知创建退货单',
                        'start_time' => time(),
                        'params' => array(
                                'sdfdata' => array('reship_id'=>$reship_id, 'order_id'=>$order_id),
                                'app' => 'oms',
                                'mdl' => 'reship',
                        ),
                        'worker' => 'ome_reship_kepler.autoSignWmsDelivery',
                );
                $queueObj->save($queueData);
            }
            
            return $this->error($error_msg, $msgcode);
        }
    }
    
    /**
     * 查询售后申请参数
     * 
     * @param array $sdf
     * @return array
     */
    protected function _format_reship_query_params($sdf)
    {
        //渠道ID
        $wms_channel_id = $sdf['wms_channel_id'];
        if(empty($wms_channel_id)){
            $wms_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        }
        
        //params
        $params = array(
                'warehouse_code' => $wms_channel_id, //渠道ID
                'trade_code' => $sdf['wms_package_bn'], //京东订单号(包裹单号)
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                'item_id' => $sdf['outer_sku'], //京东skuId
        );
        
        return $params;
    }
    
    /**
     * 同步WMS仓储的售后原因
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_resaon($sdf)
    {
        $title = sprintf('同步售后原因[%s]', $sdf['bn']);

        //params
        $params = array(
                'pin'               => $this->__channelObj->wms['crop_config']['pin'], //pin
                'order_type'        => $sdf['reship_type']=='change' ? '20' : '10', //售后服务类型id退货(10)、换货(20)
                'trade_code'        => $sdf['service_bn'], //售后服务单对应的京东订单号
                'item_id'           => $sdf['bn'], //京东商品skuId
                'warehouse_code'    => $sdf['warehouse_code'], // 渠道ID

                'problem_name' => $sdf['problem_name'], // 无实际作用只为记录
        );
        
        $result = $this->__caller->call(WMS_RETURNORDER_REASON_LIST, $params, [], $title, 10, $sdf['reship_bn'], true);

        $data = @json_decode($result['data'], true);

        $result['data'] = [];
        if ($result['rsp'] == 'succ' && $data['data']) {
            foreach ($data['data'] as $value) {
                $result['data'][$value['applyReasonName']] = $value;
            }
        }

        return $result;
    }
    
    /**
     * 取消售后申请单
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_cancel($sdf)
    {
        $keplerLib = kernel::single('ome_reship_kepler');
        
        $order_id = $sdf['order_id'];
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $msgcode = '';
        
        //兼容
        if(empty($order_id) || empty($reship_id)){
            $reshipObj = app::get('ome')->model('reship');
            $reshipInfo = $reshipObj->dump(array('reship_id'=>$reship_id), 'order_id,reship_id,reship_bn,return_id,return_type,is_check,branch_id,shop_type,shop_id');
            $order_id = $reshipInfo['order_id'];
            $reship_id = $reshipInfo['reship_id'];
        }
        
        //渠道ID(仓库上绑定的渠道ID)
        $wms_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        
        //获取京东服务单列表
        $error_msg = '';
        $serviceList = $keplerLib->get_reship_services($reship_id, false, $error_msg);
        if(!$serviceList){
            return $this->succ('没有京东服务单,返回成功;');
        }
        
        //循环同步创建申请售后
        $result = array();
        foreach ($serviceList as $key => $val)
        {
            $service_bn = $val['service_bn'];
            
            if($val['service_status'] == 'cancel'){
                continue; //过滤已经取消的服务单
            }
            
            //渠道ID,优先使用退货单上
            $wms_channel_id = ($val['wms_channel_id'] ? $val['wms_channel_id'] : $wms_channel_id);
            
            //package
            $serviceData = array(
                    'service_bn' => $service_bn, //京东服务单号(MQ消息)
                    'wms_channel_id' => $wms_channel_id,
            );
            
            //sdf
            $sdf = array_merge($sdf, $serviceData);
            
            //request
            $res = $this->reship_service_cancel($sdf);
            if($res['rsp'] == 'succ'){
                $result['succ'][] = array('service_bn'=>$service_bn);
            }else{
                $result['fail'][] = array('service_bn'=>$service_bn, 'error_msg'=>$res['msg']);
            }
        }
        
        $error_msg = '';
        if($result['fail']){
            foreach ($result['fail'] as $key => $val)
            {
                $error_msg .= $val['package_bn'].'：'.$val['error_msg'].';';
            }
            
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            return $this->error($error_msg, $msgcode);
        }
        
        return $this->succ('取消售后退货单成功');
    }
    
    /**
     * 取消售后服务单
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_service_cancel($sdf)
    {
        $processObj = app::get('ome')->model('return_process');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $service_bn = $sdf['service_bn']; //京东服务单号(字段名:afsServiceId)
        
        //title
        $title = $this->__channelObj->wms['channel_name'] . '取消售后退货单(服务单号：'. $service_bn .')';
        
        //params
        $params = $this->_format_service_cancel_params($sdf);
        
        //判断是否加密
        $gateway = '';
        
        //request
        $callback = array();
        $result = $this->__caller->call(WMS_RETURNORDER_CANCEL, $params, $callback, $title, 10, $reship_bn, true, $gateway);
        if($result['rsp'] == 'succ'){
            //更新服务单状态
            $processObj->update(array('service_status'=>'cancel'), array('reship_id'=>$reship_id, 'service_bn'=>$service_bn));
            
            //log
            $operLogObj->write_log('reship@ome', $reship_id, '服务单号：'. $service_bn .',取消售后服务单成功');
            
            return $this->succ('取消退货单成功');
        }else{
            $error_msg = '服务单号：'. $service_bn .',取消售后服务单失败：';
            $error_msg .= ($result['err_msg'] ? $result['err_msg'] : $result['msg']);
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            
            //log
            $msgcode = '';
            $operLogObj->write_log('reship@ome', $reship_id, $error_msg);
            
            return $this->error($error_msg, $msgcode);
        }
    }
    
    /**
     * 取消售后服务单参数
     * 
     * @param array $sdf
     * @return array
     */
    protected function _format_service_cancel_params($sdf)
    {
        $params = array(
                'warehouse_code' => $sdf['wms_channel_id'], //渠道ID
                'reship_bn' => $sdf['service_bn'], //京东服务单号(字段名:afsServiceId)
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
        );
        
        return $params;
    }
    
    /**
     * 更新京东服务单的退回物流信息
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_logistics($sdf)
    {
        $keplerLib = kernel::single('ome_reship_kepler');
        
        $order_id = $sdf['order_id'];
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $msgcode = '';
        
        //渠道ID(仓库上绑定的渠道ID)
        $wms_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        
        //获取京东服务单列表
        $error_msg = '';
        $serviceList = $keplerLib->get_reship_services($reship_id, false, $error_msg);
        if(!$serviceList){
            $error_msg = '更新物流信息失败：'. $error_msg;
            return $this->error($error_msg, $msgcode);
        }
        
        //循环同步创建申请售后
        $result = array();
        foreach ($serviceList as $key => $val)
        {
            $service_bn = $val['service_bn'];
            
            //渠道ID(优先使用商品关联的渠道ID)
            $wms_channel_id = ($val['wms_channel_id'] ? $val['wms_channel_id'] : $wms_channel_id);
            
            if($val['service_status'] == 'cancel'){
                continue; //过滤已经取消的服务单
            }
            
            //package
            $serviceData = array(
                    'service_bn' => $service_bn, //京东服务单号(MQ消息)
                    'logi_code' => $val['logi_code'], //退回物流公司编码
                    'logi_no' => $val['logi_no'], //退回物流单号
                    'wms_channel_id' => $wms_channel_id, //渠道ID
            );
            
            //sdf
            $sdf = array_merge($sdf, $serviceData);
            
            //request
            $res = $this->reship_service_logistics($sdf);
            if($res['rsp'] == 'succ'){
                $result['succ'][] = array('service_bn'=>$service_bn);
            }else{
                $result['fail'][] = array('service_bn'=>$service_bn, 'error_msg'=>$res['msg']);
            }
        }
        
        $error_msg = '';
        if($result['fail']){
            foreach ($result['fail'] as $key => $val)
            {
                $error_msg .= $val['package_bn'].'：'.$val['error_msg'].';';
            }
            
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            return $this->error($error_msg, $msgcode);
        }
        
        return $this->succ('更新退回物流信息成功');
    }
    
    /**
     * 更新服务单物流信息
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_service_logistics($sdf)
    {
        $processObj = app::get('ome')->model('return_process');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $service_bn = $sdf['service_bn']; //京东服务单号(字段名:afsServiceId)
        
        //title
        $title = $this->__channelObj->wms['channel_name'] . '更新服务单物流信息(服务单号：'. $service_bn .')';
        
        //params
        $params = $this->_format_service_logistics_params($sdf);
        
        //判断是否加密
        $gateway = '';
        
        //request
        $callback = array();
        $result = $this->__caller->call(WMS_RETURNORDER_UPDATE_LOGISTICS, $params, $callback, $title, 10, $reship_bn, true, $gateway);
        if($result['rsp'] == 'succ'){
            //更新服务单状态
            $processObj->update(array('logi_code'=>$sdf['return_logi_code'], 'logi_no'=>$sdf['return_logi_no']), array('reship_id'=>$reship_id, 'service_bn'=>$service_bn));
            
            //log
            $operLogObj->write_log('reship@ome', $reship_id, '服务单号：'. $service_bn .',更新物流单号：'. $sdf['return_logi_no'] .' 成功');
            
            return $this->succ('更新服务单物流信息成功');
        }else{
            $error_msg = '服务单号：'. $service_bn .',更新物流单号失败：';
            $error_msg .= ($result['err_msg'] ? $result['err_msg'] : $result['msg']);
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            
            //log
            $msgcode = '';
            $operLogObj->write_log('reship@ome', $reship_id, $error_msg);
            
            return $this->error($error_msg, $msgcode);
        }
    }
    
    /**
     * 更新服务单物流信息参数
     * 
     * @param array $sdf
     * @return array
     */
    protected function _format_service_logistics_params($sdf)
    {
        //渠道ID
        $wms_channel_id = $sdf['wms_channel_id'];
        
        //发货日期
        $send_date = ($sdf['t_begin'] ? date('Y-m-d H:i:s', $sdf['t_begin']) : date('Y-m-d H:i:s', time()));
        
        //退货物流信息
        $logi_code = ($sdf['logi_code'] ? $sdf['logi_code'] : $sdf['return_logi_code']);
        $logi_no = ($sdf['logi_no'] ? $sdf['logi_no'] : $sdf['return_logi_no']);
        $logi_name = $sdf['return_logi_name'];
        
        //params
        $params = array(
                'warehouse_code' => $wms_channel_id, //渠道ID
                'reship_bn' => $sdf['service_bn'], //京东服务单号(字段名:afsServiceId)
                'company_code' => $logi_code, //退回物流公司编码
                'company_name' => $logi_name, //退回物流公司名称
                'logistics_no' => $logi_no, //退回物流单号
                'send_date' => $send_date, //发货日期
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
        );
        
        return $params;
    }
    
    /**
     * 查询寄件地址
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_address($sdf)
    {
        $keplerLib = kernel::single('ome_reship_kepler');
        
        $order_id = $sdf['order_id'];
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $msgcode = '';
        
        //获取京东服务单列表
        $error_msg = '';
        $serviceList = $keplerLib->get_reship_services($reship_id, false, $error_msg);
        if(!$serviceList){
            $error_msg = '查询寄件地址失败：'. $error_msg;
            return $this->error($error_msg, $msgcode);
        }
        
        //渠道ID(仓库上绑定的渠道ID)
        $wms_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        
        //按服务单号查询寄件地址
        $result = array();
        foreach ($serviceList as $key => $val)
        {
            $service_bn = $val['service_bn'];
            
            //渠道ID(优先使用商品关联的渠道ID)
            $wms_channel_id = ($val['wms_channel_id'] ? $val['wms_channel_id'] : $wms_channel_id);
            
            if($val['service_status'] == 'cancel'){
                continue; //过滤已经取消的服务单
            }
            
            //package
            $serviceData = array(
                    'service_bn' => $service_bn, //京东服务单号
                    'wms_channel_id' => $wms_channel_id,
            );
            
            //sdf
            $sdf = array_merge($sdf, $serviceData);
            
            //request
            $res = $this->reship_service_address($sdf);
            if($res['rsp'] == 'succ'){
                $result['succ'][] = array('service_bn'=>$service_bn);
            }else{
                $result['fail'][] = array('service_bn'=>$service_bn, 'error_msg'=>$res['msg']);
            }
        }
        
        //[fail]只要有一个服务单获取寄件成功即可
        if($result['fail'] && empty($result['succ'])){
            $error_msg = '';
            foreach ($result['fail'] as $key => $val)
            {
                //只需要提示一条错误信息
                $error_msg = $val['error_msg'];
                break;
            }
            
            return $this->error($error_msg, $msgcode);
        }
        
        return $this->succ('查询服务单寄件地址成功');
    }
    
    /**
     * 查询服务单寄件地址
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_service_address($sdf)
    {
        $operLogObj = app::get('ome')->model('operation_log');
        $addressObj = app::get('ome')->model('return_address');
        
        $keplerLib = kernel::single('ome_reship_kepler');
        
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $service_bn = $sdf['service_bn']; //京东服务单号(字段名:afsServiceId)
        $gateway = ''; //是否加密
        
        //title
        $title = $this->__channelObj->wms['channel_name'] . '查询服务单寄件地址(服务单号：'. $service_bn .')';
        
        //check
        $addressInfo = $addressObj->dump(array('reship_id'=>$reship_id, 'service_bn'=>$service_bn), 'address_id');
        if($addressInfo){
            //log
            $msgcode = '';
            $error_msg = '服务单['. $service_bn .']寄件地址已经存在,不能重复查询';
            $operLogObj->write_log('reship@ome', $reship_id, $error_msg);
            
            //return $this->error($error_msg, $msgcode);
            return $this->succ($error_msg);
        }
        
        //params
        $params = $this->_format_service_address_params($sdf);
        
        //request
        $callback = array();
        $result = $this->__caller->call(WMS_RETURNORDER_ADDRESS, $params, $callback, $title, 10, $reship_bn, true, $gateway);
        if($result['rsp'] == 'succ'){
            //[兼容]格式化数据
            if(is_string($result['data'])){
                $result['data'] = json_decode($result['data'], true);
            }
            
            $mobile = strlen($result['data']['contactsMobile'])==11 ? $result['data']['contactsMobile'] : '';
            
            //address
            $contact_id = 0;
            $province = $city = $country = '';
            $address = str_replace(array(",",'"'), '', $result['data']['address']);
            
            //匹配退货地址中的省、市、区
            $regionInfo = $keplerLib->mappingAddressCity($sdf['shop_id'], $address);
            if($regionInfo){
                $province = $regionInfo['province'];
                $city = $regionInfo['city'];
                $country = $regionInfo['country'];
            }
            
            //保存寄件地址记录
            $sdf = array(
                    'shop_id' => '0', //默认设置为0
                    'shop_type' => '0', //默认设置为0
                    'address_type' => '1', //自主售后退货地址
                    'reship_id' => $sdf['reship_id'],
                    'wms_type' => 'yjdf', //WMS渠道类型
                    'service_bn' => $service_bn,
                    'contact_name' => $result['data']['contactsName'],
                    'seller_company' => $result['data']['contactsName'],
                    'zip_code' => $result['data']['contactsZipCode'],
                    'phone' => $result['data']['contactsMobile'],
                    'mobile_phone' => $mobile,
                    'province' => $province, //省
                    'city' => $city, //市
                    'country' => $country, //区
                    'addr' => $address, //详细街道地址
                    'cancel_def' => 'false', //是否默认退货地址
                    'contact_id' => $contact_id, //平台地址库ID
                    'md5_address' => md5($address), //md5退货地址
                    'add_type' => 'wms', //创建类型为：WMS仓储类型
            );
            $addressObj->save($sdf);
            
            //log
            $operLogObj->write_log('reship@ome', $reship_id, '服务单号：'. $service_bn .',查询服务单寄件地址成功');
            
            //设置异常：京东寄件地址解析失败
            if(empty($province) || empty($city)){
                $abnormal_status = ome_constants_reship_abnormal::__ADDRESS_FAIL_CODE;
                $sql = "UPDATE sdb_ome_reship SET abnormal_status=abnormal_status | ". $abnormal_status ." WHERE reship_id=". $reship_id;
                $addressObj->db->exec($sql);
            }
            
            return $this->succ('查询服务单寄件地址成功');
        }else{
            $error_msg = '服务单号：'. $service_bn .',查询服务单寄件地址失败：';
            $error_msg .= ($result['err_msg'] ? $result['err_msg'] : $result['msg']);
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            
            //log
            $msgcode = '';
            $operLogObj->write_log('reship@ome', $reship_id, $error_msg);
            
            return $this->error($error_msg, $msgcode);
        }
    }
    
    /**
     * 查询服务单寄件地址参数
     * 
     * @param array $sdf
     * @return array
     */
    protected function _format_service_address_params($sdf)
    {
        //渠道ID
        $wms_channel_id = $sdf['wms_channel_id'];
        
        //params
        $params = array(
                'warehouse_code' => $wms_channel_id,
                'reship_bn' => $sdf['service_bn'], //京东服务单号(字段名:afsServiceId)
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
        );
        
        return $params;
    }
    
    /**
     * 获取服务单详情
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_search($sdf)
    {
        $reshipObj = app::get('ome')->model('reship');
        $queueObj = app::get('base')->model('queue');
        
        $keplerLib = kernel::single('ome_reship_kepler');
        
        $msgcode = '';
        
        //兼容
        $fields = 'order_id,reship_id,reship_bn,return_id,return_type,is_check,branch_id,shop_type,shop_id';
        if($sdf['reship_bn']){
            $reshipInfo = $reshipObj->dump(array('reship_bn'=>$sdf['reship_bn']), $fields);
        }else{
            $reshipInfo = $reshipObj->dump(array('reship_id'=>$sdf['reship_id']), $fields);
        }
        
        $sdf['order_id'] = $reshipInfo['order_id'];
        $sdf['reship_id'] = $reshipInfo['reship_id'];
        $sdf['reship_bn'] = $reshipInfo['reship_bn'];
        
        //仓库编码
        $branchObj = app::get('ome')->model('branch');
        $branchInfo = $branchObj->dump(array('branch_id'=>$reshipInfo['branch_id']), 'branch_bn');
        
        //渠道ID(仓库上绑定的渠道ID)
        $wms_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $branchInfo['branch_bn']); //仓库上绑定的渠道ID
        
        //获取京东服务单列表
        $error_msg = '';
        $serviceList = $keplerLib->get_reship_services($sdf['reship_id'], false, $error_msg);
        if(!$serviceList){
            $error_msg = '获取服务单详情失败：'. $error_msg;
            return $this->error($error_msg, $msgcode);
        }
        
        //循环同步创建申请售后
        $result = array();
        $isNewOrder = false;
        foreach ($serviceList as $key => $val)
        {
            $service_bn = $val['service_bn'];
            
            /***
            //取消的服务单,也需要获取审核信息
            if($val['service_status'] == 'cancel'){
                continue;
            }
            ***/
            
            //渠道ID
            $wms_channel_id = ($val['wms_channel_id'] ? $val['wms_channel_id'] : $wms_channel_id);
            
            //package
            $serviceData = array(
                    'service_bn' => $service_bn, //京东服务单号(MQ消息)
                    'wms_channel_id' => $wms_channel_id,
            );
            
            //sdf
            $sdf = array_merge($sdf, $serviceData);
            
            //request
            $res = $this->reship_service_search($sdf);
            if($res['rsp'] == 'succ'){
                $result['succ'][] = array('service_bn'=>$service_bn, 'new_package_bn'=>$res['data']['new_package_bn']);
                
                //换货生成京东新订单号
                if($res['data']['new_package_bn']){
                    $isNewOrder = true;
                }
                
            }else{
                $result['fail'][] = array('service_bn'=>$service_bn, 'error_msg'=>$res['msg']);
            }
        }
        
        //[queue队列]同步京东审核意见给到抖音平台
        if($result['succ']){
            $queueData = array(
                    'queue_title' => '退换货单号['. $sdf['reship_bn'] .']同步添加抖音售后单备注内容',
                    'start_time' => time(),
                    'params' => array(
                            'sdfdata' => array('reship_id'=>$sdf['reship_id'], 'reship_bn'=>$sdf['reship_bn']),
                            'app' => 'oms',
                            'mdl' => 'reship',
                    ),
                    'worker' => 'ome_reship_luban.autoSyncReturnRemark',
            );
            $queueObj->save($queueData);
        }
        
        //[queue队列]换货完成京东云交易创建新订单
        if($isNewOrder){
            $queueParams = array(
                    'reship_id' => $sdf['reship_id'],
                    'reship_bn' => $sdf['reship_bn'],
            );
            
            foreach($result['succ'] as $key => $val)
            {
                //check
                if(empty($val['new_package_bn'])){
                    continue;
                }
                
                $queueParams['newOrders'][] = array(
                        'service_bn' => $val['service_bn'],
                        'new_package_bn' => $val['new_package_bn'],
                );
            }
            
            if($queueParams['newOrders']){
                $queueData = array(
                        'queue_title' => '换货单号['. $sdf['reship_bn'] .']京东云交易创建新订单',
                        'start_time' => time(),
                        'params' => array(
                                'sdfdata' => $queueParams,
                                'app' => 'oms',
                                'mdl' => 'reship',
                        ),
                        'worker' => 'ome_reship_kepler.createYjdfNewOrder',
                );
                $queueObj->save($queueData);
            }
        }
        
        //获取详情失败信息
        $error_msg = '';
        if($result['fail']){
            foreach ($result['fail'] as $key => $val)
            {
                $error_msg .= $val['service_bn'].'：'.$val['error_msg'].';';
            }
            
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            return $this->error($error_msg, $msgcode);
        }
        
        return $this->succ('获取服务单详情成功');
    }
    
    /**
     * 获取查询服务单详情
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_service_search($sdf)
    {
        $processObj = app::get('ome')->model('return_process');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $service_bn = $sdf['service_bn']; //京东服务单号(字段名:afsServiceId)
        
        //title
        $title = $this->__channelObj->wms['channel_name'] . '获取服务单详情(服务单号：'. $service_bn .')';
        
        //params
        $params = $this->_format_service_search_params($sdf);
        
        //判断是否加密
        $gateway = '';
        
        //request
        $callback = array();
        $result = $this->__caller->call(WMS_RETURNORDER_GET, $params, $callback, $title, 10, $reship_bn, true, $gateway);
        if($result['rsp'] == 'succ'){
            //[兼容]格式化数据
            if(is_string($result['data'])){
                $result['data'] = json_decode($result['data'], true);
            }
            
            $saveData = array();
            
            //保存京东审核意见
            $approveNotes = substr($result['data']['remark'], 0, 280);
            $approveNotes = strip_tags($approveNotes);
            $approveNotes = str_replace(array("'", '"'), '', $approveNotes);
            if($approveNotes){
                $saveData['remark'] = $approveNotes;
            }
            
            //[换货]新京东订单号
            $newOrderId = ($result['data']['new_order_id'] ? $result['data']['new_order_id'] : $result['data']['newOrderId']);
            if($newOrderId){
                $saveData['new_package_bn'] = trim($newOrderId);
            }
            
            //save
            if($saveData){
                $processObj->update($saveData, array('reship_id'=>$reship_id, 'service_bn'=>$service_bn));
            }
            
            //log
            $operLogObj->write_log('reship@ome', $reship_id, '服务单号：'. $service_bn .',获取服务单详情成功：'.$approveNotes);
            
            $msgcode = '';
            return $this->succ('获取服务单详情成功', $msgcode, $saveData);
        }else{
            $error_msg = '服务单号：'. $service_bn .',获取服务单详情失败：';
            $error_msg .= ($result['err_msg'] ? $result['err_msg'] : $result['msg']);
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            
            //log
            $msgcode = '';
            $operLogObj->write_log('reship@ome', $reship_id, $error_msg);
            
            return $this->error($error_msg, $msgcode);
        }
    }
    
    /**
     * 查询服务单寄件地址参数
     * 
     * @param array $sdf
     * @return array
     */
    protected function _format_service_search_params($sdf)
    {
        $params = array(
                'warehouse_code' => $sdf['wms_channel_id'], //渠道ID
                'reship_bn' => $sdf['service_bn'], //京东服务单号(字段名:afsServiceId)
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
        );
        
        return $params;
    }
    
    /**
     * 查询发货单包裹(京东订单号)发货状态
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_query_delivery($sdf)
    {
        $packageObj = app::get('ome')->model('delivery_package');
        
        $reshipLib = kernel::single('ome_reship');
        
        $order_id = $sdf['order_id'];
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $msgcode = '';
        
        //渠道ID
        $wms_channel_id = $this->get_warehouse_code($this->__channelObj->wms['channel_id'], $sdf['branch_bn']); //仓库上绑定的渠道ID
        
        //请求IP地址
        $sdf['remote_addr'] = base_request::get_remote_addr();
        if(empty($sdf['remote_addr'])){
            $sdf['remote_addr'] = kernel::single('base_component_request')->get_server('SERVER_ADDR');
        }
        
        if(empty($sdf['remote_addr'])){
            $sdf['remote_addr'] = '127.0.0.1';
        }
        
        //通过退货单sku,找到对应的订单包裹(每个sku会是一个包裹)
        $error_msg = '';
        $packageList = $reshipLib->get_reship_package($reship_id, $error_msg);
        if(!$packageList){
            $error_msg = '查询包裹发货状态失败：没有包裹信息!';
            return $this->error($error_msg, $msgcode);
        }
        
        //循环查询包裹发货状态
        $result = array();
        foreach ($packageList as $key => $val)
        {
            //渠道ID(优先使用商品关联的渠道ID)
            $wms_channel_id = ($val['wms_channel_id'] ? $val['wms_channel_id'] : $wms_channel_id);
            
            //发货包裹号
            $package_bn = $val['wms_package_bn'];
            
            //获取配送状态
            $dlyPackageInfo = $packageObj->dump(array('package_bn'=>$package_bn), 'package_id,shipping_status');
            if($dlyPackageInfo['shipping_status'] == '18'){
                continue; //已签收,则跳过
            }
            
            //package
            $packageData = array(
                    'wms_package_bn' => $package_bn, //京东订单号(包裹单号)
                    'wms_channel_id' => $wms_channel_id, //渠道ID
            );
            
            //sdf
            $params = array_merge($sdf, $packageData);
            
            //request
            $res = $this->query_delivery_package($params);
            if($res['rsp'] != 'succ'){
                return $res;
            }
            
            //包裹发货状态
            if(in_array($res['data']['orderStatus'], array('16', '18'))){
                $result['delivery'][] = $package_bn; //用户拒收、用户签收
            }else{
                $result['accept'][] = $package_bn;
            }
        }
        
        return $this->succ('查询包裹发货状态成功', '200', $result);
    }
    
    /**
     * 查询包裹发货状态
     * 
     * @param array $sdf
     * @return array
     */
    public function query_delivery_package($sdf)
    {
        $operLogObj = app::get('ome')->model('operation_log');
        
        $reship_id = $sdf['reship_id'];
        $reship_bn = $sdf['reship_bn'];
        $wms_package_bn = $sdf['wms_package_bn'];
        $gateway = ''; //是否加密
        
        //渠道ID
        $wms_channel_id = $val['wms_channel_id'];
        
        //title
        $title = $this->__channelObj->wms['channel_name'] . '查询包裹发货状态(京东包裹号：'. $package_bn .')';
        
        //params
        $params = array(
                'warehouse_code' => $wms_channel_id, //渠道ID
                'trade_code' => $wms_package_bn, //京东订单号(包裹单号)
                'pin' => $this->__channelObj->wms['crop_config']['pin'], //pin
                'client_ip' => $sdf['remote_addr'], //商家操作的客户端IP
        );
        
        //request
        $callback = array();
        $packageDly = array();
        $result = $this->__caller->call(WMS_SALEORDER_DELIVERY_STATUS, $params, $callback, $title, 10, $reship_bn, true, $gateway);
        if($result['rsp'] == 'succ'){
            //[兼容]格式化数据
            if(is_string($result['data'])){
                $result['data'] = json_decode($result['data'], true);
            }
            
            $packageDly = array('orderStatus'=>trim($result['data']['baseOrderInfo']['orderStatus']));
            
            $operLogObj->write_log('reship@ome', $reship_id, '京东包裹号：'. $package_bn .',查询包裹发货状态为:'. $packageDly['orderStatus']);
            
            return $this->succ('查询包裹发货状态成功', '200', $packageDly);
        }else{
            $error_msg = '京东包裹号：'. $package_bn .',查询包裹发货状态失败：';
            $error_msg .= ($result['err_msg'] ? $result['err_msg'] : $result['msg']);
            $error_msg = substr($error_msg, 0, 150); //截取150个字符
            
            $msgcode = '';
            $operLogObj->write_log('reship@ome', $reship_id, $error_msg);
            
            return $this->error($error_msg, $msgcode);
        }
    }
    
    /**
     * 同步第三方仓储WMS异常错误码
     * 
     * @param array $sdf
     * @return array
     */
    public function reship_errorcode($sdf)
    {
        $abnormalObj = app::get('wmsmgr')->model('abnormal_code');
        
        $title = $this->__channelObj->wms['channel_name'] . '同步异常错误码';
        $original_bn = date('Ymd');
        
        //错误码列表
        $codeList = array(
                '4900_101' => array('abnormal_code'=>'4900_101', 'abnormal_name'=>'下单异常-配送超区'),
                '4000' => array('abnormal_code'=>'4000', 'abnormal_name'=>'未知异常信息'),
                '4100' => array('abnormal_code'=>'4100', 'abnormal_name'=>'渠道未启用异常'),
                '4200' => array('abnormal_code'=>'4200', 'abnormal_name'=>'用户相关异常'),
                '4300' => array('abnormal_code'=>'4300', 'abnormal_name'=>'商品相关异常'),
                '4900_100' => array('abnormal_code'=>'4900_100', 'abnormal_name'=>'下单异常-商品无货'),
                '200' => array('abnormal_code'=>'200', 'abnormal_name'=>'Success'),
                '202' => array('abnormal_code'=>'202', 'abnormal_name'=>'Request params not valid'),
                '500' => array('abnormal_code'=>'500', 'abnormal_name'=>'Server error'),
                '4400' => array('abnormal_code'=>'4400', 'abnormal_name'=>'地址相关异常'),
                '4500' => array('abnormal_code'=>'4500', 'abnormal_name'=>'发票相关异常'),
                '4600' => array('abnormal_code'=>'4600', 'abnormal_name'=>'配送相关异常'),
                '4700' => array('abnormal_code'=>'4700', 'abnormal_name'=>'运费相关异常'),
                '4800' => array('abnormal_code'=>'4800', 'abnormal_name'=>'支付代扣相关异常'),
                '4900' => array('abnormal_code'=>'4900', 'abnormal_name'=>'下单异常'),
                '6000' => array('abnormal_code'=>'6000', 'abnormal_name'=>'赠品信息查询异常'),
        );
        
        //已有WMS售后原因
        $abnormalList = array();
        $tempList = $abnormalObj->getList('*', array('abnormal_type'=>'delivery'));
        if($tempList){
            foreach ($tempList as $key => $val)
            {
                $abnormal_code = $val['abnormal_code'];
                $abnormalList[$abnormal_code] = $val;
            }
        }
        
        //save
        foreach ($codeList as $key => $val)
        {
            $abnormal_code = trim($val['abnormal_code']);
            $abnormal_id = $abnormalList[$abnormal_code]['abnormal_id'];
            
            //sdf
            $sdf = array(
                    'abnormal_code' => trim($val['abnormal_code']),
                    'abnormal_name' => trim($val['abnormal_name']),
                    'abnormal_type' => 'delivery',
                    'create_time' => time(),
                    'last_modified' => time(),
            );
            
            //关联店铺售后原因
            if($abnormal_id){
                $sdf['abnormal_id'] = $abnormal_id;
                
                unset($sdf['create_time']);
            }
            
            $abnormalObj->save($sdf);
        }
        
        return $this->succ('同步售后原因成功');
    }
    
}