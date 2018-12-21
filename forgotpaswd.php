<?php
include("php_includes/check_login_status.php");
include("php_includes/mysqli_connect.php");
// If user is already logged in, header that weenis away
if($user_ok == true){
    header("location: user_audio.php?u=".$_SESSION["username"]);
    exit();
}
?><?php
// AJAX CALLS THIS CODE TO EXECUTE
if(isset($_POST["e"])){
    $e = $_POST['e'];
    $sql = "SELECT id, username, firstname FROM users WHERE email=:email AND activated='1' LIMIT 1";
    $stmt = $db_connect->prepare($sql);
    $stmt->bindParam(':email', $e, PDO::PARAM_STR);
    $stmt->execute();
    $numrows = $stmt->rowCount();
    if($numrows > 0){
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
            $id = $row["id"];
            $u = $row["username"];
            $firstname = $row['firstname'];
        }
        $emailcut = substr($e, 0, 4);
        $randNum = rand(10000,99999);
        $tempPass = "$emailcut$randNum";
        $hashTempPass = password_hash($tempPass, PASSWORD_DEFAULT);
        $sql = "UPDATE useroptions SET temp_pass=:temp_pass WHERE username=:user LIMIT 1";
        $stmt = $db_connect->prepare($sql);
        $stmt->bindParam(':temp_pass', $hashTempPass, PDO::PARAM_STR);
        $stmt->bindParam(':user', $u, PDO::PARAM_STR);
        $stmt->execute();

        // Send a mail to the user notifying of the password change request
        require("/home/naat/vendor/phpmailer/phpmailer/src/PHPMailer.php");
        $mail = new PHPMailer;

        $email_body = '<!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <title>Example Message</title>
        </head>
        <body style="margin:0px; font-family:Tahoma, Geneva, sans-serif;">
          <div style="padding:10px; background:#333; font-size:24px; color:#CCC;"><a href="http://www.naatcast.com"><img src="/images/logo.png" width="36" height="30" alt="NaatCast" style="border:none; float:left;"></a>NaatCast - Password Change Request
          </div>
          <div style="padding:24px; font-size:17px;">

        <h2>Hello '.$firstname.'</h2><p>This is an automated message from yoursite. If you did not recently initiate the Forgot Password process, please disregard this email.</p><p>You indicated that you forgot your login password. We can generate a temporary password for you to log in with, then once logged in you can change your password to anything you like.</p><p>After you click the link below your password to login will be:<br /><b>'.$tempPass.'</b></p><p><a href="http://www.naatcast.com/forgot_pass.php?u='.$u.'&p='.$hashTempPass.'">Click here now to apply the temporary password shown above to your account</a></p><p>If you do not click the link in this email, no changes will be made to your account. In order to set your login password to the temporary password you must click the link above.</p>

            </div>
        </body>
        </html>';

        $email = 'info@example.com';
        $name = 'Example';

        $mail->Host = 'email.example.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = false;                               // Enable SMTP authentication
        $mail->Username = '';                 // SMTP username
        $mail->Password = '';                           // SMTP password
        $mail->SMTPSecure = '';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 25;                                    // TCP port to connect to


        $mail->setFrom($email, $name);
        $mail->addAddress("$e");     // Add a recipient

        $mail->isHTML(true);                                  // Set email format to HTML

        $mail->Subject = 'Temporary Password ' . $name;
        $mail->Body    = $email_body;

        if($mail->send()) {
            echo "success";
            exit();
        } else {
            echo "email_send_failed";
            exit();
        }
    } else {
        echo "no_exist";
    }
    exit();
}
?><?php
// EMAIL LINK CLICK CALLS THIS CODE TO EXECUTE
if(isset($_GET['u']) && isset($_GET['p'])){
    $u = preg_replace('#[^a-z0-9]#i', '', $_GET['u']);
    $temppasshash = preg_replace('#[^a-z0-9]#i', '', $_GET['p']);
    if(strlen($temppasshash) < 10){
        exit();
    }
    $sql = "SELECT id FROM useroptions WHERE username=:user AND temp_pass=:temp_pass LIMIT 1";
    $stmt = $db_connect->prepare($sql);
    $stmt->bindParam(':user', $u, PDO::PARAM_STR);
    $stmt->bindParam(':temp_pass', $temppasshash, PDO::PARAM_STR);
    $stmt->execute();

    $numrows = $stmt->rowCount();
    if($numrows == 0){
        header("location: message.php?msg=There is no match for that username with that temporary password in the system. We cannot proceed.");
        exit();
    } else {
        $row = $stmt->fetch();
        $id = $row[0];
        $sql = "UPDATE users SET password=:password WHERE id=:id AND username=:user LIMIT 1";
        $stmt = $db_connect->prepare($sql);
        $stmt->bindParam(':password', $temppasshash, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user', $u, PDO::PARAM_STR);
        $stmt->execute();
        // Update useroptions table
        $temporarypass = '';
        $sql = "UPDATE useroptions SET temp_pass=:temp_pass WHERE username=:user LIMIT 1";
        $stmt = $db_connect->prepare($sql);
        $stmt->bindParam(':temp_pass', $temporarypass, PDO::PARAM_STR);
        $stmt->bindParam(':user', $u, PDO::PARAM_STR);
        $stmt->execute();
        header("location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Example - Forgot Password</title>
<link rel="stylesheet" href="style/normalize.css">
<link href="https://fonts.googleapis.com/css?family=Changa+One:400,400i|Open+Sans:400,400i,700,700i" rel="stylesheet">
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
<link rel="icon" href="images/favicon.ico" type="image/x-icon">
<script src="sample/datetimepicker_css.js"></script>
<link rel="stylesheet" href="style/style.css">
<link rel="stylesheet" href="style/responsive.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="js/jquery.js"></script>
<script src="js/header_scroll.js"></script>
<script src="js/main.js"></script>
<script src="js/functions.js"></script>
</head>
<body class="loginpage">
<div id="header">
<?php include_once("template_pageTop.php"); ?>
</div>
<div id="PageMiddle">
  <div id="forgotMiddle" style="margin-top: 50px;">
    <h3 style="color: white;">Generate a temporary log in password</h3>
    <form id="forgotpassform" onsubmit="return false;">
      <div style="color:white;">Enter Your Email Address:</div>
      <input id="email" type="text" onfocus="_('status').innerHTML='';" maxlength="88">
      <br /><br />
      <button id="forgotpassbtn" onclick="forgotpass()">Generate Temporary Log In Password</button>
      <p id="status"></p>
    </form>
  </div>
</div>
</body>
</html>
