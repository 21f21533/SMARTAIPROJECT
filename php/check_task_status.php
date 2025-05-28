<?php
$file = "../python/task_done.txt";

if (file_exists($file)) {
    echo "done";
    unlink($file); // delete it after confirming to avoid repeat
} else {
    echo "waiting";
}
?>
