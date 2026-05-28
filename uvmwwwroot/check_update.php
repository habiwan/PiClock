<?php
// Since the CSVs are in the same folder as this script inside Docker
$times_file = __DIR__ . "/times.csv"; 
$names_file = __DIR__ . "/names.csv";

$times_mod = file_exists($times_file) ? filemtime($times_file) : 0;
$names_mod = file_exists($names_file) ? filemtime($names_file) : 0;

// Combine both timestamps into a single unique hash
echo md5($times_mod . $names_mod);
?>
