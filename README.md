# PHPLeafNode

This a Simple PHP Code to help Developers to avoid the hassles of getting element values; particularly when scrapping the HTML Document from the Web.

PHPLeafNode accepts HTML string and return leaf nodes in array form.

How to Use

    <?php 
       $url = <your url>
	   $leafgetter = new PHPLeafNode();
	   $leaves = $leafgetter->getLeafNodes($leafgetter->file_url_contents($url));
       
       print_r($leaves);
        
        // display leaves and observe how you can get various data from array of leaves
      
    ?>
