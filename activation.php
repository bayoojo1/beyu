<?php
if (isset($_GET['id']) && isset($_GET['u']) && isset($_GET['hash'])) {
    // Connect to database and sanitize incoming $_GET variables
    include("php_includes/mysqli_connect.php");
    //include_once("php_includes/db_connect.php");
    $id = preg_replace('#[^0-9]#i', '', $_GET['id']);
    $u = preg_replace('#[^a-z0-9]#i', '', $_GET['u']);
    $hash =  $_GET['hash'];

    // Evaluate the lengths of the incoming $_GET variable
    if($id == "" || strlen($u) < 3 || ($hash) == ""){
        // Log this issue into a text file and email details to yourself
        header("location: message.php?msg=activation_string_length_issues");
        exit();
    }
    // Check their credentials against the database
    $sql = "SELECT * FROM users WHERE id=:id AND username=:username AND hash=:hash LIMIT 1";
    $stmt = $db_connect->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':username', $u, PDO::PARAM_STR);
    $stmt->bindParam(':hash', $hash, PDO::PARAM_STR);
    $stmt->execute();
    $numrows = $stmt->rowCount();
    // Evaluate for a match in the system (0 = no match, 1 = match)
    if($numrows == 0){
        // Log this potential hack attempt to text file and email details to yourself
        header("location: message.php?msg=Your credentials are not matching anything in our system");
        exit();
    } else if($numrows == 1) {
    // Match was found, you can activate them
    $activated = '1';
    $sql = "UPDATE users SET activated=:activated WHERE id=:id LIMIT 1";
    $stmt = $db_connect->prepare($sql);
    $stmt->bindParam(':activated', $activated, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    }
    // Optional double check to see if activated in fact now = 1
    $sql = "SELECT * FROM users WHERE id=:id AND activated=:activated LIMIT 1";
    $stmt = $db_connect->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':activated', $activated, PDO::PARAM_STR);
    $stmt->execute();
    $numrows = $stmt->rowCount();
    // Evaluate the double check
    if($numrows == 0){
        // Log this issue of no switch of activation field to 1
        header("location: message.php?msg=activation_failure");
        exit();
    } else if($numrows == 1) {
        // Great everything went fine with activation!
        header("location: message.php?msg=activation_success");
        exit();
    }
} else {
    // Log this issue of missing initial $_GET variables
    header("location: message.php?msg=missing_GET_variables");
    exit();
}
?>
