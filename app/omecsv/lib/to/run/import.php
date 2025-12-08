<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class omecsv_to_run_import {

    function run(&$cursor_id,$params){
		
        base_kvstore::instance($params['app'].'_'.$params['mdl'])->fetch($params['file_name'].'_sdf',$sdfContents);
       $sdfContents = unserialize( $sdfContents );
        $o = app::get($params['app'])->model($params['mdl']);
        kernel::log('model fjdisajdsk');
        kernel::log('model fjdisajdsk = '.$params['mdl'].'fdsadg'.$params['app']);
        $i = 0;
        while( $v = array_shift( $sdfContents ) ){
            kernel::log('mofdsadfa = '.$v['store']);
           if(!empty($v['store'])){
                $v['product'][0]['store'] = $v['store'];
            }
            $o->save($v);

            if( ++$i == 100 ){
                base_kvstore::instance($params['app'].'_'.$params['mdl'])->store($params['file_name'].'_sdf',serialize( $sdfContents ));
                return 1;
                break;
            }
        }
        base_kvstore::instance($params['app'].'_'.$params['mdl'])->delete($params['file_name']);
        base_kvstore::instance($params['app'].'_'.$params['mdl'])->delete($params['file_name'].'_sdf');
        base_kvstore::instance($params['app'].'_'.$params['mdl'])->delete($params['file_name'].'_error');
		
        return 0;
    }

    function turn_to_sdf(&$contents,&$cursor_id,$newarray,$params){
		 reset($contents);
        $msgList = array();
       $o = app::get($params['app'])->model($params['mdl']);//omr_model_orders
       $oIo = kernel::servicelist('omecsv_io');

       foreach( $oIo as $aIo ){
            if( $aIo->io_type_name == $params['file_type'] ){
                $importType = $aIo;
                break;
            }
        }

        unset($oIo);
        $objFunc = 'prepared_import_csv_obj';//prepared_import_csv_obj
        $rowFunc = 'prepared_import_csv_row';//prepared_import_csv_row

        $i = 0;
        $tmpl = array();
        $tTmpl = array();
        $gTitle = array();
        $data = array();
        $tObjContent = array();
        $errorObj = false;
        $doAllNum = count($contents);
        echo "<script>parent.$('total', parent.$('import_form')).setHTML(".$doAllNum.");</script>";
        ob_flush();
        flush();
        $importType->prepared_import( $params['app'],$params['mdl'] );//$importType->prepared_import(ome,orders)
        $doRowNum = 0;
        $doFailNum = 0;
        while( true ){
            if( !current($contents) && is_array($data['contents']) && current( $data['contents'] )){
                echo "<script>parent.$('processNotice', parent.$('import_form')).setHTML(\"数据处理中。。。\");</script>";
                ob_flush();
                flush();
                $saveData = $o->$objFunc( $data,$mark,$tmpl,$msg);
                echo "<script>parent.$('processBar', parent.$('import_form')).setStyle('width','80%');</script>";
                if( $saveData === false ){
                    if( $msg['error'] ) {
                        $msgList['error'][] = $msg['error'];
                        echo '<script>var objMsg=parent.$("iMsg", parent.$("import_form"));objMsg.setHTML(objMsg.getHTML()+"<br>数据处理错误：'.$msg['error'].'");</script>';;
                    }
                    return $msgList;
                }
                ob_flush();
                flush();
                if( $saveData )
                $sdfContents[] = $saveData;
                if( $mark )
                    eval('$data["'.implode('"]["',explode('/',$mark)).'"] = array();');
            }
            $curContent = array_shift( $contents );
            if( !$curContent ) break;
            $msg = array();
            $rowData = $o->$rowFunc( $curContent,$data['title'],$tmpl,$mark,$newObjFlag,$msg );
            $doRowNum ++;
            echo "<script>parent.$('iTotal', parent.$('import_form')).setHTML(".$doRowNum.");</script>";
            echo "<script>parent.$('processBar', parent.$('import_form')).setStyle('width','".($doRowNum/$doAllNum*70)."%');</script>";

            if( $msg['error'] ) {
                $msgList['error'][] = $msg['error'];
                echo '<script>var objMsg=parent.$("iMsg", parent.$("import_form"));objMsg.setHTML(objMsg.getHTML()+"<br>第'.$doRowNum.'行错误：'.$msg['error'].'");</script>';
            }
            if( $msg['warning'] ){
                echo '<script>var objMsg=parent.$("iMsg", parent.$("import_form"));objMsg.setHTML(objMsg.getHTML()+"<br>第'.$doRowNum.'行警告：'.implode(',', $msg['warning']).'");</script>';;
            }
            ob_flush();
            flush();
            if( $newObjFlag ){

                $tObjContent = array();
                if( $mark != 'title' ){
                    $msg = [];

                    $saveData = $o->$objFunc( $data,$mark,$tmpl,$msg);
                    if($msg && $msg['error'] ) {
                        $msgList['error'][] = $msg['error'];
                        echo '<script>var objMsg=parent.$("iMsg", parent.$("import_form"));objMsg.setHTML(objMsg.getHTML()+"<br>第'.$doRowNum.'行错误：'.$msg['error'].'");</script>';
                    }
                    if($msg && $msg['warning'] ){
                        echo '<script>var objMsg=parent.$("iMsg", parent.$("import_form"));objMsg.setHTML(objMsg.getHTML()+"<br>第'.$doRowNum.'行警告：'.implode(',', $msg['warning']).'");</script>';;
                    }
                    ob_flush();
                    flush();
                    if( $saveData === false ){
                        return $msgList;
                    }
                    if( $saveData )
                    $sdfContents[] = $saveData;
                    if( $mark )
                        eval('$data["'.implode('"]["',explode('/',$mark)).'"] = array();');

                }else{
                    $tTmpl = $rowData;
                    $gTitle = $curContent;
                }
                if( $rowData === false ){
                    return $msgList;
                }
            }
            if( $mark ){
                if( $mark == 'title' )
                    eval('$data["'.implode('"]["',explode('/',$mark)).'"] = $rowData;');
                else
                    eval('$data["'.implode('"]["',explode('/',$mark)).'"][] = $rowData;');
            }
        }
        return $msgList;
    }

    function get_import_type($file_type){

    }

}
