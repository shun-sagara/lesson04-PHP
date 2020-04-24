<?php
session_start();
require('dbconnect.php');

if (empty($_REQUEST['id'])) {
	header('Location: index.php'); exit();
}

$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
$posts->execute(array($_REQUEST['id']));
$post = $posts->fetch();

$members = $db->prepare('SELECT * FROM members WHERE id=?');
$members->execute(array($_SESSION['id']));
$member = $members->fetch();

//いいね機能

if (!empty($_POST)) {
	//リツイート後の投稿であるか
	$re_check = $db->prepare('SELECT * FROM posts WHERE id=?');
	$re_check->execute(array($_POST['good']));
	$re_p = $re_check->fetch();

	if ($_POST['good'] != '') {
		if ($re_p['re_post'] > 0) {
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

			//リツイートがある投稿なのか
			$repost_check = $db->prepare('SELECT * FROM posts WHERE re_post=?');
			$repost_check->execute(array($_POST['good']));
			$repost_p = $repost_check->fetch();

			$check = $db->prepare('SELECT COUNT(*) AS count FROM good WHERE good_user_id=? AND good_post_id=?');
			$check->execute(array($member['id'],$_POST['good']));
			$duplicate = $check->fetch();

			if ($duplicate['count'] > 0) {

				$good = $db->prepare('DELETE FROM good WHERE good_user_id=? AND good_post_id=?');
				$good->execute(array($member['id'],$_POST['good']));

				$points = $db->prepare('SELECT COUNT(*) AS sum FROM good WHERE good_post_id=?');
				$points->execute(array($_POST['good']));
				$point = $points->fetch();

				$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
				$good_counts->execute(array($point['sum'],$_POST['good']));

					if ($repost_p['re_post'] = $_POST['good']) {
						$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
						$good_counts->execute(array($point['sum'],$repost_p['id']));
					}

				header('Location: index.php'); exit();

			} else {

				$good = $db->prepare('INSERT INTO good SET good_user_id=?, good_post_id=?');
				$good->execute(array($member['id'],$_POST['good']));

				$points = $db->prepare('SELECT COUNT(*) AS sum FROM good WHERE good_post_id=?');
				$points->execute(array($_POST['good']));
				$point = $points->fetch();

				$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
				$good_counts->execute(array($point['sum'],$_POST['good']));

				if ($repost_p['re_post'] = $_POST['good']) {
					$good_counts = $db->prepare('UPDATE posts SET good_count=? where id=?');
					$good_counts->execute(array($point['sum'],$repost_p['id']));
				}

				header('Location: index.php'); exit();
			}
		}
	}
}


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

?>

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css">
</head>

<body>
	<div id="wrap">
	  <div id="head">
	    <h1>ひとこと掲示板</h1>
	  </div>
	  <div id="content">
			<p>&laquo;<a href="index.php">一覧にもどる</a></p>



			<div class="msg">
				<?php
				if ($post['re_post'] > 0): ?>
				<p><?php echo htmlspecialchars($post['name'], ENT_QUOTES); ?>さんがリツイートしました。</p>
				<?php endif; ?>
				<img src="member_picture/<?php echo htmlspecialchars($post['picture'], ENT_QUOTES); ?>" width="48" alt="<?php echo htmlspecialchars($post['name'], ENT_QUOTES); ?>" />
					<p><?php echo htmlspecialchars($post['message'], ENT_QUOTES); ?><span class="name">(<?php echo htmlspecialchars($post['name'], ENT_QUOTES); ?>)</span></p>
					<p class="day"><?php echo htmlspecialchars($post['created'], ENT_QUOTES);?></p>
					<p><a href="index.php?rep=<?php echo htmlspecialchars($post['id']); ?>"><img src="images/repost.png" weight="20" height="20"></a>
					</p>

					<p><?php echo htmlspecialchars($post['good_count'], ENT_QUOTES); ?></p>

					<form action="" method="post">
						<?php
						$check = $db->prepare('SELECT COUNT(*) AS count FROM good WHERE good_user_id=? AND good_post_id IN (?, ?)');
						$check->execute(array($member['id'],$post['id'],$post['re_post']));
						$duplicate= $check->fetch();
						if($duplicate['count'] > 0): ?>
								<input type="hidden" name="good" value="<?php echo htmlspecialchars($post['id']); ?>">
								<input type="image" src="images/good.png" weight="20" height="20">
							<?php else: ?>
								<input type="hidden" name="good" value="<?php echo htmlspecialchars($post['id']); ?>">
								<input type="image" src="images/normal.png" weight="20" height="20">
							<?php endif; ?>
					</form>
			</div>


		</div>
	</div>
</body>
</html>
