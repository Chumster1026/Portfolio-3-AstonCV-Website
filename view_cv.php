<?php
// initialising nulls to avoid IDE temper tantrums
$db_host = "";
$db_user = "";
$db_pass = "";
$db_name = "";

require_once "config.php";
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$viewing_user_id = $_GET["user_id"] ?? ""; // avoids warnings if theres no user_id

// leave the page if theres no user_id given
//if (!$viewing_user_id) {
//    exit();
//}

function get_user_cv($conn, $user_id): array
{
    $sql_statement = mysqli_prepare($conn, "SELECT id, personal_statement, education_summary, skills, key_programming_language FROM cvs WHERE user_id = ?");

    if (!$sql_statement) {
        return [
                "success" => false,
                "error" => "Failed to prepare CV query.",
                "cv" => null
        ];
    }

    mysqli_stmt_bind_param($sql_statement, "i", $user_id);
    mysqli_stmt_execute($sql_statement);

    $result = mysqli_stmt_get_result($sql_statement);

    if ($result && mysqli_num_rows($result) > 0) {
        $cv = mysqli_fetch_assoc($result);
        mysqli_stmt_close($sql_statement);

        return [
                "success" => true,
                "error" => "",
                "cv" => $cv
        ];
    }

    mysqli_stmt_close($sql_statement);

    return [
            "success" => false,
            "error" => "CV not found. // Does not exist",
            "cv" => null
    ];
}

// returning an array containing the id of the social and the url
function get_cv_socials($conn, $cv_id): array
{
    $sql_statement = mysqli_prepare($conn, "SELECT id, url, social_name FROM cv_socials WHERE cv_id = ?");

    if (!$sql_statement) {
        return [
                "success" => false,
                "error" => "Failed to prepare social query.",
                "socials" => []
        ];
    }

    mysqli_stmt_bind_param($sql_statement, "i", $cv_id);
    mysqli_stmt_execute($sql_statement);

    $result = mysqli_stmt_get_result($sql_statement);


    if ($result && mysqli_num_rows($result) > 0) {
        // collate the results into a nice little array
        $socials = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $socials[] = $row;
        }

        mysqli_stmt_close($sql_statement);

        return [
                "success" => true,
                "error" => "",
                "socials" => $socials
        ];
    }




    mysqli_stmt_close($sql_statement);
    return [
            "success" => false,
            "error" => "No rows returned",
            "socials" => ""
    ];
}

// gets main cv body
$main_cv = get_user_cv($conn, $viewing_user_id);
$cv_success = $main_cv["success"];
$cv_error = $main_cv["error"];
//echo $cv_error;
$the_cv = $main_cv["cv"];
//print_r($the_cv);
$cv_id = $the_cv["id"];


// gets the socials
$cv_socials = get_cv_socials($conn, $cv_id);
$social_success = $cv_socials["success"];
$social_error = $cv_socials["error"];
$the_socials = $cv_socials["socials"];



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Home</title>
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

    <section id="cv">
        <h2>Viewing CV</h2>

        <?php if (!$cv_success): ?>
            <p style="color:red;"><?php echo htmlspecialchars($cv_error); ?></p>
        <?php else: ?>

        <article class="the_entire_cv">
            <h3>Personal Statement</h3>
            <p>
                <?php echo nl2br(htmlspecialchars($the_cv["personal_statement"] ?? "No personal statement?")); ?>
            </p>

            <h3>Education Summary</h3>
            <p>
                <?php echo nl2br(htmlspecialchars($the_cv["education_summary"] ?? "No education summary?")); ?>
            </p>

            <h3>Skills</h3>
            <p>
                <?php echo nl2br(htmlspecialchars($the_cv["skills"] ?? "No skills?")); ?>
            </p>

            <h3>Key Programming Language</h3>
            <p>
                <?php echo htmlspecialchars($the_cv["key_programming_language"] ?? "Not provided"); ?>
            </p>

            <h3>Social Links</h3>

            <!--    check if there's any social links-->
            <?php if (!$social_success || empty($the_socials)): ?>
                <p>Where are your socials blud</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($the_socials as $social): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($social["social_name"] ?? "Social"); ?>:</strong>
                            <a href="<?php echo htmlspecialchars($social["url"] ?? "#"); ?>" target="_blank">
                                <?php echo htmlspecialchars($social["url"] ?? "No URL"); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <br>
            <p>
                <a href="index.php">Back to CV list</a>
            </p>
            <?php endif; ?>
    </section>

</main>


</body>
</html>

