<?php
include '../components/connect.php';

if(isset($_COOKIE['tutor_id'])){
   $tutor_id = $_COOKIE['tutor_id'];
}else{
   $tutor_id = '';
   header('location:login.php');
}

if(isset($_GET['get_id'])){
   $get_id = $_GET['get_id'];
}else{
   $get_id = '';
   header('location:contents.php');
}

if(isset($_POST['delete_video'])){
   $delete_id = $_POST['video_id'];
   $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);

   $select = $conn->prepare("SELECT * FROM `content` WHERE id = ? LIMIT 1");
   $select->execute([$delete_id]);
   $fetch = $select->fetch(PDO::FETCH_ASSOC);

   if($fetch['video_type'] != 'youtube'){
      unlink('../uploaded_files/'.$fetch['video']);
   }
   unlink('../uploaded_files/'.$fetch['thumb']);

   $conn->prepare("DELETE FROM `likes` WHERE content_id = ?")->execute([$delete_id]);
   $conn->prepare("DELETE FROM `comments` WHERE content_id = ?")->execute([$delete_id]);
   $conn->prepare("DELETE FROM `content` WHERE id = ?")->execute([$delete_id]);

   header('location:contents.php');
}

if(isset($_POST['delete_comment'])){
   $delete_id = $_POST['comment_id'];
   $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);

   $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE id = ?");
   $verify_comment->execute([$delete_id]);

   if($verify_comment->rowCount() > 0){
      $conn->prepare("DELETE FROM `comments` WHERE id = ?")->execute([$delete_id]);
      $message[] = 'Comment deleted successfully!';
   }else{
      $message[] = 'Comment already deleted!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Content</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="admin_style.css">
</head>
<body>  

<?php include '../components/admin_header.php'; ?>

<section class="view-content">
<?php
$select_content = $conn->prepare("SELECT * FROM `content` WHERE id = ? AND tutor_id = ?");
$select_content->execute([$get_id, $tutor_id]);

if($select_content->rowCount() > 0){
   while($fetch_content = $select_content->fetch(PDO::FETCH_ASSOC)){
      $video_id = $fetch_content['id'];

      $count_likes = $conn->prepare("SELECT * FROM `likes` WHERE tutor_id = ? AND content_id = ?");
      $count_likes->execute([$tutor_id, $video_id]);
      $total_likes = $count_likes->rowCount();

      $count_comments = $conn->prepare("SELECT * FROM `comments` WHERE tutor_id = ? AND content_id = ?");
      $count_comments->execute([$tutor_id, $video_id]);
      $total_comments = $count_comments->rowCount();
?>

<div class="container">
   <?php
      if($fetch_content['video_type'] == 'youtube'){
         echo '<iframe width="100%" height="400" src="'.$fetch_content['video'].'" frameborder="0" allowfullscreen></iframe>';
      } else {
         echo '<video src="../uploaded_files/'.$fetch_content['video'].'" autoplay controls poster="../uploaded_files/'.$fetch_content['thumb'].'" class="video"></video>';
      }
   ?>
   <div class="date"><i class="fas fa-calendar"></i><span><?= $fetch_content['date']; ?></span></div>
   <h3 class="title"><?= $fetch_content['title']; ?></h3>
   <div class="flex">
      <div><i class="fas fa-heart"></i><span><?= $total_likes; ?></span></div>
      <div><i class="fas fa-comment"></i><span><?= $total_comments; ?></span></div>
   </div>
   <div class="description"><?= $fetch_content['description']; ?></div>
   <form action="" method="post">
      <div class="flex-btn">
         <input type="hidden" name="video_id" value="<?= $video_id; ?>">
         <a href="update_content.php?get_id=<?= $video_id; ?>" class="option-btn">Update</a>
         <input type="submit" value="Delete" class="delete-btn" onclick="return confirm('Delete this video?');" name="delete_video">
      </div>
   </form>
</div>

<?php
   }
} else {
   echo '<p class="empty">No content found! <a href="add_content.php" class="btn" style="margin-top: 1.5rem;">Add Video</a></p>';
}
?>
</section>

<section class="comments">
   <h1 class="heading">User Comments</h1>
   <div class="show-comments">
<?php
$select_comments = $conn->prepare("SELECT * FROM `comments` WHERE content_id = ?");
$select_comments->execute([$get_id]);

if($select_comments->rowCount() > 0){
   while($fetch_comment = $select_comments->fetch(PDO::FETCH_ASSOC)){
      $select_commentor = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
      $select_commentor->execute([$fetch_comment['user_id']]);
      $fetch_commentor = $select_commentor->fetch(PDO::FETCH_ASSOC);
?>
      <div class="box">
         <div class="user">
            <img src="../uploaded_files/<?= $fetch_commentor['image']; ?>" alt="">
            <div>
               <h3><?= $fetch_commentor['name']; ?></h3>
               <span><?= $fetch_comment['date']; ?></span>
            </div>
         </div>
         <p class="text"><?= $fetch_comment['comment']; ?></p>
         <form action="" method="post" class="flex-btn">
            <input type="hidden" name="comment_id" value="<?= $fetch_comment['id']; ?>">
            <button type="submit" name="delete_comment" class="inline-delete-btn" onclick="return confirm('Delete this comment?');">Delete Comment</button>
         </form>
      </div>
<?php
   }
} else {
   echo '<p class="empty">No comments added yet!</p>';
}
?>
   </div>
</section>

<script src="admin_script.js"></script>
</body>
</html>
