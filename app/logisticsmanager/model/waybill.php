<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_mdl_waybill extends dbeav_model {

    function modifier_status($row){
            $status = '';
            if ($row=='0') {
                $status = '可用';
            }else if($row=='1'){
                $status = '已用';
            }else if($row=='2'){
                $status = '作废';
            }
            return $status;

    }

    function io_title( $filter, $ioType='csv' ){
        switch( $filter ){
            case 'waybill':
                $this->oSchema['csv'][$filter] = array(
                    '*:运单号' => 'waybill_number',
                    '*:渠道来源' => 'channel_id',
                    '*:物流公司' => 'logistics_code',
                    '*:使用状态' => 'status',
                );
                break;
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
        return $this->ioTitle[$ioType][$filter];
    }

    function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        @ini_set('memory_limit','64M');
        if( !$data['title']){
            $title = array();
            foreach( $this->io_title('waybill') as $k => $v ){
                $title[] = $v;
            }
            $data['title'] = '"'.implode('","',$title).'"';
        }
        $limit = 6000;
        $channelObj = app::get('logisticsmanager')->model('channel');
        $channel_list=$channelObj->getList('*');

        if( !$list=$this->getList('*',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $pRow = array();
            $detail['waybill_number'] = $aFilter['waybill_number'];
            foreach($channel_list as $arr){
                if($arr['channel_id']==$aFilter['channel_id'])
                $detail['channel_id'] = $arr['name'];
            }
            $detail['logistics_code'] = $aFilter['logistics_code'];
            $detail['status'] = $this->modifier_status($aFilter['status']);
            foreach( $this->oSchema['csv']['waybill'] as $k => $v ){
                $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
            }
            $data['contents'][] = '"'.implode('","',$pRow).'"';
        }

        $data['name'] = 'waybill'.date("YmdHis");
        return true;
    }


    function prepared_import_csv(){

        $this->ioObj->cacheTime = time();
    }

    function finish_import_csv(){
        header("Content-type: text/html; charset=utf-8");
        $data = $this->import_data;
        unset($this->import_data);
        $channel_id = $_GET['channel_id'];
        $channelObj = app::get('logisticsmanager')->model('channel');
        $waybillObj = app::get('logisticsmanager')->model('waybill');
        $channel = $channelObj->dump($channel_id);
        $waybill_list = array_chunk($data['waybill'],5);
        $logistics_code = $channel['logistics_code'];
        foreach ($waybill_list as $waybill ) {
            $insert_sql = array();
            foreach ($waybill as $bill ) {
                if ($bill) {
                    $insert_sql[] = array(
                        'waybill_number' => $bill,
                        'channel_id' => $channel_id,
                        'logistics_code' => $logistics_code
                    );
                }
            }
            if ($insert_sql) {
                $sql = ome_func::get_insert_sql($waybillObj, $insert_sql);
                $channelObj->db->exec($sql);
            }
        }
        $opObj = app::get('ome')->model('operation_log');
        $memo = "电子面单号导入";
        $opObj->write_log('waybill_import@logisticsmanager', $channel_id, $memo);
        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){

        return null;
    }

    //CSV导入业务处理
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){
        $mark = false;

        $fileData = $this->import_data;
        if( !$fileData )
            $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $mark = 'title';
            $channel_id = $_GET['channel_id'];

            $channelObj = app::get('logisticsmanager')->model('channel');
            $channel = $channelObj->dump($channel_id,'channel_type');

            if (!in_array($channel['channel_type'],array('customs','sto'))) {
                $msg['error'] = "目前导入只支持申通跨境类型!";
                return false;
            }
        }else{
            if ($row[0]) {
                $waybillObj = app::get('logisticsmanager')->model('waybill');
                $row[0] = trim($row[0]);
                $waybill_info = $waybillObj->getList('id',array('waybill_number'=>$row[0],'channel_id'=>$_GET['channel_id']),0,1);
                if(!$waybill_info){
                    $fileData['waybill'][] = $row[0];
                }
            }
            $this->import_data = $fileData;
        }

        return null;
    }
}

?>
