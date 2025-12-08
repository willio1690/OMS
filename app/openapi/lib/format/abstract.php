<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_format_abstract{

    public $charset_lists = array('utf-8','gbk');

    public $type_lists = array('json','xml','qimen');

    /**
     * 处理
     * @param mixed $data 数据
     * @param mixed $charset charset
     * @param mixed $type type
     * @return mixed 返回值
     */
    public function process($data,$charset,$type){

        //数据内容编码转换
        switch ($charset){
            case 'gbk':
                $this->charsetTrans($data,'gbk','utf-8');
                break;
            default:
                break;
        }

        //数据内容返回输出格式转换
        switch ($type){
            case 'json':
                $this->_outputByJson($data);
                break;
            case 'xml':
                $this->_outputByXml($data);
                break;
            case 'qimen':
                $this->_outputByQimen($data);
                break;
            default:
                break;
        }

    }
    
    // 解决json_encode后中文是unicode问题
    static function urlencode(&$arr)
    {        
        foreach ($arr as $key => &$value) {
            if (is_array($value)) {
                self::urlencode($value);
            } elseif (is_string($value)) {
                $value = urlencode($value);
            }
        }
    }

    private function _outputByJson($data){
        self::urlencode($data);
        echo urldecode(json_encode($data));
        exit;
    }

    private function _outputByXml($data){
        $this->_array2xml($data,$xml);
        echo $xml;
        exit;
    }

    private function _array2xml($data,&$xml){
        if(is_array($data)){
            foreach($data as $k=>$v){
                if(is_numeric($k)){
                    $xml.=$this->_array2xml($v,$xml);
                }else{
                    $xml.='<'.$k.'>';
                    $xml.=$this->_array2xml($v,$xml);
                    $xml.='</'.$k.'>';
                }
            }
        }elseif(is_numeric($data)){
            $xml.=$data;
        }elseif(is_string($data)){
            $xml.='<![CDATA['.$data.']]>';
        }
    }

    private function charsetTrans(&$data,$to,$from){
        foreach($data as $k=>$v){
            if(is_array($v)){
                $this->charsetTrans($data[$k]);
            }else{
                if(is_string($v)){
                    $data[$k] = mb_convert_encoding($v, $to, $from);
                }else{
                    $data[$k] = $v;
                }
            }
        }
    }
    /**
     * charFilter
     * @param mixed $str str
     * @return mixed 返回值
     */
    public function charFilter($str){
        return str_replace(array("\t","\r","\n",'"',"\\"),array(" "," "," ",'“',"/"),$str);
    }
    
    /**
     * qimen路由接口格式化返回数据
     * 
     * @param $data
     * @return void
     */
    private function _outputByQimen($data)
    {
        if(isset($data['error_response'])){
            $qimen = array('rsp'=>'fail', 'sub_code'=>$data['error_response']['code'], 'sub_message'=>$data['error_response']['msg']);
        }elseif(isset($data['response'])){
            $info = serialize($data['response']);
            $qimen = array('rsp'=>'succ', 'res' => '成功', 'data'=> $info);
        }
        
        echo json_encode($qimen);
        exit;
    }
}