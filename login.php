<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nursing Calendar</title>
</head>

<body>
    <?php
      echo '<h1>Login Form</h1>'  
    ?>
    <div id="login-card">
        <form id='login-form' method="POST" action="index.php">
            <label for="username">User</label>
            <input id="username" type="text">
            <br/>
            <label for="password">Password</label>
            <input id="password" type="text">
            <br/>
            <button id='login' type="submit">Login</button>
        </form>
    </div>
</body>

</html>
