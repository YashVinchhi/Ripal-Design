<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Signup - Ripal Design</title>
    <link rel="stylesheet" href="./css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-4.0.0.js" integrity="sha256-9fsHeVnKBvqh3FB2HYu7g2xseAZ5MlN6Kz/qnkASV8U=" crossorigin="anonymous"></script>
    <script src="./js/validation.js"></script>  
</head>

<body>
    <!-- signup form -->

    <main>
        <a href="index.php" class="btn btn-secondary top-back-btn">Back</a>

        <div class="login-card text-center">
            <div class="card-login" style="margin-bottom: 30vh;">
                <h3 class="mb-4">Signup</h3>
                <form id="signupForm" method="post" novalidate>

                    <!-- first and last name side-by-side -->
                    <div class="row g-3">
                        <div class="col-md-6 text-start">
                            <label class="firstName">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Enter your first name" data-validation="required min alphabetic" data-min="2">
                            <span id="firstName_error" class="text-danger"></span>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="lastName">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Enter your last name" data-validation="required min alphabetic" data-min="2">
                            <span id="lastName_error" class="text-danger"></span>
                        </div>
                    </div>
                    <!-- for email  -->
                    <div class="mb-3 text-start">
                        <label class="email">Email</label>
                        <input type="email" class="form-control " id="email" name="email" placeholder="example@xyz.com" data-validation="required email">
                        <span id="email_error" class="text-danger"></span>
                    </div>

                    <!-- for password  -->
                    <div class="mb-3 text-start">
                        <label class="Password">Password</label>
                        <input id="confirmPassword" name="password" type="password" class="form-control" placeholder="Enter your password" data-validation="required strongPassword">
                        <span id="password_error" class="text-danger"></span>
                    </div>
                    <!-- for confirm password  -->
                    <div class="mb-3 text-start">
                        <label class="Password">Confirm Password</label>
                        <input id="password" name="confirmPassword" type="password" class="form-control" placeholder="Confirm your password" data-validation="required confirmPassword">
                        <span id="confirmPassword_error" class="text-danger"></span>
                    </div>

                    <!-- for phone number  -->

                    <div class="mb-3 text-start">
                        <label class="phone">Phone Number</label>
                        <input type="tel" class="form-control " id="phone" name="phoneNumber" placeholder="Enter your phone number" data-validation="required number min max" data-min="10" data-max="10">
                        <span id="phoneNumber_error" class="text-danger"></span>
                    </div>

                    <!-- for remider  -->
                    <div class="remider">
                        <div class="re">
                            <input type="checkbox" id="remember" name="terms" class="form-check-input" data-validation="required">
                            <label for="terms" class="form-check-label">Remember me</label>
                            <span id="terms_error" class="text-danger d-none"></span>
                        </div>
                    </div>
                    <button type="submit" class="btn-1">Create Account</button>
                    <p class="mt-4">Already have an account? <a href="./login.php" class="text-decoration-none text-white">login</a> </p>

                </form>
            </div>
        </div>
    </main>

</body>

</html>