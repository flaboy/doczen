<?php
class doczen_builder_html{
    
    var $had_title = false;
    
    function init($master){
        $this->options = &$master->options;
        $this->master = &$master;
        $this->render = new doczen_render_html;
        $this->render->build_path = $this->build_path;
    }
    
    function finish(){
        doczen_utils::sync_dir($this->options['static_dir'], $this->build_path.'/_static');
        foreach($this->master->images as $res){
            $from = $this->master->source.'/'.$res;
            $to = $this->build_path.'/'.$res;
            if(file_exists($from)){
                if(!file_exists($from) || 
                        filemtime($from)!=filemtime($to) ){
                    //echo pathinfo($to,PATHINFO_EXTENSION),"\n";
                    doczen_utils::mkdir_p(dirname($to));
                    copy($from , $to);
                    echo "+ Image: $to\n";
                    touch($to,filemtime($from));
                }
            }
        }
    }
    
    function add_page($data){
        $target = $this->target_file($data['file']);
        if(file_exists($target) && filemtime($target) >= $data['last_modified']){
            return;
        }
        
        $data['index'][0] =  doczen_utils::path_diff($data['file'],substr($data['index'][0],0,-4).'.html');
        if($data['nav']){
            foreach($data['nav'] as $k=>$lnk){
                $data['nav'][$k][0] = doczen_utils::path_diff($data['file'],substr($lnk[0],0,-4).'.html');
            }
        }else{
            $data['nav'] = array($data['index']);
        }
        
        if(!$this->had_title){
            echo "\n=Build html=\n";
            $this->had_title = true;
        }
        
        $data['body'] = $this->render->fetch($data);
        unset($data['parts'], $data['links']);
        $data['toc'] = $this->render->toc($data['toc'],'reference internal');
        
        $content = $this->template_it($data);
        $dirname = dirname($target);
        doczen_utils::mkdir_p($dirname);
        echo "+ $target\n";
        file_put_contents($target,$content);
    }
    
    function target_file($path){
        return $this->build_path.'/'.substr($path,0,-4).'.html';
    }
    
    public function is_need_build($path,$time){
        $target = $this->target_file($path);
        return (!file_exists($target) || filemtime($target) < $time);
    }
    
    private function template_it($data){
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
        ob_start();
        include($this->options['template_dir'].'/default.tpl');
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
}