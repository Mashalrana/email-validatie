<?php
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\ElseIf_;

session_start();
$servername = "localhost";
$username = "root"; // PAS DEZE AAN ALS DAT NODIG IS
$password = ""; // PAS DEZE AAN ALS DAT NODIG IS
$db = "leaky_guest_book";
$conn;

try {
    $conn = new PDO("mysql:host=$servername;dbname=$db;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable error handling
} catch (PDOException $e) {
    die("Failed to open database connection, did you start it and configure the credentials properly?");
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$token = $_SESSION['token'];

function userIsAdmin($conn)
{
    if (isset($_COOKIE['admin'])) {
        $adminCookie = $_COOKIE['admin'];
        $stmt = $conn->prepare("SELECT cookie FROM `admin_cookies` WHERE cookie = ?");
        $stmt->execute([$adminCookie]);
        return $stmt->fetchColumn() !== false;
    }
    return false;
}
?>

<html>

<head>
    <title>Leaky-Guestbook</title>
    <style>
        body {
            width: 100%;
        }

        .body-container {
            background-color: aliceblue;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 100px;
            padding-right: 100px;
            padding-bottom: 20px;
        }

        .heading {
            text-align: center;
        }

        .disclosure-notice {
            color: lightgray;
        }
    </style>
</head>

<body>
    <div class="body-container">
        <h1 class="heading">Gastenboek 'De lekkage'</h1>
        <form action="guestbook.php" method="post">
            Email: <input type="email" name="email"><br />
            <input type="hidden" value="red" name="color">
            Bericht: <textarea name="text" minlength="4"></textarea><br />
            <?php if (userIsAdmin($conn)) : ?>
                <input type="hidden" name="admin" value="<?php echo $_COOKIE['admin']; ?>">
            <?php endif; ?>
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <input type="submit">
        </form>
        <hr />
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "geen geldige email.";
                exit;
            }
            $text = $_POST['text'];
            $admin = isset($_POST['admin']) ? 1 : 0;
            if (userIsAdmin($conn)) {
                $color = $_POST['color'];
            } else {
                $color = "red"; // default color
            }
            $stmt = $conn->prepare("INSERT INTO `entries`(`email`, `color`, `admin`, `text`) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $color, $admin, $text]);
        }

        $stmt = $conn->query("SELECT `email`, `text`, `color`, `admin` FROM `entries`");
        foreach ($stmt->fetchAll() as $row) {
            echo "<div style=\"color: " . htmlspecialchars($row['color']) . "\">Email: " . htmlspecialchars($row['email']);
            if ($row['admin']) {
                echo '&#9812;';
            }
            echo ": " . htmlspecialchars($row['text']) . "</div><br />";
        }
        ?>
        <hr />
        <div class="disclosure-notice">
            <p>
                Hierbij krijgt iedereen expliciete toestemming om dit Gastenboek zelf te gebruiken voor welke doeleinden dan ook.
            </p>
            <p>
                Onthoud dat je voor andere websites altijd je aan de principes van
                <a href="https://en.wikipedia.org/wiki/Responsible_disclosure" target="_blank" style="color: lightgray;">
                    Responsible Disclosure
                </a> wilt houden.
            </p>
        </div>
    </div>
</body>

</html>
