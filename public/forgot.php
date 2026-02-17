<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Ripal Design</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/forgot.css">
    <script src="https://code.jquery.com/jquery-4.0.0.js" integrity="sha256-9fsHeVnKBvqh3FB2HYu7g2xseAZ5MlN6Kz/qnkASV8U=" crossorigin="anonymous"></script>

    <script src="./js/validation.js"></script>

</head>

<body>
    <main>

        <a href="index.php" class="btn btn-secondary top-back-btn">Back</a>
        <div class="login-card text-center">
            <div class="card-login">
                <h3 class="mb-5">Forgot Password</h3>
                <p class="infem">Please enter your email address you'd like your password reset information sent to.</p>
                <form id="forgotPasswordForm" method="post" novalidate>
                    <!-- for email  -->
                    <div class="mb-3 text-start">
                        <label class="email">Email</label>
                        <input type="email" class="form-control " id="email" name="email" placeholder="example@xyz.com" data-validation="required email">
                        <span id="email_error" class="text-danger"></span>
                    </div>
                    <button type="submit" class="btn-1">Send Reset Link</button>
                    <a href="./login.php" class="text-decoration-none text-white p-3 d-block">Back to login</a>
                </form>
            </div>
        </div>
    </main>
</body>

</html>