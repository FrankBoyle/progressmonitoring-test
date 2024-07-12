<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-LKFCCN4XXS"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-LKFCCN4XXS');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Layout</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <header class="dashboard-header">
            <div class="logo">
                <img src="bFactor_logo.png" alt="Logo">
            </div>
            <div class="header-icons">
                <a href="students.php" class="nav-link">
                    <i class="nav-icon"></i>
                    <p>Home</p>
                </a>
                <a href="./users/logout.php" class="nav-link">
                    <i class="nav-icon"></i>
                    <p>Sign Out</p>
                </a>
            </div>
        </header>

        <div class="center-content">
            <div class="login-box">
                <h1 class="login-box-msg">Register</h1>
                <form method="post" action="./users/register_backend.php" name="registration">
                    <div style="position: relative;">
                        <input type="text" class="form-control" name="fname" id="fname" placeholder="First Name" required>
                        <span class="fas fa-envelope"></span>
                    </div>
                    <div style="position: relative;">
                        <input type="text" class="form-control" name="lname" id="lname" placeholder="Last Name" required>
                        <span class="fas fa-user"></span>
                    </div>
                    <div style="position: relative;">
                        <input type="text" class="form-control" name="school_uuid" id="school_uuid" placeholder="School UUID" required>
                        <span class="fas fa-school"></span>
                    </div>
                    <div style="position: relative;">
                        <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                        <span class="fas fa-envelope"></span>
                    </div>
                    <div style="position: relative;">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                        <span class="fas fa-lock"></span>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="agreeTerms" name="terms" value="agree" required>
                                <label for="agreeTerms">I agree to the <a href="#">terms</a></label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" name="register" id="register" value="Register" class="btn btn-primary btn-block">Register</button>
                        </div>
                    </div>
                    <a href="login.php" class="text-center">I already have a membership</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
