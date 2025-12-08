<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class service implements Iterator{
    
    function __construct($service_define,$filter=null){
        $this->iterator = new ArrayIterator($service_define['list']);
        $this->interface = $service_define['interface'];
        $this->filter = $filter;
        $this->valid();
    }

    function current(){
        return $this->current_object;
    }
    
    /**
     * rewind
     * @return mixed 返回值
     */
    public function rewind() {
        $this->iterator->rewind();
    }

    /**
     * key
     * @return mixed 返回值
     */
    public function key() {
        return $this->iterator->current();
    }

    /**
     * next
     * @return mixed 返回值
     */
    public function next() {
        return $this->iterator->next();
    }

    /**
     * valid
     * @return mixed 返回值
     */
    public function valid() {
        while($this->iterator->valid()){
            if($this->filter()){
                return true;
            }else{
                $this->iterator->next();
            }
        };
        return false;
    }
    
    private function filter(){
		if ($this->filter){
			$current = $this->iterator->current();
			if (is_array($this->filter) && !in_array($current,$this->filter)) $this->iterator->next();
			if (!is_array($this->filter) && $this->filter != $current) $this->iterator->next();
		}
		$current = $this->iterator->current();
        if($current){
            try{
                $this->current_object = kernel::single($current);
            }catch(Throwable $th){
                kernel::log($current.' service '.$th->getMessage());
                return false;
            }
            if($this->current_object){
                if($this->interface && $this->current_object instanceof $this->interface){
                    return false;
                }
                return true;
            }
        }
        return false;
    }

}


