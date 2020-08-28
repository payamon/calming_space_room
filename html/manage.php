<?php
//日付を取得
$log = date('Y-m-d H:i:s');
$success_msg = [];
$errorss = [];
$board_id_data = [];
$board_data = [];

$process_kind = "";
$name = "";
$price = "";
$stock = "";
$status = "";
$option_status = "";

$host     = 'localhost';
$username = 'root';   // MySQLのユーザ名
$password = '99hhICxfi';       // MySQLのパスワード
$dbname   = 'calming_space_room';     // MySQLのDB名(このコースではMySQLのユーザ名と同じです）
$charset  = 'utf8';   // データベースの文字コード
 
// MySQL用のDSN文字列
$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;
 

// 画像ファイル関係コード
$img_dir    = './img/';    // アップロードした画像ファイルの保存ディレクトリ
$err_msg    = array();     // エラーメッセージ
$new_img_filename = '';   // アップロードした新しい画像ファイル名

try {
    // データベースに接続
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

if( isset($_POST['process_kind']) ) {
    $process_kind = $_POST['process_kind'];
}


if ($process_kind === 'insert_item') {
    
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    if (isset($_POST['name']) === TRUE) {
        $name = $_POST['name'];
    }
    if (isset($_POST['price']) === TRUE) {
        $price = $_POST['price'];
    }
    if (isset($_POST['stock']) === TRUE) {
        $stock = $_POST['stock'];
    }
    if (isset($_POST['status']) === TRUE) {
        $status = $_POST['status'];
    }
    if (isset($_POST['kind']) === TRUE) {
        $kind = $_POST['kind'];
    }
    
    
    if (mb_strlen($name) === 0) {
        $errorss[] = '名前が入力されていません';
    }
        
    if (mb_strlen($price) === 0) {
        $errorss[] = '値段が入力されていません';
    }
    
    if (!preg_match('/^[0-9]+$/', $price) && mb_strlen($price) !== 0){
        $errorss[] = '値段が整数ではありません';
    }
    
    if (mb_strlen($stock) === 0) {
        $errorss[] = '個数が入力されていません';
    }
    
    if (!preg_match('/^[0-9]+$/', $stock) && mb_strlen($stock) !== 0){
        $errorss[] = '個数が整数ではありません';
    }
    
    if (!preg_match('/^[0-1]*$/', $status)) {
        $errorss[] = 'ステータスが選ばれていません';
    }
    
    // HTTP POST でファイルがアップロードされたかどうかチェック
    if (is_uploaded_file($_FILES['new_img']['tmp_name']) === TRUE) {
        // 画像の拡張子を取得
        $extension = pathinfo($_FILES['new_img']['name'], PATHINFO_EXTENSION);
        // 指定の拡張子であるかどうかチェック
        if ($extension === 'jpeg' || $extension === 'JPEG' || $extension === 'jpg' || $extension === 'JPG' || $extension === 'png' || $extension === 'PNG') {
        // 保存する新しいファイル名の生成（ユニークな値を設定する）
            $new_img_filename = sha1(uniqid(mt_rand(), true)). '.' . $extension;
            // 同名ファイルが存在するかどうかチェック
            if (is_file($img_dir . $new_img_filename) !== TRUE) {
                // アップロードされたファイルを指定ディレクトリに移動して保存
                if (move_uploaded_file($_FILES['new_img']['tmp_name'], $img_dir . $new_img_filename) !== TRUE) {
                    $errorss[] = 'ファイルアップロードに失敗しました';
                }
            } else {
                $errorss[] = 'ファイルアップロードに失敗しました。再度お試しください。';
            }
        } else {
            $errorss[] = 'ファイル形式が異なります。画像ファイルはJPEGまたはPNGのみ利用可能です。';
        }
    } else {
        $errorss[] = 'ファイルを選択してください';
    }

    if (count($errorss) === 0) {
    
            // トランザクション開始
            $dbh->beginTransaction();
    
            try {
                //商品情報管理
                $board_name = $name;
                $board_price = $price;
                $board_stock = $stock;
                $board_status = $status;
                // SQL文を作成
                $sql = 'INSERT INTO ec_item_master (board_name, board_kind, board_price, img, board_status, create_datetime) 
                        VALUES(?, ?, ?, ?, ?, ?)';
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                // SQL文のプレースホルダに値をバインド
                $stmt->bindValue(1, $board_name, PDO::PARAM_STR);
                $stmt->bindValue(2, $kind, PDO::PARAM_INT);
                $stmt->bindValue(3, $board_price, PDO::PARAM_INT);
                $stmt->bindValue(4, $new_img_filename, PDO::PARAM_STR);
                $stmt->bindValue(5, $board_status, PDO::PARAM_INT);
                $stmt->bindValue(6, $log, PDO::PARAM_STR);
                // SQLを実行
                $stmt->execute();
                
                
                $sql = 'SELECT board_id
                        FROM ec_item_master 
                        WHERE img = ?';
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindValue(1, $new_img_filename, PDO::PARAM_STR);
                // SQLを実行
                $stmt->execute();
                // レコードの取得
                $board_id_data = $stmt->fetchAll();
                
                foreach ($board_id_data as $value) {
                    $board_id = $value['board_id'];
                }
      
                //在庫数管$board_stock
                // SQL文を作成
                $sql = 'INSERT INTO ec_item_stock (board_id, board_stock, create_datetime) 
                        VALUES(?, ?, ?)';
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                // SQL文のプレースホルダに値をバインド
                $stmt->bindValue(1, $board_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $board_stock, PDO::PARAM_INT);
                $stmt->bindValue(3, $log, PDO::PARAM_STR);
                 // SQLを実行
                $stmt->execute();
                
                $success_msg[] = "商品を追加しました";
          
                // コミット処理
                $dbh->commit();
            } catch (PDOException $e) {
            // ロールバック処理
            $dbh->rollback();
            // 例外をスロー
            throw $e;
            }
        }
}

}else if ($process_kind === 'update_kind') {
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    if (isset($_POST['kind']) === TRUE) {
        $kind = $_POST['kind'];
    }
    if (isset($_POST['board_id']) === TRUE) {
        $board_id = $_POST['board_id'];
    }
    
    //在庫数の変更
    // SQL文を作成
    $sql = 'UPDATE ec_item_master 
            SET board_kind=?, update_datetime=? 
            WHERE board_id = ?';
    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    // SQL文のプレースホルダに値をバインド
    $stmt->bindValue(1, $kind, PDO::PARAM_INT);
    $stmt->bindValue(2, $log, PDO::PARAM_STR);
    $stmt->bindValue(3, $board_id, PDO::PARAM_INT);
    // SQLを実行
    $stmt->execute();
      
    $success_msg[] = "種類変更完了";
      
}


}else if ($process_kind === 'update_stock') {
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    if (isset($_POST['update_stock']) === TRUE) {
        $update_stock = $_POST['update_stock'];
    }
    if (isset($_POST['board_id']) === TRUE) {
        $board_id = $_POST['board_id'];
    }
    
    if (mb_strlen($update_stock) === 0) {
        $errorss[] = '変更された個数が入力されていません';
    }
    if (!preg_match('/^[0-9]+$/', $update_stock) && mb_strlen($update_stock) !== 0){
        $errorss[] = '変更された個数が整数ではありません';
    }
    
    if(count($errorss) === 0){
    
      //在庫数の変更
      // SQL文を作成
      $sql = 'UPDATE ec_item_stock 
              SET board_stock=?, update_datetime=? 
              WHERE board_id = ?';
      // SQL文を実行する準備
      $stmt = $dbh->prepare($sql);
      // SQL文のプレースホルダに値をバインド
      $stmt->bindValue(1, $update_stock, PDO::PARAM_INT);
      $stmt->bindValue(2, $log, PDO::PARAM_STR);
      $stmt->bindValue(3, $board_id, PDO::PARAM_INT);
       // SQLを実行
      $stmt->execute();
      
      $success_msg[] = "在庫変更完了";
      
    }
}

}else if ($process_kind === 'change_status') {
    
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        
        if (isset($_POST['option_status']) === TRUE) {
            $option_status = $_POST['option_status'];
        }
        if (isset($_POST['board_id']) === TRUE) {
            $board_id = $_POST['board_id'];
        }
        
          //ステータスの変更
          // SQL文を作成
          $sql = 'UPDATE ec_item_master 
                  SET board_status=?, update_datetime=? 
                  WHERE board_id = ?';
          // SQL文を実行する準備
          $stmt = $dbh->prepare($sql);
          // SQL文の?に値をバインド
          $stmt->bindValue(1, $option_status, PDO::PARAM_INT);
          $stmt->bindValue(2, $log, PDO::PARAM_STR);
          $stmt->bindValue(3, $board_id, PDO::PARAM_INT);
           // SQLを実行
          $stmt->execute();
          
          $success_msg[] = "ステータス変更完了";
          
    }

}else if ($process_kind === 'delete_stock') {
    
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        if (isset($_POST['board_id']) === TRUE) {
            $board_id = $_POST['board_id'];
        }

        //ステータスの変更
        // SQL文を作成
        $sql = 'DELETE ec_item_master,ec_item_stock
                FROM ec_item_master 
                INNER JOIN ec_item_stock
                ON ec_item_master.board_id = ec_item_stock.board_id
                WHERE ec_item_master.board_id = ?';
        // SQL文を実行する準備
        $stmt = $dbh->prepare($sql);
        // SQL文の?に値をバインド
        $stmt->bindValue(1, $board_id, PDO::PARAM_INT);
        // SQLを実行
        $stmt->execute();
        
        $sql = 'DELETE ec_cart
                FROM ec_cart 
                WHERE board_id = ?';
        // SQL文を実行する準備
        $stmt = $dbh->prepare($sql);
        // SQL文の?に値をバインド
        $stmt->bindValue(1, $board_id, PDO::PARAM_INT);
        // SQLを実行
        $stmt->execute();
        
        $success_msg[] = "削除完了";
        
    }
}

} catch (PDOException $e) {
    // 接続失敗した場合
    $err_msg['db_connect'] = 'DBエラー：'.$e->getMessage();
}


// 商品情報管理と在庫数管理のテーブル結合SQL文を作成
$sql = 'SELECT * 
        FROM ec_item_master 
        INNER JOIN ec_item_stock 
        ON ec_item_master.board_id = ec_item_stock.board_id';
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
        <title>管理ページ</title>
        <link rel="stylesheet" href="manage.css">
    </head>
    
    <body>
        <h1>Calming&nbsp;Space&nbsp;Room：商品管理ページ</h1>
        
            <a href="patient.php">購入履歴</a>
        
<?php if (count($errorss) > 0) { ?>
        <ul>
    <?php foreach ($errorss as $error) { ?>
            <li><?php print $error; ?></li>
    <?php } ?>
        </ul>
<?php } ?>
        
        <ul>
<?php foreach ($success_msg as $success) { ?>
            <li><?php print $success; ?></li>
<?php } ?>
        </ul>
        
        <h2>新規商品追加</h2>
        <form method="post" enctype="multipart/form-data">
            <div>名前:
                <input type="text" size=20 name="name">
            </div>
            <div>種類:
              <select name="kind">
                <option value="0">ソファー</option>
                <option value="1">ベッド</option>
                <option value="2">椅子</option>
                <option value="3">カーペット</option>
              </select>
            </div>
            <div>値段:
                <input type="text" size=20 name="price">
            </div>
            <div>個数:
                <input type="text" size=20 name="stock">
            </div>
            <div>
                <input type="file" name="new_img">
            </div>
            <div>
              <select name="status">
                <option value="0">非公開</option>
                <option value="1">公開</option>
              </select>
            </div>
            <div>
            <input type="hidden" name="process_kind" value="insert_item">
            <input type="submit" name="submit" value="商品の登録">
            </div>
        </form>
        
        <h2>商品情報変更</h2>
        <p>商品一覧</p>
        
        <menu>
            <div class="doctor_menu">
                <table>
                  <tr>
                    <th>商品画像</th>
                    <th>商品名</th>
                    <th>種類</th>
                    <th>価格</th>
                    <th>在庫数</th>
                    <th>ステータス</th>
                  </tr>
                  
<?php foreach($board_data as $value){ ?>
                    <tr>
                      <td><img class="furniture_img" src="<?php print $img_dir . $value['img'];?>"></td>
                      <td><?php print htmlspecialchars ($value['board_name'], ENT_QUOTES); ?></td>
                      <td>
    <?php if(($value['board_kind']) === 0){ ?>  
                        <form method="post">
                            <select name="kind">
                                <option value="<?php print $value['board_kind'];?>">ソファー</option>
                                <option value="1">ベッド</option>
                                <option value="2">椅子</option>
                                <option value="3">カーペット</option>
                              </select>
                            <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                            <input type="hidden" name="process_kind" value="update_kind">
                            <input type="submit" value="変更">
                        </form>
    <?php }else if (($value['board_kind']) === 1){ ?>
                        <form method="post">
                            <select name="kind">
                                <option value="<?php print $value['board_kind'];?>">ベッド</option>
                                <option value="0">ソファー</option>
                                <option value="2">椅子</option>
                                <option value="3">カーペット</option>
                              </select>
                            <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                            <input type="hidden" name="process_kind" value="update_kind">
                            <input type="submit" value="変更">
                        </form>
    <?php }else if (($value['board_kind']) === 2){ ?>
                        <form method="post">
                            <select name="kind">
                                <option value="<?php print $value['board_kind'];?>">椅子</option>
                                <option value="0">ソファー</option>
                                <option value="1">ベッド</option>
                                <option value="3">カーペット</option>
                              </select>
                            <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                            <input type="hidden" name="process_kind" value="update_kind">
                            <input type="submit" value="変更">
                        </form>
    <?php }else if (($value['board_kind']) === 3){ ?>
                        <form method="post">
                            <select name="kind">
                                <option value="<?php print $value['board_kind'];?>">カーペット</option>
                                <option value="0">ソファー</option>
                                <option value="1">ベッド</option>
                                <option value="2">椅子</option>
                              </select>
                            <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                            <input type="hidden" name="process_kind" value="update_kind">
                            <input type="submit" value="変更">
                        </form>
    <?php } ?>
                      </td>
                      <td><?php print htmlspecialchars ($value['board_price'], ENT_QUOTES); ?>円</td>
                      
                      <td>
                        <form method="post">
                        <input type="text" size=4 name="update_stock" value="<?php print $value['board_stock'];?>">個
                        <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                        <input type="hidden" name="process_kind" value="update_stock">
                        <input type="submit" value="変更">
                        </form>
                      </td>
        
                      <td>
                        <form method="post">
    <?php if(($value['board_status']) === 0){ ?>
                            <input type="submit" name="privacy_status" value="非公開->公開">
                            <input type="hidden" name="option_status" value="1">
                            <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                            <input type="hidden" name="process_kind" value="change_status">
    <?php }else{ ?>
                            <input type="submit" name="privacy_status" value="公開->非公開">
                            <input type="hidden" name="option_status" value="0">
                            <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                            <input type="hidden" name="process_kind" value="change_status">
    <?php } ?>
                        </form>
                      </td>
                      
                      <td>
                        <form method="post">
                        <input type="hidden" name="board_id" value="<?php print $value['board_id'];?>">
                        <input type="hidden" name="process_kind" value="delete_stock">
                        <input type="submit" value="削除">
                        </form>
                      </td>
                      
                    </tr>
<?php } ?>
                </table>
            </div>
        </menu>
    </body>
</html>