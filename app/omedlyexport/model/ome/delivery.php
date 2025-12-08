<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omedlyexport_mdl_ome_delivery extends ome_mdl_delivery{

    //是否有导出配置
    var $has_export_cnf = true;

    public $export_name = '发货单';

    function __construct()
    {
        parent::__construct(app::get('ome'));
    }

    /**
     * 数据导出
     */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $oBranch = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        
        //第三方发货,选定全部，导出的过滤条件
        if($filter['ctl'] == 'admin_receipts_outer' && $filter['isSelectedAll'] == '_ALL_'){
            #已发货
            if($filter['view'] == 1){
                $filter['status'] = array(0 =>'succ');
            }
            #未发货
            if($filter['view'] == 2){
                $filter['status'] = array (0 => 'ready',1 => 'progress');
            }
            //$oBranch = app::get('ome')->model('branch');
            $outerBranch = array();
            #第三方仓库
            $tmpBranchList = $oBranch->getList('branch_id',array('owner'=>'2'));
            #获取操作员管辖仓库
            foreach ($tmpBranchList as $key => $value) {
                $outerBranch[] = $value['branch_id'];
            }
            //$is_super = kernel::single('desktop_user')->is_super();
            if (!$is_super) {
                $branch_ids = $oBranch->getBranchByUser(true);
                if ($branch_ids) {
                    $filter['branch_id'] = $filter['branch_id'] ? $filter['branch_id'] : $branch_ids;
                    $filter['branch_id'] = array_intersect($filter['branch_id'], $outerBranch); //取管辖仓与第三方仓的交集
                } else {
                   $filter['branch_id'] = 'false';
                }
            } else {
                if($filter['branch_id']){
                    $filter['branch_id'] = $filter['branch_id'];
                 }else{
                    $filter['branch_id'] =  $outerBranch;
                 }
            }
        }elseif($filter['ctl'] == 'admin_delivery_send' && $filter['isSelectedAll'] == '_ALL_'){
            //通知仓库新建列表--导出
            if($filter['view'] == '3'){
                $filter['sync_status'] = '2';
            }elseif($filter['view'] == '4'){
                $filter['sync_status'] = '3';
            }
        }
        
         //获取所有仓库名称
         $all_branch_info = $oBranch->getList('branch_id,name',array());
         $all_branch_name = array();
         foreach($all_branch_info as $v){
             $all_branch_name[$v['branch_id']] = $v['name'];
         }
         unset($all_branch_info);

        foreach($filter as $key=>$val){
            if(($filter[$key] == '' || empty($filter[$key])) && $key != 'parent_id'){
                unset($filter[$key]);
            }
        }
        $deliveryObj = app::get('ome')->model('delivery');
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $obj_queue_items   = app::get('ome')->model('print_queue_items');
        $deliveryObj->filter_use_like = true;
        $filterSql = $deliveryObj->_filter($filter,$tableAlias,$baseWhere);

        $deliveryColumns = array_keys($deliveryObj->_columns($filter,$tableAlias,$baseWhere));
        foreach($deliveryColumns as $col){
            if($col == 'delivery'){
                continue;
            }
            $filterSql = str_replace('.'.$col,'D.'.$col,str_replace('`sdb_ome_delivery`','',$filterSql));
            $filterSql = str_replace('AND delivery_id','AND D.delivery_id',$filterSql);
        }
        if($filterSql){
            $whereSql = ' WHERE '.$filterSql;
        }
        /***
        $sql = 'SELECT DI.item_id,O.order_bn,O.shop_id,O.tax_no,D.delivery_bn,D.member_id,D.logi_name,D.logi_no,D.ship_addr,D.ship_area,D.ship_name,D.ship_tel,D.ship_mobile,D.delivery_time,D.ship_zip,DI.bn,DI.product_name,OI.nums as number,OI.price,
            ROUND((O.cost_freight/OI.nums)*DI.number,3) AS freight,
            ROUND((O.cost_freight/OI.nums)*DI.number,3)+ROUND((OI.price)*OI.nums,3) as total
            FROM sdb_ome_delivery_items AS DI
            LEFT JOIN sdb_ome_delivery AS D
                    ON D.delivery_id = DI.delivery_id
            LEFT JOIN sdb_ome_delivery_order AS DO
                    ON DO.delivery_id = D.delivery_id
            LEFT JOIN sdb_ome_orders AS O
                    ON O.order_id = DO.order_id
            INNER JOIN sdb_ome_order_items AS OI
                    ON OI.order_id = O.order_id AND DI.bn=OI.bn '.$whereSql.' AND OI.delete=\'false\' group by OI.item_id ORDER BY D.delivery_id DESC';
        ***/

        //[拆单]增加获取发货单D.branch_id对应仓库
        $delivery_list  = array();
        $sql = 'select D.delivery_id, D.branch_id from sdb_ome_delivery AS D '.$whereSql.' ORDER BY D.delivery_id DESC';

        $rows = $this->db->selectLimit($sql,$limit,$offset);
        if (!$rows) {
            return false;
        }
        $ids = array();
        foreach ($rows as $k => $row){
            $ids[] = $row['delivery_id'];

            $delivery_list[$row['delivery_id']] = $row;
        }
        unset($rows);
        
        $basicMaterialObj    = app::get('material')->model('basic_material');

        $sql = 'SELECT DI.item_id, DI.product_id, D.delivery_bn, D.delivery_id, D.member_id, D.logi_name, D.logi_no, D.ship_addr, D.ship_area, D.ship_name, D.ship_tel, D.ship_mobile, D.delivery_time, D.ship_zip, DI.bn, DI.number as dn
            FROM sdb_ome_delivery_items AS DI
            LEFT JOIN sdb_ome_delivery AS D  ON D.delivery_id = DI.delivery_id
            where D.delivery_id in ('.implode(',',$ids).') ORDER BY D.delivery_id DESC';

        $rows = $this->db->select($sql);
        $tmp_delivery_info = array();
        foreach ($rows as $k => $row)
        {
            #基础物料名称
            $material_row          = $basicMaterialObj->dump(array('bm_id'=>$row['product_id']), 'material_name');
            $row['product_name']   = $material_row['material_name'];

            $tmp_delivery_info[$row['delivery_id'].$row['bn']] = $row;
        }
        unset($rows);

        //[拆单]获取多个发货单对应订单信息
        $sql    = "SELECT DI.delivery_id, O.order_bn, O.custom_mark, O.mark_text, O.shop_id, O.tax_no, O.cost_freight,
                    DI.bn, DI.price, DI.amount, DI.number, DI.product_id
                    FROM sdb_ome_orders AS O
                    LEFT JOIN sdb_ome_delivery_items_detail AS DI
                            ON DI.order_id = O.order_id
                    WHERE DI.delivery_id in(".implode(',',$ids).") ORDER BY DI.delivery_id DESC";

        /***
        $sql = 'SELECT D.branch_id,O.order_bn,O.custom_mark,O.mark_text,O.shop_id,O.tax_no,O.cost_freight,OI.nums as number,ROUND((OI.sale_price/OI.nums),3) as price,OI.sale_price,DO.delivery_id,OI.bn,OI.product_id,D.branch_id
            FROM sdb_ome_order_items AS OI
            LEFT JOIN sdb_ome_orders AS O
                    ON O.order_id = OI.order_id
            LEFT JOIN sdb_ome_delivery_order AS DO
                    ON DO.order_id = O.order_id
            LEFT JOIN sdb_ome_delivery AS D
                    ON D.delivery_id = DO.delivery_id
            where D.delivery_id in ('.implode(',',$ids).') AND OI.delete=\'false\' ORDER BY D.delivery_id DESC, OI.bn ASC';
        ***/
        $rows = $this->db->select($sql);
        //备注显示方式
        $markShowMethod = app::get('ome')->getConf('ome.order.mark');
        $tmp_order = array();
        foreach ($rows as $k => $row)
        {
            //[拆单]独立获取branch_id值
            $row['branch_id']           = $delivery_list[$row['delivery_id']]['branch_id'];
            $rows[$k]['branch_id']      = $row['branch_id'];

             #同一订单运费只显示一次

             if(!isset($tmp_order[$row['order_bn']])){
                $tmp_order[$row['order_bn']] = $row['order_bn'];
                $cost_freight = round(($row['cost_freight']/$row['number'])*$tmp_delivery_info[$row['delivery_id'].$row['bn']]['dn'],3);
            }else{

                $cost_freight = 0;
            }
            if(isset($tmp_delivery_info[$row['delivery_id'].$row['bn']])){
                $rows[$k] = array_merge($row,$tmp_delivery_info[$row['delivery_id'].$row['bn']]);
                $rows[$k]['freight'] = $cost_freight;
                //$rows[$k]['total'] = $cost_freight+ROUND($row['sale_price'],3);

                $rows[$k]['total']  = $cost_freight + (ROUND($row['price'], 3) * $row['number']);
            }
            $rows[$k]['branch_id'] = $all_branch_name[$row['branch_id']]?$all_branch_name[$row['branch_id']]:'-';
            #获取所有货位
            $_sql = 'select store_position from sdb_ome_branch_pos bpos left join sdb_ome_branch_product_pos  ppos on bpos. pos_id=ppos.pos_id where bpos.branch_id='.$row['branch_id'].' and product_id='.$row['product_id'];
            $_rows = $this->db->select($_sql);
            $_store_position = null;
            if(!empty($_rows[0])){
                #一个货品有多个货位时，中间要隔开
                foreach($_rows as $v){
                     $_store_position .= $v['store_position'].'|';
                }
            }
            #切掉尾部符号
            $_store_position  = substr_replace($_store_position,'',-1,1);
            $rows[$k]['store_position'] = $_store_position;

            #处理货品多规格值
            $spec_value = '';
            /*
            if(is_array($product_info['spec_desc']['spec_value']) && !empty($product_info['spec_desc']['spec_value'])){
                $spec_value = implode('|',$product_info['spec_desc']['spec_value']);
            }
            */
            $rows[$k]['spec_value']  = '';//$spec_value;

            //计量单位
            $goodsInfo = $basicMaterialExtObj->getList('unit',array('bm_id'=>$row['product_id']));
            $rows[$k]['unit']  = isset($goodsInfo[0]['unit']) ? $goodsInfo[0]['unit'] : '';
            $queue_items = $obj_queue_items->getlist('ident,ident_dly',array('delivery_id'=>$rows[$k]['delivery_id']));
            if($queue_items[0]['ident'] && $queue_items[0]['ident_dly']){
                $rows[$k]['ident'] = $queue_items[0]['ident'].'_'.$queue_items[0]['ident_dly'];
            }
            /* if($row['custom_mark']) {
                $custom_mark = unserialize($row['custom_mark']);
                if (is_array($custom_mark) || !empty($custom_mark)){
                    if($markShowMethod == 'all'){
                        foreach ($custom_mark as $_custom_mark ) {
                            $str_custom_mark .= $_custom_mark['op_content'];
                        }
                    }else{
                        $_memo = array_pop($custom_mark);
                        $str_custom_mark = $_memo['op_content'];
                    }
                }
                $rows[$k]['custom_mark'] = $str_custom_mark;
            }
            if($row['mark_text']) {
                $mark_text = unserialize($row['mark_text']);
                if (is_array($mark_text) || !empty($mark_text)){
                    if($markShowMethod == 'all'){
                        foreach ($mark_text as $im) {
                            $str_mark_text .= $im['op_content'];
                        }
                    }else{
                        $_memo = array_pop($mark_text);
                        $str_mark_text = $_memo['op_content'];
                    }
                }
                $rows[$k]['mark_text'] = $str_mark_text;
            } */
            unset($row,$_rows,$product_info);
        }
        $rows = $this->convert($rows);

        $item=array();
        $i=0;
        foreach($rows as $key=>$row){
            $ship_addr_arr = explode(':', $row['ship_area']);
            $rows[$key]['ship_area'] = $ship_addr_arr[1];
            $member = array();
            $memberObj = app::get('ome')->model('members');
            $member = $memberObj->getList('uname',array('member_id'=>$row['member_id']),0,1);
            $rows[$key]['member_id'] = $member[0]['uname'];
            $rows[$key]['order_bn'] =$row['order_bn']."\t";
            $rows[$key]['logi_no'] .= "\t";
            $item_id = $row['item_id'];
            if(isset($item[$item_id])){
                $i++;
                $rows[$key]['item_id']= $item_id.'_'.$i;
            }else{
                $item[$item_id]=$item_id;
                $rows[$key]['item_id']= $item_id;
            }
        }
        return $rows;
    }

    //格式化输出的内容字段
    /**
     * convert
     * @param mixed $rows rows
     * @param mixed $fields fields
     * @param mixed $has_detail has_detail
     * @return mixed 返回值
     */
    public function convert($rows, &$fields='', $has_detail=1){
        //反转扩展字段
        $fields = str_replace('column_custom_mark', 'custom_mark', $fields);
        $fields = str_replace('column_mark_text', 'mark_text', $fields);
        $fields = str_replace('column_tax_no', 'tax_no', $fields);
        $fields = str_replace('column_ident', 'ident', $fields);
        $fields = str_replace('column_bufa_reason', 'bufa_reason', $fields);
        $fields = str_replace('column_relate_order_bn', 'relate_order_bn', $fields);

        $tmp_rows = array();
        $schema = $this->get_schema();
        $detail_schema = $this->get_exp_detail_schema();
        //针对大而全的数据做格式化过滤，如果包含明细
        if($has_detail == 1){
            /*
            //找出不要的字段
            foreach($schema['in_list'] as $sk => $col){
                //将需要的字段从所有字段数组里去掉
                if(strpos($fields, $col) !== false){
                    unset($schema['in_list'][$sk]);
                }
            }

            foreach($rows as $key=>$row){
                foreach ($row as $column => $value) {
                    //不要的字段去掉
                    if(!in_array($column, $schema['in_list'])){
                        $tmp_rows[$key][$column] = $value;
                    }
                }
            }
            */

            //先处理主数据的排序
            foreach (explode(',', $fields) as $k => $col) {
                foreach ($rows as $key=>$row) {
                    foreach ($row as $cl => $value) {
                        //只保留配置的主字段
                        if($col == $cl){
                            $tmp_rows[$key][$col] = $row[$col];
                        }
                    }
                }
            }

            //继续处理明细数据的排序
            foreach ($detail_schema['columns'] as $col => $arr) {
                foreach ($rows as $key=>$row) {
                    foreach ($row as $cl => $value) {
                        //只保留配置的主字段
                        if($col == $cl){
                            $tmp_rows[$key][$col] = $row[$col];
                        }
                    }
                }
            }

        }else{
            $tmp_bns = array();
            //先将数组合并,去掉重复记录
            foreach($rows as $key=>$row){
                if(empty($tmp_bns[$row['delivery_bn']])){
                    $tmp_bns[$row['delivery_bn']][$row['order_bn']]= $row['order_bn'];
                }else{
                    $tmp_bns[$row['delivery_bn']][$row['order_bn']]= $row['order_bn'];
                    unset($rows[$key]);
                }
            }

            foreach (explode(',', $fields) as $k => $col) {
                foreach ($rows as $key=>$row) {
                    $row['order_bn'] = str_replace("\t",'',implode('、', $tmp_bns[$row['delivery_bn']]))."\t";
                    foreach ($row as $cl => $value) {
                        //只保留配置的主字段
                        if($col == $cl){
                            $tmp_rows[$key][$col] = $row[$col];
                        }
                    }
                }
            }
        }

        return $tmp_rows;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'order_bn' => array(
                    'type' => 'varchar(32)',
                    'required' => true,
                    'label' => '订单号',
                    'editable' => false,
                    'order' => 1,
                ),
                'shop_id' => array(
                    'type' => 'table:shop@ome',
                    'label' => '来源店铺',
                    'editable' => false,
                    'order' => 10,
                ),
                'tax_no' => array(
                    'type' => 'varchar(50)',
                    'label' => '发票号',
                    'editable' => false,
                    'order' => 50,
                ),
                'member_id' => array(
                    'type' => 'varchar(50)',
                    'label' => '会员用户名',
                    'comment' => '订货会员ID',
                    'editable' => false,
                    'order' => 12,
                ),
                'logi_name' => array(
                    'type' => 'varchar(100)',
                    'label' => '物流公司',
                    'comment' => '物流公司名称',
                    'editable' => false,
                    'order' => 14,
                ),
                'freight' => array(
                    'type' => 'money',
                    'default' => '0',
                    'required' => true,
                    'label' => '配送费用',
                    'editable' => false,
                    'order' => 17,
                ),
                'logi_no' => array(
                    'type' => 'varchar(50)',
                    'default' => '0',
                    'label' => '物流单号',
                    'editable' => false,
                    'order' => 16,
                ),
               'ship_addr' => array(
                  'type' => 'varchar(100)',
                  'label' => '收货地址',
                  'comment' => '收货人地址',
                  'editable' => false,
                  'order' => 39,
                ),
                'ship_area' => array(
                  'type' => 'region',
                  'label' => '收货地区',
                  'comment' => '收货人地区',
                  'editable' => false,
                  'order' => 38,
                ),
                'ship_name' => array(
                  'type' => 'varchar(50)',
                  'label' => '收货人',
                  'comment' => '收货人姓名',
                  'editable' => false,
                  'order' => 30,
                ),
                'ship_tel' => array(
                  'type' => 'varchar(30)',
                  'label' => '收货人电话',
                  'comment' => '收货人电话',
                  'editable' => false,
                  'order' => 32,
                ),
                'ship_mobile' => array(
                  'type' => 'varchar(50)',
                  'label' => '收货人手机',
                  'comment' => '收货人手机',
                  'editable' => false,
                  'order' => 31,
                ),
                'ship_zip' => array(
                  'type' => 'varchar(20)',
                  'label' => '收货邮编',
                  'comment' => '收货人邮编',
                  'editable' => false,
                  'order' => 35,
                ),
                'delivery_time' => array(
                    'type' => 'time',
                    'label' => '发货时间',
                    'comment' => '发货时间',
                    'editable' => false,
                    'order' => 90,
                ),
                'delivery_bn' => array(
                    'type' => 'varchar(32)',
                    'label' => '发货单号',
                    'comment' => '配送流水号',
                    'editable' => false,
                    'order' => 2,
                ),
                'ident' => array(
                    'type' => 'varchar(64)',
                    'label' => '批次号',
                    'comment' => '本次打印的批次号',
                    'editable' => false,
                    'order' => 5,
                ),
                'custom_mark' => array(
                    'type' => 'longtext',
                    'label' => '买家留言',
                    'editable' => false,
                    'order' => 90,
                ),
                'mark_text' => array(
                    'type' => 'longtext',
                    'label' => '客服备注',
                    'editable' => false,
                    'order' => 91,
                ),
                'branch_id' => array(
                    'type' => 'number',
                    'editable' => false,
                    'label' => '发货仓库',
                    'order' => 51,
                ),
                'create_time'=>array (
                  'type' => 'time',
                  'label' => '创建时间',
                  'editable' => false,
                  'order' => 98,
                ),
                'order_createtime' => array (
                    'type' => 'time',
                    'label' => '成单时间',
                    'width' => 130,
                    'editable' => false,
                    'order' => 97,
                ),
                'embrace_time'=>array (
                    'type' => 'time',
                    'label' => '快件揽收时间',
                    'editable' => false,
                    'order' => 95,
                ),
                'sign_time'=>array (
                    'type' => 'time',
                    'label' => '客户签收时间',
                    'editable' => false,
                    'order' => 96,
                ),
                'last_modified'=>array (
                    'type' => 'last_modify',
                    'label' => '最后更新时间',
                    'editable' => false,
                    'order' => 99,
                ),
                'package_bn'=>array (
                    'type' => 'varchar(50)',
                    'label' => '包裹号',
                    'editable' => false,
                    'order' => 20,
                ),
                'bufa_reason'=>array (
                    'type' => 'varchar(255)',
                    'label' => '补发原因',
                    'editable' => false,
                    'order' => 100,
                ),
                'relate_order_bn'=>array (
                    'type' => 'varchar(32)',
                    'label' => '关联订单号',
                    'editable' => false,
                    'order' => 100,
                ),
            ),
            'idColumn' => 'bn',
            'in_list' => array(
                0 => 'order_bn',
                1 => 'shop_id',
                2 => 'tax_no',
                3 => 'member_id',
                4 => 'logi_name',
                //5 => 'bn',
                //6 => 'product_name',
                //7 => 'number',
                //8 => 'price',
                9 => 'freight',
                //10 => 'total',
                11 => 'logi_no',
                12 => 'ship_addr',
                13 => 'ship_area',
                14 => 'ship_name',
                15 => 'ship_tel',
                16 => 'ship_mobile',
                17 => 'ship_zip',
                18 => 'delivery_time',
                19 => 'delivery_bn',
                //20 => 'item_id',
                //21=> 'store_position',
                //22=> 'spec_value',
                23=> 'ident',
                24=>'custom_mark',
                25=>'mark_text',
                26=> 'branch_id',
                27=>'create_time',
                28=>'embrace_time',
                29=>'sign_time',
                30=>'last_modified',
                31=>'package_bn',
                32 => 'order_createtime',
                33 => 'bufa_reason',
                34 => 'relate_order_bn',
            ),
            'default_in_list' => array(
                0 => 'order_bn',
                1 => 'shop_id',
                2 => 'tax_no',
                3 => 'member_id',
                4 => 'logi_name',
                //5 => 'bn',
                //6 => 'product_name',
                //7 => 'number',
                //8 => 'price',
                9 => 'freight',
                //10 => 'total',
                11 => 'logi_no',
                12 => 'ship_addr',
                13 => 'ship_area',
                14 => 'ship_name',
                15 => 'ship_tel',
                16 => 'ship_mobile',
                17 => 'ship_zip',
                18 => 'delivery_time',
                19 => 'delivery_bn',
                //20 => 'item_id',
                //21=>  'store_position',
                //22=> 'spec_value',
                23=> 'ident',
                24=>'custom_mark',
                25=>'mark_text',
                26=>'branch_id',
                27=>'create_time',
                28=>'embrace_time',
                29=>'sign_time',
                30=>'last_modified',
                31=>'package_bn',
                32 => 'order_createtime',
                33 => 'bufa_reason',
                34 => 'relate_order_bn',
            ),
        );
        
        return $schema;
    }

    //定义导出明细内容的相关字段
    /**
     * 获取_exp_detail_schema
     * @return mixed 返回结果
     */
    public function get_exp_detail_schema(){
        $schema = array (
            'columns' => array (
                'bn' => array(
                    'type' => 'varchar(30)',
                    'label' => '商品货号',
                    'width' => 85,
                    'editable' => false,
                ),
                'product_name' => array(
                    'type' => 'varchar(200)',
                    'required' => true,
                    'default' => '',
                    'label' => '商品名称',
                    'width' => 190,
                    'editable' => false,
                ),
                'number' => array(
                    'type' => 'number',
                    'required' => true,
                    'default' => 0,
                    'label' => '购买数量',
                    'editable' => false,
                ),
                'price' => array(
                    'type' => 'money',
                    'default' => '0',
                    'required' => true,
                    'label' => '商品单价',
                    'editable' => false,
                ),
                'avgprice' => array(
                    'type' => 'money',
                    'default' => '0',
                    'required' => true,
                    'label' => '商品均单价',
                    'editable' => false,
                ),
                'weight' => array(
                    'type' => 'decimal(20,3)',
                    'default' => '0.000',
                    'label' => '商品重量(g)',
                    'editable' => false,
                ),
                'total' => array(
                    'type' => 'money',
                    'default' => '0',
                    'label' => '总价',
                    'width' => 70,
                    'editable' => false,
                ),
                'item_id' => array(
                    'type' => 'int unsigned',
                    'label' => '发货单明细流水号',
                    'comment' => '发货单明细流水号',
                    'editable' => false,
                ),
                'store_position' => array(
                    'type' => 'varchar(100)',
                    'label' => '货位',
                    'comment' => '货位',
                    'editable' => false,
                ),
                'spec_value' => array(
                    'type' => 'varchar(100)',
                    'label' => '规格',
                    'comment' => '规格',
                    'editable' => false,
                ),
            ),
        );
        return $schema;
    }

    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null){
        $deliveryObj = app::get('ome')->model('delivery');
        $deliveryObj->filter_use_like = true;
        $filterSql = $deliveryObj->_filter($filter,$tableAlias,$baseWhere);
        $deliveryColumns = array_keys($deliveryObj->_columns($filter,$tableAlias,$baseWhere));
        foreach($deliveryColumns as $col){
            if($col == 'delivery'){
                continue;
            }
            //$filterSql = str_replace('.'.$col,'D.'.$col,str_replace('`sdb_ome_delivery`','',$filterSql));
            $filterSql = str_replace('`sdb_ome_delivery`.'.$col,'D.'.$col,$filterSql);
            $filterSql = str_replace('AND delivery_id','AND D.delivery_id',$filterSql);
        }

        $sql = 'SELECT count(D.delivery_id) as _count FROM sdb_ome_delivery as D where '.$filterSql;

        $count = $this->db->selectrow($sql);
        return intval($count['_count']);

    }
    function export_csv($data,$exportType = 1 ){
        if(!$this->is_queue_export){
            $data['title'] = $this->charset->utf2local($data['title']);
            foreach ($data['contents'] as $key => $value) {
                $data['contents'][$key] = $this->charset->utf2local($value);
            }
        }

        $output = array();
        $output[] = $data['title']."\n".implode("\n",(array)$data['contents']);

        if ($this->is_queue_export == true) {
            return implode("\n",$output);
        } else {
            echo implode("\n",$output);
        }
    }
    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'delivery';
        if ($logParams['app'] == 'omedlyexport' && $logParams['ctl'] == 'ome_delivery') {
            $type .= '_orders';
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'delivery';
        $type .= '_import';
        return $type;
    }
    
    //根据过滤条件获取导出发货单的主键数据数组
    public function getPrimaryIdsByCustom($filter, $op_id)
    {
        $oBranch = app::get('ome')->model('branch');
        #第三方发货,选定全部，导出的过滤条件
        if($filter['ctl'] == 'admin_receipts_outer' && $filter['isSelectedAll'] == '_ALL_'){
            #过滤子单
            $filter['parent_id'] = 0;
            #已发货
            if($filter['view'] == 1){
                $filter['status'] = array(0 =>'succ');
            }
            #未发货
            if($filter['view'] == 2){
                $filter['status'] = array (0 => 'ready',1 => 'progress');
            }
            //$oBranch = app::get('ome')->model('branch');
            $outerBranch = array();
            #第三方仓库
            $tmpBranchList = $oBranch->getList('branch_id',array('owner'=>'2'));
            #获取操作员管辖仓库
            foreach ($tmpBranchList as $key => $value) {
                $outerBranch[] = $value['branch_id'];
            }

            $userObj = app::get('desktop')->model('users');
            $userInfo = $userObj->dump($op_id,'super');
            if (!$userInfo['super']) {
                $branch_ids = kernel::single('ome_op')->getBranchByOpId($op_id);
                if ($branch_ids) {
                    $filter['branch_id'] = $filter['branch_id'] ? $filter['branch_id'] : $branch_ids;
                    $filter['branch_id'] = array_intersect($filter['branch_id'], $outerBranch); //取管辖仓与第三方仓的交集
                } else {
                   $filter['branch_id'] = 'false';
                }
            } else {
                if($filter['branch_id']){
                    $filter['branch_id'] = $filter['branch_id'];
                 }else{
                    $filter['branch_id'] =  $outerBranch;
                 }
            }
        }elseif($filter['ctl'] == 'admin_delivery_send' && $filter['isSelectedAll'] == '_ALL_'){
            //通知仓库新建列表--导出
            if($filter['view'] == '3'){
                $filter['sync_status'] = '2';
            }elseif($filter['view'] == '4'){
                $filter['sync_status'] = '3';
            }
        }
        
        foreach($filter as $key=>$val){
            if(($filter[$key] == '' || empty($filter[$key])) && $key != 'parent_id'){
                unset($filter[$key]);
            }
        }
        $deliveryObj = app::get('ome')->model('delivery');
        $deliveryObj->filter_use_like = true;
        $filterSql = $deliveryObj->_filter($filter,$tableAlias,$baseWhere);

        $deliveryColumns = array_keys($deliveryObj->_columns($filter,$tableAlias,$baseWhere));
        foreach($deliveryColumns as $col){
            if($col == 'delivery'){
                continue;
            }
            if($col == 'sync' && isset($filter["sync"])){ //开发线存在sync的高级筛选 做替换时sync后要加个= 否则会覆盖后续的sync_开头的字段 sql条件会出错
                $filterSql = str_replace('.'.$col."=",'D.'.$col."=",str_replace('`sdb_ome_delivery`','',$filterSql));
            }else{
                $filterSql = str_replace('.'.$col,'D.'.$col,str_replace('`sdb_ome_delivery`','',$filterSql));
            }
            $filterSql = str_replace('AND delivery_id','AND D.delivery_id',$filterSql);
        }
        
        //[兼容]防止替换sql语句中输出DD.***导致sql报错
        $filterSql = str_replace('DD.', 'D.', $filterSql);
        
        if($filter['sku']=='single'){
            $filterSql .= ' AND D.skuNum=1';
        }

        if($filter['sku']=='multi'){
            $filterSql .= ' AND D.skuNum!=1';
        }

        if($filterSql){
            $whereSql = ' WHERE '.$filterSql;
        }

        $sql = 'select D.delivery_id  from sdb_ome_delivery AS D '.$whereSql.' ORDER BY D.delivery_id DESC';
        $rows = $this->db->select($sql);
        if (!$rows) {
            return false;
        }
        $ids = array();
        foreach ($rows as $k => $row){
            $ids[] = $row['delivery_id'];
        }
        
        return $ids;
    }

    //根据主键id获取导出数据
    /**
     * 获取ExportDataByCustom
     * @param mixed $fields fields
     * @param mixed $filter filter
     * @param mixed $has_detail has_detail
     * @param mixed $curr_sheet curr_sheet
     * @param mixed $start start
     * @param mixed $end end
     * @param mixed $op_id ID
     * @return mixed 返回结果
     */
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id){
        $ids = $filter['delivery_id'];
        $obj_queue_items   = app::get('ome')->model('print_queue_items');
        $oBranch = app::get('ome')->model('branch');
        $dlyObj = app::get('ome')->model('delivery');
        $dlyorderObj = app::get('ome')->model('delivery_order');

        // 发货单明细
        $sql = sprintf('SELECT * FROM sdb_ome_delivery_items_detail WHERE delivery_id in(%s)',implode(',',$ids));
        $rows = $this->db->select($sql);
        
        //发货单
        $fields = 'branch_id,delivery_bn,delivery_id,member_id,logi_name,logi_no,ship_addr,ship_area,ship_name,ship_tel,ship_mobile,delivery_time,ship_zip,create_time,embrace_time,sign_time,last_modified,order_createtime,shop_id';
        $sql = sprintf('SELECT '. $fields .' FROM sdb_ome_delivery WHERE delivery_id in(%s)', implode(',',$ids));
        $list = $this->db->select($sql);

        $branchIds = array();
        foreach ($list as $key => $value) {
            $deliverys[$value['delivery_id']] = $value;
            $branchIds[] = $value['branch_id'];
        }

        // 批次
        $sql = sprintf('SELECT * FROM sdb_ome_print_queue_items WHERE delivery_id in(%s)',implode(',',$ids));
        $list = $this->db->select($sql);
        foreach ($list as $key => $value) {
            $print_batch[$value['delivery_id']] = $value;
        }

        // 订单
        $orderIds = array(); $productIds = array();
        foreach ($rows as $key => $value) {
            $orderIds[] = $value['order_id'];
            $productIds[] = $value['product_id'];
        }
        $sql = sprintf('SELECT order_id, order_bn, custom_mark, mark_text, shop_id, tax_no, cost_freight, bufa_reason, relate_order_bn FROM sdb_ome_orders where order_id in(%s)',implode(',',$orderIds));
        $list = $this->db->select($sql);
        foreach ($list as $key => $value) {
            $orders[$value['order_id']] = $value;
        }

        // 订单明细
        $sql = sprintf('SELECT * FROM sdb_ome_order_objects WHERE order_id in(%s)',implode(',',$orderIds));
        $list = $this->db->select($sql);
        foreach ($list as $key => $value) {
            $orders[$value['order_id']]['order_objects'][$value['obj_id']] = $value;
        }

        $sql = sprintf('SELECT *,nums AS quantity FROM sdb_ome_order_items WHERE order_id in(%s)',implode(',',$orderIds));
        $list = $this->db->select($sql);
        foreach ($list as $key => $value) {
            $orders[$value['order_id']]['order_objects'][$value['obj_id']]['order_items'][$value['item_id']] = $value;
        }

        // 基础物料信息
        $sql    = "SELECT a.bm_id, a.material_name, b.weight FROM sdb_material_basic_material AS a
                   LEFT JOIN sdb_material_basic_material_ext AS b ON a.bm_id=b.bm_id WHERE a.bm_id in(". implode(',', $productIds) .")";
        $list = $this->db->select($sql);

        $products    = array();
        foreach ($list as $key => $value)
        {
            $products[$value['bm_id']] = array('product_id'=>$value['bm_id'], 'product_name'=>$value['material_name'], 'weight'=>$value['weight']);
        }

        // 仓库
        $branches = array();
        if ($branchIds) {
            $sql = sprintf('SELECT branch_id,name FROM sdb_ome_branch WHERE branch_id in(%s)',implode(',',$branchIds));
            $list = $this->db->select($sql);
            foreach ($list as $key => $value) {
                $branches[$value['branch_id']] = $value;
            }
        }

        // 发货单对应的订单
        $delivery_order = array();
        foreach ($rows as $key => $value) {
            $delivery_order[$value['delivery_id']][$value['order_id']] = $orders[$value['order_id']];
        }
        
        //包裹号(一个发货单有多个包裹号的场景)
        $packageList = array();
        $sql = sprintf('SELECT package_id,delivery_id,package_bn,status FROM sdb_ome_delivery_package WHERE delivery_id IN(%s)', implode(',',$ids));
        $tempData = $this->db->select($sql);
        if($tempData){
            foreach ($tempData as $tempKey => $tempVal)
            {
                $delivery_id = $tempVal['delivery_id'];
                $package_bn = $tempVal['package_bn'];
                
                //过滤已经取消的
                if($tempVal['status'] == 'cancel'){
                    continue;
                }
                
                $packageList[$delivery_id][$package_bn] = $package_bn;
            }
        }
        
        //备注显示方式
        $markShowMethod = app::get('ome')->getConf('ome.order.mark');
        $tmp_order = array(); $cost_freight_flag = array();
        foreach ($rows as &$row){
            $delivery_id = $row['delivery_id'];
            
            $row = array_merge($row,(array)$orders[$row['order_id']],(array)$deliverys[$row['delivery_id']],(array)$products[$row['product_id']],(array)$print_batch[$row['delivery_id']]); unset($row['order_objects']);

            $row['freight'] = $cost_freight_flag[$row['order_id']] ? 0 : $row['cost_freight'];
            $cost_freight_flag[$row['order_id']] = $row['order_id'];
            $row['total'] = $row['freight'] + $row['amount'];
            $row['branch_id'] = $branches[$row['branch_id']]['name'] ? $branches[$row['branch_id']]['name']:'-';
            #获取所有货位
            $_sql = 'select store_position from sdb_ome_branch_pos bpos left join sdb_ome_branch_product_pos  ppos on bpos. pos_id=ppos.pos_id where bpos.branch_id='.intval($row['branch_id']).' and product_id='.$row['product_id'];
            $_rows = $this->db->select($_sql);
            $_store_position = null;
            if(!empty($_rows[0])){
                #一个货品有多个货位时，中间要隔开
                foreach($_rows as $v){
                     $_store_position .= $v['store_position'].'|';
                }
            }
            #切掉尾部符号
            $_store_position  = substr_replace($_store_position,'',-1,1);
            $row['store_position'] = $_store_position;

            //处理商品均单价
            $sale_orders = $this->getsale_price($delivery_order[$row['delivery_id']]);
            $row['avgprice'] = $sale_orders[$row['bn']] ? $sale_orders[$row['bn']] : 0;
            $row['spec_value']  = '';
            $row['ident'] = $row['ident'] && $row['ident_dly'] ?  $row['ident'].'_'.$row['ident_dly'] : '-';

            $str_custom_mark ='';
            if($row['custom_mark']) {
                $custom_mark = unserialize($row['custom_mark']);
                if (is_array($custom_mark) || !empty($custom_mark)){
                    if($markShowMethod == 'all'){
                        foreach ($custom_mark as $_custom_mark ) {
                            $str_custom_mark .= $_custom_mark['op_content'];
                        }
                    }else{
                        $_memo = array_pop($custom_mark);
                        $str_custom_mark = $_memo['op_content'];
                    }
                }
                $row['custom_mark'] = $str_custom_mark;
            }else{
                $row['custom_mark'] = '-';
            }

            $str_mark_text ='';
            if($row['mark_text']) {
                $mark_text = unserialize($row['mark_text']);
                if (is_array($mark_text) || !empty($mark_text)){
                    if($markShowMethod == 'all'){
                        foreach ($mark_text as $im) {
                            $str_mark_text .= $im['op_content'];
                        }
                    }else{
                        $_memo = array_pop($mark_text);
                        $str_mark_text = $_memo['op_content'];
                    }
                }
                $row['mark_text'] = $str_mark_text;
            }else{
                $row['mark_text'] = '-';
            }
            
            //包裹号(一个发货单有多个包裹号的场景)
            $row['package_bn'] = '-';
            if($packageList[$delivery_id]){
                $row['package_bn'] = implode('，', $packageList[$delivery_id]);
            }
    
            $row['create_time']      = $row['create_time'] ? date('Y-m-d H:i:s', $row['create_time']) : $row['create_time'];
            $row['last_modified']    = $row['last_modified'] ? date('Y-m-d H:i:s', $row['last_modified']) : $row['last_modified'];
            $row['order_createtime'] = $row['order_createtime'] ? date('Y-m-d H:i:s', $row['order_createtime']) : $row['order_createtime'];
            $row['delivery_time']    = $row['delivery_time'] ? date('Y-m-d H:i:s', $row['delivery_time']) : $row['delivery_time'];
            $row['embrace_time']     = $row['embrace_time'] ? date('Y-m-d H:i:s', $row['embrace_time']) : $row['embrace_time'];
            $row['sign_time']        = $row['sign_time'] ? date('Y-m-d H:i:s', $row['sign_time']) : $row['sign_time'];
        }

        $item=array();
        $i=0;
        foreach($rows as $key=>$value){
            $ship_addr_arr = explode(':', $value['ship_area']);
            $rows[$key]['ship_area'] = $ship_addr_arr[1];
            $member = array();
            $memberObj = app::get('ome')->model('members');
            $member = $memberObj->getList('uname',array('member_id'=>$value['member_id']),0,1);
            $rows[$key]['member_id'] = $member[0]['uname'];
            
            //$rows[$key]['order_bn'] .= "\t";
            
            $rows[$key]['logi_no'] .= "\t";
            $item_id = $value['item_id'];
            if(isset($item[$item_id])){
                $i++;
                $rows[$key]['item_id']= $item_id.'_'.$i;
            }else{
                $item[$item_id]=$item_id;
                $rows[$key]['item_id']= $item_id;
            }
        }

        //导出数据客户敏感信息处理
        $securityLib = kernel::single('ome_security_customer');
        $securityLib->check_sensitive_info($rows , 'omedlyexport_mdl_ome_delivery', $op_id);
        
        //订单号
        $fields .= ',order_bn,bufa_reason,relate_order_bn';
        
        //error_log(var_export($rows,true)."\n\t",3,"/www/be.log");
        $crows = $this->convert($rows, $fields, $has_detail);
        //error_log(var_export($crows,true)."\n\t",3,"/www/af.log");
        
        //使用csv的方式格式化导出数据
        $new_rows = $this->formatCsvExport($crows);

        $export_arr['content']['main'] = array();
        //如果是第一分片那么加上标题
        if($curr_sheet == 1){

            $title = array();
            $main_schema = $this->get_schema();
            $detail_schema = $this->get_exp_detail_schema();
            //error_log(var_export($new_rows,true)."\n\t",3,"/www/new_rows.log");

            foreach (explode(',', $fields) as $key => $col) {
                if(isset($main_schema['columns'][$col])){
                    $title[] = "*:".$main_schema['columns'][$col]['label'];
                }
            }

            if($has_detail == 1){
                foreach ($detail_schema['columns'] as $key => $col) {
                    $title[] = "*:".$col['label'];
                }
            }

            foreach ((array)$title as $key => $value) {
                $title[$key] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }

            $export_arr['content']['main'][0] = implode(',', $title);
            unset($main_schema, $detail_schema);
        }

        $new_line = 1;
        foreach($new_rows as $row => $content){
            $tmp_arr = array();
            foreach ($content as $value) {
                $tmp_arr[] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }
            $export_arr['content']['main'][$new_line] = implode(',', $tmp_arr);
            $new_line++;
        }
        
        //销毁
        unset($packageList);
        
        return $export_arr;

    }

    //导出重写该方法，直接通过自定义schema获取字段列表
    /**
     * extra_cols
     * @return mixed 返回值
     */
    public function extra_cols(){
        return array();
    }

    //重写字段方法，导出格式化的时候会调用到，不在名单里的字段直接剔除
    /**
     * _columns
     * @return mixed 返回值
     */
    public function _columns(){
        $main_schema = $this->get_schema();
        $detail_schema = $this->get_exp_detail_schema();
        return array_merge($main_schema['columns'], $detail_schema['columns']);
    }

    function getsale_price($data){

        $sale_order = array();
        foreach($data as $key=>$order){
            // $order = $orderObj->dump($val['order_id'],"order_id",array("order_objects"=>array("*",array("order_items"=>array('bn,pmt_price,sale_price,nums,price')))));
            foreach($order['order_objects'] as $k=>$v){
                if($v['obj_type']=='pkg' || $v['obj_type']=='gift' || $v['obj_type']=='giftpackage'){
                    $item_amount = $this->db->selectrow('SELECT sum(nums) as nums FROM sdb_ome_order_items WHERE obj_id='.$v['obj_id'].'');
                    $pvg_price = round($v['sale_price']/$item_amount['nums'],2);
                    foreach($v['order_items'] as $k1=>$v1){
                        if(isset($sale_order[$v1['bn']])){
                            $sale_order[$v1['bn']]['obj_quantity'] += $v1['quantity'];
                            $sale_order[$v1['bn']]['obj_sale_price'] += ($v1['quantity']*$pvg_price);
                        }else{
                            $sale_order[$v1['bn']]['obj_quantity'] = $v1['quantity'];
                            $sale_order[$v1['bn']]['obj_sale_price'] = ($v1['quantity']*$pvg_price);
                        }
                    }
                } else {
                    foreach( $v['order_items'] as $k1=>$v1 ){
                         if ( isset( $sale_order[$v1['bn']]) ){
                            $sale_order[$v1['bn']]['quantity'] += $v1['quantity'];
                            $sale_order[$v1['bn']]['sale_price'] += $v1['sale_price'];
                        }else{
                            $sale_order[$v1['bn']]['quantity'] = $v1['quantity'];
                            $sale_order[$v1['bn']]['sale_price'] = $v1['sale_price'];
                        }
                    }
                }
            }
        }

        $sale_price = array();
        foreach($sale_order as $k=>$v){
            $price = ($v['obj_sale_price']+$v['sale_price']);
            $quantity = $v['quantity']+$v['obj_quantity'];
            $sale_price[$k]=$quantity == 0 ? 0 : round($price/$quantity,2);
        }

        return $sale_price;

    }
}
