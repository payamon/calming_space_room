<?php
//日付を取得
$log = date('Y-m-d H:i:s');

$err_msg    = []; 
$errorss = [];
$success_msg = [];
$data = [];
$board_stock_data = [];
$cart_data = [];
$board_data = [];

$img_dir    = './img/';
    
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

    // セッション開始
    session_start();
    // セッション変数からuser_id取得
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        // 非ログインの場合、ログインページへリダイレクト
        header('Location: log_in.php');
        exit;
    }
    
    $sql = 'SELECT user_id, user_name
            FROM ec_user
            WHERE user_id = ?';
    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $data = $stmt->fetchAll();
    
    foreach ($data as $value) {
        
        // ユーザ名を取得できたか確認
        if (isset($value['user_name'])) {
            $user_name = $value['user_name'];
        } else {
            // ユーザ名が取得できない場合、ログアウト処理へリダイレクト
            header('Location: log_out.php');
            exit;
        }
    
    }
  
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
        if (isset($_POST['user_id']) === TRUE) {
            $user_id = $_POST['user_id'];
        }
        
        if (isset($_POST['board_id']) === TRUE) {
            $board_id = $_POST['board_id'];
        }
        
        // 商品情報管理と在庫数管理のテーブル結合SQL文を作成
        $sql = 'SELECT user_id, board_id, amount
                FROM ec_cart 
                WHERE user_id = ? AND board_id = ?';
        // SQL文を実行する準備
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $board_id, PDO::PARAM_INT);
        // SQLを実行
        $stmt->execute();
        // レコードの取得
        $cart_data = $stmt->fetchAll();

        if (count($cart_data) === 0) {
            
            // INSERT文実行
            $amount = 1 ;
            // 商品情報管理と在庫数管理のテーブル結合SQL文を作成
            $sql = 'INSERT INTO ec_cart (user_id, board_id, amount, create_datetime)
                    VALUE (?, ?, ?, ?)';
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $board_id, PDO::PARAM_INT);
            $stmt->bindValue(3, $amount, PDO::PARAM_INT);
            $stmt->bindValue(4, $log, PDO::PARAM_STR);
            // SQLを実行
            $stmt->execute();
            
            $success_msg[] = 'カートに追加しました';
            
        } else {
   
            // SQL文を作成
            $sql = 'SELECT board_stock, amount
                    FROM ec_item_stock
                    INNER JOIN ec_cart
                    ON ec_item_stock.board_id = ec_cart.board_id
                    WHERE ec_item_stock.board_id = ?';
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            // SQL文のプレースホルダに値をバインド
            $stmt->bindValue(1, $board_id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            // レコードの取得
            $board_stock_data = $stmt->fetchAll();
                
            foreach ($board_stock_data as $value) {

                if ($value['board_stock'] <= $value['amount']) {
                    $errorss[] = '申し訳ありません。在庫が足りません。';
                }
                
            }
                
            if(count($errorss) === 0){
       
                // UPDATE文実行
                foreach ($cart_data as $value) {
                    $update_amount = $value['amount'] + 1;
                }
                
                // 商品情報管理と在庫数管理のテーブル結合SQL文を作成
                $sql = 'UPDATE ec_cart 
                        SET amount = ?, update_datetime = ?
                        WHERE user_id = ? AND board_id = ?';
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindValue(1, $update_amount, PDO::PARAM_INT);
                $stmt->bindValue(2, $log, PDO::PARAM_STR);
                $stmt->bindValue(3, $user_id, PDO::PARAM_INT);
                $stmt->bindValue(4, $board_id, PDO::PARAM_INT);
                // SQLを実行
                $stmt->execute();
                    
                $success_msg[] = '同じ商品をカートに追加しました';
                    
            }
            
        }

    }
    
} catch (PDOException $e) {
    // 接続失敗した場合
    $err_msg['db_connect'] = 'DBエラー：'.$e->getMessage();
}

$sql = 'SELECT *
        FROM ec_item_master 
        INNER JOIN ec_item_stock
        ON ec_item_master.board_id = ec_item_stock.board_id
        WHERE board_status = 1 AND board_kind = 2';
// SQL文を実行する準備
$stmt = $dbh->prepare($sql);
// SQLを実行
$stmt->execute();
// レコードの取得
$board_data = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>家具一覧ページ</title>
        <link rel="stylesheet" href="index.css">
    </head>
    
    <body>
        
        <header>
            <div class="header_contents">
                    <div class="header_menu">
                        <div class="title_place">
                            <h1>Calming&nbsp;Space&nbsp;Room</h1>
                        </div>
                        <div class="header_right_side">
                            <div class="shopping_cart">
                                <a class="cart" href="cart.php"><img class="cart_img" src="s00028.jpg"></a>
                            </div>
                            <div class="log_out">
                                <a class="cart" href="log_out.php"><img class="cart_img" src="log_out.png"></a>
                            </div>
                        </div>
                    </div>
                <div class="header_rist">
                   <a class="menu all" href="index.php">all</a>
                   <a class="menu sofa" href="index_sofa.php">sofa</a>
                   <a class="menu bed" href="index_bed.php">bed</a>
                   <a class="menu carpet" href="index_carpet.php">carpet</a>
                   <a class="menu table" href="index_chair.php">chair</a>
                </div>
            </div>
        </header>
        
        <name>
            <div class="name_position">
                <div calss="user_name">
                    <p>ユーザー名：ようこそ<?php print $user_name; ?>さん</p>
                </div>
            </div>
        </name>
        
        <msg>
            <div class="msg_position">
                <ul>
<?php foreach ($success_msg as $success) { ?>
                    <li><?php print $success; ?></li>
<?php } ?>
                </ul>
<?php if (count($errorss) > 0) { ?>
    <?php foreach ($errorss as $error) { ?>
                <ul>
                    <li><?php print $error; ?></li>
                </ul>
            </div>
    <?php } ?>
<?php } ?>
        </msg>
        
        <menu>
            <h1>家具一覧</h1>
            <div class="doctor_menu">
<?php foreach ($board_data as $value){ ?>
    <?php foreach ($data as $cart) { ?>
                <figure>
                    <form method="post">
                        <img class="doctor_img" src="<?php print $img_dir . $value['img']; ?>">
                        <figcaption>商品名:<?php print htmlspecialchars($value['board_name'], ENT_QUOTES); ?></figcaption>
    <?php if(($value['board_kind']) === 0){ ?>  
                        <figcaption>種類:ソファー</figcaption>
                        <input type="hidden" name="kind" value="<?php print $value['board_kind']; ?>">
    <?php }else if (($value['board_kind']) === 1){ ?>
                        <figcaption>種類:ベッド</figcaption>
                        <input type="hidden" name="kind" value="<?php print $value['board_kind']; ?>">
    <?php }else if (($value['board_kind']) === 2){ ?>
                        <figcaption>種類:椅子</figcaption>
                        <input type="hidden" name="kind" value="<?php print $value['board_kind']; ?>">
    <?php }else if (($value['board_kind']) === 3){ ?>
                        <figcaption>種類:カーペット</figcaption>
                        <input type="hidden" name="kind" value="<?php print $value['board_kind']; ?>">
    <?php } ?>
                        <figcaption>価格:<?php print htmlspecialchars($value['board_price'], ENT_QUOTES); ?>円</figcaption>
        <?php if($value['board_stock'] === 0){ ?>
                        <figcaption>sold&nbsp;out</figcaption>
        <?php }else{ ?>
                        <figcaption>
                            <input type="hidden" name="user_id" value="<?php print $cart['user_id']; ?>">
                            <input type="hidden" name="board_id" value="<?php print $value['board_id']; ?>">
                            <input type="submit" value="カートに入れる">
                        </figcaption>
        <?php } ?>
                    </form>
                </figure>
    <?php } ?>
<?php } ?>
            </div>
        </menu>
        
        <footer>
            <p class="copy_rights"><small>Copyright&nbsp;CalmingSpaceRoom&nbsp;All&nbsp;Rights&nbsp;Reserved.</small></p>
        </footer>
    </body>
</html>