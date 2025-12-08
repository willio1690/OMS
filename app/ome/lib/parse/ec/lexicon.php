<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_parse_ec_lexicon {

    private static $SURE_LIB = array();
    private static $DENY_LIB = array();
    private static $CONJUNCT_LIB = array();
    private static $EXPRESS_LIB = array();
    private static $PUNCTUATION_LIB = array();
    private static $ALL_LIST = array();
    
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->init();
    }
    
    /**
     * 设置Content
     * @param mixed $content content
     * @return mixed 返回操作结果
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
    
    /**
     * 获取KeyWordList
     * @return mixed 返回结果
     */
    public function getKeyWordList()
    {
        if(empty(self::$ALL_LIST))
        {
            self::$ALL_LIST = array_merge(self::$SURE_LIB, self::$DENY_LIB, self::$PUNCTUATION_LIB, self::$EXPRESS_LIB);
        }
        
        return self::$ALL_LIST;
    }
    
    /**
     * 获取Weight
     * @param mixed $keyWord keyWord
     * @return mixed 返回结果
     */
    public function getWeight($keyWord)
    {
        if(isset(self::$ALL_LIST[$keyWord]))
        {
            return self::$ALL_LIST[$keyWord]['weight'];
        }
        
        return 0;
    }
    
    /**
     * 获取PunctuationLib
     * @return mixed 返回结果
     */
    public function getPunctuationLib()
    {
        return self::$PUNCTUATION_LIB;
    }
    
    /**
     * 获取SureLib
     * @return mixed 返回结果
     */
    public function getSureLib()
    {
        return self::$SURE_LIB;
    }
    
    /**
     * 获取ConjunctLib
     * @return mixed 返回结果
     */
    public function getConjunctLib()
    {
        return self::$SURE_LIB;
    }
    
    /**
     * 获取DenyLib
     * @return mixed 返回结果
     */
    public function getDenyLib()
    {
        return self::$DENY_LIB;
    }
    
    /**
     * 获取ExpressLib
     * @return mixed 返回结果
     */
    public function getExpressLib()
    {
        return self::$EXPRESS_LIB;
    }
    
    /**
     * 获取ExpressType
     * @param mixed $keyWord keyWord
     * @return mixed 返回结果
     */
    public function getExpressType($keyWord)
    {
        return isset(self::$EXPRESS_LIB[$keyWord]) ? self::$EXPRESS_LIB[$keyWord]['appendInfo'] : ''; 
    }
    
    private function init()
    {
        $this->loadLib();
        $this->loadPuncatuationLib();
        
        $this->getKeyWordList();
    }
    
    /**
     * 根据实际语义的修饰程度设置权重，值越大越优先
     */
    private function loadLib()
    {
        self::$SURE_LIB = array(
        	'一定' => array('weight' => 100), 
        	'只要' => array('weight' => 100), 
        	'只用' => array('weight' => 100), 
        	'必须' => array('weight' => 100),
        	'请发' => array('weight' => 100),
        	'请用' => array('weight' => 100),
        	'最好' => array('weight' => 99), 
        	'也行' => array('weight' => 98), 
        	'要是' => array('weight' => 90), 
        	'调整' => array('weight' => 90), 
        	'修改' => array('weight' => 90), 
        	'改换' => array('weight' => 90), 
        	'改为' => array('weight' => 90), 
        	'修正' => array('weight' => 90), 
        	'可以' => array('weight' => 80), 
        );
        
        self::$DENY_LIB = array(
        	'非'     => array('weight' => -100), 
        	'别'     => array('weight' => -100), 
        	'不要'   => array('weight' => -100),
        	'不得'   => array('weight' => -100),
        	'不能'   => array('weight' => -100),
        	'不到'   => array('weight' => -100),
        	'不用'   => array('weight' => -100),
        	'不行'   => array('weight' => -100),
        	'除了'   => array('weight' => -100),
        	'停止'   => array('weight' => -100),
        	'不能是' => array('weight' => -100), 
        	'最不好' => array('weight' => -99), 
        	'不合做' => array('weight' => -99), 
        	'不合作' => array('weight' => -99), 
        );
        
        self::$CONJUNCT_LIB = array(
        	'、'   => array('weight' => 10), 
        	'或'   => array('weight' => 10), 
        	'或者' => array('weight' => 10),
        	'另外' => array('weight' => 10), 
        	'要是' => array('weight' => 10), 
        );
        
        // KEY要统一大写
        self::$EXPRESS_LIB = array(
        	'EMS'      => array('weight' => null, 'appendInfo' => 'EMS'),
        	'申通'     => array('weight' => null, 'appendInfo' => 'STO'), 
        	'顺丰'     => array('weight' => null, 'appendInfo' => 'SF'), 
        	'顺风'     => array('weight' => null, 'appendInfo' => 'SF'), 
        	'CCES'     => array('weight' => null, 'appendInfo' => 'CCES'), 
        	'圆通'     => array('weight' => null, 'appendInfo' => 'YTO'), 
        	'圓通'     => array('weight' => null, 'appendInfo' => 'YTO'), 
        	'中通'     => array('weight' => null, 'appendInfo' => 'ZTO'), 
        	'韵达'     => array('weight' => null, 'appendInfo' => 'YUNDA'), 
        	'快捷'     => array('weight' => null, 'appendInfo' => 'CNKJ'), 
        	'汇通'     => array('weight' => null, 'appendInfo' => 'HTKY'), 
        	'宅急送'   => array('weight' => null, 'appendInfo' => 'ZJS'), 
        	'天天'     => array('weight' => null, 'appendInfo' => 'TTKDEX'), 
        	'龙邦'     => array('weight' => null, 'appendInfo' => 'LB'), 
        );
    }
    
    private function loadPuncatuationLib()
    {
        self::$PUNCTUATION_LIB = array(
            ','   => array('weight' => 0),
            '，'   => array('weight' => 0),
            '.'   => array('weight' => 0),
            '。'  => array('weight' => 0),
            ';'   => array('weight' => 0),
            '；'   => array('weight' => 0),
            '但是'   => array('weight' => 0),
        );
    }
    
}

?>