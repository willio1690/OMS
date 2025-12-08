<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_process_ar{

    public function structure_import_data(&$mdl,$row,&$format_row=array(),&$result){
        if( $row == null ){
            $result['status'] = 'success';
        }else{
            if( $row['group'] == 1 ){
                //组织数据
                $format_row = array(
                    'date' => trim($row['data']['*:账单日期']),
                    'serial_number' => trim($row['data']['*:业务流水号']),
                    'member' => trim($row['data']['*:客户/会员']),
                    'type' => trim($row['data']['*:业务类型']),
                    'order_bn' => trim($row['data']['*:订单号']),
                    'relate_order_bn' => trim($row['data']['*:关联订单号']),
                    'sale_money' => trim($row['data']['*:商品成交金额']),
                    'fee_money' => trim($row['data']['*:运费收入']),
                    'money' => trim($row['data']['*:应收金额']),
                );
                //item
                $mdl->_import_func->select_data_indextype = 'title';
                $item = $mdl->_import_func->select(2,array(trim($row['data']['*:业务流水号'])));
                $_item = array();
                if($item){
                    $products_normal_mdl = app::get('material')->model('basic_material');
                    foreach($item as $v){
                        $inline = $v['_inline'];
                        $product = $products_normal_mdl->getlist('bm_id as product_id',array('material_bn'=>$v['*:商品货号']),0,1);
                        $_item[$inline]['serial_number'] = $format_row['serial_number'];
                        $_item[$inline]['bn'] = trim($v['*:商品货号']);
                        $_item[$inline]['name'] = trim($v['*:商品名称']);
                        $_item[$inline]['nums'] = trim($v['*:数量']);
                        $_item[$inline]['price'] = trim($v['*:金额']);
                        $_item[$inline]['isExists'] = empty($product[0]['product_id']) ? 'false' : 'true';
                    }
                    $format_row['item'] = $_item;
                }

            }
            if( $row['group'] == 2 ){
                $item['serial_number'] = trim($row['data']['*:业务流水号']);
                $format_row = $item;
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
            $rs = $mdl->_import_func->is_repeat();
            $result = $rs;
        }else{
            if($row['group'] == 1) {
                //基础数据验证
                $result = $this->field_verify($row,$mdl,true);
            }
            if( $row['group'] == 2 ){
                $result = $this->field_verify_product_isExists($row,$mdl);
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
                //数据验证
                $result = $this->field_verify($row,$mdl);
                //数据保存
                $order = finance_io_bill_func::order_is_exists($row['data']['order_bn']);
                $shop = finance_io_bill_func::getShopByShopID($order['shop_id']);


                $sdf = array(
                    'trade_time' => $row['data']['date'],
                    'member' => $row['data']['member'],
                    'type' => $row['data']['type'],
                    'order_bn' => $row['data']['order_bn'],
                    'relate_order_bn' => $row['data']['relate_order_bn'],
                    'channel_id' => $shop['shop_id'],
                    'channel_name' => $shop['name'],
                    'sale_money' => $row['data']['sale_money'],
                    'fee_money' => $row['data']['fee_money'],
                    'money' => $row['data']['money'],
                    'serial_number' => $row['data']['serial_number'],
                    'memo' => '',
                    'unique_id' => finance_func::unique_id(array(
                        $row['data']['serial_number']
                    )),
                );
                $items = array();
                foreach($row['data']['item'] as $v){
                    $items[] = array(
                        'bn' => $v['bn'],
                        'name' => $v['name'],
                        'num' => $v['nums'],
                        'money' => $v['price'],
                    );
                }
                $sdf['items'] = $items;

                $rs = finance_io_bill_func::ar_save($sdf);
                if($rs['status'] == 'fail'){
                    $result['status'] = 'fail';
                    $result['msg'][ $row['inline'] ] .= $rs['msg']?$rs['msg'].'|':'';
                }

            }
        }
    }


    private function field_verify($row,&$mdl,$is_judge_repeat = false){
        $result = array();
        $inline = $row['inline'];
        $data = $row['data'];

        #业务流水号是否为空验证
        if($data['serial_number'] == ''){
            $result['msg'][ $inline ] .= '业务流水号不能为空|';
            $result['status'] = 'fail';
        }

        #订单号是否存在验证
        $rs = finance_io_bill_verify::isOrder($data['order_bn'],'订单号不存在');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #关联订单号是否存在验证
        if($data['relate_order_bn']){
            $rs = finance_io_bill_verify::isOrder($data['relate_order_bn'],'关联订单号不存在');
            if($rs['status'] == 'fail'){
                $result['msg'][ $inline ] .= $rs['msg'].'|';
                $result['status'] = 'fail';
            }
        }

        #商品成交金额验证
        if($data['sale_money']){
            $rs = finance_io_bill_verify::isPrice($data['sale_money'],'商品成交金额格式错误');
            if($rs['status'] == 'fail'){
                $result['msg'][ $inline ] .= $rs['msg'].'|';
                $result['status'] = 'fail';
            }
        }

        #运费收入验证
        if($data['fee_money']){
            $rs = finance_io_bill_verify::isPrice($data['fee_money'],'运费收入格式错误');
            if($rs['status'] == 'fail'){
                $result['msg'][ $inline ] .= $rs['msg'].'|';
                $result['status'] = 'fail';
            }
        }

        #应收金额验证
        $rs = finance_io_bill_verify::isPrice($data['money'],'应收金额格式错误');
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #账单日期验证
        if($data['date']!=''){
            $rs = finance_io_bill_verify::isDate($data['date'],'账单日期格式错误');
            if($rs['status'] == 'fail'){
                $result['msg'][ $inline ] .= $rs['msg'].'|';
                $result['status'] = 'fail';
            }
        }

        #业务类型验证
        $type = array('销售出库','销售退货','销售换货','销售退款');
        if(!in_array($data['type'],$type)){
            $rs['status'] = 'fail';
            $rs['msg'] = '业务类型错误';
        }else{
            $rs['status'] = 'success';
        }
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }

        #主信息判重
        if($is_judge_repeat == true){
            $mdl->_import_func->set_unique($data['serial_number'],$inline,false,'');
            $mainNums = $mdl->_import_func->unique_nums;
        }

        #明细验证
        if($data['item']){
            foreach($data['item'] as $itemLine => $item){
                #商品货号验证
                $products_mdl = app::get('material')->model('basic_material');
                $products = $products_mdl->getlist('bm_id as product_id',array('material_bn'=>$item['bn']),0,1);
                if(empty($products[0]['product_id'])){
                    $result['msg'][ $itemLine ] .= '货品不存在|';
                    $result['status'] = 'fail';
                }

                #数量验证
                $rs = finance_io_bill_verify::isPrice($item['nums'],'数量格式错误');
                if($rs['status'] == 'fail'){
                    $result['msg'][ $itemLine ] .= $rs['msg'].'|';
                    $result['status'] = 'fail';
                }

                #金额验证
                $rs = finance_io_bill_verify::isPrice($item['price'],'金额格式错误');
                if($rs['status'] == 'fail'){
                    $result['msg'][ $itemLine ] .= $rs['msg'].'|';
                    $result['status'] = 'fail';
                }

                $unique = md5($data['serial_number'].$item['bn']);
                if($is_judge_repeat == true && $mainNums<=1)    $mdl->_import_func->set_unique($unique,$itemLine,false,$item['bn'].'有重复');
            }
        }else{
            $result['msg'][ $inline ] .= '缺少明细货品|';
            $result['status'] = 'fail';
        }
        
        #账期验证
        $rs = finance_io_bill_verify::isTaskCheckInitTime($data['date'],$mdl->_import_func->task_id);
        if($rs['status'] == 'fail'){
            $result['msg'][ $inline ] .= $rs['msg'].'|';
            $result['status'] = 'fail';
        }
        return $result;
    }

    //验证货品是否存在主明细
    /**
     * field_verify_product_isExists
     * @param mixed $row row
     * @param mixed $mdl mdl
     * @return mixed 返回值
     */
    public function field_verify_product_isExists(&$row,&$mdl){
        $result = array();
        $data = $mdl->_import_func->select(1,array($row['data']['serial_number']),'fkey','mkey');
        if(count($data)<=0){
            $result['msg'][ $row['inline'] ] .= '找不到相应单据信息|';
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
    public function getSDf(&$mdl,$row)
    {
        if(!$row) return false;

        static $oldKey,$layer;

        $titles = finance_io_bill_title::getTitle('ar');
        foreach ($titles as $key => $value) {
            if ( !$oldKey ) {
                $title = $value;
                $layer = $key;
            } else {
                if ($row == array_values($value)) {
                    $title = $value;
                    $layer = $key;
                    unset($oldKey);
                    break;
                }
            }
        }

        if (!$oldKey) {

            # 读取文件标题，并记录它的位置
            foreach ($title as $key => $value) {
                $pCol = array_search($value, $row,true);
                if ($pCol === false) {
                    $oldKey = '';
                    return false;
                }

                $oldKey[$key] = $pCol;
            }
            return false;
        }

        if (empty($oldKey)) {
            return false;
        }

        # 读取数据
        foreach ($oldKey as $column => $pCol) {
            $tmp[$column] = $row[$pCol];
        }

        # 验证数据
        switch ($layer) {
            case '1':
                $base_sdf = array(
                    'trade_time' => $tmp['date'],
                    'member' => $tmp['member'],
                    'type' => $tmp['type'],
                    'order_bn' => $tmp['order_bn'],
                    'relate_order_bn' => $tmp['relate_order_bn'],
                    'channel_id' => '',
                    'channel_name' => '',
                    'sale_money' => $tmp['sale_money'] ? $tmp['sale_money'] : 0,
                    'fee_money' => $tmp['fee_money'] ? $tmp['fee_money'] : 0,
                    'money' => $tmp['money'] ? $tmp['money'] : 0,
                    'serial_number' => $tmp['serial_number'],
                    'memo' => '',
                    'unique_id' => finance_func::unique_id(array(
                        $tmp['serial_number']
                    )),
                );
                break;
            case '2':
                $base_sdf = array(
                    'bn' => $tmp['bn'],
                    'name' => $tmp['name'],
                    'num' => $tmp['nums'] ? $tmp['nums'] : 1,
                    'money' => $tmp['price'] ? $tmp['price'] : 0,
                );
                break;
            default:
                return false;
                break;
        }

        $sdf[] = $base_sdf;

        $mdl->import_layer = $layer;
        
        return $sdf;
    }

}
?>