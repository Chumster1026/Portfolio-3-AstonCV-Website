<?php
// initialising nulls to avoid IDE temper tantrums
$db_host = "";
$db_user = "";
$db_pass = "";
$db_name = "";


session_start();

require_once "config.php";
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$name = "";
$email = "";
$password = "";
$phone = "";
$error = "";
$success = "";

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





// can return just a string, might be better to use a dictionary though because then you can have error or success be empty or not for whichever state then just check for emptiness
// function sign_up_user($conn, $name, $email, $password, $phone) returns a dictionary with 2 string mapped elements
function sign_up_user($conn, $name, $email, $password, $phone): array
{
    if (empty($name) || empty($email) || empty($password) || empty($phone)) {
        return ["success" => 0,
                "error" => "All fields are required."];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { # filter exists to validate emails
        return ["success" => 0,
                "error" => "Invalid email."];
    } else {
        # add password restrictions here later

        # Passwords hashed as a precautionary measure, it is good practice, helpful in defending against MITM attacks.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Using a transaction to ensure that all 3 tables are updated at the same time -- no cascading errors
        mysqli_begin_transaction($conn); // transaction BEGINS NOW

        $sql_statement = mysqli_prepare($conn, "INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
        if ($sql_statement) {
            mysqli_stmt_bind_param($sql_statement, "ssss", $name, $email, $hashed_password, $phone);
            # using prepared statements to defend against SQL injection
            if (mysqli_stmt_execute($sql_statement)) {
        //      $create_user_info = True; --> ditching this variable, just auto-creating user info now whenever this query successfully executes
                $user_id = mysqli_insert_id($conn); // this line gets the most recently inserted user_id so it jumps a lot of hoops
                mysqli_stmt_close($sql_statement);

                if (!create_default_profile($conn, $user_id)) {
                    mysqli_rollback($conn);
                    return ["success" => false,
                            "error" => "Failed to create user profile."];
                }
                if (!create_default_cv($conn, $user_id)) {
                    mysqli_rollback($conn);
                    return ["success" => false,
                            "error" => "Failed to create user cv."];
                }

                $types = ["github", "linkedin", "website"];

//                create 3 null social entries for all users that signup
                $i = 0;
                while ($i <= 2) {
                    if (!create_default_cv_socials($conn, $user_id, $types[$i])) {
                        mysqli_rollback($conn);
                        return ["success" => false,
                                "error" => "Failed to create user cv social."];
                    }
                    $i++;
                }


                mysqli_commit($conn);

                return ["success" => "Account created.",
                        "error" => ""];

                # success and error vars planned for use in form HTML
            }
            else {
                mysqli_rollback($conn);
                return ["success" => 0,
                        "error" => mysqli_error($conn)];
            }
        }
    }
    // return nulls on both
    return ["success" => "",
            "error" => ""];
}



function create_default_cv ($conn, $user_id): bool
{
    $sql_statement = mysqli_prepare($conn, "INSERT INTO cvs (user_id) VALUES (?)");
    mysqli_stmt_bind_param($sql_statement, "i", $user_id);

    return mysqli_stmt_execute($sql_statement);

//    $cv_id = mysqli_insert_id($conn);
//
//    return create_default_cv_socials($conn, $cv_id);

}

function create_default_profile ($conn, $user_id): bool {
    $sql_statement = mysqli_prepare($conn, "INSERT INTO profiles (user_id) VALUES (?)");
    mysqli_stmt_bind_param($sql_statement, "i", $user_id);

    return mysqli_stmt_execute($sql_statement); // returns true or false
}

function create_default_cv_socials($conn, $cv_id, $social_name): bool {
    $sql_statement = mysqli_prepare($conn, "INSERT INTO cv_socials (cv_id, social_name) VALUES (?, ?)");
    mysqli_stmt_bind_param($sql_statement, "is", $cv_id, $social_name);

    return mysqli_stmt_execute($sql_statement);
}







# Using trim as it removes whitespaces, basically python strip()
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $phone = trim($_POST["phone"]);

    $result = sign_up_user($conn, $name, $email, $password, $phone);
    $success = $result["success"];
    $error = $result["error"];
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SIGNUP</title>
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
        <h2>Signup</h2>
        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color:salmon;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form id="signup_form" method="post" action="signup.php">
            <label>Full name: <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required></label><br>
            <label>Email: <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required></label><br>
            <label>Password: <input type="password" name="password" required></label><br>
            <label>Phone: <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required></label><br>
            <input type="submit" value="Sign Up">
        </form>
        <h2>Already have an account?</h2>
        <h2><a href="login.php">Login</a></h2>
    </main>


</body>
</html>

