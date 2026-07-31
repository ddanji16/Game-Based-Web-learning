<?php

session_start();  
include '../Admin-folder/database.php';



if(isset($_POST["register"])){

 if($_POST["createpassword"] == $_POST["confirmpassword"]){

    $firstname = $_POST["firstname"];
    $middlename = $_POST["middlename"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    $createpassword = $_POST["createpassword"];
    $confirmpassword = $_POST["confirmpassword"];
    $usertype = $_POST["usertype"];


    $_SESSION['name']= $firstname;  
    $_SESSION['middlename']= $middlename;
    $_SESSION['lastname']= $lastname;
    $_SESSION['email']= $email;
    $_SESSION['createpassword']= $createpassword;
    $_SESSION['confirmpassword']= $confirmpassword;
    $_SESSION['usertype']= $usertype;

    

    $quary = "INSERT INTO users (firstname, middlename, lastname, Email, createpassword, confirmpassword, usertype) VALUES ('$firstname', '$middlename', '$lastname', '$email', '$createpassword', '$confirmpassword', '$usertype')";
    $quary_run = mysqli_query($con, $quary);

        if($quary_run){
            header("location: login.php");
        }
        else{
            echo"Not Register Not inserted to database";
        }
    
    
 }
else{
    echo '<script>alert("Password does not match")</script>';

}}
?>




<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title> Jidanao  Elementary School MLS - Sign Up</title>
    <link rel="stylesheet" href="register.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  </head>


  <body>
    <div class="container">
      <div class="left">
        
        <img src="logo.jpg" class="logo" />

        <h1>Jidanao School LMS</h1>
        <h2>Management Learning System</h2>

        <p>
          Create your MLS account to access learning materials, announcements,
          grades, attendance, and school activities. Join our digital learning
          community today.
        </p>

      </div>



      <div class="right">
        <form action="register.php" method="POST">
          <h2>Create Account</h2>

          <p>Fill in the information below.</p>

          <div class="input-box">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="firstname"  placeholder="First Name" />
          </div>

          <div class="input-box">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="middlename" placeholder="Middle Name" />
          </div>

          <div class="input-box">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="lastname" placeholder="Last Name" required />
          </div>

          <div class="input-box">
            <i class="fa-solid fa-envelope"></i>
            <input type="email" name="email" placeholder="Email Address" required />
          </div>

          <div class="input-box">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="password" name="createpassword" placeholder="Password" required/>
          </div>

          <div class="input-box">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="confirmPassword" name="confirmpassword" placeholder="Confirm Password" required />
          </div>

          <div class="input-box">

            <select name="usertype" required>
              <option value="" disabled selected>Select User Type</option>

              <option value="1">Administrator</option>

              <option value="2">Teacher</option>

              <option value="0">Student</option>
            </select>
          </div>

          <div class="options">
            <label>
              <input type="checkbox" onclick="showPassword()" />
               Show Password
            </label>
          </div>

          <button type="submit" name="register">Create Account</button>

          <p class="login">  Already have an account? <a href="login.php"> Login Here </a></p>

        </form>
      </div>
    </div>



    <script>
      function showPassword() {
        var pass = document.getElementById("password");
        var confirm = document.getElementById("confirmPassword");

        if (pass.type === "password") {
          pass.type = "text";
          confirm.type = "text";
        } else {
          pass.type = "password";
          confirm.type = "password";
        }
      }
    </script>



  </body>
</html>
