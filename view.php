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

if (isset($_REQUEST['rep'])) {
	$repost = $db->prepare('SELECT * FROM posts WHERE id=?');
	$repost->execute(array($_REQUEST['rep']));
	$rep = $repost->fetch();

	if ($rep['re_post'] == 1){
		$remessage = $db->prepare('DELETE FROM posts WHERE id=?');
		$remessage->execute(array($rep['id']));

		header('Location: index.php'); exit();

	}	else {
		$remessage = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?,re_post=?,created=NOW()');
		$remessage->execute(array(
			$member['id'],
			$rep['message'],
			$rep['reply_post_id'],
			1
	));

	header('Location: index.php'); exit();
	}
}

if (!empty($_POST)) {
	//いいね重複検査
	$check = $db->prepare('SELECT COUNT(*) AS count FROM good WHERE good_user_id=? AND good_post_id=?');
	$check->execute(array($member['id'],$_POST['good']));
	$duplicate= $check->fetch();

	if ($_POST['good'] != '') {
	  if($duplicate['count'] > 0) {
			$good = $db->prepare('DELETE FROM good WHERE good_user_id=? AND good_post_id=?');
			$good->execute(array($member['id'],$_POST['good']));

			header('Location: index.php'); exit();
		} else {
			$good = $db->prepare('INSERT INTO good SET good_user_id=?, good_post_id=?');
			$good->execute(array($member['id'],$_POST['good']));

			header('Location: index.php'); exit();
		}
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

				<img src="member_picture/<?php echo htmlspecialchars($post['picture'], ENT_QUOTES); ?>" width="48" alt="<?php echo htmlspecialchars($post['name'], ENT_QUOTES); ?>" />
					<p><?php echo htmlspecialchars($post['message'], ENT_QUOTES); ?><span class="name">(<?php echo htmlspecialchars($post['name'], ENT_QUOTES); ?>)</span></p>
					<p class="day"><?php echo htmlspecialchars($post['created'], ENT_QUOTES);?></p>
					<p><a href="index.php?rep=<?php echo htmlspecialchars($post['id']); ?>"><img src="images/repost.png" weight="20" height="20"></a>
					</p>

					<p><?php
					$points = $db->prepare('SELECT COUNT(*) AS sum FROM good WHERE good_post_id=?');
					$points->execute(array($post['id']));
					$point= $points->fetch();
							echo htmlspecialchars($point['sum'], ENT_QUOTES); ?></p>

					<form action="" method="post">
						<?php
							$check = $db->prepare('SELECT COUNT(*) AS count FROM good WHERE good_user_id=? AND good_post_id=?');
							$check->execute(array($member['id'],$post['id']));
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
