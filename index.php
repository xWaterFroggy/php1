<?php
session_start();

// Database credentials
$host = getenv('DB_HOST') ?: 'dpg-d6tbg95m5p6s73b9ib20-a';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'genevieve_db';
$user = getenv('DB_USER') ?: 'genevieve_db_user';
$password = getenv('DB_PASSWORD') ?: 'o6Hr9QZkHLBvppW4PZpO0cXpQybFgFhf';

$error_message = null;

// Create a database connection
try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create table if it doesn't exist
$query = "
CREATE TABLE IF NOT EXISTS scores (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
$conn->exec($query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && isset($_POST['score'])) {
    $name = $_POST['name'];
    $score = (int)$_POST['score'];

    if ($score > 100) {
        $_SESSION['error_message'] = "Score cannot be higher than 100.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    if (!empty($name) && is_numeric($score)) {
        $stmt = $conn->prepare("INSERT INTO scores (name, score) VALUES (:name, :score)");
        $stmt->execute([':name' => $name, ':score' => $score]);
        // Redirect to avoid form resubmission on refresh
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Get and clear error message
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}


// Fetch the last 5 entries
$stmt = $conn->query("SELECT name, score FROM scores ORDER BY created_at DESC LIMIT 5");
$scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color:rgb(173, 248, 138);
            color: #333;
            margin: 0;
            padding: 2em;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 600px;
            background-color: #fff;
            padding: 2em;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
            margin-top: 0;
        }
        h1 {
            text-align: center;
        }
        form {
            display: grid;
            gap: 1em;
            margin-bottom: 2em;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
        }
        button {
            padding: 12px;
            background-color:rgb(27, 155, 110);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s;
            width: 100%;
        }
        button:hover {
            background-color:rgb(145, 20, 20);
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.1em;
        }
        li:nth-child(odd) {
            background-color: #e8ecef;
        }
        .score-name {
            font-weight: bold;
        }
        .score-value {
            font-weight: normal;
            background-color:rgb(219, 52, 183);
            color: white;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.9em;
        }
        .no-scores {
            text-align: center;
            color: #7f8c8d;
            padding: 2em;
            background-color: transparent;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1em;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            margin-bottom: 1em;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Geneviève's Scoreboard</h1>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="score">Score:</label>
                <input type="number" id="score" name="score" required max="100">
            </div>
            <button type="submit">Add Score</button>
        </form>

        <h2>Last 5 Entries</h2>
        <ul>
            <?php
            if (count($scores) > 0) {
                foreach ($scores as $row) {
                    echo "<li><span class='score-name'>" . htmlspecialchars($row['name']) . "</span> <span class='score-value'>" . htmlspecialchars($row['score']) . "</span></li>";
                }
            } else {
                echo "<li class='no-scores'>No scores yet.</li>";
            }
            ?>
        </ul>
    </div>

</body>
</html>
