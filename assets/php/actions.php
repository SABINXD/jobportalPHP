<?php
require_once 'function.php';
if (isset($_GET['signup'])) {
    $response = validateSignupForm($_POST);
    if ($response['status']) {
        if (createUser($_POST)) {
            header('Location: ../../?login&newuser');
        } else {
            echo "<script>alert('Something is wrong')</script>";
        }
    } else {
        $_SESSION['error'] = $response;
        $_SESSION['formdata'] = $_POST;
        header("location:../../?signup");
    }

}
//php for login validation for user
if (isset($_GET['login'])) {

    $response = validateLoginForm($_POST);
    if ($response['status']) {
        $_SESSION['auth'] = true;
        $_SESSION['userdata'] = $response['user'];
      

       
        header("location:../../?home");

    } else {
        $_SESSION['error'] = $response;
        $_SESSION['formdata'] = $_POST;
        header("location:../../?login");
    }

}
if (isset($_GET['post_job'])) {
    if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
        $response = validateJobPostingForm($_POST);
        if ($response['status']) {
            if (addJob($_POST)) {
                header('Location: ../../?job_posted=success');
            } else {
                echo "<script>alert('Something went wrong while posting the job.')</script>";
            }
        } else {
            $_SESSION['error'] = $response;
            $_SESSION['formdata'] = $_POST;
            header("location:../../?post_job");
        }
    } else {
        header("location:../../?login");
    }
}



?>