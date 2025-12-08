<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class omecsv_io_type_excel extends omecsv_phpexcel 
{
    protected $model;
    protected $charset = null;

    public function init( &$model ){
        $model->charset = $this->charset;
        $model->io = $this;
        $this->model = $model;
    }
    
    function fgethandle($inputFileName,&$contents){
        try {
            $contents = $this->importExecl($inputFileName);
        }catch (Exception $e) {
            throw $e;
        }
    }

    function fgetlist( &$data,&$model,$filter,$offset,$exportType =1 ){
        $limit = 100;

        $cols = $model->_columns();
        if(!$data['title']){
            $this->title = array();
            foreach( $this->getTitle($cols) as $titlek => $aTitle ){
                $this->title[$titlek] = $aTitle;
            }
            $data['title'] = '"'.implode('","',$this->title).'"';
        }

        if(!$list = $model->getList(implode(',',array_keys($cols)),$filter,$offset*$limit,$limit))return false;

        foreach( $list as $line => $row ){
            $rowVal = array();
            foreach( $row as $col => $val ){

                if( in_array( $cols[$col]['type'],array('time','last_modify') ) && $val ){
                   $val = date('Y-m-d H:i',$val);
                }
                if ($cols[$col]['type'] == 'longtext'){
                    if (strpos($val, "\n") !== false){
                        $val = str_replace("\n", " ", $val);
                    }
                }

                if( strpos( (string)$cols[$col]['type'], 'table:')===0 ){
                    $subobj = explode( '@',substr($cols[$col]['type'],6) );
                    if( !$subobj[1] )
                        $subobj[1] = $model->app->app_id;
                    $subobj = app::get($subobj[1])->model( $subobj[0] );
                    $subVal = $subobj->dump( array( $subobj->schema['idColumn']=> $val ),$subobj->schema['textColumn'] );
                    $val = $subVal[$subobj->schema['textColumn']]?$subVal[$subobj->schema['textColumn']]:$val;
                }

                if( array_key_exists( $col, $this->title ) )
                    $rowVal[] = addslashes(  (is_array($cols[$col]['type'])?$cols[$col]['type'][$val]:$val ) );
            }
            $data['contents'][] = '"'.implode('","',$rowVal).'"';
        }
        return true;

    }
    
    function prepared_import( $appId,$mdl ){
        $this->model = app::get($appId)->model($mdl);
        $this->model->ioObj = $this;
        if( method_exists( $this->model,'prepared_import_csv' ) ){
            $this->model->prepared_import_csv();
        }
        return;
    }

    function finish_import(){
        if( method_exists( $this->model,'finish_import_csv' ) ){
            $this->model->finish_import_csv();
        }
    }

    public function export_header($filename)
    {
        header("Content-Type: text/csv");
        $file_name = $filename.".csv";
        $encoded_filename = urlencode($file_name);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);

        $ua = $_SERVER["HTTP_USER_AGENT"];
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox$/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $file_name . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
        }
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
    }

    public function export($data,$offset,$model,$exportType=1){
        foreach($data as $pColumn=>$pValue)
        {
            $this->objPHPExcel->setActiveSheetIndex(0)
            ->setCellValueExplicitByColumnAndRow($pColumn, $offset, $pValue);
        }
    }

    public function finish_export(){
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel , 'CSV')->setUseBOM(true);
        $objWriter->save('php://output');
    }

}
