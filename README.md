# PHPLeafNode

This a Simple PHP Code to help Developers to avoid the hassles of getting element values; particularly when scrapping the HTML Document from the Web.

PHPLeafNode accepts HTML string and return leaf nodes in array form.

# How to Use

    <?php
	/*
	Method 1 => Directly passing HTML
	
	$html=<<<HTML
	YOUR HTML
    HTML;
    
	$leafnode = new PHPLeafNode($html,'');
	print_r($leafnode->getValues());
	*/
	
	//Method 2 => Directly passing URL
	
    	$url = <your url>
	
	$leafnode = new PHPLeafNode(PHPLeafNode::file_url_contents($url),DO_NOT_STRIP_UPPER_TAG);
	print_r($leafnode->getValues());
        
        // display leaves and observe how you can get various data from array of leaves
    ?>
