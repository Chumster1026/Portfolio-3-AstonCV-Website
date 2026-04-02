<?php
// initialising nulls to avoid IDE temper tantrums
session_start();
$db_host = "";
$db_user = "";
$db_pass = "";
$db_name = "";

require_once "config.php";
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$email = "";
$password = "";

// pre-jacking the variables in case the if block doesn't run
// will cause errors otherwise

$error = "";
$success = false;
$user_id = null;
$name = "";

function get_csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function verify_csrf_token($submitted_token): bool
{
    if (empty($_SESSION["csrf_token"]) || empty($submitted_token)) {
        return false;
    }

    return hash_equals($_SESSION["csrf_token"], $submitted_token);
}




// login_user returns an array as it will store the login details within the user session
// using email as email has enforced uniqueness, names do not (Names are not treated as usernames)
function login_user ($connection, $email, $password): array
{
    if (empty($email) || empty($password)) {
        return [
                "success" => false,
                "error" => "All fields are required.",
                "id" => null,
                "name" => ""
        ];
    }

    $sql_statement = mysqli_prepare($connection, "SELECT id, name, password FROM users WHERE email = ?");

    if (!$sql_statement) {
        return [
                "success" => false,
                "error" => "Failed to prepare login query.",
                "user_id" => null,
                "name" => ""
        ];
    }

    mysqli_stmt_bind_param($sql_statement, "s", $email);
    mysqli_stmt_execute($sql_statement);

    $result = mysqli_stmt_get_result($sql_statement);

    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($sql_statement);
        return [
                "success" => false,
                "error" => "Invalid email or password.",
                "user_id" => null,
                "name" => ""
        ];
    }
    // mysqli_fetch_assoc fetches the query as an array
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($sql_statement);

    if (!password_verify($password, $user["password"])) {
        return [
                "success" => false,
                "error" => "Invalid email or password.",
                "user_id" => null,
                "name" => ""
        ];
    }
    // return case for password verified
    return [
            "success" => true,
            "error" => "",
            "user_id" => $user["id"],
            "name" => $user["name"]
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // avoiding undefined index warnings here
    $submitted_csrf = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($submitted_csrf)) {
        $error = "Invalid request. Please refresh the page and try again.";
    }

    else {

        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        $result = login_user($conn, $email, $password);
        $success = $result["success"];
        $error = $result["error"];
        $user_id = $result["user_id"];
        $name = $result["name"];

        if ($result["success"]) {
            session_regenerate_id(true); // helps prevent session fixation

            $_SESSION["logged_in"] = true;
            $_SESSION["user_id"] = $user_id;
            $_SESSION["user_name"] = $name;

            header("Location: index.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>LOGIN</title>
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
    <h2>LOGIN</h2>
    <form id="login_form" method="post" action="login.php">
<!--        implementing csrf protection in the login-->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
<!--     echoing the email back in case of a failed login-->
        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color:salmon;">Logged In!<</p>
        <?php endif; ?>
        <label>Email: <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <input type="submit" value="Login">
    </form>
</main>


</body>
</html>

