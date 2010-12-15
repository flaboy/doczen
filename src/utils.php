<?php
class doczen_utils{
    
    static function mkdir_p($dir,$dirmode=0755){
        $path = explode('/',str_replace('\\','/',$dir));
        $depth = count($path);
        for($i=$depth;$i>0;$i--){
            if(file_exists(implode('/',array_slice($path,0,$i)))){
                break;
            }
        }
        for($i;$i<$depth;$i++){
            if($d= implode('/',array_slice($path,0,$i+1))){
                if(!is_dir($d)) mkdir($d,$dirmode);
            }
        }
        return is_dir($dir);
    }
    
    static function path_diff($from,$to){
        
        $from = dirname($from);
        $from = ($from=='.')?array():explode('/',$from);
        
        $to_basename = basename($to);
        $to = dirname($to);
        $to = ($to=='.')?array():explode('/',$to);
        
        $len_from = count($from);
        $len_to = count($to);
        
        for($i=0;isset($from[$i]) && isset($to[$i]) && $from[$i]==$to[$i];$i++){
            unset($to[$i]);
        }
        
        $dir = str_repeat('../',$len_from-$i). implode('/',$to);
        switch(substr($dir,-1,1)){
            case '/':
            $ret= $dir.$to_basename;
            break;
            
            case '':
            $ret = $to_basename;
            break;

            default:
            $ret = $dir.'/'.$to_basename;
        }
        return $ret;
    }
    
    static function poweredby(){
        return "使用 <a href=\"http://www.doczen.net/\">Doczen</a> 0.1";
    }
    
    static function sync_dir($from,$to,$filter=''){
        $handle = opendir($from);
        while (($file = readdir($handle)) !== false) {
            if($file{0}!='.'){
                if(is_dir($from.'/'.$file)){
                    self::sync_dir($from.'/'.$file,$to.'/'.$file);
                }elseif(!file_exists($to.'/'.$file) || filemtime($from.'/'.$file)!=filemtime($to.'/'.$file)){
                    if(!$filter || preg_match($file,$filter)){
                        if(!is_dir($to)){
                            doczen_utils::mkdir_p($to);
                        }
                        copy($from.'/'.$file,$to.'/'.$file);
                        echo "+ $from/$file -> $to/$file\n";
                        touch($to.'/'.$file,filemtime($from.'/'.$file));
                    }
                }
            }
        }
        closedir($handle);
    }
    
    static function dot2file($dot_text,$target_file){
        
        $dot_cmd = "dot -Gcharset=\"utf-8\" -Efontsize=10 -Nfontsize=10 -T ".pathinfo($target_file,PATHINFO_EXTENSION);
        if(isset($_ENV['GRAPHVIZ_FONT'])){
            $font = $_ENV['GRAPHVIZ_FONT'];;
            $dot_cmd.= " -Gfontname=\"$font\" -Efontname=\"Microsoft Yahei\" -Nfontname=\"Microsoft Yahei\"";
        }
        
        $target_handle = fopen($target_file,'w');
        
        $descriptorspec = array(
           0 => array("pipe", "r"),
           1 => $target_handle,
           2 => array("pipe", "w")
        );
        
        $pipes = array(null,&$target);
        $process = proc_open($dot_cmd, $descriptorspec, $pipes);
        if (is_resource($process)) {
            fwrite($pipes[0], $dot_text);
            fclose($pipes[0]);
            fclose($target_handle);
            $error = stream_get_contents($pipes[2]);
            if(proc_close($process)>0){
                return $error;
            }else{
                return null;
            }
        }else{
            fclose($target_handle);
        }
    }
}