<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Mission5-4</title>
</head>
<body>

<?php
// --- データベース接続設定 ---
$dsn = 'mysql:dbname=XXXXXXXXdb;host=localhost';
$user = 'XXXXXXXXX';
$password_db = 'XXXXXXXXXX';
$pdo = new PDO($dsn, $user, $password_db, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

// --- テーブル作成 ---
$sql = "CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32),
    comment TEXT,
    post_date DATETIME,
    password VARCHAR(255)
)";
$pdo->query($sql);

// --- 変数初期化 ---
$edit_id = '';
$edit_name = '';
$edit_comment = '';
$error_message = '';
$success_message = '';

// --- POST処理 ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 編集フォーム 選択
    if (isset($_POST["submit_edit_select"])) {
        $edit_select_id = intval($_POST["edit_select_id"] ?? 0);
        $password = trim($_POST["password"] ?? '');

        if ($edit_select_id > 0 && !empty($password)) {
            $sql = "SELECT * FROM posts WHERE id = :id AND password = :password";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $edit_select_id, PDO::PARAM_INT);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();
            $post_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($post_to_edit) {
                $edit_id = $post_to_edit['id'];
                $edit_name = $post_to_edit['name'];
                $edit_comment = $post_to_edit['comment'];
                $success_message = "投稿を編集します。";
            } else {
                $error_message = "投稿番号またはパスワードが違います。";
            }
        } else {
            $error_message = "編集番号とパスワードを入力してください。";
        }
    }

    // 投稿・編集
    if (isset($_POST["submit_post"])) {
        $name = trim($_POST["name"] ?? '');
        $comment = trim($_POST["comment"] ?? '');
        $password = trim($_POST["password"] ?? '');
        $editing_id = intval($_POST["edit_id"] ?? 0);

        if (!empty($name) && !empty($comment)) {
            //posts というテーブルの name カラムと comment カラムに:name と :comment という プレースホルダー（後で値が入る場所） にそれぞれ値を入れて
            //新しいレコード（行）を追加（挿入）する命令。
            if ($editing_id > 0) {
                $sql = "UPDATE posts SET name = :name, comment = :comment, post_date = NOW() WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                 /* $stmt = $pdo->prepare($sql);について
                SQL文をデータベースに送る前に「準備」する処理。
                プレースホルダー（:nameや:comment）を使うことで、SQLインジェクション攻撃を防ぐ。
                prepare() は「SQL文のひな形」をデータベースに渡し、実行の準備をして $stmt に結果を返します。
                */
                $stmt->bindParam(':name', $name);//QL文中のプレースホルダーに、実際の値を「バインド（結びつけ）」する。:name に $name を入れる
                $stmt->bindParam(':comment', $comment);//こうすることで値の型やエスケープ処理も自動で安全に行われます。:comment に $comment を入れる
                $stmt->bindParam(':id', $editing_id);
                $stmt->execute();//準備済みのSQLを実際にデータベースに送って実行.
                $success_message = "投稿を編集しました。";
                $edit_id = $edit_name = $edit_comment = '';
            } else {
                if (!empty($password)) {
                    $sql = "INSERT INTO posts (name, comment, post_date, password) VALUES (:name, :comment, NOW(), :password)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':comment', $comment);
                    $stmt->bindParam(':password', $password);
                    $stmt->execute();
                    $success_message = "投稿を受け付けました。";
                } else {
                    $error_message = "パスワードを入力してください。";
                }
            }
        } else {
            $error_message = "名前とコメントを両方入力してください。";
        }
    }

    // 削除
    if (isset($_POST["submit_delete"])) {
        $delete_id = intval($_POST["delete_id"] ?? 0);
        $password = trim($_POST["password"] ?? '');

        if ($delete_id > 0 && !empty($password)) {
            $sql_check = "SELECT id FROM posts WHERE id = :id AND password = :password";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':id', $delete_id);
            $stmt_check->bindParam(':password', $password);
            $stmt_check->execute();

            if ($stmt_check->fetch()) {
                $sql_delete = "DELETE FROM posts WHERE id = :id";
                $stmt_delete = $pdo->prepare($sql_delete);
                $stmt_delete->bindParam(':id', $delete_id);
                $stmt_delete->execute();
                $success_message = "投稿を削除しました。";
            } else {
                $error_message = "投稿番号またはパスワードが違います。";
            }
        } else {
            $error_message = "削除番号とパスワードを入力してください。";
        }
    }
}
?>

<h1>簡易掲示板</h1>

<?php
if (!empty($error_message)) echo "<p style='color:red;'>{$error_message}</p>";
if (!empty($success_message)) echo "<p style='color:green;'>{$success_message}</p>";
?>

<!-- 投稿フォーム -->
<form method="post">
    <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($edit_id); ?>">
    <strong>【投稿フォーム】</strong> <?php if($edit_id) echo "（編集中）"; ?><br>
    名前　　：<input type="text" name="name" value="<?php echo htmlspecialchars($edit_name); ?>" required><br>
    コメント：<input type="text" name="comment" value="<?php echo htmlspecialchars($edit_comment); ?>" required><br>
    ﾊﾟｽﾜｰﾄﾞ ：<input type="password" name="password" <?php if($edit_id) echo 'placeholder="不要"' ?>><br>
    <input type="submit" name="submit_post" value="送信">
</form><br>

<!-- 削除フォーム -->
<form method="post">
    <strong>【削除フォーム】</strong><br>
    削除番号：<input type="number" name="delete_id"><br>
    ﾊﾟｽﾜｰﾄﾞ ：<input type="password" name="password"><br>
    <input type="submit" name="submit_delete" value="削除">
</form><br>

<!-- 編集フォーム -->
<form method="post">
    <strong>【編集フォーム】</strong><br>
    編集番号：<input type="number" name="edit_select_id"><br>
    ﾊﾟｽﾜｰﾄﾞ ：<input type="password" name="password"><br>
    <input type="submit" name="submit_edit_select" value="編集">
</form>

<hr>
<h2>投稿一覧</h2>

<?php
$sql = "SELECT * FROM posts ORDER BY id ASC";
$stmt = $pdo->query($sql);//$pdo->query($sql) は、このSQLをデータベースに送り実行し、その結果を管理するステートメントオブジェクト（$stmt）を取得
$posts = $stmt->fetchAll();// クエリ結果の全行を配列として取得
if ($posts) {// 送信postで実行が成功しているかチェック
    foreach ($posts as $post) {
        echo "<div style='border-bottom:1px dashed #ccc; margin-bottom:10px;'>";
        echo "<p><strong>{$post['id']}：{$post['name']}</strong><br>";
        echo nl2br(htmlspecialchars($post['comment'])) . "<br>";
        echo "<small>投稿日時：{$post['post_date']}</small></p>";
        echo "</div>";
    }
} else {
    echo "<p>まだ投稿がありません。</p>";
}
?>
</body>
</html>

<!--
$stmt は 「ステートメントオブジェクト」 の略で、PDO（PHP Data Objects）がSQLの実行準備や結果を管理するためのオブジェクト
$stmt は 「ステートメントオブジェクト」または false のいずれかになります。つまり、SQLの実行に成功すればオブジェクト、失敗すれば false 

$sql = "INSERT INTO posts (name, comment) VALUES (:name, :comment)";
$stmt = $pdo->prepare($sql);               // ←① SQLテンプレートを準備
$stmt->bindParam(':name', $name);          // ←② プレースホルダーに値を入れる
$stmt->bindParam(':comment', $comment);
$stmt->execute();                          // ←③ 実行（データベースに送信）
$stmt は、SQLを 「安全に実行するための中間処理オブジェクト」
この仕組みを プリペアドステートメント（prepared statement）
SQLインジェクション（不正アクセス）の対策にもなる


