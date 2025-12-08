<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 退货单响应业务
 *
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_yjdf_response_reship extends erpapi_wms_response_reship
{
    /**
     * 接收退货服务单信息
     * 
     * @param array $sdf
     * @return array
     */

    public function status_update($params)
    {
        $foreignObj = app::get('console')->model('foreign_sku');
        
        $title = $this->__channelObj->wms['channel_name'] .'退货单';
        $this->__apilog['title'] = $title .'['. $params['reship_bn'] .']';
        $this->__apilog['original_bn'] = $params['reship_bn'];
        
        //[兼容]推送赠品时,退货单号加了下划线(_)
        //@todo：京东退货channelAfsApplyId字段是唯一性的，所以退赠品时,退货单号以下划线(_)加上了赠品货号;
        $original_bn = trim($params['reship_bn']);
        $tempData = explode('_', $original_bn);
        $reship_bn = $tempData[0];
        
        $this->__apilog['original_bn'] = $reship_bn;
        
        //data
        $data = array(
                'reship_bn'    => $reship_bn,
                'logi_code'    => $params['logistics'],
                'logi_no'      => $params['logi_no'],
                'branch_bn'    => $params['warehouse'],
                'memo'         => $params['remark'],
                'operate_time' => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'), //服务单申请时间
                'wms_id'       => $this->__channelObj->wms['channel_id'],
        );
        
        //京东服务单
        $serivce = array(
                'wms_type' => 'yjdf', //WMS仓储类型
                'service_bn'=> trim($params['afsServiceId']), //京东服务单号
                'service_type'=> trim($params['afsType']), //售后服务类型,10：退货 20：换货
                'delivery_bn'=> trim($params['channelOrderId']), //渠道订单号(OMS发货单号)
                'package_bn'=> trim($params['orderId']), //京东订单号(包裹号)
                'wms_order_code'=> trim($params['afsApplyId']), //京东售后申请单号(请求同步创建售后申请单,京东返回的申请单号)
                'afsResultType' => trim($params['afsResultType']), //平台售后状态
                'stepType' => trim($params['stepType']), //平台处理环节
        );
        
        //merge
        $data = array_merge($data, $serivce);
        
        //状态转大写
        $params['status'] = strtoupper($params['status']);
        
        //[兼容]京东不传reship_bn
        if(empty($data['reship_bn'])){
            $processObj = app::get('ome')->model('return_process');
            $processInfo = $processObj->dump(array('package_bn'=>$data['package_bn'], 'service_bn'=>$data['service_bn']), 'reship_id,wms_order_code,service_type');
            if($processInfo){
                $reshipObj = app::get('ome')->model('reship');
                $reshipInfo = $reshipObj->dump(array('reship_id'=>$processInfo['reship_id']), 'reship_id,reship_bn');
                $data['reship_bn'] = $reshipInfo['reship_bn'];
                
                //original_bn
                $this->__apilog['title'] = $title .'['. $data['reship_bn'] .']'. $params['status'];
                $this->__apilog['original_bn'] = $data['reship_bn'];
            }
            
            if(empty($data['wms_order_code'])){
                $data['wms_order_code'] = $processInfo['wms_order_code'];
            }
            
            if(empty($data['service_type'])){
                $data['service_type'] = $processInfo['service_type'];
            }
        }
        
        
        //下面这块代码后面需要包成funcion方法重复调用
        //[兼容]京东同分同秒推送ACCEPT通知成功 和 APPLY状态
        //@todo：导致OMS先接收到APPLY状态，所以查询不到resip_bn退货单号;
        if(empty($data['reship_bn']) && $data['package_bn']){
            
            //[隔5秒]防止京东同分同秒先推送APPLY状态
            sleep(5);
            
            //[隔5秒]再次获取京东服务单
            $processObj = app::get('ome')->model('return_process');
            $processInfo = $processObj->dump(array('package_bn'=>$data['package_bn'], 'service_bn'=>$data['service_bn']), 'reship_id,wms_order_code,service_type');
            if($processInfo){
                $reshipObj = app::get('ome')->model('reship');
                $reshipInfo = $reshipObj->dump(array('reship_id'=>$processInfo['reship_id']), 'reship_id,reship_bn');
                $data['reship_bn'] = $reshipInfo['reship_bn'];
                
                //original_bn
                $this->__apilog['title'] = $title .'兼容stepType['. $data['reship_bn'] .']'. $params['status'];
                $this->__apilog['original_bn'] = $data['reship_bn'];
            }
            
            if(empty($data['wms_order_code'])){
                $data['wms_order_code'] = $processInfo['wms_order_code'];
            }
            
            if(empty($data['service_type'])){
                $data['service_type'] = $processInfo['service_type'];
            }
            
        }
        
        //switch
        switch($params['status'])
        {
            case 'FINISH': $data['status']='FINISH';break;
            case 'PARTIN': $data['status']='PARTIN';break;
            case 'CANCEL':
            case 'CLOSE':
            case 'FAILED':
            case 'DENY':
                $data['status'] = 'CLOSE';
                break;
            default:
                $data['status'] = $params['status'];
                break;
        }
        
        //获取退货包裹明细(按京东订单号、WMS货号为数组下标)
        $keplerLib = kernel::single('ome_reship_kepler');
        $filter = array('reship_id'=>$reshipInfo['reship_id'], 'reship_bn'=>$data['reship_bn']);
        $packageList = $keplerLib->getReshipPackageInfo($filter);
        
        //京东订单号(包裹号)
        $wms_package_bn = $data['package_bn'];
        $packageItems = $packageList[$wms_package_bn]['items'];
        
        //items
        //todo：京东云交易不会传sku退货数量,矩阵固定normal_num是1
        $reship_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],true) : array();
        if($items){
            foreach($items as $key => $val)
            {
                $wms_sku_bn = $val['product_bn']; //京东传过来的是wms仓储的sku货号
                $wms_sku_bn = str_replace(array("'", '"'), '', $wms_sku_bn); //过滤危险字符
                
                //check
                if (empty($wms_sku_bn)){
                    continue;
                }
                
                //sku退货包裹信息(product代表普通商品,gift代表赠品)
                //$itemSkuInfo = $packageItems['product'][$wms_sku_bn];
                
                //获取退货数量(京东云交易不会传sku退货数量,矩阵固定normal_num是1 )
                //$return_nums = ($itemSkuInfo['return_nums'] ? $itemSkuInfo['return_nums'] : $val['normal_num']);
                
                //[京东云交易]每一个数量就是一个服务单,矩阵固定normal_num会传1
                $val['normal_num'] = intval($val['normal_num']);
                $return_nums = ($val['normal_num'] ? $val['normal_num'] : 1);
                
                $defective_num = intval($val['defective_num']); //不良品,固定为0
                
                //京东sku_id转换成oms货号
                $skuInfo = $foreignObj->db_dump(array('outer_sku'=>$wms_sku_bn, 'wms_id'=>$this->__channelObj->wms['channel_id']), 'inner_sku,outer_sku,price');
                if($skuInfo){
                    $val['wms_sku_bn'] = $wms_sku_bn; //wms仓储的sku货号
                    $val['product_bn'] = $skuInfo['inner_sku']; //OMS基础物料编码
                    $val['price'] = $skuInfo['price'];
                }
                
                //item
                $product_bn = $val['product_bn'];
                $reship_items[$product_bn]['wms_sku_bn'] = $val['wms_sku_bn']; //wms仓储的sku货号
                $reship_items[$product_bn]['bn'] = $val['product_bn'];
                $reship_items[$product_bn]['normal_num'] = (int)$reship_items[$product_bn]['normal_num'] + $return_nums;
                $reship_items[$product_bn]['defective_num'] = (int)$reship_items[$product_bn]['defective_num'] + $defective_num;
            }
        }
        $data['items'] = $reship_items;
        
        return $data;
    }
    
    /**
     * 京东云交易订单退款MQ消息
     * @todo：消息主题：ct_order_refund
     * 
     * @param array $params
     * @return array
     */
    public function service_refund($params)
    {
        $processObj = app::get('ome')->model('return_process');
        
        //params
        $service_bn = trim($params['service_number']);
        $package_bn = (trim($params['orderId']) ? $params['orderId'] : $params['oid']);
        $refund_time = $params['refund_time'];
        $refund_fee = $params['refund_fee'];
        $reship_bn = '';
        
        //title
        $title = $this->__channelObj->wms['channel_name'] .'云交易订单退款MQ消息';
        $this->__apilog['title'] = $title .'['. $service_bn .']';
        $this->__apilog['original_bn'] = $service_bn; //京东服务单号
        
        //查询京东服务单信息
        $processInfo = $processObj->dump(array('package_bn'=>$package_bn, 'service_bn'=>$service_bn), 'reship_id,wms_order_code,service_type');
        if($processInfo){
            $reshipObj = app::get('ome')->model('reship');
            $reshipInfo = $reshipObj->dump(array('reship_id'=>$processInfo['reship_id']), 'reship_id,reship_bn');
            $reship_bn = $reshipInfo['reship_bn'];
            
            //original_bn
            $this->__apilog['original_bn'] = $reship_bn;
        }
        
        //data
        $data = array(
                'reship_bn' => $reship_bn,
                'package_bn' => $package_bn,
                'service_bn' => $service_bn,
                'refund_fee' => $refund_fee,
                'refund_time' => $refund_time,
                'wms_id' => $this->__channelObj->wms['channel_id'],
        );
        
        return $data;
    }
}
