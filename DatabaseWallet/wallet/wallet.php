<?php
$conn = new mysqli("localhost", "root", "Root@123", "digital_wallet");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure transactions table exists
$conn->query("CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255),
    type VARCHAR(10),
    amount DECIMAL(10,2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// LOGIN/CREATE FLOW
if (isset($_POST['login'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];

    $query = "SELECT * FROM users WHERE email='$email' AND contact='$contact'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        showDashboard($user, $conn);
    } else {
        echo "<h4>No account found for this user.</h4>";
        echo <<<FORM
        <form action="wallet.php" method="POST">
            <input type="hidden" name="name" value="$name">
            <input type="hidden" name="email" value="$email">
            <input type="hidden" name="contact" value="$contact">
            <div class="mb-3">
                <input type="number" step="0.01" class="form-control" name="balance" placeholder="Initial Balance" required>
            </div>
            <button type="submit" name="create" class="btn btn-success">Create New Account</button>
        </form>
FORM;
    }
}

// CREATE ACCOUNT
if (isset($_POST['create'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $balance = $_POST['balance'];

    $insert = "INSERT INTO users (name, email, contact, balance) VALUES ('$name', '$email', '$contact', '$balance')";
    if ($conn->query($insert) === TRUE) {
        $user = ['name' => $name, 'email' => $email, 'balance' => $balance];
        echo "<h4>Account created successfully for $name!</h4>";
        showDashboard($user, $conn);
    } else {
        echo "Error creating account: " . $conn->error;
    }
}

// HANDLE TRANSACTIONS
if (isset($_POST['operate'])) {
    $email = $_POST['email'];
    $type = $_POST['type'];
    $amount = $_POST['amount'];

    $q = "SELECT balance, name FROM users WHERE email='$email'";
    $res = $conn->query($q);
    $row = $res->fetch_assoc();

    $name = $row['name'];
    $balance = $row['balance'];

    if ($type === "deposit") {
        $balance += $amount;
        $conn->query("INSERT INTO transactions (email, type, amount) VALUES ('$email', 'deposit', '$amount')");
    } elseif ($type === "withdraw") {
        if ($balance >= $amount) {
            $balance -= $amount;
            $conn->query("INSERT INTO transactions (email, type, amount) VALUES ('$email', 'withdraw', '$amount')");
        } else {
            echo "<p style='color:red;'>Insufficient balance!</p>";
        }
    }

    $conn->query("UPDATE users SET balance='$balance' WHERE email='$email'");
    $user = ['name' => $name, 'email' => $email, 'balance' => $balance];
    showDashboard($user, $conn);
}

function showDashboard($user, $conn) {
    $name = $user['name'];
    $email = $user['email'];
    $balance = $user['balance'];

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Wallet Dashboard</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        body {
          background: linear-gradient(to right, #7f00ff, #e100ff);
          color: white;
          font-family: 'Segoe UI', sans-serif;
          display: flex;
          align-items: center;
          justify-content: center;
          height: 100vh;
          margin: 0;
        }
        .dashboard {
          text-align: center;
          background: rgba(255,255,255,0.1);
          padding: 40px;
          border-radius: 20px;
          box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }
        .btn-custom {
          background-color: #ffffff22;
          color: white;
          border: none;
          margin: 10px;
          padding: 10px 20px;
          border-radius: 30px;
          transition: 0.3s ease;
        }
        .btn-custom:hover {
          background-color: white;
          color: #7f00ff;
        }
        table {
          color: white;
          margin-top: 20px;
        }
      </style>
    </head>
    <body>

    <div class="dashboard">
      <h1 class="mb-4">Hi, <strong>$name</strong> 👋</h1>
      <h3>Your Wallet Balance</h3>
      <h2 class="mb-4">₹$balance</h2>

      <form action="wallet.php" method="POST">
        <input type="hidden" name="email" value="$email">
        <div class="mb-2">
            <select name="type" class="form-select">
                <option value="deposit">Deposit</option>
                <option value="withdraw">Withdraw</option>
            </select>
        </div>
        <div class="mb-2">
            <input type="number" step="0.01" class="form-control" name="amount" placeholder="Enter amount" required>
        </div>
        <button type="submit" name="operate" class="btn btn-custom">Submit</button>
      </form>

      <h4 class="mt-4">Transaction History</h4>
      <table class="table table-bordered table-striped">
        <thead>
          <tr><th>Type</th><th>Amount</th><th>Date</th></tr>
        </thead>
        <tbody>
HTML;

    $res = $conn->query("SELECT * FROM transactions WHERE email='$email' ORDER BY timestamp DESC LIMIT 10");
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['type']}</td><td>₹{$row['amount']}</td><td>{$row['timestamp']}</td></tr>";
    }

    echo <<<HTML
        </tbody>
      </table>
    </div>

    </body>
    </html>
HTML;
}

$conn->close();
?>