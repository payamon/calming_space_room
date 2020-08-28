<?php
$log = date('Y-m-d H:i:s');

$search_data = [];
$err_msg    = []; 
$success_msg = [];

$img_dir    = './img/';
    
// ここに登録処理を記述する
$host     = 'localhost';
$username = 'root';   // MySQLのユーザ名
$password = '99hhICxfi';       // MySQLのパスワード
$dbname   = 'calming_space_room';    // MySQLのDB名(今回、MySQLのユーザ名を入力してください)
$charset  = 'utf8';   // データベースの文字コード
 
// MySQL用のDSN文字列
$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;
    
try {
    // データベースに接続
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        
        if (isset($_POST['new_user']) === TRUE) {
        $new_user = $_POST['new_user'];
        }
        
        if (isset($_POST['new_password']) === TRUE) {
        $new_password = $_POST['new_password'];
        }
        
        if (!preg_match('/^([a-zA-Z0-9]{6,})$/', $new_user) ) {
            $err_msg[] = 'ユーザー名は半角英数字の6文字以上で登録して下さい';
        }
        
        if (!preg_match('/^([a-zA-Z0-9]{6,})$/', $new_password) ) {
            $err_msg[] = 'パスワードは半角英数字の6文字以上で登録して下さい';
        }
        
        if (count($err_msg) === 0) {
            
            // SQL文を作成
            $sql = 'SELECT user_name
                    FROM ec_user';
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            // SQLを実行
            $stmt->execute();
            // レコードの取得
            $search_data = $stmt->fetchAll();
            
            foreach ($search_data as $value) {
                
                if ($value['user_name'] === $new_user){
                    $err_msg[] = 'このユーザーIDは既に登録されています';
                }
            }
            
            if (count($err_msg) === 0) {
            
                // SQL文を作成
                $sql = 'INSERT INTO ec_user (user_name, password, create_datetime) 
                        VALUES(?, ?, ?)';
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                // SQL文のプレースホルダに値をバインド
                $stmt->bindValue(1, $new_user, PDO::PARAM_STR);
                $stmt->bindValue(2, $new_password, PDO::PARAM_STR);
                $stmt->bindValue(3, $log, PDO::PARAM_STR);
                // SQLを実行
                $stmt->execute();
                
                $success_msg[] = 'ユーザー登録が完了しました';
                
            }
            
        }

    }

} catch (PDOException $e) {
    // 接続失敗した場合
    $err_msg['db_connect'] = 'DBエラー：'.$e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>ユーザー登録ページ</title>
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
            <h1>ユーザー登録</h1>
<?php if (count($err_msg) > 0) { ?>
    <?php foreach ($err_msg as $value) { ?>
                <ul>
                    <li class="err_color"><?php print $value; ?></li>
                </ul>
    <?php } ?>
<?php } ?>

<?php if (count($success_msg) > 0) { ?>
    <?php foreach ($success_msg as $value) { ?>       
                <ul>
                    <li class="success_color"><?php print $value; ?></li>
                </ul>
    <?php } ?>
<?php } ?>
            <form method="post">
                <p calss="form-item">
                    <label>ユーザー名</label>
                    <input type="text" size=20 name="new_user">
                </p>
                <p calss="form-item">
                    <label>パスワード</label>
                    <input type="text" size=20 name="new_password">
                </p>
                <p calss="button-panel">
                    <input type="submit" class="button" name="submit" value="ユーザーを新規作成する">
                </p>
            </form>
            <div class="form-footer">
                <a href="log_in.php">ログインページ</a>
            </div>
        </div>
    </body>
</html>