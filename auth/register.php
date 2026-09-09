<?php

include "../config/database.php";


$username = $_POST['username'];
$email    = $_POST['email'];
$password = $_POST['password'];
$referral = $_POST['referral'] ?? "";


$hashPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
"INSERT INTO users 
(username,email,password,referral)
VALUES (?,?,?,?)"
);

$stmt->bind_param(
"ssss",
$username,
$email,
$hashPassword,
$referral
);

if($stmt->execute()){

echo '
<!DOCTYPE html>
<html>
<head>

<title>Register Success</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Poppins,Arial,sans-serif;
}


body{

    height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;

    background:
    radial-gradient(circle at top,#3a2500,#080808 60%);

    overflow:hidden;

    color:white;

}


/* PARTICLE */

.particles{

    position:fixed;
    inset:0;

    overflow:hidden;

}


.particles span{

    position:absolute;

    width:8px;
    height:8px;

    background:#FFD700;

    border-radius:50%;

    box-shadow:
    0 0 20px #FFD700;

    animation:goldFall 5s linear infinite;

}


.particles span:nth-child(1){left:10%;animation-delay:0s}
.particles span:nth-child(2){left:25%;animation-delay:1s}
.particles span:nth-child(3){left:40%;animation-delay:2s}
.particles span:nth-child(4){left:60%;animation-delay:.5s}
.particles span:nth-child(5){left:75%;animation-delay:1.8s}
.particles span:nth-child(6){left:85%;animation-delay:3s}
.particles span:nth-child(7){left:50%;animation-delay:2.5s}



@keyframes goldFall{


0%{

transform:
translateY(100vh)
rotate(0deg)
scale(.2);

opacity:0;

}


30%{

opacity:1;

}


100%{

transform:
translateY(-100px)
rotate(360deg)
scale(1.5);

opacity:0;

}

}



/* CARD */

.success-box{


width:420px;

padding:40px;

text-align:center;


background:
rgba(255,255,255,.08);


border:

1px solid rgba(255,215,0,.3);


border-radius:30px;


backdrop-filter:blur(15px);


box-shadow:

0 30px 80px rgba(0,0,0,.7);


animation:

show .6s ease;


z-index:2;


}



@keyframes show{


from{

opacity:0;

transform:
translateY(40px)
scale(.8);

}

to{

opacity:1;

transform:
translateY(0)
scale(1);

}


}



/* CHECK */

.check{


width:90px;

height:90px;


margin:auto;


display:flex;

align-items:center;

justify-content:center;


border-radius:50%;


background:#FFD700;


color:#111;


font-size:50px;


font-weight:bold;


box-shadow:

0 0 40px rgba(255,215,0,.8);


animation:

pulse 1.5s infinite;


}



@keyframes pulse{

50%{

transform:scale(1.1);

}

}



h2{


margin-top:25px;

font-size:30px;

color:#FFD700;


}



p{

margin-top:15px;

color:#ccc;

line-height:1.6;

}




#count{

color:#FFD700;

font-size:25px;

}




.btn{


display:inline-block;

    margin-top:25px;

    padding:14px 35px;

    border-radius:30px;


    background:transparent;

    border:2px solid #FFD700;


    color:#FFD700;


    font-weight:bold;

    text-decoration:none;


    letter-spacing:1px;


    transition:.35s;


    position:relative;

    overflow:hidden;

    z-index:1;



}
    .btn::before{

    content:"";

    position:absolute;

    left:0;

    bottom:0;

    width:100%;

    height:0%;


    background:#FFD700;


    transition:.35s;


    z-index:-1;

}



.btn:hover{


color:#111;

    box-shadow:

    0 0 25px rgba(255,215,0,.7);


    transform:translateY(-5px);



}
    .btn:hover::before{

    height:100%;

}
    /* saat ditekan */

.btn:active{

    transform:scale(.92);

    box-shadow:

    0 0 40px #FFD700;

}


</style>

</head>


<body>


<div class="particles">

<span></span>
<span></span>
<span></span>
<span></span>
<span></span>
<span></span>
<span></span>

</div>



<div class="success-box">


<div class="check">

✓

</div>


<h2>

Registrasi Berhasil!

</h2>


<p>

Selamat! Akun Royal Knight berhasil dibuat.

<br>

Nikmati pengalaman gaming premium.

</p>



<p>

Redirect ke halaman utama dalam

<br>

<b id="count">5</b>

detik

</p>



<a href="../beranda.html" class="btn">

MULAI BERMAIN

</a>


</div>



<script>


let time=5;


let count=document.getElementById("count");


let timer=setInterval(()=>{


time--;


count.innerHTML=time;



if(time<=0){


clearInterval(timer);


window.location="/casino/beranda.html";


}


},1000);



</script>


</body>

</html>

';

}else{

    if(str_contains($stmt->error,"Duplicate")){

        echo '
<!DOCTYPE html>
<html>
<head>

<title>Register Failed</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Poppins,Arial,sans-serif;
}


body{

    height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;

    background:
    radial-gradient(circle at top,#3a0000,#080808 60%);

    overflow:hidden;

    color:white;

}



/* PARTICLE MERAH EMAS */

.particles{

    position:fixed;
    inset:0;

}


.particles span{

    position:absolute;

    width:8px;
    height:8px;

    background:#ff4444;

    border-radius:50%;

    box-shadow:
    0 0 20px #ff4444;

    animation:fall 5s linear infinite;

}



.particles span:nth-child(1){left:10%;animation-delay:0s}
.particles span:nth-child(2){left:25%;animation-delay:1s}
.particles span:nth-child(3){left:40%;animation-delay:2s}
.particles span:nth-child(4){left:60%;animation-delay:.5s}
.particles span:nth-child(5){left:75%;animation-delay:1.8s}
.particles span:nth-child(6){left:85%;animation-delay:3s}



@keyframes fall{


0%{

transform:
translateY(100vh)
scale(.2);

opacity:0;

}


30%{

opacity:1;

}


100%{

transform:
translateY(-100px)
rotate(360deg)
scale(1.5);

opacity:0;

}

}



/* CARD */

.failed-box{


width:420px;

padding:40px;

text-align:center;

background:
rgba(255,255,255,.08);

border:

1px solid rgba(255,70,70,.4);


border-radius:30px;

backdrop-filter:blur(15px);


box-shadow:

0 30px 80px rgba(0,0,0,.7);


animation:

show .6s ease;

}



@keyframes show{


from{

opacity:0;

transform:
scale(.7)
translateY(40px);

}


to{

opacity:1;

transform:
scale(1)
translateY(0);

}


}



/* ICON */


.cross{


width:90px;

height:90px;

margin:auto;


display:flex;

align-items:center;

justify-content:center;


border-radius:50%;


background:#ff4444;


color:white;


font-size:50px;


font-weight:bold;


box-shadow:

0 0 40px rgba(255,0,0,.7);


animation:pulse 1.5s infinite;


}



@keyframes pulse{


50%{

transform:scale(1.1);

}

}



h2{

margin-top:25px;

font-size:30px;

color:#ff5555;

}



p{

margin-top:15px;

color:#ccc;

line-height:1.6;

}



.btn{


display:inline-block;

    margin-top:25px;

    padding:14px 35px;

    border-radius:30px;


    background:transparent;

    border:2px solid #FFD700;


    color:#FFD700;


    font-weight:bold;

    text-decoration:none;


    letter-spacing:1px;


    transition:.35s;


    position:relative;

    overflow:hidden;

    z-index:1;


}
    .btn::before{

    content:"";

    position:absolute;

    left:0;

    bottom:0;

    width:100%;

    height:0%;


    background:#FFD700;


    transition:.35s;


    z-index:-1;

}



.btn:hover{

color:#111;

    box-shadow:

    0 0 25px rgba(255,215,0,.7);


    transform:translateY(-5px);

}
    .btn:hover::before{

    height:100%;

}
    btn:active{

    transform:scale(.92);

    box-shadow:

    0 0 40px #FFD700;

}


</style>


</head>


<body>


<div class="particles">

<span></span>
<span></span>
<span></span>
<span></span>
<span></span>
<span></span>

</div>



<div class="failed-box">


<div class="cross">

✕

</div>



<h2>

Registrasi Gagal!

</h2>



<p>

Username atau Email sudah digunakan.

<br>

Silahkan gunakan data yang berbeda.

</p>



<a href="../beranda.html" class="btn">

COBA LAGI

</a>


</div>



</body>

</html>

';

    }else{

        echo "
        <script>
        alert('Error: ".$stmt->error."');
        window.history.back();
        </script>
        ";

    }

}
?>