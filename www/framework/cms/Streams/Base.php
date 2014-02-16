<?php

namespace depage\cms\Streams;

abstract class Base {
    protected $position = 0;
    protected $data = null;

    // {{{ registerAsStream()
    public static function registerStream($protocol, Array $parameters = array())
    {
        $class = get_called_class();
        static::$parameters = $parameters;

        if (in_array($class, stream_get_wrappers())) {
            stream_wrapper_unregister($protocol);
        }
        stream_wrapper_register($protocol, $class);
    }
    // }}}
    // {{{ init()
    public function init()
    {
        foreach (static::$parameters as $key => $value) {
            $this->$key = $value;
        }
    }
    // }}}
    
    // {{{ stream_open()
    public abstract function stream_open($path, $mode, $options, &$opened_path);
    // }}}
    // {{{ stream_read()
    public function stream_read($count)
    {
        $ret = substr($this->data, $this->position, $count); 
        $this->position += $count;   

        return $ret;
    }
    // }}}
    // {{{ stream_write()
    public function stream_write($data)
    {
        return 0;
    }
    // }}}
    // {{{ stream_eof()
    public function stream_eof()
    {
        return $this->position <= strlen($this->data);
    }
    // }}}
    // {{{ stream_stat()
    public function stream_stat(){
        return array();     
        
    }
    // }}}
    // {{{ url_stat()
    public function url_stat(){
        return array();     
        
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
