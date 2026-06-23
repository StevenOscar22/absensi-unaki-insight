<?php
include "db.php";
$res = $conn->query("SHOW TABLES");
$schema = [];
while($r = $res->fetch_array()) {
    $table = $r[0];
    $schema[$table] = [];
    $res2 = $conn->query("DESCRIBE $table");
    while($r2 = $res2->fetch_assoc()) {
        $schema[$table][] = $r2;
    }
}
file_put_contents('schema.json', json_encode($schema, JSON_PRETTY_PRINT));
echo "Done";
?>
