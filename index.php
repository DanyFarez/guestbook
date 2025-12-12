<?php
// --- 1. SETUP TIMEZONE & CONNECTION ---
date_default_timezone_set("Asia/Kuala_Lumpur"); 

$servername = "sql300.infinityfree.com";
$username = "if0_40661777";
$password = "2023859514";
$dbname = "if0_40661777_guestbook";

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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- GLOBAL RESET --- */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #1e0533, #110e2e, #000000);
            min-height: 100vh;
            color: white;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Aligns to top for scrolling */
            padding: 40px 20px;
        }

        /* --- RESPONSIVE CONTAINER --- */
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            
            /* Responsive Width Logic */
            width: 100%; 
            max-width: 500px; 
            
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
            margin: 0 auto;
        }

        h2 {
            text-align: center;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 1px;
            font-size: 2rem;
            background: linear-gradient(to right, #00c6ff, #0072ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p.subtitle {
            text-align: center;
            color: #aaa;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        /* --- FORM ELEMENTS --- */
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #ddd; }
        
        input[type="text"], textarea {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #444;
            color: #fff;
            padding: 15px;
            border-radius: 12px;
            font-family: inherit;
            
            /* Prevents auto-zoom on iPhone */
            font-size: 16px; 
            
            margin-bottom: 20px;
            transition: 0.3s;
        }

        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #00c6ff;
            box-shadow: 0 0 10px rgba(0, 198, 255, 0.3);
        }

        /* --- CAMERA BUTTON STYLES --- */
        #media-upload { display: none; } /* Hide default input */
        
        .custom-file-upload {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 15px; /* Larger tap area for mobile */
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            cursor: pointer;
            color: #aaa;
            transition: 0.3s;
            background: rgba(255, 255, 255, 0.02);
            margin-bottom: 20px;
            font-size: 16px;
        }

        .custom-file-upload:hover {
            border-color: #00c6ff;
            color: #fff;
            background: rgba(0, 198, 255, 0.1);
        }

        button {
            width: 100%;
            padding: 16px; /* Larger tap area */
            background: linear-gradient(90deg, #00c6ff, #0072ff);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 114, 255, 0.4);
        }

        /* --- FEED SECTION --- */
        .feed-header {
            margin-top: 40px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
            position: relative; 
        }

        .avatar {
            width: 45px;
            height: 45px;
            min-width: 45px; /* Ensures it doesn't squish on small phones */
            border-radius: 50%;
            margin-right: 15px;
            background: #222;
        }

        .content { 
            width: 100%; 
            overflow: hidden; /* Prevents overflow on small screens */
        }

        .content h4 {
            color: #fff;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .content p {
            color: #ccc;
            font-size: 0.95rem;
            line-height: 1.5;
            word-wrap: break-word; /* Breaks long words so they fit */
        }

        /* --- MEDIA (Image/Video) RESIZING --- */
        .post-media {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .content span {
            display: block;
            margin-top: 5px;
            font-size: 0.75rem;
            color: #00c6ff;
            font-weight: 500;
        }

        .delete-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #ff4444;
            text-decoration: none;
            font-size: 0.9rem;
            opacity: 0.7;
            padding: 5px; /* Easier to tap */
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            font-size: 0.8rem;
            color: #666;
        }

        /* --- MOBILE TWEAKS --- */
        @media (max-width: 600px) {
            body { padding: 20px 10px; }
            .glass-panel { padding: 20px; width: 100%; }
            h2 { font-size: 1.6rem; }
            .avatar { width: 40px; height: 40px; min-width: 40px; }
            .custom-file-upload { padding: 12px; font-size: 0.9rem; }
        }
    </style>
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

                    // --- DISPLAY MEDIA ---
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