<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class doczen_parser{

    var $_blocks = array();
    var $_in_pre = false;
    var $_in_table = false;
    var $_in_p = false;
    var $_last_line_is_empty = false;
    var $links = array();
    var $part = array();
    var $toc = array();
    var $ref_id = 1;
    var $inline_marker = null;
    
    var $options = array(
            'image' => array('gif','png','jpg'),
            'movie' => array('avi','mov'),
        );

    function __destruct(){
        if($this->handle){
            fclose($this->handle);
        }
    }
    
    function begin($code,$options=''){
        array_unshift($this->_blocks,$code);
        $this->part[] = array(DOC_CODE_BEGIN,$code,$options);
    }
    
    function subject($depth,$text){
        $this->toc[] = array($depth,$text,'#id'.$this->ref_id);
        $this->part[] = array(DOC_CODE_TITLE,$text,$depth,$this->ref_id++);
    }
    
    function begin_pre($type){
        $this->part[] = array(DOC_CODE_PRE,$type,'');
    }
    
    function end($code){
        array_shift($this->_blocks);
        $this->part[] = array(DOC_CODE_END,$code);
    }
    
    function body($body,$keep=false){
        
        if($keep){
            $last = count($this->part)-1;
            $this->part[$last][2] .= $body;
        }else{
            $body = preg_split('/\[([^\]]*\.t2t)\]/',$body, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach($body as $k=>$v){
                if($k%2==0){
                    $this->inline($v);
                }else{
                    if($p=strrpos($v,' ')){
                        $file = substr($v,$p+1);
                        $text = substr($v,0,$p);
                    }else{
                        $text = '';
                        $file = $v;
                    }
                    $this->add_link($file,0);
                    $this->insert('t2tlnk',array($file,$text));
                }
            }
        }
    }
    
    function inline($line){
        
        /*list($inline_key, $inline_mark) = $this->inline_re();
        $line = preg_replace($inline_key, $inline_mark, trim($line) );*/
        
        $line = $this->premark_inline($line);
        
        echo $line,"\n";
        $line = preg_split('/\{\@MARK\@([a-z0-9]+)_(.+?)_\@KRAM\@\}/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
        print_r($line);
        
        foreach($line as $k=>$v){
            switch($k%3){
                case 0:
                    $this->part[] = array(DOC_CODE_TEXT,$v);
                break;
                
                case 1:
                    $cmd = $v;
                break;
                
                case 2:
                    $this->part[] = array(DOC_CODE_INLINE,$cmd,$v);
                break;
            }
        }
    }
    
    function premark_inline($line){
        //if($this->inline_marker) return $this->inline_marker;
        $map = array(
                '/\[([^\s\]].+)\s+([^\s\]]+)\]/'=>'lnk|\1 \2',
                '/\[([^\]]+)\.('.implode('|',$this->options['image']).')\]/'=>'image|\1.\2',
                '/\[([^\]]+)\.('.implode('|',$this->options['movie']).')\]/'=>'movie|\1.\2',
                '/\`\`(.+?)\`\`/'=>'code|\1',
                '/""(.+?)""/'=>'quote|\1',
                "/''(.+?)''/"=>'mark|\1',
                '/\/\/(.+?)\/\//'=>'italic|\1',
                '/--(.+?)--/'=>'deleted|\1',
                '/__(.+?)__/'=>'underline|\1',
                '/\*\*(.+?)\*\*/'=>'strong|\1',
                '#(http|https)(://[^\]\s]+)#'=>'lnk|\1\2',
            );
            
        foreach($map as $k=>$v){
            preg_replace_callback($k,$text,create_function('$matches',''));
        }
        
        foreach($map as $k=>$v){
            $p = strpos($v,'|');
            $tag = substr($v,0,$p);
            $replace = substr($v,$p+1);
            $marker[] = "{@MARK@{$tag}_{$replace}_@KRAM@}"; 
        }
        $this->inline_marker = array(array_keys($map),$marker);
        return $this->inline_marker;
    }
    
    function line($code){
        $this->part[] = array(DOC_CODE_LINE,$code);
    }
    
    function insert($type,$options=''){
        $this->part[] = array(DOC_CODE_CMD,$type,$options);
    }
    
    function set_option($key,$value){
        $this->options[$key] = $value;
    }

    function load($dir,$file){
        $data['file'] = $file;
        $this->filedir = dirname($file);
        $this->docdir = $dir;
        $this->workdir = getcwd();
        $this->handle = @fopen($dir.'/'.$file, "r");
        $data['title'] = trim(fgets($this->handle,1024));
        fgets($this->handle,512);
        fgets($this->handle,512);

        if ($this->handle) {
            while (!feof($this->handle)) {
                $this->process(fgets($this->handle, 4096));
            }
        }
        foreach($this->_blocks as $blk){
            $this->end($blk);
        }
        
        $data['parts'] = $this->part;
        $data['links'] = array_values($this->links);
        $data['toc'] = $this->toc;
        
        return $data;
    }

    function process($line){

        if($this->_in_pre){
            if(trim($line) == $this->_in_pre){
                $this->_in_pre = false;
                return ;
            }else{
                return $this->body($line,1);
            }
        }

        if(trim($line)==''){
            if(!$this->_last_line_is_empty){
                foreach($this->_blocks as $b){
                    $this->end($b);
                }
                $this->_in_p = false;
                return;
            }else{
                $this->_last_line_is_empty = true;
                return $this->line('br');    
            }
        }

        $this->_last_line_is_empty = false;
        foreach($this->block_re() as $pattern =>$func){
            if(preg_match($pattern,$line,$match)){
                if($this->_in_table && $func!='table'){
                    $this->_in_table = false;
                    $this->end('table');
                }
                return $this->{'proc_'.$func}($match);
            }
        }
        
        if($this->_in_table){
            $this->_in_table = false;
            $this->end('table');
        }

        $pre_block_define = array(
                '```'=>'pre',
                '...'=>'dot',
                '"""'=>'quote',
                "'''"=>'mark',
            );
        if(isset($pre_block_define[trim($line)]) 
                && $this->_in_pre = trim($line)){
            return $this->begin_pre($pre_block_define[trim($line)]);
        }

        if(!$this->_in_p){
            $this->_in_p = true;
            $this->begin('p');
        }
        return $this->body($line);
    }

    function block_re(){
        return array(
            '/^%!([a-z][a-z0-9]+)\s*([^\s]*)\s*(.*)/'=>'command',
            '/^%/'=>'skip',
            '/^(={5,80}|-{5,80})\s*$/'=>'line',
            '/^(-{5,80})\s*$/'=>'line',
            '/^\+(=+).*?(=+)(\+)\s*$/'=>'title',
            '/^(=+).*?(=+)()\s*$/'=>'title',
            '/^([-+])\s(.*)/'=>'list',
            '/^(\|{1,2})\s(.+)\s\|{1,2}\s*$/'=>'table',
        );
    }
    
    function proc_line($match){
        $this->line($match[1]{0}=='='?'hr-strong':'hr');
    }

    function proc_table($match){
        
        if(!$this->_in_table){
            $this->_in_table = true;
            $this->begin('table');
        }
        
        $code = $match[1]=='|'?'td':'th';
        $this->begin('tr');
        foreach(explode(' | ',$match[2]) as $col){
            $this->begin($code);
            $this->body($col);
            $this->end($code);
        }
        $this->end('tr');
    }

    function proc_skip($match){}
    
    function proc_command($match){
        $func = 'command_'.$match[1];
        if(method_exists($this,$func)){
            $this->$func($match[2],$match[3]);
        }
    }
    
    function command_include($file_inc,$options){
        $subparser = new doczen_parser;
        
        $subparser->ref_id = &$this->ref_id;
        $subparser->toc = &$this->toc;
        $subparser->links = &$this->links;
        $subparser->part = &$this->part;
        
        $file = $subparser->load($this->docdir, $this->filedir.'/'.$file_inc);
    }
    
    function command_link($link_what,$options){
        chdir($this->docdir);
        parse_str($options,$options);
        
        foreach(glob($this->filedir.'/'.$link_what) as $link){
            if(substr($link,0,2)=='./'){
                $link = substr($link,2);
            }
            $this->insert('link',$link);
            $this->add_link($link,isset($options['max-depth'])?$options['max-depth']:3);
        }
        chdir($this->workdir);
    }

    function proc_list($match){
        $list_code = $match[1]=='-'?'ul':'ol';
        if(!isset($this->_blocks[0]) || $this->_blocks[0]!=$list_code){
            $this->begin($list_code);
        }
        $this->begin('li');
        $this->body($match[2]);
        $this->end('li');
    }

    function proc_title($match){
        $depth = min(strlen($match[1]),strlen($match[2]));
        $text = trim($match[0]);
        
        /*if($match[3]=='+'){
            $prefix = ++$this->seq[$depth];
            for($i=$depth+1;$i<10;$i++){
                $this->seq[$i] = 0; //重置子节点计数器
            }
            $prefix .= '. ';
            $text = substr($text,1,-1);
        }else{
            $prefix = '';
        }*/
        
        $text = trim(substr($text,$depth,0-$depth));
        $this->subject($depth,$text);
    }

    
    private function add_link($page,$maxdepth=3){
        $this->links[$page] = array($page,$maxdepth);
    }

}
