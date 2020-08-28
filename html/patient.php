<?php
$patient_data = [];

// 画像ファイル関係コード
$img_dir    = './img/';    // アップロードした画像ファイルの保存ディレクトリ

// ここに登録処理を記述する
$host     = 'localhost';
$username = 'root';   // MySQLのユーザ名
$password = '99hhICxfi';       // MySQLのパスワード
$dbname   = 'calming_space_room';   // MySQLのDB名(今回、MySQLのユーザ名を入力してください)
$charset  = 'utf8';   // データベースの文字コード
 
// MySQL用のDSN文字列
$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;
    
try {
    // データベースに接続
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $sql = 'SELECT *
            FROM ec_user_buy
            INNER JOIN ec_item_master
            ON ec_user_buy.board_id = ec_item_master.board_id
            WHERE ec_user_buy.user_id';
    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $patient_data = $stmt->fetchAll();

} catch (PDOException $e) {
    // 接続失敗した場合
    $err_msg['db_connect'] = 'DBエラー：'.$e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>購入履歴</title>
        <link rel="stylesheet" href="manage.css">
    </head>
    <body>
        <h1>購入履歴</h1>
<?php foreach ($patient_data as $value){ ?>
            <div class="history_rist">
                <figure>
                    <figcaption><p>ユーザー名：<?php print htmlspecialchars($value['user_name'], ENT_QUOTES); ?></p></figcaption>
                    <figcaption><p>商品写真：<img class="furniture_img" src="<?php print $img_dir . $value['img'];?>"></p></figcaption>
                    <figcaption><p>価格：<?php print $value['board_price']; ?></p></figcaption>
                    <figcaption><p>点数：<?php print $value['amount']; ?></p></figcaption>
                    <figcaption><p>購入日：<?php print $value['create_datetime']; ?></p></figcaption>
                </figure>
            </div>
<?php } ?>
    </body>
</html>