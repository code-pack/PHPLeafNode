# Domleaves

This a Simple PHP Code to help Developers to avoid the hassles of getting element values; particularly when scrapping the HTML Document from the Web.

DomLeaves only accepts HTML in which leaves are the targets to be obtained.

# How to Use

    <?php 
        $html = <<HTML

           <!DOCTYPE>
           <html>
              .......
             <body>
                   ....
             </body>
           </html>
    HTML;

        $leaves = new DomLeaves();
        $leaves->getValues($html); //in array
        
        // display leaves and observe how you can get various data from array of leaves
      
    ?>
