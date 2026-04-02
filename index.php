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

if (!$conn) {
    echo "no conn";
    die("Connection failed: " . mysqli_connect_error());
}
//
//
//$sql = "SELECT * FROM users";
//
//
//$result = mysqli_query($conn, $sql);
//if (mysqli_num_rows($result) > 0) {
//    while($row = mysqli_fetch_assoc($result)) {
//        echo $row["name"];
//    }
//}
//else {echo "No data received";}


function get_cvs($conn): array {
    // some aggregate SQL function action here
    // selecting cvs.id as cv_id for reference in the HTML to set up a hyperlink url to the view_cv page
    $sql_statement = "SELECT users.name, users.email, cvs.key_programming_language, cvs.id as cv_id, users.id as user_id FROM users INNER JOIN cvs ON cvs.user_id = users.id ORDER BY users.name";

    $info = mysqli_query($conn, $sql_statement);

    if (!$info) {
        return [
                "success" => false,
                "error" => mysqli_error($conn),
                "cvs" => []
        ];
    }

    $cvs = [];

    // no list comprehensions im not that good with php yet
    while ($row = mysqli_fetch_assoc($info)) {
        $cvs[] = $row;
    }

    return [
            "success" => true,
            "error" => "",
            "cvs" => $cvs
    ];
}


$cv_result = get_cvs($conn);
$cvs = $cv_result["cvs"];
$error = $cv_result["error"];

mysqli_close($conn);

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
        <p class="intro_box">
            Welcome to the Aston CV Compendium. This website stores the CVs of persons affiliated with Aston University. If you would like your CV to be published on this website please sign up!
        </p>

        <section id="cv_list">
            <h2>Available CVs</h2>
<!--generally using htmlspecialchars as a security measure against XSS-->
            <?php if (!empty($error)): ?>
                <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if (empty($cvs)): ?>
                <p>No CVs found.</p>
            <?php else: ?>
                <?php foreach ($cvs as $cv): ?>
                    <article class="cv">
                        <h3>
                            <a href="view_cv.php?user_id=<?php echo urlencode($cv["user_id"]); ?>">
                                <?php echo htmlspecialchars($cv["name"]); ?>'s CV
                            </a>
                        </h3>

                        <p>
                            <strong>Name:</strong>
                            <?php echo htmlspecialchars($cv["name"]); ?>
                        </p>

                        <p>
                            <strong>Email:</strong>
                            <?php echo htmlspecialchars($cv["email"]); ?>
                        </p>

                        <p>
                            <strong>Key Programming Language:</strong>
                            <?php echo htmlspecialchars($cv["key_programming_language"] ?? "Not provided"); ?>
                        </p>
                    </article>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>







</body>
</html>

