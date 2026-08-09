<?php

session_start();  
include '../Admin-folder/database.php';



// Ensure the users table has a grade_level column (Grades 1-6).
mysqli_query($con, "ALTER TABLE users ADD COLUMN IF NOT EXISTS grade_level TINYINT NULL DEFAULT NULL");

if(isset($_POST["register"])){

 if($_POST["createpassword"] === $_POST["confirmpassword"]){

    $firstname = $_POST["firstname"];
    $middlename = $_POST["middlename"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];
    // Store a one-way hash, never the user's actual password.
    $passwordHash = password_hash($_POST["createpassword"], PASSWORD_DEFAULT);
    $usertype = $_POST["usertype"];

    // Capture the selected grade level (1-6). Only meaningful for students.
    $gradeLevel = isset($_POST["grade_level"]) && $_POST["grade_level"] !== ""
        ? (int) $_POST["grade_level"]
        : null;
    if ($gradeLevel !== null && ($gradeLevel < 1 || $gradeLevel > 6)) {
        $gradeLevel = null;
    }


    $_SESSION['name']= $firstname;  
    $_SESSION['middlename']= $middlename;
    $_SESSION['lastname']= $lastname;
    $_SESSION['email']= $email;
    $_SESSION['usertype']= $usertype;
    $_SESSION['grade_level']= $gradeLevel;

    

    $query = "INSERT INTO users (firstname, middlename, lastname, Email, createpassword, confirmpassword, usertype, grade_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $statement = mysqli_prepare($con, $query);

    // Keep the existing confirmation column hashed too; it contains no plaintext password.
    mysqli_stmt_bind_param($statement, "ssssssii", $firstname, $middlename, $lastname, $email, $passwordHash, $passwordHash, $usertype, $gradeLevel);
    $quary_run = mysqli_stmt_execute($statement);

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

              <!--
              <option value="1">Administrator</option>
              -->

              <option value="2">User</option>

              <option value="0">Student</option>
            </select>
          </div>

          <div class="input-box">
            <i class="fa-solid fa-graduation-cap"></i>
            <select name="grade_level">
              <option value="" selected>Select Grade Level</option>
              <option value="1">Grade 1</option>
              <option value="2">Grade 2</option>
              <option value="3">Grade 3</option>
              <option value="4">Grade 4</option>
              <option value="5">Grade 5</option>
              <option value="6">Grade 6</option>
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
