<?php

    session_start();
    include '../Admin-folder/database.php';
    $invalid = "";



if(isset($_POST["login"])){

    $useremail = $_POST["email"];
    $userpassword = $_POST["password"];

    $query = "SELECT * FROM users WHERE Email='$useremail' AND createpassword='$userpassword'";
    $query_run = mysqli_query($con, $query);

    if(mysqli_num_rows($query_run) > 0){

        $row = mysqli_fetch_assoc($query_run);

        $_SESSION["email"] = $row["Email"];
        $_SESSION["usertype"] = $row["usertype"];
        $_SESSION["firstname"] = $row["firstname"];

        if($row["usertype"] == 0){
            header("location: ../index.php");
            exit();
        }
        elseif($row["usertype"] == 1){
            header("location: ../Admin-folder/admin.php");
            exit();
        }
        elseif($row["usertype"] == 2){
            header("location: ../Teacher-folder/teacher.php");
            exit();
        }

    }else{
        $invalid = "Invalid Email or Password";
    }
}

 

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Jidanao Elementary School MLS</title>
        <link rel="stylesheet" href="login.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    </head>
<body>

<div class="container">

    <div class="left">
        <img src="logo.jpg" class="logo">

        <h1> Jidanao School LMS</h1>
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


            <button type="submit" value="login" name="login"> Login </button>
            <span class="err"><?= $invalid?></span><br> 

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