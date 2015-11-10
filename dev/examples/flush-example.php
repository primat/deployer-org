<?php

// Turn off output buffering
ini_set('output_buffering', 'off');
// Turn off PHP output compression
ini_set('zlib.output_compression', false);

//Flush (send) the output buffer and turn off output buffering
while (@ob_end_flush());

// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);
echo "add this";
echo str_pad("",1024," ");
echo "<br />";
//ob_flush();
flush();
sleep(5);
echo "Program Output";
//ob_flush();
flush();