<?php
    define('EMPTY_STRING','');
    define('FROM_MIDDLE_OR_END',true);
    define('FROM_FRONT',false);
    
    
    class DomLeaves{
        
        public $result = array();
        
	public function removeWhitespaceOrNewline($html){
            $html1 = preg_replace('~^[\r\n\s\t]+~is',EMPTY_STRING,$html);
            $html1 = preg_replace('~[\r\n\s\t]+$~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~>[\r\n\s\t]+([^<]*)[\r\n\s\t]+<~is','>$1<',$html1);
            $html1 = preg_replace('~>[\r\n\s\t]+<~is','><',$html1);
            return $html1;
	}
        public function removeNoise($html){
            $html1 = preg_replace('~\&lt\;~is','<',$html);
            $html1 = preg_replace('~\&gt\;~is','>',$html1);
            $html1 = preg_replace('~\&quot\;~is','"',$html1);
            $html1 = preg_replace('~<\s*script[^>]*>[\r\n\s\t]*.*?[\r\n\s\t]*<\s*/\s*script\s*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<\s*script\s*>[\r\n\s\t]*.*?[\r\n\s\t]*<\s*/\s*script\s*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<\s*script[^>]*[^/]>[\r\n\s\t]*.*?[\r\n\s\t]*<\s*/\s*script\s*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<\s*style[^>]*>[\r\n\s\t]*.*?[\r\n\s\t]*<\s*/\s*style\s*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<\s*style\s*>[\r\n\s\t]*.*?[\r\n\s\t]*<\s*/\s*style\s*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<\s*style[^>]*[^/]>[\r\n\s\t]*.*?[\r\n\s\t]*<\s*/\s*style\s*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<\s*noscript[^>]*>[\r\n\s\t]*.*?[\r\n\s\t]*<\s*/\s*noscript\s*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<\s*noscript\s*>[\r\n\s\t]*.*?[\r\n\s\t]*<\s*/\s*noscript\s*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<\s*noscript[^>]*[^/]>[\r\n\s\t]*.*?[\r\n\s\t]*<\s*/\s*noscript\s*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<!--[\r\n\s\t]*(.*?)[\r\n\s\t]*-->~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<!\[CDATA\[(.*?)\]\]>~is','$1',$html1);
            $html1 = preg_replace('~<!\[if(.*?)\]>(.*?)<!\[endif\](--)?>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<![^>]*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~<link[^>]*>~is',EMPTY_STRING,$html1);
            $html1 = preg_replace('~</?br\s*/?>~is',EMPTY_STRING,$html1);
            return $html1;
        }
	public function hasChildNode($html){
            $tags = DomLeaves::getAllTag($html);
            $result = false;
            if(!empty($tags)){
                unset($tags[0][0]);
                unset($tags[1][0]);
                $tags[0] = array_values($tags[0]);
                $tags[1] = array_values($tags[1]);
                foreach($tags[0] as $k=>$v){
                    if(preg_match('~<\w+\s*~is',$v,$mtch)){
                        $result = true;
                        break;
                    }
                }
            }
            return $result;
	}
        public function getLeaf($el){
            $tag = $this->getAllTag($el);
            if(
                (
                    //containers with or without nodes but seen as leafs
                    preg_match('~<video\s*~is',$tag[0][0],$mm1)
                    ||preg_match('~<svg\s*~is',$tag[0][0],$mm2)
                    ||preg_match('~<canvas\s*~is',$tag[0][0],$mm3)
                    ||preg_match('~<audio\s*~is',$tag[0][0],$mm4)
                    ||preg_match('~<a(?!\w+)~is',$tag[0][0],$mm5)
                )
                &&$this->hasChildNode($el)
            ){
                $this->result[] = $el;
            }
            else{
                if($this->hasChildNode($el)){
					$el = $this->stripHtml($el);
					$children = $this->getChildren($el);
					$size = count($children);
					for($i=0;$i<$size;$i++){
                        $this->getLeaf($children[$i]);
					}
                }
                else{
                    $this->result[] = $el;
                }
            }
        }
	public function stripHtml($html){
            $noncontainertag = DomLeaves::getNonContainerTags();
		    $tags = DomLeaves::getAllTag($html);
			$start = $tags[0][0];
			$html1 = substr($html,strlen($start),strlen($html));
			if(preg_match("~<(\w+)\s*~is",$start,$matches)){
                if(!in_array($matches[1],$noncontainertag,true)){
                    if(preg_match('~</'.$matches[1].'>$~is',$html1,$m)){
                        $end = "</".$matches[1].">";
                        $mtch = substr($html1,0,(strlen($html1)-strlen($end)));
                        return self::removeWhitespaceOrNewline($mtch);
                    }
                    else
                        return $html1;
                }
            }
            else
                return $html1;
	}
	public function getChildren($html1){
            $html = $html1;
            $tags = DomLeaves::getAllTag($html);
            $size = count($tags[0]);
            $children = array();
            
            $noncontainertag = DomLeaves::getNonContainerTags();
            
            if($size>1){
		for($i=0;$i<$size;$i++){
		   if(preg_match("~<(\w+)\s*~is",$tags[0][$i],$matches)){
                        $start1 = $matches[1];
                        $openOffset = $tags[1][$i];
                        if(!in_array($start1,$noncontainertag,true)){
                            $end = "</".$start1.">";
                            $close = 1;
                            for($j=($i+1);$j<$size;$j++){
                                if(preg_match("~<(\w+)\s*~is",$tags[0][$j],$matches1)){
                                    $start2 = $matches1[1];
                                    if($start1==$start2){
                                        $close++;
                                    }
                                }
                                else{
                                    if(preg_match("~".$end."~is",$tags[0][$j],$matches1)){
                                        $close--;
                                        if($close==0){
                                            $result = DomLeaves::getChildrenTagHTML($tags[0][$i],$html,$tags[1][$j],$openOffset);
                                            $children[] = $result["children"];
                                            $html = $result["html"];
                                            $tags = $result["tags"];
                                            if(!empty($tags[0])) $size = count($tags[0]);
                                            else $size = 0;
                                            $i = -1;
                                            break;
                                        }
                                    }
                                }
                                if($j==($size-1)&&$close>0){
                                    $result = DomLeaves::getChildrenTagHTML($tags[0][$i],$html,'',$openOffset);
                                    $children[] = $result["children"];
                                    $html = $result["html"];
                                    $tags = $result["tags"];
                                    if(!empty($tags[0])) $size = count($tags[0]);
                                    else $size = 0;
                                    $i = -1;
                                    break;
                                }
                            }
                        }
                        else{
                            $result = DomLeaves::getChildrenTagHTML($tags[0][$i],$html,'',$openOffset);
                            $children[] = $result["children"];
                            $html = $result["html"];
                            $tags = $result["tags"];
                            if(!empty($tags[0])) $size = count($tags[0]);
                            else $size = 0;
                            $i = -1;
                            break;
                        }
                    }
                }
            }
	   else{
                if(!preg_match('~</\w+>~is',$tags[0][0],$mm))
		     $children[] = $tags[0][0];
            }
            
            return $children;
	}
        public function getChildrenTagHTML($el,$html,$closeOffset,$openOffset){
            $arr = array();
            $endOffset1 = strlen($el);
            if(empty($closeOffset))
                $endOffset = $openOffset+$endOffset1;
            else
               $endOffset = $closeOffset+$endOffset1;
            $str = substr($html,$openOffset,$endOffset);
            $arr["children"] = $str;
            $arr["html"] = substr($html,strlen($str));
            $arr["tags"] = DomLeaves::getAllTag($html);
            return $arr;
        }
        public function getContainerLeafArray(){
            return array(
                'video','svg','canvas','audio','a'
            );
        }
        public function getNonContainerTags(){
            return array(
                'br','hr','wbr','input','keygen','img','area','source','track','link','col','meta','base','basefont','embed','param',
                'animate','animateTransform','circle','ellipse','feColorMatrix','feGaussianBlur','fePointLight','feComposite','feDistantLight',
                'feSpotLight','rect','font-face-name','image','line','stop','path','polyline','use','mpath','polygon','tref'
            );
        }
        public function getContainerTags(){
            return array(
                'html','title','body','h1','h2','h3','h4','h5','h6','p','acronym','abbr','address','b','bdi','bdo','big','blockquote',
                'center','cite','code','del','dfn','em','font','i','ins','kbd','mark','meter','pre','progress','q','rp','rt','ruby',
                's','samp','small','strike','strong','sub','sup','time','tt','u','var','form','textarea','button','select','optgroup',
                'option','label','fieldset','legend','datalist','output','frame','frameset','noframes','iframe','map','canvas','figcaption',
                'figure','audio','video','a','nav','ul','ol','li','dir','dl','dt','dd','menu','menuitem','table','caption','th','tr','td',
                'thead','tbody','tfoot','colgroup','div','span','header','footer','main','section','article','aside','details','dialog',
                'summary','head','applet','object','animateMotion','clipPath','defs','feDiffuseLighting','filter','desc','switch','foreignObject',
                'tspan','text','g','font-face','font-face-src','missing-glyph','glyph','linearGradient','marker','mask','pattern','radialGradient',
                'symbol','textPath','svg'
            );
        }
	public function getAllTag($html){
            $tags = array();
            if(!empty($html)){
                if(preg_match_all('~(?!<\s*>)\<(?:(?>[^<>]+)|(?R))*\>~is',$html,$matchall,PREG_OFFSET_CAPTURE|PREG_SET_ORDER)){
                   foreach($matchall as $m){
                       $tags[0][] = $m[0][0];
                       $tags[1][] = $m[0][1];
                   }
                }
                else{
                    $tags[0][] = $html;
                    $tags[1][] = 0;
                }
            }
            return $tags;
	}
        public function correctDom($html1){
			$html = $html1;
            try{
                if(preg_match('~^[^><]*~is',$html1,$m)){
                    $s = preg_replace('~\s*~','',$m[0]);
                    if(!empty($s))
                        $html=preg_replace('~^([^><]+)~is','<untag>$1</untag>',$html1);
                }
            }
            catch(Exception $ex){
            }
	   return $html;
        }
	public function getLeafNodes($html){
            $html1 = $this->removeNoise($html);
            $html1 = $this->removeWhitespaceOrNewline($html1);
            $html1 = $this->correctDom($html1);
            
	    $children = $this->getChildren($html1);
            
            $size = count($children);
            for($i=0;$i<$size;$i++){
                $this->getLeaf($children[$i]);
            }
            
            return $this->result;
	}
        public function file_url_contents($url){
            $crl = curl_init();
            $timeout = 30;
            curl_setopt ($crl, CURLOPT_URL,$url);
            curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
            $ret = curl_exec($crl);
            curl_close($crl);
            return $ret;
        }
    }

?>  
