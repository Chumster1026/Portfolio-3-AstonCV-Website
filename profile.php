<?php
// initialising nulls to avoid IDE temper tantrums

session_start();

if (empty($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit();
}

// pre-jack vars again
$user_id = $_SESSION["user_id"];
$profile_text = "";
$error = "";
$success = "";
$name = $_SESSION["user_name"];

// modular design so far, this can be expanded much further to allow for profile pictures, images, custom backgrounds, further customization if ever required.


$db_host = "";
$db_user = "";
$db_pass = "";
$db_name = "";

require_once "config.php";
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

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

// maybe I should make a dedicated function for all query types that will just auto set everything
// was done in python but not sure if I have the time
function set_profile_text($conn, $user_id, $profile_text) {

    // Prepared statements to protect against SQL injection
    $sql_statement = mysqli_prepare($conn, "UPDATE profiles SET profile_text = ? WHERE user_id = ?");

    // if this somehow fails then we abort
    // Not using a transaction here because updating only 1 table, plus lazy = better
    if (!$sql_statement) {
        return [
                "success" => false,
                "error" => "Failed to prepare profile update query."
        ];
    }

    mysqli_stmt_bind_param($sql_statement, "si", $profile_text, $user_id);

    if (mysqli_stmt_execute($sql_statement)) {
        mysqli_stmt_close($sql_statement);
        return [
                "success" => true,
                "error" => ""
        ];
    }

    // if there's an error then just query and pop that in the return array
    $error = mysqli_error($conn);
    mysqli_stmt_close($sql_statement);

    return [
            "success" => false,
            "error" => $error
    ];
}

// funcs above here <--------------------------------------------------------------------------->

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // no trimming because it will not be used by search queries
    $profile_text = $_POST["profile_text"] ?? "";
    $text_limit = 2000;

    // max char limit of 2000 -- surely any user would keep it below this, right? RIGHT??
//    echo strlen($profile_text);
    if (strlen($profile_text) <= $text_limit) {
        $update_result = set_profile_text($conn, $user_id, $profile_text);

        if ($update_result["success"]) {
            $success = "Profile updated successfully.";
        } else {
            $error = $update_result["error"];
        }
    }
    else {
        $error = "Profile text must be under 2000 characters.";
    }
}

// getting profile info so the user can see what they are about to completely edit
$profile_info = get_profile_text($conn, $user_id);
$current_profile_text = $profile_info["profile_text"] ?? "";



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profile</title>
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
    <h2><?php echo htmlspecialchars($name); ?>'s Profile</h2>
    <h2>Tell us a bit about yourself!</h2>
    <form name="edit about" method="post" action="profile.php">
<!--        CSS needed to tidy up this text box and make it much larger-->
        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color:salmon;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <label for="profile_text">Make a profile description!</label><br>
        <textarea
                id="profile_text"
                name="profile_text"
                rows="6"
                cols="60"
                placeholder="Tell users about the semantics you wouldn't see on a CV!"
        ><?php echo htmlspecialchars($profile_text ?? ""); ?></textarea>
        <br>
        <input type="submit" value="update_description">

    </form>

    <br>
    <h2>Your current Description:</h2>
    <p class="intro_box"><?php echo htmlspecialchars($current_profile_text); ?></p>
</main>


</body>
</html>

