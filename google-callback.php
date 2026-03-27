<?php
session_start();
require_once 'config/config.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // 1. Get Access Token via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];

        // 2. Get User Profile Info via cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v2/userinfo");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $profile_response = curl_exec($ch);
        curl_close($ch);

        $profile = json_decode($profile_response, true);

        if (isset($profile['email'])) {
            $email = $profile['email'];
            $name = $profile['name'];
            $google_id = $profile['id'];

            // 3. Check if user exists in DB
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user) {
                // Log them in
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['profile_image'] = $user['profile_image'];
            }
            else {
                // Register them instantly
                $dummy_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // random pass
                $phone = '0000000000'; // placeholder

                $inst = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
                $inst->bind_param('ssss', $name, $email, $phone, $dummy_password);
                $inst->execute();

                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['profile_image'] = 'default.png';
            }

            header('Location: index.php');
            exit;
        }
    }
}

// Fallback if failed
header('Location: login.php?error=google_auth_failed');
exit;
?>

