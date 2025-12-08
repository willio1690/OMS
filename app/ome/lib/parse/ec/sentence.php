<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_parse_ec_sentence {

    private $lexicon;
    private $kwList = array();
    
    /**
     * __construct
     * @param mixed $lexicon lexicon
     * @return mixed 返回值
     */
    public function __construct(& $lexicon)
    {
        $this->lexicon = $lexicon;
    }
    
    /**
     * insert
     * @param mixed $info info
     * @return mixed 返回值
     */
    public function insert($info)
    {
        $this->kwList[$this->getPos($info)] = $info;
    }
    
    /**
     * 获取
     * @param mixed $pos pos
     * @return mixed 返回结果
     */
    public function get($pos)
    {
        if(isset($this->kwList[$pos]))
        {
            return $this->kwList[$pos];
        }
        
        return array();
    }
    
    /**
     * hasPos
     * @param mixed $pos pos
     * @return mixed 返回值
     */
    public function hasPos($pos)
    {
        return isset($this->kwList[$pos]);
    }
    
    /**
     * 添加Weight
     * @param mixed $pos pos
     * @param mixed $value value
     * @return mixed 返回值
     */
    public function addWeight($pos, $value)
    {
        if(!isset($this->kwList[$pos]['weight']))
        {
            $this->kwList[$pos]['weight'] =  $this->lexicon->getWeight($this->kwList[$pos]['keyWord']);
        }
        else
        {
            $this->kwList[$pos]['weight'] = $this->kwList[$pos]['weight'];
        }
        
        //echo '<BR>'. $pos . '=>'. $value . ' 加 '. $this->kwList[$pos]['weight'] . '等于<BR><BR>';
        $this->kwList[$pos]['weight'] += $value;
    }
    
    /**
     * 获取List
     * @return mixed 返回结果
     */
    public function getList()
    {
        $this->sort();
        
        return $this->kwList;
    }
    
    /**
     * sort
     * @return mixed 返回值
     */
    public function sort()
    {
        ksort($this->kwList);
    }
    
    /**
     * filter
     * @param mixed $puncatuationInfoList puncatuationInfoList
     * @return mixed 返回值
     */
    public function filter($puncatuationInfoList)
    {
        foreach ($this->kwList as $p=>$info)
        {
            if(isset($puncatuationInfoList[$info['keyWord']]))
            {
                unset($this->kwList[$p]);
            }
            else 
            {
                break;
            }
        }
        
        $this->kwList = array_reverse($this->kwList, true);
        
        foreach ($this->kwList as $p=>$info)
        {
            if(isset($puncatuationInfoList[$info['keyWord']]))
            {
                unset($this->kwList[$p]);
            }
            else 
            {
                break;
            }
        }
        
        return array_reverse($this->kwList, true);
    }
    
    /**
     * revert
     * @return mixed 返回值
     */
    public function revert()
    {
        $this->kwList = array();
    }
    
    private function getPos($info)
    {
        $p = array_pop($info['pos']);
        
        return $p['index'];
    }
    
}

?>