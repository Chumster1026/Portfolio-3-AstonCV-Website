<?php
// initialising nulls to avoid IDE temper tantrums
$db_host = "";
$db_user = "";
$db_pass = "";
$db_name = "";

// getting user id from URL
$profile_user_id = $_GET["user_id"] ?? "";
$error = "";
$profile_text = "";

require_once "config.php";


$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// requiring this func from profile as it can be used here too
function get_profile_text($conn, $user_id): array
{
    $sql_statement = mysqli_prepare($conn, "SELECT profile_text FROM profiles WHERE user_id = ?");

    if (!$sql_statement) {
        return [
                "success" => false,
                "error" => "Error occurred in preparation stage",
                "profile_text" => ""
        ];
    }

    mysqli_stmt_bind_param($sql_statement, "i", $user_id);
    mysqli_stmt_execute($sql_statement);

    $result = mysqli_stmt_get_result($sql_statement);

    // Using greater than instead of equal to just in case of a semantic error
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($sql_statement);

        return [
                "success" => true,
                "error" => "",
                "profile_text" => $row["profile_text"] ?? ""
        ];
    }

    mysqli_stmt_close($sql_statement);

    return [
            "success" => false,
            "error" => "Profile not found.",
            "profile_text" => ""
    ];
}

function get_name_from_id($conn, $user_id) {
    $sql_statement = mysqli_prepare($conn, "SELECT name FROM users WHERE id = ?");

    if (!$sql_statement) {
        return [
                "success" => false,
                "error" => "Failed to prepare sql statement",
                "name" => ""
        ];
    }

    mysqli_stmt_bind_param($sql_statement, "i", $user_id);
    mysqli_stmt_execute($sql_statement);

    $result = mysqli_stmt_get_result($sql_statement);

    // Using greater than instead of equal to just in case of a semantic error
    if ($result && mysqli_num_rows($result) > 0)
    {
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($sql_statement);

        return [
                "success" => true,
                "error" => "",
                "name" => $row["name"] ?? ""
        ];
    }

        mysqli_stmt_close($sql_statement);

        return [
                "success" => false,
                "error" => "Profile not found.",
                "profile_text" => ""
        ];
}

// defining a function capable of performing both the get name from id and the get profile text, basically a simple universal getter
// this is improvement no?
//function db_queryer($conn, $selecting, $from, $attribute, $equal_to_this): array {
//    $sql_statement = mysqli_prepare($conn, "SELECT ? FROM ? WHERE ? = ?");
//
//    if (!$sql_statement) {
//        return [
//                "success" => false,
//                "error" => "Failed to prepare sql statement",
//                $selecting => ""
//        ];
//    }
//
//    mysqli_stmt_bind_param($sql_statement, "sssi", $selecting, $from, $attribute, $equal_to_this);
//    mysqli_stmt_execute($sql_statement);
//
//    $result = mysqli_stmt_get_result($sql_statement);
//
//    // Using greater than instead of equal to just in case of a semantic error
//    if ($result && mysqli_num_rows($result) > 0)
//    {
//        $row = mysqli_fetch_assoc($result);
//        mysqli_stmt_close($sql_statement);
//
//        return [
//                "success" => true,
//                "error" => "",
//                $selecting => $row[$selecting] ?? ""
//        ];
//    }
//
//    mysqli_stmt_close($sql_statement);
//
//    return [
//            "success" => false,
//            "error" => "No results found.",
//            $selecting => ""
//    ];
//}






?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>view_profile</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
<header id="main-header">
    <h1 id="Navi">Navigation</h1>
    <nav>
        <ul class="navigation_links">
            <li><a href="index.php">Home</a></li>
            <li><a href="signup.php">Signup</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="profile.php">Customize Your Profile</a></li>
            <li><a href="customize_cv.php">Customize Your CV</a></li>
        </ul>
    </nav>
</header>

<main>

</main>


</body>
</html>

