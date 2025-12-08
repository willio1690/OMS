<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class dbeav_select_mysql extends dbeav_select_adapter 
{
    function __contruct($params=null) 
    {
        parent::__contruct();
    }//End Function

    /**
     * connect
     * @return mixed 返回值
     */
    public function connect() 
    {
        if($this->_connect == null){
            $this->_connect = kernel::database();
        }
    }//End Function

    /**
     * 设置_obj
     * @param mixed $selectObj selectObj
     * @return mixed 返回操作结果
     */
    public function set_obj($selectObj) 
    {
        $this->selectObj = $selectObj;
    }//End Function

    /**
     * exec
     * @return mixed 返回值
     */
    public function exec() 
    {
        $this->connect();
        $sql = $this->selectObj->assemble();
        $res = $this->_connect->exec($sql);
        return $res['rs'];
    }//End Function

    /**
     * select
     * @return mixed 返回值
     */
    public function select() 
    {
        $this->connect();
        $sql = $this->selectObj->assemble();
        $data = $this->_connect->select($sql);
        $cols = $this->selectObj->get_columns();
        $this->selectObj->get_model()->tidy_data($data, (count($cols))?join(',', $cols):'*');
        return $data;
    }//End Function
    
    /**
     * limit
     * @param mixed $sql sql
     * @param mixed $count count
     * @param mixed $offset offset
     * @return mixed 返回值
     */
    public function limit($sql, $count, $offset=0) 
    {
        return sprintf('%s LIMIT %s %s', $sql, ($offset>0) ? $offset . ',' : '', intval($count));
    }//End Function

    /**
     * quote_column_as
     * @param mixed $column column
     * @param mixed $alias alias
     * @return mixed 返回值
     */
    public function quote_column_as($column, $alias)
    {
        if($column == '*')  return $column;
        if($alias != null)
            return sprintf('%s AS %s', $column, $this->quote_identifier($alias));
        else
            return  $column;
    }//End Function
    
    /**
     * quote_identifier
     * @param mixed $name name
     * @return mixed 返回值
     */
    public function quote_identifier($name) 
    {
        return sprintf('`%s`', $name);
    }//End Function

    /**
     * quote_into
     * @param mixed $str str
     * @param mixed $val val
     * @return mixed 返回值
     */
    public function quote_into($str, $val) 
    {
        return str_replace('?', $this->quote($val), $str);
    }//End Function

    /**
     * quote
     * @param mixed $val val
     * @return mixed 返回值
     */
    public function quote($val) 
    {
        if (is_int($val)) {
            return $val;
        } elseif (is_float($val)) {
            return sprintf('%F', $val);
        }
        return "'" . addcslashes($val, "\000\n\r\\'\"\032") . "'";
    }//End Function

    /**
     * fetch
     * @param mixed $fetchMode fetchMode
     * @return mixed 返回值
     */
    public function fetch($fetchMode=null) 
    {
        if($fetchMode == null) $fetchMode = 'all';
        $method = 'fetch_' . strtolower($fetchMode);
        if(method_exists($this, $method)){
            if(func_num_args() > 1){
                $args = func_get_args();
                unset($args[0]);
                return call_user_func_array(array($this, $method), $args);
            }else{
                return $this->$method();
            }
        }else{
            return false;
        }
    }//End Function

    /**
     * fetch_all
     * @return mixed 返回值
     */
    public function fetch_all() 
    {
        return $this->select();
    }//End Function

    /**
     * fetch_row
     * @return mixed 返回值
     */
    public function fetch_row() 
    {
        $data = $this->fetch_all();
        return $data[0];
    }//End Function

    /**
     * fetch_one
     * @return mixed 返回值
     */
    public function fetch_one() 
    {
        $data = $this->fetch_row();
        if(is_array($data)){
            foreach($data AS $d)
                return $d;
        }
        return false;
    }//End Function
    
    /**
     * fetch_col
     * @return mixed 返回值
     */
    public function fetch_col() 
    {
        $res = $this->select();
        $cols = func_get_args();
        foreach($res AS $row){
            if(func_num_args() > 0){
                foreach($row AS $key=>$val){
                    if(in_array($key, $cols)){
                        $data[$key][] = $val;
                    }
                }
            }else{
                foreach($row AS $key=>$val){
                    $data[$key][]= $val;
                }
            }
        }
        return (array) $data;
    }//End Function

}//End Class
