<?php
// استخدم هذا بدلاً من session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// مسح جميع بيانات الجلسة
$_SESSION = array();

// مسح جميع بيانات الجلسة
$_SESSION = array();

// إذا كنت تريد تدمير الجلسة تمامًا، قم أيضًا بحذف كوكي الجلسة
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// أخيرًا، تدمير الجلسة
session_destroy();

// توجيه المستخدم إلى الصفحة الرئيسية
header("Location: index.php");
exit;
?>