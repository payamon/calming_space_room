<?php
//日付を取得
$log = date('Y-m-d H:i:s');

$err_msg    = [];
$success_msg = [];
$errorss = [];
$board_stock_data = [];
$cart_data = [];
$total_data = [];
$data = [];
$ids = [];
$id_total = [];

$process_kind = "";

$img_dir    = './img/';

$host     = 'localhost';
$username = 'root';   // MySQLのユーザ名
$password = '99hhICxfi';       // MySQLのパスワード
$dbname   = 'calming_space_room';    // MySQLのDB名(今回、MySQLのユーザ名を入力してください)
$charset  = 'utf8';   // データベースの文字コード
 
// MySQL用のDSN文字列
$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;

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
    
try {
    // データベースに接続
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if( isset($_POST['process_kind']) ) {
        $process_kind = $_POST['process_kind'];
    }
    
    if ($process_kind === 'update_cart_stock') {
    
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
        
            if (isset($_POST['update_cart_stock']) === TRUE) {
                $update_cart_stock = $_POST['update_cart_stock'];
            }
            
            if (isset($_POST['user_id']) === TRUE) {
                $user_id = $_POST['user_id'];
            }
            
            if (isset($_POST['board_id']) === TRUE) {
                $board_id = $_POST['board_id'];
            }
            
            if (mb_strlen($update_cart_stock) === 0) {
                $errorss[] = '変更された個数が入力されていません';
            }
            if (!preg_match('/^[0-9]+$/', $update_cart_stock) && mb_strlen($update_cart_stock) !== 0) {
                $errorss[] = '変更された個数が整数ではありません';
            }
            
            if(count($errorss) === 0){
                
                // SQL文を作成
                $sql = 'select board_stock
                        FROM ec_item_stock
                        WHERE board_id = ?';
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                // SQL文のプレースホルダに値をバインド
                $stmt->bindValue(1, $board_id, PDO::PARAM_INT);
                // SQLを実行
                $stmt->execute();
                // レコードの取得
                $board_stock_data = $stmt->fetchAll();
                
                foreach ($board_stock_data as $value) {
                    $stock_date = $value['board_stock'];
                }
                
                if ($value['board_stock'] < $update_cart_stock) {
                    $errorss[] = '申し訳ありません。在庫が足りません。';
                }
                
                if(count($errorss) === 0){
                
                    // SQL文を作成
                    $sql = 'UPDATE ec_cart 
                            SET amount = ?, update_datetime = ? 
                            WHERE board_id = ? AND user_id = ?';
                    // SQL文を実行する準備
                    $stmt = $dbh->prepare($sql);
                    // SQL文のプレースホルダに値をバインド
                    $stmt->bindValue(1, $update_cart_stock, PDO::PARAM_INT);
                    $stmt->bindValue(2, $log, PDO::PARAM_STR);
                    $stmt->bindValue(3, $board_id, PDO::PARAM_INT);
                    $stmt->bindValue(4, $user_id, PDO::PARAM_INT);
                    // SQLを実行
                    $stmt->execute();
                  
                    $success_msg[] = "数量を変更しました";
                
                }
                
            }
            
        }
    
    }
    
    if ($process_kind === 'delete_cart_stock') {
        
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            
            if (isset($_POST['user_id']) === TRUE) {
                $user_id = $_POST['user_id'];
            }
            
            if (isset($_POST['board_id']) === TRUE) {
                $board_id = $_POST['board_id'];
            }
        
            // SQL文を作成
            $sql = 'DELETE ec_cart
                    FROM ec_cart
                    WHERE user_id = ? AND board_id = ?';
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            // SQL文の?に値をバインド
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $board_id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            $success_msg[] = "カートから商品を削除しました";
            
        }
        
    }

    $sql = 'SELECT *
            FROM ec_item_master 
            INNER JOIN ec_cart
            ON ec_item_master.board_id = ec_cart.board_id
            WHERE user_id = ? AND board_status = 1';
    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $total_data = $stmt->fetchAll();


    $sql = 'SELECT id, board_price, amount
            FROM ec_item_master 
            INNER JOIN ec_cart
            ON ec_item_master.board_id = ec_cart.board_id
            WHERE user_id = ? AND board_status = 1';
    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $ids = $stmt->fetchAll();

    foreach ($ids as $value) {
        $id_total[] = $value['id'];
    }

    $max = count($id_total);
    $goukei = 0; 

    for ($i=0; $i<$max; $i++) {

        foreach ($ids as $i => $value) {
            
            $total = $value['board_price']* $value['amount'];
            
            $goukei = $goukei+$total;
            
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
        <title>ショッピングカートページ</title>
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
                                <a class="cart" href="index.php"><img class="cart_img" src="iconmonstr-home-thin.png"></a>
                            </div>
                            <div class="log_out">
                                <a class="cart" href="log_out.php"><img class="cart_img" src="log_out.png"></a>
                            </div>
                        </div>
                    </div>
            </div>
        </header>
        
        <name>
            <div class="name_position">
                <div calss="user_name">
                    <h1>ショッピングカート</h1>
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
            <div class="doctor_menu">
                <table>
                    <tr>
                        <th>商品写真</th>
                        <th>商品名</th>
                        <th>価格</th>
                        <th>数量</th>
                        <th></th>
                    </tr>
<?php foreach ($total_data as $value){ ?>
                        <div>
                            <tr>
                                <td><img class="furniture_img" src="<?php print $img_dir . $value['img']; ?>"></td>
                                <td><?php print htmlspecialchars ($value['board_name'], ENT_QUOTES); ?></td>
                                <td><?php print htmlspecialchars ($value['board_price'], ENT_QUOTES); ?>円</td>
                                <td>
                                    <form method="post">
                                        <input type="text" size=4 name="update_cart_stock" value="<?php print $value['amount'];?>">点
                                        <input type="hidden" name="user_id" value="<?php print $value['user_id'];?>">
                                        <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                                        <input type="hidden" name="process_kind" value="update_cart_stock">
                                        <input type="submit" value="変更">
                                    </form>
                                </td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="user_id" value="<?php print $value['user_id'];?>">
                                        <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                                        <input type="hidden" name="process_kind" value="delete_cart_stock">
                                        <input type="submit" value="削除">
                                    </form>
                                </td>
                            </tr>
                        </div>
<?php } ?>
                </table>
                <div>
                    <p>合計金額:<?php print $goukei; ?>円</p>
                </div>
                <form method="post" action="complete.php">
                    <div>
                        <input type="hidden" name="process_kind" value="purchase">
                        <input type="submit" value="購入">
                    </div>
                </form>
            </div>
        </menu>
        
        <footer>
            <p class="copy_rights"><small>Copyright&nbsp;CalmingSpaceRoom&nbsp;All&nbsp;Rights&nbsp;Reserved.</small></p>
        </footer>
    </body>
</html>