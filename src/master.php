<?php
define('DOC_CODE_BEGIN',1);
define('DOC_CODE_END',2);
define('DOC_CODE_TEXT',3);
define('DOC_CODE_LINE',4);
define('DOC_CODE_CMD',5);
define('DOC_CODE_TITLE',6);
define('DOC_CODE_INLINE',7);
define('DOC_CODE_PRE',8);

class doczen_master{
    
    var $build_path = 'build';
    var $task_done = array();
    var $task_todo = array();
    var $index_page = 'index.t2t';
    var $had_scan_title = false;
    
    var $options = array(
            'template_dir'=>'source/_template',
            'static_dir'=>'source/_static',
            'source'=>'source',
        );
    
    function scan($path){
        $this->start_time = time();
        $this->source = $this->options['source'];
        doczen_utils::mkdir_p($this->build_path.'/cache');
        $this->index_page = $path;
        $this->add_task($path);
        $this->work();
        $this->index_page_title = $this->get_item_part($this->index_page,'title');
        
        $this->work_refs();
        $this->resource = array();
        $this->file_list = array_keys($this->task_done);
        
        
        if($this->had_scan_title){
            $this->update_idx();
        }
        return $this->file_list;
    }
    
    function work_refs(){
        if($this->refs_todo){
            $ref_page_path = '_refs.t2t';
            $ref_page_title = '资料';
            $ref_page_data = array(
                    'title'=>$ref_page_title,
                    'file'=>$ref_page_path,
                );
            $nav = array(
                    array($this->index_page,$this->index_page_title),
                    array($ref_page_path,$ref_page_title)
                );
            $this->task_done[$ref_page_path] = $ref_page_path;
            foreach($this->refs_todo as $page){
                if(!$this->task_done[$page]){
                    $ref_page_data['parts'][] = array(DOC_CODE_CMD,'link',$page);
                    $ref_page_data['links'][] = array($page,1);
                    $this->add_task($page,$nav);
                    $this->work();
                }
            }
            $this->write_cache($ref_page_path,$ref_page_data);
        }
    }
    
    function update_idx(){
        echo "+ Scan TOC...\n";
        $max = count($this->file_list)-1;
        for($i=$max;$i>-1;$i--){
            $data = $this->get_item_all($this->file_list[$i]);
            $toc = array();
            foreach((array)$data['links'] as $link){
                if($link[1]>0){
                    $link_toc = $this->get_item_part($link[0],'fulltoc');
                    $toc[$link[0]] = array();
                    foreach((array)$link_toc as $k=>$sbj){
                        if($sbj[0]<=$link[1]){
                            $toc[$link[0]][] = $sbj;
                        }
                    }
                }
            }
            
            $re_toc = array(
                    array(1,$data['title'],$data['file'])
                );
            
            foreach((array)$data['parts'] as $k=>$part){
                if($part[0]==DOC_CODE_INLINE){
                    if($part[1] == 'image'){
                        if(false===strpos($part[2],'://')){
                            $this->images[] = dirname($data['file']).'/'.$part[2];
                        }
                    }elseif($part[1] == 'movie'){
                        if(false===strpos($part[2],'://')){
                            $this->movies[] = dirname($data['file']).'/'.$part[2];
                        }
                    }
                }elseif($part[0]==DOC_CODE_CMD){
                    
                    if($part[1]=='link'){
                        for($j=$k-1;!isset($data['parts'][$j]) && $j>0;$j--){;}

                        if($j>-1 && $data['parts'][$j][0] ==DOC_CODE_CMD && $data['parts'][$j][1]=='toc'){
                            $data['parts'][$j][2] = array_merge($data['parts'][$j][2], $toc[$part[2]]);
                            unset($data['parts'][$k]);
                        }else{
                            $data['parts'][$k][1] = 'toc';
                            $data['parts'][$k][2] = $toc[$part[2]];
                        }
                        foreach((array)$toc[$part[2]] as $t){
                            $t[0]+=1;
                            $re_toc[] = $t;
                        }    
                    }elseif($part[1]=='t2tlnk'){
                        list($lnk_target,$lnk_text) = $part[2];
                        if(!$lnk_text){
                            $lnk_text = $this->get_item_part($lnk_target,'title');
                        }
                        $lnk_target = doczen_utils::path_diff($data['file'],substr($lnk_target,0,-4).'.html');
                        $data['parts'][$k] = array(
                                DOC_CODE_INLINE,
                                'lnk',
                                "$lnk_text $lnk_target"
                            );
                    }
                    
                }elseif($part[0]==DOC_CODE_TITLE){
                    $re_toc[] = array($part[2]+1,$part[1],$data['file'].'#id'.$part[3]);
                }
            }
            $data['fulltoc'] = $re_toc;
            $this->write_cache($this->file_list[$i],$data);
        }
    }
    
    function write_cache($file,$data){
        $data['last_modified']= time();
        return file_put_contents($this->build_path.'/cache/'.md5($file)
            ,serialize($data));
    }
    
    function build($type='html'){
        $class = 'doczen_builder_'.$type;
        $builder = new $class;
        $builder->build_path = $this->build_path.'/'.$type;
        doczen_utils::mkdir_p($this->build_path.'/'.$type);
        $builder->init($this);
        
        $max = count($this->file_list);
        
        for($i=0;$i<$max;$i++){
            
            $data = $this->get_item_all($this->file_list[$i]);
            $data['static'] = doczen_utils::path_diff($this->file_list[$i],'_static/');
            $data['index'] = array($this->index_page, $this->index_page_title);
            
            if($i>0){
                $data['prev'] = array(
                        doczen_utils::path_diff($this->file_list[$i],substr($this->file_list[$i-1],0,-4)),
                        $this->get_item_part($this->file_list[$i-1],'title')
                    );    
            }
            
            if($i<$max-1){
                $data['next'] = array(
                        doczen_utils::path_diff($this->file_list[$i],substr($this->file_list[$i+1],0,-4)),
                        $this->get_item_part($this->file_list[$i+1],'title')
                    );
            }
            
            $builder->add_page($data);
            
        }
        
        $builder->finish();
    }
    
    function get_item_all($file){
        $content = @file_get_contents($this->build_path.'/cache/'.md5($file));
        return unserialize($content);
    }
    
    function get_item_part($file,$part){
        $item = $this->get_item_all($file);
        return $item[$part];
    }
    
    function add_task($path,$nav=null){
        if(!isset($this->task_done[$path])){
            $this->task_todo[$path] = array($path,$nav);
        }
    }
    
    function work(){
        $todo = array_shift($this->task_todo);
        
        if(!$todo) return;
        
        list($path,$nav) = $todo;
        if(!file_exists($this->source.'/'.$path)) return;
        
        $this->task_done[$path] = 1;
        
        $cache_file = $this->build_path.'/cache/'.md5($path);
        if(!file_exists($cache_file) || filemtime($cache_file) < 
                max($this->start_time, filemtime($this->source.'/'.$path))
                ){
            if(!$this->had_scan_title){
                $this->had_scan_title=true;
                echo "\n=Update=\n";
            }
            echo '+ '.$path."\n";
            $t2t_parser = new doczen_parser();
            $item = $t2t_parser->load($this->source,$path);
            $item['nav'] = $nav;
            $this->write_cache($path,$item);
        }else{
            $item = unserialize(file_get_contents($cache_file));
        }
        
        $nav[] = array($path,$item['title']);
        foreach($item['links'] as $page){
            if($page[1]){
                $this->add_task($page[0] , $nav);
                $this->work();
            }else{
                $this->refs_todo[$page[0]] = $page[0];
            }
        }
        
    }
    
    function set_options($k,$v){
        $this->options[$k] = $v;
    }
    
}