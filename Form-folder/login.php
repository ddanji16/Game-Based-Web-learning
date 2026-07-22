<?php

/*
session_start();

$email = $password = "";


if(isset($_POST["login"])){

    $useremail = $_POST["email"];
    $userpassword = $_POST["password"];

    if(empty($useremail)){
        echo"Empty Email";
    }
    elseif(empty($userpassword)){
        echo"Empty password";
    }
    elseif(empty($_SESSION["email"])){
        echo "no email register";
    }
    elseif (empty($_SESSION["email"])){
        echo "no password register";
    }

    elseif($useremail ==  $_SESSION["email"] && $userpassword  == $_SESSION["createpassword"]) {
      
         if($_SESSION["usertype"] == 0){
            header("location: index.php");
        }
         elseif($_SESSION["usertype"] == 1){
            header("location: dashboard.php");
    }
  }
else{
    echo "Invalid input";
}

}
*/


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Elementary School MLS</title>
        <link rel="stylesheet" href="login.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    </head>
<body>

<div class="container">

    <div class="left">
        <img src="img/logo.jpg" class="logo">

        <h1>Elementary School</h1>
        <h2>Management Learning System</h2>

        <p>
            Welcome to the online learning portal where
            students, teachers, and administrators can
            access learning materials, announcements,
            attendance, and academic records.
        </p>

    </div>

    <div class="right">

        <form action="login.php" method="POST">

            <h2>Welcome Back</h2>
            <p>Please login to continue</p>
            

            <div class="input-box">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>


            <div class="input-box">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
            </div>


            <div class="options">
                <label>
                    <input type="checkbox"onclick="showPassword()"> Show Password
                </label>
                <a href="#">Forgot Password?</a>
            </div>


            <button type="submit"> Login </button>

            <p class="register"> Don't have an account?  <a href="Register.php"> Register Here  </a>
            </p>
        </form>
    </div>
</div>






<script>
function showPassword(){
    var x = document.getElementById("password");

    if(x.type==="password"){
        x.type="text";
    }else{
        x.type="password";
    }
}
</script>

</body>
</html>