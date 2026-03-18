<?php
// Database credentials
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

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

    if (!empty($name) && is_numeric($score)) {
        $stmt = $conn->prepare("INSERT INTO scores (name, score) VALUES (:name, :score)");
        $stmt->execute([':name' => $name, ':score' => $score]);
    }
}

// Fetch the last 5 entries
$stmt = $conn->query("SELECT name, score FROM scores ORDER BY created_at DESC LIMIT 5");
$scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Scoreboard</title>
</head>
<body>

    <h1>Scoreboard</h1>

    <form method="POST" action="index.php">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="score">Score:</label>
        <input type="number" id="score" name="score" required>
        <button type="submit">Add Score</button>
    </form>

    <h2>Last 5 Entries</h2>
    <ul>
        <?php
        if (count($scores) > 0) {
            foreach ($scores as $row) {
                echo "<li>" . htmlspecialchars($row['name']) . ": " . htmlspecialchars($row['score']) . "</li>";
            }
        } else {
            echo "<li>No scores yet.</li>";
        }
        ?>
    </ul>

</body>
</html>
