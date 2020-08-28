<?php
$data = [];

$user_name = "";
$passwd = "";

// リクエストメソッド確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  // POSTでなければログインページへリダイレクト
  header('Location: log_in.php');
  exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
    try {
            
        $host     = 'localhost';
        $username = 'root';   // MySQLのユーザ名
        $password = '99hhICxfi';       // MySQLのパスワード
        $dbname   = 'calming_space_room';    // MySQLのDB名(今回、MySQLのユーザ名を入力してください)
        $charset  = 'utf8';   // データベースの文字コード
         
        // MySQL用のDSN文字列
        $dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;
            
        // データベースに接続
        $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        // セッション開始
        session_start();
        // POST値取得
            
        if (isset($_POST['user_name']) === TRUE) {
            $user_name = $_POST['user_name'];
        }
                
        if (isset($_POST['passwd']) === TRUE) {
            $passwd = $_POST['passwd'];
        }
                
        // メールアドレスをCookieへ保存
        setcookie('user_name', $user_name, time() + 60 * 60 * 24 * 365);
            
        $sql = 'SELECT user_id
                FROM ec_user
                WHERE user_name = ? AND password = ? ';
        // SQL文を実行する準備
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(1, $user_name, PDO::PARAM_STR);
        $stmt->bindValue(2, $passwd, PDO::PARAM_STR);
        // SQLを実行
        $stmt->execute();
        // レコードの取得
        $data = $stmt->fetchAll();
            
        
        foreach ($data as $value) {
        
            // 登録データを取得できたか確認
            if (isset($value['user_id'])) {
                // セッション変数にuser_idを保存
                $_SESSION['user_id'] = $value['user_id'];
                // ログイン済みユーザのホームページへリダイレクト
                header('Location: index.php');
                exit;
            } else {
                // ログインページへリダイレクト
                header('Location: log_in.php');
                exit;
            }
        }
        
        
    } catch (PDOException $e) {
    // 接続失敗した場合
    $err_msg['db_connect'] = 'DBエラー：'.$e->getMessage();
    }
}
?>