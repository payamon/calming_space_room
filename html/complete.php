<?php
//日付を取得
$log = date('Y-m-d H:i:s');

$err_msg    = [];
$success_msg = [];
$errorss = [];
$total_data = [];
$data = [];
$cart_id_total = [];
$ids = [];
$id_total = [];
$user_name = [];

$process_kind = '';

$img_dir    = './img/';

$host     = 'localhost';
$username = 'root';   // MySQLのユーザ名
$password = '99hhICxfi';       // MySQLのパスワード
$dbname   = 'calming_space_room';   // MySQLのDB名(今回、MySQLのユーザ名を入力してください)
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
    
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
        if( isset($_POST['process_kind']) ) {
            $process_kind = $_POST['process_kind'];
        }
        
        }
        
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
        
        if ($process_kind === 'purchase') {
            
            // トランザクション開始
            $dbh->beginTransaction();
    
            try {
                
                $sql = 'SELECT *
                        FROM ec_item_master 
                        INNER JOIN ec_item_stock
                        ON ec_item_master.board_id = ec_item_stock.board_id
                        INNER JOIN ec_cart
                        ON ec_item_stock.board_id = ec_cart.board_id
                        WHERE user_id = ? AND board_status = 1';
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
                // SQLを実行
                $stmt->execute();
                // レコードの取得
                $total_data = $stmt->fetchAll();
                
                foreach ($total_data as $value) {
                    $cart_id_total[] = $value['id'];
                }
                
                $max = count($cart_id_total);
                    
                for ($i=0; $i<$max; $i++) {
        
                    foreach ($total_data as $i => $value) {
                        
                        $board_stock = $value['board_stock']-$value['amount'];  
                        // SQL文を作成
                        $sql = 'UPDATE ec_item_stock 
                                SET board_stock = ?, update_datetime = ? 
                                WHERE board_id = ?';
                        // SQL文を実行する準備
                        $stmt = $dbh->prepare($sql);
                        // SQL文のプレースホルダに値をバインド
                        $stmt->bindValue(1, $board_stock, PDO::PARAM_INT);
                        $stmt->bindValue(2, $log, PDO::PARAM_STR);
                        $stmt->bindValue(3, $value['board_id'], PDO::PARAM_INT);
                        // SQLを実行
                        $stmt->execute();
                        
                        // SQL文を作成
                        $sql = 'SELECT user_name 
                                FROM ec_user 
                                WHERE user_id = ?';
                        // SQL文を実行する準備
                        $stmt = $dbh->prepare($sql);
                        // SQL文のプレースホルダに値をバインド
                        $stmt->bindValue(1, $value['user_id'], PDO::PARAM_INT);
                        // SQLを実行
                        $stmt->execute();
                        $user_name = $stmt->fetchAll();
                        
                        foreach ($user_name as $user) {
                            
                            $sql = 'INSERT INTO ec_user_buy(user_id, user_name, board_id, amount, create_datetime)
                                    VALUES(?, ?, ?, ?, ?)';
                            // SQL文を実行する準備
                            $stmt = $dbh->prepare($sql);
                            $stmt->bindValue(1, $value['user_id'], PDO::PARAM_INT);
                            $stmt->bindValue(2, $user['user_name'], PDO::PARAM_STR);
                            $stmt->bindValue(3, $value['board_id'], PDO::PARAM_INT);
                            $stmt->bindValue(4, $value['amount'], PDO::PARAM_INT);
                            $stmt->bindValue(5, $log, PDO::PARAM_STR);
                            // SQLを実行
                            $stmt->execute();
                        
                            
                        }
                        
                    }
                          
                }
            
                $sql = 'DELETE ec_item_master,ec_item_stock
                        FROM ec_item_master 
                        INNER JOIN ec_item_stock
                        ON ec_item_master.board_id = ec_item_stock.board_id
                        WHERE board_stock = 0';
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                // SQLを実行
                $stmt->execute();
                
                $sql = 'DELETE ec_cart
                        FROM ec_cart 
                        WHERE user_id = ?';
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
                // SQLを実行
                $stmt->execute();
            
                // コミット処理
                $dbh->commit();
            } catch (PDOException $e) {
            // ロールバック処理
            $dbh->rollback();
            // 例外をスロー
            throw $e;
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
        <title>購入結果ページ</title>
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
                    <h1>購入いただきありがとうございます</h1>
                </div>
            </div>
        </name>
        
        <menu>
            <div class="doctor_menu">
                <table>
                    <tr>
                        <th>商品写真</th>
                        <th>商品名</th>
                        <th>価格</th>
                        <th>数量</th>
                    </tr>
<?php foreach ($total_data as $value){ ?>
                        <div>
                            <tr>
                                <td><img class="furniture_img" src="<?php print $img_dir . $value['img']; ?>"></td>
                                <td><?php print htmlspecialchars ($value['board_name'], ENT_QUOTES); ?></td>
                                <td><?php print htmlspecialchars ($value['board_price'], ENT_QUOTES); ?>円</td>
                                <td>
                                    <?php print $value['amount'];?>点
                                </td>
                            </tr>
                        </div>
<?php } ?>
                </table>
                <div>
                    <p>合計金額:<?php print $goukei; ?>円</p>
                </div>
            </div>
        </menu>
        
        <footer>
            <p class="copy_rights"><small>Copyright&nbsp;CalmingSpaceRoom&nbsp;All&nbsp;Rights&nbsp;Reserved.</small></p>
        </footer>
    </body>
</html>