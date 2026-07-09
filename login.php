<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) { header('Location: ' . $baseUrl . '/dashboard.php'); exit; }
$pageTitle = 'Log in';
$activeNav = 'login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="form-page">
    <div class="eyebrow">Welcome back</div>
    <h1>Log in</h1>
    <div id="formAlert"></div>
    <form id="loginForm">
        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" required autocomplete="email">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
    <p style="margin-top:18px; font-size:0.88rem;">No account yet? <a href="<?= $baseUrl ?>/register.php">Create one</a></p>
    <div class="card" style="margin-top: 28px;">
        <p class="text-faint" style="margin-bottom:8px; font-size:0.8rem;">Demo accounts</p>
        <p style="font-family:var(--font-mono); font-size:0.78rem; margin:0;">
            admin@agrimarket.test / Admin123!<br>
            trader@agrimarket.test / Trader123!<br>
            farmer@agrimarket.test / Farmer123!
        </p>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e){

    e.preventDefault();

    const alert=document.getElementById('formAlert');

    alert.innerHTML='';

    try{

        const response=await fetch('<?= $baseUrl ?>/api/auth.php?action=login',{

            method:'POST',

            headers:{
                'Content-Type':'application/json'
            },

            credentials:'same-origin',

            body:JSON.stringify({

                email:document.getElementById('email').value,

                password:document.getElementById('password').value

            })

        });

        const data=await response.json();

        if(!response.ok){

            throw new Error(data.error);

        }

        window.location='<?= $baseUrl ?>/dashboard.php';

    }catch(err){

        alert.innerHTML=
            '<div class="alert alert-error">'+err.message+'</div>';

    }

});

</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
