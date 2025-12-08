<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_mdl_refundinfo extends dbeav_model
{
    var $export_name = '退货单';
    var $has_export_cnf = true;


    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'saleordid'=>app::get('base')->_('订单号'),
            'partcode'=>app::get('base')->_('备件码'),
        );
        return array_merge($childOptions,$parentOptions);
    }


    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){

        if (isset($filter['saleordid'])){
            $refundinfo_ids = array();
            $refundinfo_ids[] = 0;
            $items = $this->db->select("SELECT refundinfo_id FROM sdb_ediws_refundinfo_items  WHERE saleordid='".$filter['saleordid']."'");

            if($items){
                foreach($items as $v){
                    $refundinfo_ids[] = $v['refundinfo_id'];
                }
            }
            $where .= '  AND refundinfo_id IN ('.implode(',', $refundinfo_ids).')';
            unset($filter['saleordid']);

        }

        if (isset($filter['partcode'])){
            $refundinfo_ids = array();
            $refundinfo_ids[] = 0;
            $items = $this->db->select("SELECT refundinfo_id FROM sdb_ediws_refundinfo_items  WHERE partcode='".$filter['partcode']."'");

            if($items){
                foreach($items as $v){
                    $refundinfo_ids[] = $v['refundinfo_id'];
                }
            }
            $where .= '  AND refundinfo_id IN ('.implode(',', $refundinfo_ids).')';
            unset($filter['partcode']);

        }
       
       
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }


    /**
     * 获取ExportTitle
     * @param mixed $fields fields
     * @return mixed 返回结果
     */
    public function getExportTitle($fields){

       
        $export_columns = array(
            'outno'         =>  '*:出库单号',
            'refundid'      =>  '*:退货单号',
            'applydatetime' =>  '*:申请日期',
            'return_sn'     =>  '*:退供单号',
            'iostock_time'  =>  '*:入库时间',
            'iostock_status'=>  '*:入库状态',
            'wareid'    =>  '*:商品编号',
           'partcode'    =>  '*:备件条码',
           'saleordid'    =>  '*:平台原始订单号',
           'shipcode'    =>  '*:运单号',
         

        );

        $title = array();
        foreach( $export_columns as $k => $col ){
                $title[] = $export_columns[$k];
        }
        
        return mb_convert_encoding(implode(',',$title), 'GBK', 'UTF-8');
    }

    //根据查询条件获取导出数据
    /**
     * 获取ExportDataByCustom
     * @param mixed $fields fields
     * @param mixed $filter filter
     * @param mixed $has_detail has_detail
     * @param mixed $curr_sheet curr_sheet
     * @param mixed $start start
     * @param mixed $end end
     * @return mixed 返回结果
     */
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){

        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        if( !$list=$this->getlist('*',$filter) ) return false;

      
        foreach ($list as $aFilter) {
            $refundinfo_id = $aFilter['refundinfo_id'];


            $items = $this->db->select("SELECT * from sdb_ediws_refundinfo_items where refundinfo_id=".$refundinfo_id."");

            foreach($items as $v){

                $listRow = array();
          
           
                $listRow['outno'] = $aFilter['outno'];
                $listRow['refundid'] = $aFilter['refundid'];
                $applydatetime = $aFilter['applydatetime'] ? date('Y-m-d H:i:s',$aFilter['applydatetime']) : '';

                $iostock_time = $aFilter['iostock_time'] ? date('Y-m-d H:i:s',$aFilter['iostock_time']) : '';
                $listRow['applydatetime'] = $applydatetime;

                $listRow['return_sn'] = $aFilter['return_sn'];
                $listRow['iostock_time'] = $iostock_time;
                $iostock_status = $aFilter['iostock_status']=='1' ? '已入库' : '未入库';
                $listRow['iostock_status'] = mb_convert_encoding($iostock_status, 'GBK', 'UTF-8');
                $listRow['wareid'] = $v['wareid'];

                $listRow['partcode'] = $v['partcode'];
                $listRow['saleordid'] = $v['saleordid'];

                $listRow['shipcode'] = $v['shipcode'];
                $listRow['in_num'] = $v['in_num'];

                $data['content']['main'][] = implode(',', $listRow);

            }
            
           
        }
        
        return $data;
       
    }
   
}