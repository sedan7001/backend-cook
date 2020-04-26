<div id="logo"><a href="../index.php"><img src="../img/logo.png" border="0"></a>
</div>
<div id="moto"><img src="../img/moto.png"></div>
<div id="top_login">

    <?php
    if (!$userid) {
        echo "<a href='../login/login_form.php'>로그인</a>|";
        echo "<a href='../member/member_form.php'>회원가입</a>";
    } else {
        if($userid=="admin"){
            echo "<a href='../admin/admin.php'>관리자페이지</a>|";
        }
        echo $usernick."(level:".$userlevel.")|";

        echo "<a href='../login/logout.php'>로그아웃</a>|";
        echo "<a href='../login/member_form_modify.php'>정보수정</a>";
    }
    ?>
</div>