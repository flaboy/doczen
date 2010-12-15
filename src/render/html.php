<?php
class doczen_render_html{
    
    var $ref_id = 0;
    var $tmp_file_id = 1;
    var $file;
    
    function fetch($data){
        
        $this->html = '';
        $this->file = $data['file'];
        
        foreach($data['parts'] as $part){
            switch($part[0]){
                case DOC_CODE_BEGIN:
                $this->on_begin($part[1],$part[2]);
                break;

                case DOC_CODE_END:
                $this->on_end($part[1]);
                break;

                case DOC_CODE_TEXT:
                $this->on_text($part[1]);
                break;
                
                case DOC_CODE_LINE:
                $this->on_line($part[1]);
                break;
                
                case DOC_CODE_CMD:
                $this->on_cmd($part[1],$part[2]);
                break;
                
                case DOC_CODE_TITLE:
                $this->on_subject($part[1],$part[2]);
                break;
                
                case DOC_CODE_INLINE:
                $this->on_inline($part[1],$part[2]);
                break;
                
                case DOC_CODE_PRE:
                $this->on_pre($part[1],$part[2]);
                break;
                
            }
        }
        
        return $this->html;
    }
    
    function on_begin($code,$option){
        if($code=='table'){
            $this->html .= '<table class="docutils">';
        }else{
            $this->html .= "<{$code}>";    
        }
    }
    
    function on_end($code){
        $this->html .= "</{$code}>";
    }
    
    
    function on_pre($type,$text){
        switch($type){
            case 'quote':
                $this->html .= '<pre class="quote">'.htmlspecialchars($text).'</pre>';            
            break;
            
            case 'mark':
                $this->html .= '<pre class="mark">'.htmlspecialchars($text).'</pre>';
            break;
            
            case 'dot':
                $text = "digraph G { \n".$text.' } ';
                $gif = $this->build_path.'/_dot_'.md5($text).'.gif';
                $error = doczen_utils::dot2file($text, $gif);
                if($error){
                    $this->html .= '<pre class="mark">'.htmlspecialchars($error).'</pre>';
                }else{
                    $this->on_inline('image',doczen_utils::path_diff($this->file,basename($gif)));
                }
                break;
            
            default:
                if(preg_match('/^\<\?php/',$text)){
                    $this->html .= '<div class="code">'.highlight_string($text,1).'</div>';
                }else{
                    $this->html .= '<pre>'.htmlspecialchars($text).'</pre>';
                }
            break;
        }
    }
    
    function on_text($text){
        $this->html .= htmlspecialchars($text);
    }
    
    function on_inline($cmd,$text){
        switch($cmd){
            
            case 'lnk':
            $text = trim($text);
            if($p = strpos($text,' ')){
                $link = substr($text,$p+1);
                $text = substr($text,0,$p);
            }else{
                $link = $text;
            }
            $this->html .= "<a href=\"{$link}\">{$text}</a>";
            break;
            
            case 'image':
            $text = trim($text);
            $this->html .= "<br /><img border=\"0\" alt=\"\" align=\"middle\" src=\"{$text}\" />";
            break;
            
            case 'strong':
            $text = htmlspecialchars($text);
            $this->html .= "<b>{$text}</b>";
            break;
            
            case 'italic':
            $text = htmlspecialchars($text);
            $this->html .= "<i>{$text}</i>";
            break;
            
            case 'code':
            $text = htmlspecialchars($text);
            $this->html .= "<tt class=\"docutils literal\"><span class=\"pre\">{$text}</span></tt>";
            break;
            
            case 'quote':
            $text = htmlspecialchars($text);
            $this->html .= "<span class=\"quote\">{$text}</span>";
            break;
            
            case 'mark':
            $text = htmlspecialchars($text);
            $this->html .= "<span class=\"mark\">{$text}</span>";
            break;
            
            case 'underline':
            $text = htmlspecialchars($text);
            $this->html .= "<u>{$text}</u>";
            break;
            
            case 'deleted':
            $text = htmlspecialchars($text);
            $this->html .= "<s>{$text}</s>";
            break;
            
            default:
            $text = htmlspecialchars($text);
            $this->html .= "<span style='background:#000;color:#fff' onclick='alert(\"{$cmd}\");'>{$text}</span>";
        }
    }
    
    function on_line($code){
        switch($code){
            case 'hr':
                $this->html .= '<hr noshade size="1">';
            break;
            
            case 'hr-strong':
                $this->html .= '<hr noshade size="5">';
            break;
            
            default:
                $this->html .= '<br />';
            break;
        }
    }
    
    function on_cmd($type,$option){
        switch($type){
            case 'toc':
                $this->html .= $this->toc($option);
            break;
            
            default:
                $this->html .= "<pre>$type\n\n";
                $this->html .= print_r($option,1).'</pre>';
            break;
        }
    }
    
    function on_subject($text,$depth){
        $text = trim($text);
        $id = 'id'.($this->ref_id++);
        $depth++;
        $this->html .= "<h{$depth}>$text</h{$depth}>";
        $this->html .= '<a class="headerlink" href="#'.$id.'" title="永久链接至标题">¶</a>';
    }
    
    function toc($toc,$class=''){
        $out = "\n<ul>\n";
        $p=1;
        foreach($toc as $t){
            
            @list($b, $text,$link) = $t;
            if(false===($sharp = strpos($link,'#'))){
                $link = substr($link,0,-4).'.html';
                $link = doczen_utils::path_diff($this->file,$link);
            }elseif($sharp>0){
                $link = substr($link,0,$sharp-4).'.html'.substr($link,$sharp);
                $link = doczen_utils::path_diff($this->file,$link);
            }
            
            while($b>$p){
                $p++;
                $out .= str_repeat(' ',4*$p)."<ul>\n";
            }
            
            while($b<$p){
                $p--;
                $out .= str_repeat(' ',4*$p)."</ul>\n";
            }
            $out .= str_repeat(' ',4*$b)."<li><a href=\"$link\" class=\"$class\">{$text}</a>\n";
        }
        while($p>0){
            $p--;
            $out .= str_repeat(' ',4*$p)."</ul>\n";
        }
        return $out;
    }
    
}