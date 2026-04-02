<?php
// no cv editing for users not logged in
session_start();

if (empty($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// initialising nulls to avoid IDE temper tantrums
$db_host = "";
$db_user = "";
$db_pass = "";
$db_name = "";

// stuff appearing in tbl_cvs
$personal_statement = "";
$education = "";
$skills = "";

$education_summary = "";
$key_programming_language = "";
$error = "";
$success = false; // default value empty or false

// stuff appearing in socials db
$url = "";
$social_name = "";

// socials links -- approach will include a mapping of the variables

$github_url = "";
$linkedin_url = "";
$website_url = "";


require_once "config.php";
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

//same code different function
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
        $socials = mysqli_fetch_assoc($result);
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

// this only updates the cvs table, another func needed for cv_socials, and perhaps a master func that calls both of these inside of a transaction
function update_cv($conn, $user_id, $personal_statement, $education_summary, $skills, $key_programming_language): array
{
    $sql_statement = mysqli_prepare($conn, "UPDATE cvs SET personal_statement = ?, education_summary = ?, skills = ?, key_programming_language = ? WHERE user_id = ?");

    if (!$sql_statement) {
        return [
                "success" => false,
                "error" => "Failed to prepare CV update query."
        ];
    }

    mysqli_stmt_bind_param($sql_statement, "ssssi", $personal_statement, $education_summary, $skills, $key_programming_language, $user_id);

    $result = mysqli_stmt_execute($sql_statement);
    $db_error = mysqli_error($conn);
    mysqli_stmt_close($sql_statement);

    if ($result) {
        return [
                "success" => true,
                "error" => ""
        ];
    }

    return [
            "success" => false,
            "error" => $db_error
    ];
}

// inserts new row
function add_cv_social ($conn, $cv_id, $url, $social_name): array {
    $sql_statement = mysqli_prepare($conn, "INSERT INTO cv_socials (cv_id, url, social_name) VALUES (?, ?, ?) ");

    if (!$sql_statement) {
        return [
                "success" => false,
                "error" => "Failed to prepare CV update query."
        ];
    }

    mysqli_stmt_bind_param($sql_statement, "iss", $cv_id, $url, $social_name);
    $result = mysqli_stmt_execute($sql_statement);
    $db_error = mysqli_error($conn);
    mysqli_stmt_close($sql_statement);

    if ($result) {
        return [
                "success" => true,
                "error" => ""
        ];
    }

    return [
            "success" => false,
            "error" => $db_error
    ];
}

// updates existing social
// potential semantic here as no real identifier for the url, maybe need to alter table to have a name for the social
// force the user to pick a type from radio or dropdown for universal naming convention on the social
function update_cv_social ($conn, $cv_id, $url, $social_name): array {
    $sql_statement = mysqli_prepare($conn, "UPDATE cv_socials SET url = ? WHERE cv_id = ? AND social_name = ?");

    if (!$sql_statement) {
        return [
                "success" => false,
                "error" => "Failed to prepare CV update query."
        ];
    }

    mysqli_stmt_bind_param($sql_statement, "sis", $url, $cv_id, $social_name);
    $result = mysqli_stmt_execute($sql_statement);
    $db_error = mysqli_error($conn);
    mysqli_stmt_close($sql_statement);
    if ($result) {
        return [
                "success" => true,
                "error" => ""
        ];
    }

    return [
            "success" => false,
            "error" => $db_error
    ];
}


function apply_all_changes($conn, $user_id, $cv_id, $personal_statement, $education_summary, $skills, $key_programming_language, $github_url, $linkedin_url, $website_url): array {
    $urls = [$github_url, $linkedin_url, $website_url];

    $url_mapping = [$github_url => "github",
            $linkedin_url => "linkedin",
            $website_url => "website"];

    mysqli_begin_transaction($conn);
    //map url names using linked lists
    foreach($urls as $url) {
//        echo $url . "\n";

        $result = update_cv_social($conn, $cv_id, $url, $url_mapping[$url]);
        if ($result["error"]) {
            mysqli_rollback($conn);
            return [
                    "success" => false,
                    "error" => $result["error"]
            ];
        }
    }

    $cv_result = update_cv($conn, $user_id, $personal_statement, $education_summary, $skills, $key_programming_language);
    if ($cv_result["error"]) {
        mysqli_rollback($conn);
        return [
                "success" => false,
                "error" => $cv_result["error"]
        ];
    }

    mysqli_commit($conn);
    return [
            "success" => true,
            "error" => ""
        ];
}

// -----------------------------------------------------------------------------------------
$user_cv = get_user_cv($conn, $user_id);
$cv_id = $user_cv["cv"]["id"];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $personal_statement = $_POST["personal_statement"] ?? "";
    $education_summary = $_POST["education_summary"] ?? "";
    $skills = $_POST["skills"] ?? "";
    $key_programming_language = $_POST["key_programming_language"] ?? "";

//    echo $personal_statement;
//    echo $education_summary;
//    echo $skills;
//    echo $key_programming_language;


    $github_url = $_POST["github_url"] ?? "";
    $linkedin_url = $_POST["linkedin_url"] ?? "";
    $website_url = $_POST["website_url"] ?? "";

    $url_mapping = [$github_url => "github",
            $linkedin_url => "linkedin",
            $website_url => "website"];

    $result_of_chaos = apply_all_changes($conn, $user_id, $cv_id, $personal_statement, $education_summary, $skills, $key_programming_language, $github_url, $linkedin_url, $website_url);

    $success = $result_of_chaos["success"];
    $error = $result_of_chaos["error"];


//    collect vars above and see profile.php for what to do
//    beginning transaction here

}




?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customize CV</title>
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
    <h2>Customise Your CV</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green;"><?php echo htmlspecialchars("CV has been saved!"); ?></p>
    <?php endif; ?>

    <form method="post" action="customize_cv.php">
        <div>
            <label for="personal_statement">Personal Statement</label><br>
            <textarea
                    id="personal_statement"
                    name="personal_statement"
                    rows="6"
                    cols="60"
                    placeholder="Tell us about yourself!"
            ><?php echo htmlspecialchars($personal_statement); ?></textarea>
        </div>
        <br>

        <div>
            <label for="education_summary">Education Summary</label><br>
            <textarea
                    id="education_summary"
                    name="education_summary"
                    rows="6"
                    cols="60"
                    placeholder="List your education, qualifications, grades, or relevant study"
            ><?php echo htmlspecialchars($education_summary ?? ""); ?></textarea>
        </div>
        <br>

        <div>
            <label for="skills">Skills</label><br>
            <textarea
                    id="skills"
                    name="skills"
                    rows="6"
                    cols="60"
                    placeholder="Tell us about your skills!"
            ><?php echo htmlspecialchars($skills); ?></textarea>
        </div>
        <br>

        <div>
            <label for="key_programming_language">Key Programming Language</label><br>
            <input
                    type="text"
                    id="key_programming_language"
                    name="key_programming_language"
                    value="<?php echo htmlspecialchars($key_programming_language ?? ""); ?>"
                    placeholder="e.g. Python"
            >
        </div>
        <br>
<!--    experimenting with fieldset legend blocks to see if it works better with CSS-->
        <fieldset>
            <legend>Social Links</legend>

            <div>
                <label for="github_url">GitHub URL</label><br>
                <input
                        type="url"
                        id="github_url"
                        name="github_url"
                        value="<?php echo htmlspecialchars($github_url); ?>"
                        placeholder="https://github.com/yourusername"
                >
            </div>
            <br>

            <div>
                <label for="linkedin_url">LinkedIn URL</label><br>
                <input
                        type="url"
                        id="linkedin_url"
                        name="linkedin_url"
                        value="<?php echo htmlspecialchars($linkedin_url); ?>"
                        placeholder="https://linkedin.com/in/yourusername"
                >
            </div>
            <br>

            <div>
                <label for="website_url">Website / Portfolio URL</label><br>
                <input
                        type="url"
                        id="website_url"
                        name="website_url"
                        value="<?php echo htmlspecialchars($website_url); ?>"
                        placeholder="https://yourwebsite.com"
                >
            </div>
        </fieldset>
        <br>

        <input type="submit" value="Save CV">
    </form>
</main>


</body>
</html>

