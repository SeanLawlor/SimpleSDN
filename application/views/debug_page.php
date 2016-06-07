<?php
foreach ($commands as $cmd => $output) {
    print_r('<h3>'.$cmd.'</h3>');
    print_r('<pre>'.print_r($output, TRUE).'</pre>');
}