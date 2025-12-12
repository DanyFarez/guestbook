<?php
// --- 1. SETUP TIMEZONE & CONNECTION ---
date_default_timezone_set("Asia/Kuala_Lumpur"); 

$servername = "";
$username = "";
$password = "";
$dbname = "";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// --- 2. ADMIN SECRET KEY ---
$admin_secret = "danny123"; 
$is_admin = isset($_GET['admin']) && $_GET['admin'] === $admin_secret;

// --- 3. DELETE LOGIC ---
if ($is_admin && isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);
    // Optional: Delete file from folder if you want
    $conn->query("DELETE FROM entries WHERE id = $id_to_delete");
    header("Location: " . $_SERVER['PHP_SELF'] . "?admin=" . $admin_secret);
    exit();
}

// --- 4. PROFANITY FILTER ---
function filterBadWords($text) {
    $bad_words = array("stupid", "idiot", "badword", "ugly");
    $replacements = array("******", "******", "[censored]", "******");
    return str_ireplace($bad_words, $replacements, $text);
}

// --- 5. INSERT LOGIC ---
$status = "";
if (isset($_POST['submit'])) {
    $user = htmlspecialchars($_POST['username']);
    $msg = htmlspecialchars($_POST['message']);

    $user = filterBadWords($user);
    $msg = filterBadWords($msg);

    // --- NEW: FILE UPLOAD HANDLING ---
    $file_path = NULL; 
    
    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'mp4');
        $filename = $_FILES['media']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            $new_filename = uniqid() . "." . $file_ext;
            $destination = "uploads/" . $new_filename;

            if (move_uploaded_file($_FILES['media']['tmp_name'], $destination)) {
                $file_path = $new_filename;
            } else {
                $status = "Error moving file.";
            }
        } else {
            $status = "Invalid file type.";
        }
    }

    if (!empty($user) && !empty($msg) && $status == "") {
        $kl_time = date("Y-m-d H:i:s");
        
        $stmt = $conn->prepare("INSERT INTO entries (username, message, created_at, media_file) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user, $msg, $kl_time, $file_path);
        
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF']); 
            exit();
        } else {
            $status = "Error: " . $conn->error;
        }
    }
}

$sql_get = "SELECT * FROM entries ORDER BY created_at DESC";
$result = $conn->query($sql_get);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>The Digital Wall</title>
    
    <link rel="stylesheet" href="style.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="glass-panel">
        <h2>THE DIGITAL WALL</h2>
        <p class="subtitle">Leave your message for dany!</p>
        
        <?php if($is_admin): ?>
            <p style="text-align:center; color:#00ff00; margin-bottom:10px;">🛡️ ADMIN MODE ACTIVE</p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <label>Name</label>
            <input type="text" name="username" placeholder="Who are you?" required autocomplete="off">
            
            <label>Message</label>
            <textarea name="message" rows="3" placeholder="Write message here ..." required></textarea>
            
            <input type="file" name="media" id="media-upload" accept="image/*,video/mp4" onchange="updateFileName()">
            <label for="media-upload" class="custom-file-upload">
                <i class="fas fa-camera"></i> 
                <span id="file-label">Add Photo or Video</span>
            </label>

            <button type="submit" name="submit">Submit</button>
        </form>

        <div class="feed-header">Recent Updates</div>

        <div class="entries">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $avatar_url = "https://api.dicebear.com/7.x/bottts/svg?seed=" . urlencode($row["username"]);
                    $nice_date = date("d M Y, h:i A", strtotime($row["created_at"]));

                    echo "<div class='card'>";
                    echo "<img src='$avatar_url' class='avatar' alt='avatar'>";
                    echo "<div class='content'>";
                    echo "<h4>" . $row["username"] . "</h4>";
                    echo "<p>" . $row["message"] . "</p>";

                    if (!empty($row['media_file'])) {
                        $file_ext = strtolower(pathinfo($row['media_file'], PATHINFO_EXTENSION));
                        $file_url = "uploads/" . $row['media_file'];
                        if ($file_ext == 'mp4') {
                            echo "<video class='post-media' controls><source src='$file_url' type='video/mp4'></video>";
                        } else {
                            echo "<img src='$file_url' class='post-media' alt='Uploaded image'>";
                        }
                    }

                    echo "<span>" . $nice_date . "</span>";
                    echo "</div>";

                    if ($is_admin) {
                        echo "<a href='?admin=$admin_secret&delete=" . $row['id'] . "' class='delete-btn' onclick='return confirm(\"Delete this message?\");'><i class='fas fa-trash'></i></a>";
                    }

                    echo "</div>";
                }
            } else {
                echo "<p style='text-align:center; color:#555;'>No message yet. Be the first.</p>";
            }
            ?>
        </div>

        <div class="footer">
            &copy; <?php echo date("Y"); ?> Danny Fareez. All rights reserved.
        </div>
    </div>

    <script>
        function updateFileName() {
            const input = document.getElementById('media-upload');
            const label = document.getElementById('file-label');
            if (input.files.length > 0) {
                label.textContent = input.files[0].name;
                label.style.color = "#00c6ff";
            }
        }
    </script>

</body>
</html>
