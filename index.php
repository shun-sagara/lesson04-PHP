<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
	// ログインしている
	$_SESSION['time'] = time();

	$members = $db->prepare('SELECT * FROM members WHERE id=?');
	$members->execute(array($_SESSION['id']));
	$member = $members->fetch();
} else {
	// ログインしていない
	header('Location: login.php'); exit();
}

// 投稿を記録する
$message="";
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
		if (isset($_REQUEST['res'])) {
		$message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?, created=NOW()');
		$message->execute(array(
			$member['id'],
			$_POST['message'],
			$_POST['reply_post_id']
		));

		header('Location: index.php'); exit();
	} else {
		$message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, created=NOW()');
		$message->execute(array(
			$member['id'],
			$_POST['message']
		));

		header('Location: index.php'); exit();
		}
	}
}

// 投稿を取得する
if (isset($_REQUEST['page'])) {
$page = $_REQUEST['page'];
if ($page == '') {
	$page = 1;
}
$page = max($page, 1);
} else {
 $page= 1;
}


// 最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0, $start);

$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT ?, 5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();

// 返信の場合
if (isset($_REQUEST['res'])) {
	$response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
	$response->execute(array($_REQUEST['res']));

	$table = $response->fetch();
	$message = '@' . $table['name'] . ' ' . $table['message'];
}
//いいね機能

if (!empty($_POST)) {
	//リツイート後の投稿であるか
	$re_check = $db->prepare('SELECT * FROM posts WHERE id=?');
	$re_check->execute(array($_POST['good']));
	$re_p = $re_check->fetch();

	if ($_POST['good'] != '') {
		if($re_p['re_post'] > 0) {
			$check = $db->prepare('SELECT COUNT(*) AS count FROM good WHERE good_user_id=? AND good_post_id=?');
			$check->execute(array($member['id'],$re_p['re_post']));
			$duplicate = $check->fetch();

			if ($duplicate['count'] > 0) {

				$good = $db->prepare('DELETE FROM good WHERE good_user_id=? AND good_post_id=?');
				$good->execute(array($member['id'],$re_p['re_post']));

				$points = $db->prepare('SELECT COUNT(*) AS sum FROM good WHERE good_post_id=?');
				$points->execute(array($re_p['re_post']));
				$point = $points->fetch();

				$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
				$good_counts->execute(array($point['sum'],$re_p['re_post']));

				$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
				$good_counts->execute(array($point['sum'],$_POST['good']));

				header('Location: index.php'); exit();

			} else {

				$good = $db->prepare('INSERT INTO good SET good_user_id=?, good_post_id=?');
				$good->execute(array($member['id'],$re_p['re_post']));

				$points = $db->prepare('SELECT COUNT(*) AS sum FROM good WHERE good_post_id=?');
				$points->execute(array($re_p['re_post']));
				$point = $points->fetch();

				$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
				$good_counts->execute(array($point['sum'],$re_p['re_post']));

				$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
				$good_counts->execute(array($point['sum'],$_POST['good']));

				header('Location: index.php'); exit();

			}
		} else {

			$check = $db->prepare('SELECT COUNT(*) AS count FROM good WHERE good_user_id=? AND good_post_id=?');
			$check->execute(array($member['id'],$_POST['good']));
			$duplicate = $check->fetch();　　

			if($duplicate['count'] > 0) {

				$good = $db->prepare('DELETE FROM good WHERE good_user_id=? AND good_post_id=?');
				$good->execute(array($member['id'],$_POST['good']));

				$points = $db->prepare('SELECT COUNT(*) AS sum FROM good WHERE good_post_id=?');
				$points->execute(array($_POST['good']));
				$point = $points->fetch();

				$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
				$good_counts->execute(array($point['sum'],$_POST['good']));

				header('Location: index.php'); exit();

			} else {

				$good = $db->prepare('INSERT INTO good SET good_user_id=?, good_post_id=?');
				$good->execute(array($member['id'],$_POST['good']));

				$points = $db->prepare('SELECT COUNT(*) AS sum FROM good WHERE good_post_id=?');
				$points->execute(array($_POST['good']));
				$point = $points->fetch();

				$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
				$good_counts->execute(array($point['sum'],$_POST['good']));

				header('Location: index.php'); exit();
			}
		}
	}
}
//いいね機能終わり

//リツイート初め
if (isset($_REQUEST['rep'])) {
	$repost = $db->prepare('SELECT * FROM posts WHERE id=?');
	$repost->execute(array($_REQUEST['rep']));
	$rep = $repost->fetch();

		if ($rep['re_post'] > 0){
			$remessage = $db->prepare('DELETE FROM posts WHERE id=?');
			$remessage->execute(array($rep['id']));

			header('Location: index.php'); exit();

		}	else {
			$remessage = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?,re_post=?,good_count=?,created=NOW()');
			$remessage->execute(array(
				$member['id'],
				$rep['message'],
				$rep['reply_post_id'],
				$_REQUEST['rep'],
				$rep['good_count']
		));

			header('Location: index.php'); exit();
		}
	}

// htmlspecialcharsのショートカット
function h($value) {
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定します
function makeLink($value) {
	return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>' , $value);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
</head>

<body>
	<div id="wrap">
	  <div id="head">
	    <h1>ひとこと掲示板</h1>
	  </div>
	  <div id="content">
			<div style="text-align: right"><a href="logout.php">ログアウト</a></div>
			<form action="" method="post">
				<dl>
					<dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
	        <dd>
	          <textarea name="message" cols="50" rows="5"><?php echo h($message); ?></textarea>
						<?php if(isset($_REQUEST['res'])):?>
	          	<input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
						<?php endif; ?>
	        </dd>
	      </dl>
	      <div>
	        <p>
	          <input type="submit" value="投稿する" />
	        </p>
	      </div>
	    </form>

	<?php
	foreach($posts as $post):
	?>
			<div class="msg">
				<?php
				if ($post['re_post'] > 0): ?>
				<p><?php echo h($post['name']); ?>さんがリツイートしました。</p>
				<?php endif; ?>
				<img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" />
					<p><?php echo makeLink(h($post['message']));?>
						<span class="name">（<?php echo h($post['name']); ?>）</span>
						[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]
						<a href="index.php?rep=<?php echo h($post['id']); ?>"><img src="images/repost.png" weight="20" height="20"></a>
					</p>
					<p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
					<?php if ($post['reply_post_id'] > 0): ?>
					<a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
					<?php endif; ?>
					<?php if ($_SESSION['id'] == $post['member_id']): ?>
					[<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color:#F33;">削除</a>]
					<?php endif; ?>
					</p>

					<p><?php echo h($post['good_count']); ?></p>

					<form action="" method="post">
						<?php
						$check = $db->prepare('SELECT COUNT(*) AS count FROM good WHERE good_user_id=? AND good_post_id IN (?, ?)');
						$check->execute(array($member['id'],$post['id'],$post['re_post']));
						$duplicate= $check->fetch();
						if($duplicate['count'] > 0): ?>
								<input type="hidden" name="good" value="<?php echo h($post['id']); ?>">
								<input type="image" src="images/good.png" weight="20" height="20">
							<?php else: ?>
								<input type="hidden" name="good" value="<?php echo h($post['id']); ?>">
								<input type="image" src="images/normal.png" weight="20" height="20">
							<?php endif; ?>
					</form>

			</div>

			<?php
			endforeach;
			?>

			<ul class="paging">
			<?php
			if ($page > 1) {
			?>
			<li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
			<?php
			} else {
			?>
			<li>前のページへ</li>
			<?php
			}
			?>
			<?php
			if ($page < $maxPage) {
			?>
			<li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
			<?php
			} else {
			?>
			<li>次のページへ</li>
			<?php
			}
			?>
			</ul>
	  </div>
	</div>
</body>
</html>
