<?php

// セッション開始
session_start();
// セッション変数からログイン済みか確認
if (isset($_SESSION['user_id'])) {
    // ログイン済みの場合、ホームページへリダイレクト
    header('Location: index.php');
    exit;
}
// Cookie情報からユーザー名を取得
if (isset($_COOKIE['user_name'])) {
    $user_name = $_COOKIE['user_name'];
} else {
    $user_name = '';
}

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>ログインページ</title>
        <link rel="stylesheet" href="log_in.css">
    </head>
    <body>
        <header>
            <div class="header_contents">
                <div calss="title_place">
                    <h1>Calming&nbsp;Space&nbsp;Room</h1>
                </div>
            </div>
        </header>
        <div class="form-wrapper">
            <h1>ログイン</h1>
            <form action="log_in_manage.php" method="post">
                <p calss="form-item">
                    <label>ユーザー名</label>
                    <input type="text" id="user_name" name="user_name" required="required" value="<?php print $user_name; ?>"></input>
                </p>
                <p calss="form-item">
                    <label>パスワード</label>
                    <input type="password" id="passwd" name="passwd" required="required" placeholder="password" value=""></input>
                </p>
                <p calss="button-panel">
                    <input type="submit" class="button" value="ログイン"></input>
                </p>
            </form>
            <div class="form-footer">
                <p><a href="log_entry.php">会員登録</a></p>
            </div>
        </div>
    </body>
</html>