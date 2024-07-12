<?php
session_start();
include('./users/auth_session.php');
include('./users/db.php');

// Debugging: Output session variables
echo '<pre>';
echo 'Session Variables:';
print_r($_SESSION);
echo '</pre>';
?>

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
    <title>Manage Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://unpkg.com/tabulator-tables@6.2.1/dist/css/tabulator.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://unpkg.com/tabulator-tables@6.2.1/dist/js/tabulator.min.js"></script>
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

    <main class="content-students">
        <!-- Existing Users Management Section -->
        <section class="box manage-section">
            <h2>Manage Users <button class="toggle-btn" onclick="toggleSection('users-section')">+</button></h2>
            <div id="users-section" class="collapsible-content">
                <div id="approved-users-table-container"></div>
                <div id="waiting-approval-table-container"></div>
            </div>
        </section>

        <!-- New Students Management Section -->
        <section class="box manage-section">
            <h2>Manage Students <button class="toggle-btn" onclick="toggleSection('students-section')">+</button></h2>
            <div id="students-section" class="collapsible-content">
                <div id="active-students-table-container"></div>
                <div id="archived-students-table-container"></div>
            </div>
        </section>

        <section class="box manage-section">
            <h2>Assign Users to School <button class="toggle-btn" onclick="toggleSection('assign-users-section')">+</button></h2>
            <div id="assign-users-section" class="collapsible-content">
                <div>
                    <label for="program-users-search">Search Users by Program:</label>
                    <input type="text" id="program-users-search" placeholder="Enter user's name or email">
                    <button onclick="searchProgramUsers()">Search</button>
                </div>
                <div id="program-users-table-container"></div>
            </div>
        </section>

    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://unpkg.com/tabulator-tables@5.2.7/dist/js/tabulator.min.js"></script>
<script src="scripts.js"></script>
</body>
</html>

