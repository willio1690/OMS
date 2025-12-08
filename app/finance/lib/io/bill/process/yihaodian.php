<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_process_yihaodian{

    public function structure_import_data(&$mdl,$row,&$format_row=array(),&$result){
        if( $row == null ){
            $result['status'] = 'success';
        }else{
            if( $row['group'] == 1 ){
                //组织数据
                $format_row = array(
                    'goods_bn' => trim($row['data']['商品编号']),
                    'order_bn' => trim($row['data']['订单号']),
                    'sale' => trim($row['data']['销售额']),
                    'refund' => trim($row['data']['退款额']),
                    'fee' => trim($row['data']['佣金']),
                    'ship' => trim($row['data']['配送费']),
                    'date' => trim($row['data']['可结算日期']),
                    'is_js' => trim($row['data']['是否已结款']),
                    'js_type' => trim($row['data']['结算类型']),
                );
            }
        }
    }

    /**
     * 检查ing_import_data
     * @param mixed $mdl mdl
     * @param mixed $row row
     * @param mixed $result result
     * @return mixed 返回验证结果
     */
    public function checking_import_data(&$mdl,$row,&$result){
        if( $row == null ){
            $result['status'] = 'success';
        }else{
            if($row['group'] == 1) {
                if($row['data']['is_js'] == '已结款'){
                    //基础数据验证
                    $result = $this->field_verify($row,$mdl);
                }
            }
        }
    }

    /**
     * finish_import_data
     * @param mixed $mdl mdl
     * @param mixed $row row
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function finish_import_data(&$mdl,$row,&$result){
        if( $row == null ){
            $result['status'] = 'success';
        }else{
            if( $row['group'] == 1 ){
                if($row['data']['is_js'] == '已结款'){
                    //数据验证
                    $result = $this->field_verify($row,$mdl);
                    //数据保存
                    $order = finance_io_bill_func::order_is_exists($row['data']['order_bn']);
                    $shop = finance_io_bill_func::getShopByShopID($order['shop_id']);

                    $sdf = array(
                        'order_bn' => $row['data']['order_bn'],
                        'channel_id' => $shop['shop_id'],
                        'channel_name' => $shop['name'],
                        'trade_time' => $row['data']['date'],
                        'fee_obj' => '一号店',
                        'money' => '',
                        'fee_item' => '',
                        'member' => '一号店',
                        'credential_number' => '',
                    );

                    //货款（收入）
                    if($row['data']['sale'] != '' && $row['data']['sale'] != '0' && $row['data']['js_type'] == '普通结算'){
                        $sdf1 = $sdf;
                        $sdf1['money'] = $row['data']['sale'];
                        $sdf1['fee_item'] = '销售收款';
                        $sdf1['unique_id'] = finance_func::unique_id(array(
                            $row['data']['goods_bn'],
                            $row['data']['order_bn'],
                            $sdf1['fee_item'],
                        ));
                        $rs = finance_io_bill_func::save($sdf1);
                        if($rs['status'] == 'fail'){
                            $result['status'] = 'fail';
                            $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                        }
                    }

                    //货款（支出）
                    if($row['data']['refund'] != '' && $row['data']['refund'] != '0' && $row['data']['js_type'] == '退换货结算'){
                        $sdf2 = $sdf;
                        $sdf2['money'] = '-'.$row['data']['refund'];
                        $sdf2['fee_item'] = '销售退款';
                        $sdf2['unique_id'] = finance_func::unique_id(array(
                            $row['data']['goods_bn'],
                            $row['data']['order_bn'],
                            $sdf2['fee_item'],
                        ));
                        $rs = finance_io_bill_func::save($sdf2);
                        if($rs['status'] == 'fail'){
                            $result['status'] = 'fail';
                            $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                        }
                    }

                    //佣金
                    if($row['data']['fee'] != '' && $row['data']['fee'] != '0'){
                        $sdf3 = $sdf;
                        $sdf3['money'] = $row['data']['fee'];
                        $sdf3['fee_item'] = '佣金';
                        $sdf3['unique_id'] = finance_func::unique_id(array(
                            $row['data']['goods_bn'],
                            $row['data']['order_bn'],
                            $sdf3['fee_item'],
                        ));
                        $rs = finance_io_bill_func::save($sdf3);
                        if($rs['status'] == 'fail'){
                            $result['status'] = 'fail';
                            $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                        }
                    }

                    //配送费
                    if($row['data']['ship'] != '' && $row['data']['ship'] != '0'){
                        $sdf4 = $sdf;
                        $sdf4['money'] = $row['data']['ship'];
                        $sdf4['fee_item'] = '物流费';
                        $sdf4['unique_id'] = finance_func::unique_id(array(
                            $row['data']['goods_bn'],
                            $row['data']['order_bn'],
                            $sdf4['fee_item'],
                        ));
                        $rs = finance_io_bill_func::save($sdf4);
                        if($rs['status'] == 'fail'){
                            $result['status'] = 'fail';
                            $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                        }
                    }
                }
            }
        }
    }


    private function field_verify($row,$mdl){
        $result = array();
        $inline = $row['inline'];
        $data = $row['data'];

        #订单号是否存在验证
        $rs = finance_io_bill_verify::isOrder($data['order_bn'],'订单号不存在');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #销售额验证
        $rs = finance_io_bill_verify::isPrice($data['sale'],'销售额格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }   

        #退款额验证
        $rs = finance_io_bill_verify::isPrice($data['refund'],'退款额格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #佣金验证
        $rs = finance_io_bill_verify::isPrice($data['fee'],'佣金格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #配送费验证
        $rs = finance_io_bill_verify::isPrice($data['ship'],'配送费格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #日期验证
        if($data['date']!=''){
            $rs = finance_io_bill_verify::isDate($data['date'],'可结算日期格式错误');
            if($rs['status'] == 'fail'){
                $result['msg'][ $inline ] .= $rs['msg'].'|';
                $result['status'] = 'fail';
            }
        }

        #账期验证
        $rs = finance_io_bill_verify::isTaskCheckInitTime($data['date'],$mdl->_import_func->task_id);
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        return $result;
    }

    /**
     * 读取到的数据格式化
     *
     * @param Object $mdl MODEL层对象
     * @param Array $row 读取一行
     * @return void
     * @author 
     **/
    public function getSDf(&$mdl,$row,&$mark)
    {
        
        if(!$row) return false;

        static $oldKey;
        static $title;
        if (!$oldKey) {
            $title = finance_io_bill_title::getTitle('yihaodian');

            # 读取文件标题，并记录它的位置
            foreach ($title as $key => $value) {
                $pCol = array_search($value, $row,true);
                if ($pCol === false) {
                    $oldKey = '';
                    return false;
                }

                $oldKey[$key] = $pCol;
            }

            $mark = 'title';

            return $title;
        }

        $mark = 'contents';
        # 读取数据
        foreach ($oldKey as $column => $pCol) {
            $tmp[$column] = $row[$pCol];
        }

        if ($tmp['is_js'] != '已结款') {
            return false;
        }

        $base_sdf = array(
            'order_bn'          => $tmp['order_bn'],
            'channel_id'        => '',
            'channel_name'      => '',
            'trade_time'        => $tmp['date'],
            'fee_obj'           => '一号店',
            'money'             => '',
            'fee_item'          => '',
            'member'            => '一号店',
            'credential_number' => '',
        );

        $sdf = array();
        // 货款（收入）--过滤掉金额为零的信息
        if ( !empty( $tmp['sale'] ) && $tmp['js_type'] == '普通结算') {
            $unique_id = finance_func::unique_id(array(
                $tmp['goods_bn'],
                $tmp['order_bn'],
                '销售收款',
            ));
            $sdf[] = array_merge($base_sdf,array('money'=>$tmp['sale'],'fee_item'=>'销售收款','unique_id'=>$unique_id));
        }

        // 货款（支出）--过滤掉金额为零的信息
        if ( !empty( $tmp['refund'] ) && $tmp['js_type'] == '退换货结算') {
            $unique_id = finance_func::unique_id(array(
                $tmp['goods_bn'],
                $tmp['order_bn'],
                '销售退款',
            ));
            $sdf[] = array_merge($base_sdf,array('money'=>'-'.$tmp['refund'],'fee_item'=>'销售退款','unique_id'=>$unique_id));
        }

        // 佣金--过滤掉金额为零的信息
        if ( !empty( $tmp['fee'] ) ) {
            $unique_id = finance_func::unique_id(array(
                $tmp['goods_bn'],
                $tmp['order_bn'],
                '佣金',
            ));
            $sdf[] = array_merge($base_sdf,array('money'=>$tmp['fee'],'fee_item'=>'佣金','unique_id'=>$unique_id));
        }

        // 物流费--过滤掉金额为零的信息
        if ( !empty( $tmp['ship'] ) ) {
            $unique_id = finance_func::unique_id(array(
                $tmp['goods_bn'],
                $tmp['order_bn'],
                '物流费',
            ));
            $sdf[] = array_merge($base_sdf,array('money'=>$tmp['ship'],'fee_item'=>'物流费','unique_id'=>$unique_id));
        }

        return $sdf;
    }
}
?>