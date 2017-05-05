<?php
	define('EMPTY_STRING','');
	define('STRIP_UPPER_TAG',true);
	define('DO_NOT_STRIP_UPPER_TAG',false);

	class PHPLeafNode{

		private $html;
		private $tags = array();
		public $result = array();

		public function __construct($html,$flag){
			if($flag==DO_NOT_STRIP_UPPER_TAG){
				//$noisestart = microtime(true);
				$this->html = self::removeNoise($html);
				//echo "Noise takes = ".(microtime(true)-$noisestart)."\n";

				//$whitespacestart = microtime(true);
				$this->html = self::removeWhitespaceOrNewline($this->html);
				//echo "Whitespace removal takes = ".(microtime(true)-$whitespacestart)."\n";

				/*
				    functions to correct entire DOM; particularly in situations where there will be 
				    any word between <a>any word</b>, <a>any word<a>, <a>any word<b>, </b>anyword</b>, </b>any word,
				    any word<a>, </b>any word </a>, </b>any word<a>, </b>any word<b>
				*/

				//$correctdomstart = microtime(true);
				self::correctDom1();
				self::correctDom2();
				self::correctDom3();
				self::correctDom4();
				self::correctDom5();
				self::correctDom6();
				self::correctDom7();
				//echo "CorrectDom takes = ".(microtime(true)-$correctdomstart)."\n";

				//$getalltagstart = microtime(true);
				$this->tags = self::getAllTag($this->html);
				//echo "Getalltag takes = ".(microtime(true)-$getalltagstart)."\n";

			}
			else{
				$html1 = self::stripHtml($html,$flag);
				$this->tags = self::getAllTag($html1);
				$this->html = $html1;
			}
		}
		public function scraperSet($html,$flag){
			$html1 = $this->stripHtml($html,$flag);
			$this->tags = $this->getAllTag($html1);
			$this->html = $html1;
		}
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
			$html1 = preg_replace('~=\s*\'\s*\'~is','=\'.\'',$html1); //add . into attributes with empty value '' to avoid error when processing correctDom1
			$html1 = preg_replace('~=\s*"\s*"~is','="."',$html1); //add . into attributes with empty value "" to avoid error when processing correctDom1
			return $html1;
		}
		public function hasChildNode($html){
			$tag = $this->getAllTag($html);
			unset($tag[0]);
			$tag = array_values($tag);
			$result = false;
			foreach($tag as $k=>$v){
				if(preg_match('~<[\w]+[^>]*>~is',$v,$mtch)){
					$result = true;
					break;
				}
			}
			return $result;
		}  
		public function getLeaf($el){
			$tag = $this->getAllTag($el);
			if((preg_match('~<video[^>]*>~is',$tag[0],$mm1)||preg_match('~<svg[^>]*>~is',$tag[0],$mm2)||preg_match('~<canvas[^>]*>~is',$tag[0],$mm4))
			||(preg_match('~<a\s+([^>]*)>~is',$tag[0],$mm3)&&$this->hasChildNode($el))){
				$this->result[] = $el;
			}
			else{
				if($this->hasChildNode($el)){
					$this->scraperSet($el,STRIP_UPPER_TAG);
					$children = $this->getChildren();
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
		public function stripHtml($html,$flag){
			if($flag==STRIP_UPPER_TAG){
				$tags = $this->getAllTag($html);
				$start = $tags[0];
				$html1 = substr($html,strlen($start),strlen($html));
				if(preg_match("~<(\w+)[^>]*>~is",$start,$matches)){
					$end = "</".$matches[1].">";
					$mtch = substr($html1,0,(strlen($html1)-strlen($end)));
					return $this->removeWhitespaceOrNewline($mtch);
				}
				else
					return $html1;
			}
		}
		public function getChildren(){
			$size = count($this->tags);
			$children = array();
			$tags = $this->tags;
			$html = $this->html;

			if($size>1){
				for($i=0;$i<$size;$i++){
					if(preg_match("~<(\w+)[^>]*>~is",$tags[$i],$matches)){
						$start = $matches[1];
						if($size>1){
							$end = "</".$start.">";
							$close = 1;
							for($j=($i+1);$j<$size;$j++){
								if(preg_match("~<(\w+)[^>]*>~is",$tags[$j],$matches1)){
									$start1 = $matches1[1];
									if($start1==$start){
										$close++;
									}
								}
								else{
									if(preg_match("~".$end."~is",$tags[$j])){
										$close--;
										if($close==0){
											$i = $j;
											$_array = explode($end,$html);
											$sum = 0;
											$str='';
											$_size = count($_array);
											for($index=0;$index<$_size;$index++){
												$s = preg_replace('~\s*~','',$_array[$index]);
												$c_tag = 0;
												if(!empty($s)){
													$tag = $this->getAllTag($_array[$index]);
													$c_tag = count($tag);
												}
												$str=$str.$_array[$index].$end;
												$sum+=$c_tag+1;
												if($sum==($i+1)){
													if(preg_match('~^<'.$start.'[^>]*>(.*?)'.$end.'~is',$str,$match)){
														$children[] = $str;
														$html = substr($html,strlen($str),strlen($html));
														for($k=0;$k<=$i;$k++){
															unset($tags[$k]);
														}
														$tags = array_values($tags);
														$i = -1;
														$size = count($tags);
													}
													break;
												}
											}
											break;
										}
									}
								}
								if($j==($size-1)&&$close>0){
									$children[] = $tags[$i];
									$html = substr($html,strlen($tags[$i]),strlen($html));
									unset($tags[$i]);
									$tags = array_values($tags);
									$size = count($tags);
									$i = -1;
									$close = 0;
									break;
								}
							}
						}
						else{
							$children[] = $tags[$i];
							$html = substr($html,strlen($tags[$i]),strlen($html));
							unset($tags[$i]);
							$tags = array_values($tags);
							$size = count($tags);
							$close = 0;
						}
					}
					else{
						$html = substr($html,strlen($tags[$i]),strlen($html));
						unset($tags[$i]);
						$tags = array_values($tags);
						$i = -1;
						$size = count($tags);
					} 
				}
			}
			else{
				if(!preg_match('~</\w+>~is',$tags[0],$mm))
					$children[] = $tags[0];
			}

			return $children;
		}
		public function getAllTag($html){
			$tags = array();

			if(preg_match_all('~(?!<\s*>)\<(?:(?>[^<>]+)|(?R))*\>~im',$html,$matchall,PREG_SET_ORDER)){
				foreach($matchall as $m){
					$tags[] = $m[0];
				}
			}
			else
				$tags[] = $html;
			return $tags;
		}
		public function correctDom1(){
			try{
				//expression to get anything within ='anything' or ="anything" followed by " or '
				if(preg_match_all('~((?<==\')(.(?!\'))*(\W|\w*))\'|((?<==")(.(?!"))*(\W|\w*))"~im',$this->html,$matchall,PREG_SET_ORDER)){
					foreach($matchall as $m){ //loop each match
						if(preg_match('~\<~is',$m[0],$mtch1)||preg_match('~\>~is',$m[0],$mtch2)){ //if match contains < or >, also ignore match without < or >
							//get ending character which may be " or '
							$end = $m[0][(strlen($m[0])-1)];
							$replace1 = substr($m[0],0,(strlen($m[0])-1)); //omit ending character
							//replace symbol to special character
							$replace = preg_replace('~"~is','&quot;',$replace1);
							$replace = preg_replace('~<~is','&lt;',$replace);
							$replace = preg_replace('~>~is','&gt;',$replace);
							$replace = preg_replace('~\'~is','&#39;',$replace);
							/*
								escape delimiter in preg_quote in order to reach end of delimiter, then do the replacement of
								old pattern to new replacement value
							*/
							$this->html = preg_replace("~".preg_quote(($end.$replace1.$end),'~')."~is",$end.$replace.$end,$this->html);
						}
					}
				}
			}
			catch(Exception $ex){
				//echo $ex;
			}
		}
		//function to enclose any word between <a>any word</b> into <span>any word<span> to form <a><span>any word</span></b>
		public function correctDom2(){
			try{
				//negative lookbehind expression to match <a>any word</b>
				if(preg_match_all('~(?!<(\w+)[^>]*>[^><]*</\1>)<(\w+)[^>]*>([^><]+)</\w+>~is',$this->html,$matchall,PREG_SET_ORDER)){
					foreach($matchall as $m){
						$tags = $this->getAllTag($m[0]); //get all tags
						$s = preg_replace('~\s*~','',$m[3]); //remove whitespace
						if(!empty($s)){ //is not empty then if length is greater than 0
							$str = $tags[0].'<span>'.$m[3].'</span>'.$tags[1]; //form <a><span>any word</span></b>
							$str = $this->removeWhitespaceOrNewline($str); //remove any whitespace or new line in new replacement value
							$this->html = str_replace($m[0],$str,$this->html); //replace <a>any word</b> to <a><span>any word</span></b>
						}
					}
				}
			}
			catch(Exception $ex){
				//echo $ex;
			}
		}
		public function correctDom3(){
			try{
				if(preg_match_all('~(?=(</\w+>([^><]+)</\w+>))~is',$this->html,$matchall,PREG_SET_ORDER)){
					foreach($matchall as $m){
						$tags = $this->getAllTag($m[1]);
						$s = preg_replace('~\s*~','',$m[2]);
						if(!empty($s)){
							$str = $tags[0].'<span>'.$m[2].'</span>'.$tags[1];
							$str = $this->removeWhitespaceOrNewline($str);
							$this->html = str_replace($m[1],$str,$this->html);
						}
					}
				}
			}
			catch(Exception $ex){
				//echo $ex;
			}
		}
		public function correctDom4(){
			try{
				if(preg_match_all('~(?=(<\w+[^>]*>([^><]+)<\w+[^>]*>))~is',$this->html,$matchall,PREG_SET_ORDER)){
					foreach($matchall as $m){
						$tags = $this->getAllTag($m[1]);
						$s = preg_replace('~\s*~','',$m[2]);
						if(!empty($s)){
							$str = $tags[0].'<span>'.$m[2].'</span>'.$tags[1];
							$str = $this->removeWhitespaceOrNewline($str);
							$this->html = str_replace($m[1],$str,$this->html);
						}
					}
				}
			}
			catch(Exception $ex){
				//echo $ex;
			}
		}
		public function correctDom5(){
			try{
				if(preg_match('~^[^><]+~is',$this->html,$m)){
					$s = preg_replace('~\s*~','',$m[0]);
					if(!empty($s))
						$this->html=preg_replace('~^([^><]+)~is','<span>$1</span>',$this->html);
				}
			}
			catch(Exception $ex){
				//echo $ex;
			}
		}
		public function correctDom6(){
			try{
				if(preg_match_all('~</\w+>([^><]+)<\w+[^>]*>~is',$this->html,$matchall,PREG_SET_ORDER)){
					foreach($matchall as $m){
						$tags = $this->getAllTag($m[0]);
						$s = preg_replace('~\s*~','',$m[1]);
						if(!empty($s)){
							$str = $tags[0].'<span>'.$m[1].'</span>'.$tags[1];
							$str = $this->removeWhitespaceOrNewline($str);
							$this->html = str_replace($m[0],$str,$this->html);
						}
					}
				}
			}
			catch(Exception $ex){
				//echo $ex;
			}
		}
		public function correctDom7(){
			try{
				if(preg_match('~=\'.\'~is',$this->html,$m)){
					$this->html = preg_replace('~=\'.\'~is','=\'\'',$this->html);
				}
				if(preg_match('~="."~is',$this->html,$m)){
					$this->html = preg_replace('~="."~is','=""',$this->html);
				}
			}
			catch(Exception $ex){
				//echo $ex;
			}
		}
		public function getValues(){
			$children = $this->getChildren();

			//$loopstart = microtime(true);
			$size = count($children);
			for($i=0;$i<$size;$i++){
				$this->getLeaf($children[$i]);
			}
			//echo 'Loop takes = '.(microtime(true)-$loopstart)."\n";

			return $this->result;
		}
		public function file_url_contents($url){
			$c = curl_init($url);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false); //to overcome ssl error but insecure
			$html = curl_exec($c);
			if (curl_error($c))
				die(curl_error($c));

			// Get the status code
			$status = curl_getinfo($c, CURLINFO_HTTP_CODE);

			curl_close($c);
			
           		 return $html;
		}
	}
?>  
